<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Evita lixo de cache ao criar/alterar perms/roles
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'api';

        $permissions = [
            // Banners
            'banners.view',
            'banners.create',
            'banners.update',
            'banners.delete',
            'banners.publish',

            // Categories (cassino)
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            // Slots (cassino)
            'slots.view',
            'slots.create',
            'slots.update',
            'slots.delete',

            // Users (admins)
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // RBAC
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',

            // Settings
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',

            // Showcases (cassino)
            'showcases.view',
            'showcases.create',
            'showcases.update',
            'showcases.delete',

            // Menus
            'menus.view',
            'menus.create',
            'menus.update',
            'menus.delete',
            'menus.items.manage',

            // Top Lists (cassino)
            'toplists.view',
            'toplists.create',
            'toplists.update',
            'toplists.delete',

            // Awards (Mais Premiados)
            'awards.view',
            'awards.create',
            'awards.update',
            'awards.publish',
            'awards.delete',

            // Top Winners (cassino)
            'winners.view',
            'winners.create',
            'winners.update',
            'winners.publish',
            'winners.delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        $rolesMatrix = [
            'admin' => $permissions,
            'content' => [
                'banners.view', 'banners.create', 'banners.update',
                'categories.view', 'categories.create', 'categories.update',
                'slots.view', 'slots.create', 'slots.update',
                'settings.view',
            ],
            'marketing' => [
                'banners.view', 'banners.publish',
                'categories.view',
                'slots.view',
                'settings.view',
            ],
            'support' => [
                'banners.view',
                'categories.view',
                'slots.view',
                'users.view',
                'roles.view',
                'permissions.view',
                'settings.view',
            ],
        ];

        foreach ($rolesMatrix as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            $role->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✅ Roles e permissions criadas/atualizadas com sucesso!');
    }
}