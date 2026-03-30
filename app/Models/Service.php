<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id_service';
    protected $guarded = [];

    protected $casts = [
        'actif' => 'boolean',
        'date_debut_validite' => 'date',
        'date_fin_validite' => 'date',
    ];

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class, 'id_direction', 'id_direction');
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(Utilisateur::class, 'id_service', 'id_service');
    }

    public function utilisateursPerimetre(): BelongsToMany
    {
        return $this->belongsToMany(
            Utilisateur::class,
            'perimetre_service',
            'id_service',
            'id_utilisateur',
            'id_service',
            'id_utilisateur'
        );
    }
}
