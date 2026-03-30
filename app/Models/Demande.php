<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Demande extends Model
{
    protected $table = 'demandes';
    protected $primaryKey = 'id_demande';
    protected $guarded = [];

    public function agentTraitant(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_agent_traitant', 'id_utilisateur');
    }

    public function chefAffectations(): HasMany
    {
        return $this->hasMany(HistoriqueAction::class, 'id_demande', 'id_demande')
            ->where('type_action', 'affectation_agent');
    }
}
