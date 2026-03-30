<?php

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    protected $table = 'roles';
    protected $primaryKey = 'id_role';
    protected $guarded = [];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected string $guard_name = 'web';
}
