<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ProductionSeedingTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'production.admin@anbg.ga';

    public function test_production_seeding_creates_only_the_configured_initial_account(): void
    {
        $this->configureProductionSeeding('ProductionBootstrap@2026');

        $this->runProductionSeeder();

        $admin = DB::table('utilisateurs')->where('email', self::ADMIN_EMAIL)->first();

        $this->assertNotNull($admin);
        $this->assertTrue((bool) $admin->actif);
        $this->assertTrue((bool) $admin->changement_mdp_requis);
        $this->assertTrue(Hash::check('ProductionBootstrap@2026', (string) $admin->password_hash));
        $this->assertDatabaseHas('utilisateur_role', [
            'id_utilisateur' => $admin->id_utilisateur,
            'id_role' => DB::table('roles')->where('code', 'admin')->value('id_role'),
        ]);

        $this->assertDatabaseMissing('utilisateurs', ['email' => 'accueil@anbg.ga']);
        $this->assertDatabaseMissing('utilisateurs', ['email' => 'agent.ds@anbg.ga']);
        $this->assertDatabaseCount('demandes', 0);
    }

    public function test_production_seeding_requires_a_password_for_the_first_administrator(): void
    {
        $this->configureProductionSeeding(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'INITIAL_ADMIN_PASSWORD is required when creating the first production administrator.'
        );

        $this->runProductionSeeder();
    }

    public function test_repeated_production_seeding_does_not_reset_the_administrator(): void
    {
        $this->configureProductionSeeding('ProductionBootstrap@2026');
        $this->runProductionSeeder();

        $admin = DB::table('utilisateurs')->where('email', self::ADMIN_EMAIL)->firstOrFail();
        $ciqRoleId = (int) DB::table('roles')->where('code', 'ciq')->value('id_role');
        $changedPassword = 'AdministratorChanged@2026';

        DB::table('utilisateurs')->where('id_utilisateur', $admin->id_utilisateur)->update([
            'nom' => 'Responsable',
            'password_hash' => Hash::make($changedPassword),
            'changement_mdp_requis' => false,
            'updated_at' => now(),
        ]);
        DB::table('utilisateur_role')->insert([
            'id_utilisateur' => $admin->id_utilisateur,
            'id_role' => $ciqRoleId,
        ]);
        DB::table('model_has_roles')->insert([
            'id_role' => $ciqRoleId,
            'model_type' => Utilisateur::class,
            'model_id' => $admin->id_utilisateur,
        ]);

        config(['deployment.initial_admin.password' => 'AnotherBootstrap@2026']);
        $this->runProductionSeeder();

        $admin = DB::table('utilisateurs')->where('email', self::ADMIN_EMAIL)->firstOrFail();

        $this->assertSame('Responsable', $admin->nom);
        $this->assertFalse((bool) $admin->changement_mdp_requis);
        $this->assertTrue(Hash::check($changedPassword, (string) $admin->password_hash));
        $this->assertDatabaseHas('utilisateur_role', [
            'id_utilisateur' => $admin->id_utilisateur,
            'id_role' => $ciqRoleId,
        ]);
    }

    private function configureProductionSeeding(?string $password): void
    {
        $this->app->instance('env', 'production');
        config([
            'app.env' => 'production',
            'deployment.initial_admin.email' => self::ADMIN_EMAIL,
            'deployment.initial_admin.password' => $password,
        ]);

        $existingAdminIds = DB::table('utilisateurs')
            ->whereIn('email', ['admin@anbg.ga', self::ADMIN_EMAIL])
            ->pluck('id_utilisateur');

        DB::table('model_has_roles')->whereIn('model_id', $existingAdminIds)->delete();
        DB::table('utilisateur_role')->whereIn('id_utilisateur', $existingAdminIds)->delete();
        DB::table('utilisateurs')->whereIn('id_utilisateur', $existingAdminIds)->delete();
    }

    private function runProductionSeeder(): void
    {
        $seeder = app(DatabaseSeeder::class);
        $seeder->setContainer($this->app);
        $seeder->run();
    }
}
