<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueAction extends Model
{
    protected $table = 'historique_actions';
    protected $primaryKey = 'id_action';
    protected $guarded = [];
}
