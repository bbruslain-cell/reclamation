<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    public function test_every_non_public_application_route_requires_agent_authentication(): void
    {
        $publicSignatures = [
            'GET /',
            'HEAD /',
            'GET login',
            'HEAD login',
            'POST login',
            'POST logout',
            'GET reclamations',
            'HEAD reclamations',
            'POST reclamations',
            'GET reclamations/nouvelle',
            'HEAD reclamations/nouvelle',
            'GET up',
            'HEAD up',
        ];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                $signature = $method.' '.$route->uri();

                if (in_array($signature, $publicSignatures, true)) {
                    continue;
                }

                $this->assertContains(
                    'agent.auth',
                    $route->gatherMiddleware(),
                    "La route {$signature} doit etre protegee par agent.auth."
                );
            }
        }
    }

    public function test_internal_api_routes_use_session_authentication_csrf_and_rate_limiting(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            $this->assertContains('web', $middleware, "CSRF absent sur {$route->uri()}.");
            $this->assertContains('agent.auth', $middleware, "Authentification absente sur {$route->uri()}.");
            $this->assertContains('throttle:60,1', $middleware, "Limitation absente sur {$route->uri()}.");
        }
    }

    public function test_private_storage_serving_routes_are_not_registered(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('storage.local'));
        $this->assertNull(Route::getRoutes()->getByName('storage.local.upload'));
    }

    public function test_legacy_direction_redirect_accepts_only_safe_read_methods(): void
    {
        $route = collect(Route::getRoutes())->first(
            static fn ($candidate) => $candidate->uri() === 'direction/inbox'
        );

        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
    }

    public function test_guests_cannot_reach_protected_web_or_api_routes(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->getJson('/api/demandes')->assertUnauthorized();
    }

    public function test_default_csp_uses_a_nonce_and_disables_inline_event_handlers(): void
    {
        $response = $this->get('/login')->assertOk();
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/'nonce-[A-Za-z0-9]+'/", $csp);
        $this->assertStringContainsString("script-src-attr 'none'", $csp);

        preg_match('/(?:^|; )script-src ([^;]+)/', $csp, $matches);
        $scriptSources = $matches[1] ?? '';

        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSources);
        $this->assertStringNotContainsString("'unsafe-eval'", $scriptSources);
    }

    public function test_public_vue_form_gets_only_the_runtime_compiler_csp_exception(): void
    {
        $request = Request::create('/reclamations/nouvelle', 'GET');
        $response = app(SecurityHeaders::class)->handle(
            $request,
            static fn () => response('ok')
        );
        $csp = (string) $response->headers->get('Content-Security-Policy');

        preg_match('/(?:^|; )script-src ([^;]+)/', $csp, $matches);
        $scriptSources = $matches[1] ?? '';

        $this->assertStringContainsString("'unsafe-eval'", $scriptSources);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSources);
        $this->assertStringNotContainsString('https://unpkg.com', $scriptSources);
        $this->assertStringNotContainsString('https://cdn.jsdelivr.net', $scriptSources);
    }

    public function test_blade_scripts_are_nonce_protected_and_have_no_inline_event_attributes(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = File::get($file->getPathname());
            preg_match_all('/<script\b[^>]*>/i', $contents, $scriptTags);

            foreach ($scriptTags[0] as $scriptTag) {
                $this->assertStringContainsString(
                    'nonce=',
                    $scriptTag,
                    "Nonce CSP absent dans {$file->getRelativePathname()}."
                );
            }

            preg_match_all('/<[a-z][^>]*>/i', $contents, $htmlTags);

            foreach ($htmlTags[0] as $htmlTag) {
                $this->assertDoesNotMatchRegularExpression(
                    '/\son[a-z]+\s*=/i',
                    $htmlTag,
                    "Gestionnaire d'evenement HTML interdit dans {$file->getRelativePathname()}."
                );
            }
        }
    }
}
