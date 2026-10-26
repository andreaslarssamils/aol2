<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Plattformsrollerna: admin, redaktor, verksamhetsagare. */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([User::ROLE_ADMIN, User::ROLE_REDAKTOR, User::ROLE_VERKSAMHETSAGARE] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
