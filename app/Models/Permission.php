<?php

namespace App\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    protected $table = 'permissions';
    protected $primaryKey = 'id_permission';
    protected $guarded = [];

    protected string $guard_name = 'web';
}
