<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Utilisateur extends Authenticatable
{
    use HasRoles;

    protected $table = 'utilisateurs';
    protected $primaryKey = 'id_utilisateur';
    protected $guarded = [];
    protected $hidden = ['password_hash', 'remember_token'];

    protected $casts = [
        'actif' => 'boolean',
        'changement_mdp_requis' => 'boolean',
        'derniere_connexion' => 'datetime',
        'bloque_jusqua' => 'datetime',
    ];

    protected string $guard_name = 'web';

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service', 'id_service');
    }

    public function perimetreDirections(): BelongsToMany
    {
        return $this->belongsToMany(
            Direction::class,
            'perimetre_direction',
            'id_utilisateur',
            'id_direction',
            'id_utilisateur',
            'id_direction'
        );
    }

    public function perimetreServices(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'perimetre_service',
            'id_utilisateur',
            'id_service',
            'id_utilisateur',
            'id_service'
        );
    }

    public function historiqueActions(): HasMany
    {
        return $this->hasMany(HistoriqueAction::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function scopeAgents(Builder $query): Builder
    {
        return $query->role('agent');
    }

    public function scopeChefs(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->role('chef_service')->orWhere(function (Builder $sub): void {
                $sub->role('chef_direction');
            });
        });
    }

    public function scopeAccueil(Builder $query): Builder
    {
        return $query->role('accueil');
    }
}
