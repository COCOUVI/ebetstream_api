<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crée les permissions si elles n'existent pas déjà
        $edit = Permission::firstOrCreate(['name' => 'edit articles']);
        $delete = Permission::firstOrCreate(['name' => 'delete articles']);
        $publish = Permission::firstOrCreate(['name' => 'publish articles']);

        // Crée les rôles si inexistants et leur attribue les permissions
        $writer = Role::firstOrCreate(['name' => 'writer']);
        $writer->givePermissionTo($edit);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([$edit, $delete, $publish]);
    }
}
