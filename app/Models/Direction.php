<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Direction extends Model
{
    protected $table = 'directions';
    protected $primaryKey = 'id_direction';
    protected $guarded = [];

    protected $casts = [
        'actif' => 'boolean',
        'date_debut_validite' => 'date',
        'date_fin_validite' => 'date',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'id_direction', 'id_direction');
    }

    public function utilisateursPerimetre(): BelongsToMany
    {
        return $this->belongsToMany(
            Utilisateur::class,
            'perimetre_direction',
            'id_direction',
            'id_utilisateur',
            'id_direction',
            'id_utilisateur'
        );
    }
}
