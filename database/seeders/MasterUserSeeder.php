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
            ['email' => 'admin@betaki.bet.br'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Betaki@123'),
            ]
        );

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        $user->syncRoles([$role]);

        $this->command->info('Usuário master criado: admin@betaki.bet.br / Betaki@123');
    }
}