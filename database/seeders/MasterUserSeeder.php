<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class MasterUserSeeder extends Seeder
{
    public function run(): void
    {
        // Cria usuário master
        $user = User::updateOrCreate(
            ['email' => 'admin@betaki.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Betaki@123'),
            ]
        );

        // Cria ou obtém role admin
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        // Vincula a role
        $user->syncRoles([$role]);

        $this->command->info('Usuário master criado: admin@betaki.com / Betaki@123');
    }
}