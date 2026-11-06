<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // Taquilla
        $taquilla = User::firstOrCreate(
            ['email' => 'taquilla@example.com'],
            [
                'name' => 'Operador de Taquilla',
                'password' => Hash::make('password'),
            ]
        );
        $taquilla->assignRole('taquilla');

        // Validador de boletos en puerta
        $validador = User::firstOrCreate(
            ['email' => 'validador@example.com'],
            [
                'name' => 'Control de Acceso / Puerta',
                'password' => Hash::make('password'),
            ]
        );
        $validador->assignRole('validador');
    }
}
