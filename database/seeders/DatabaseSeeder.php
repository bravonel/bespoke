<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Bespoke2025!');

        $colaboradores = [
            // Cuentas
            ['name' => 'Carmen Bravo Tapia',    'email' => 'carmen@bespokeadvertising.com.mx',              'area' => 'Cuentas',          'puesto' => 'Gestor de proyectos'],
            ['name' => 'Roberto Moreno',         'email' => 'roberto_moreno@bespokeadvertising.com.mx',      'area' => 'Cuentas',          'puesto' => 'Gestor de proyectos'],
            ['name' => 'Michelle Olvera',        'email' => 'michelle@bespokeadvertising.com.mx',            'area' => 'Cuentas',          'puesto' => 'Gestor de proyectos'],
            ['name' => 'Zaira Quezada',          'email' => 'zaira@bespokeadvertising.com.mx',               'area' => 'Cuentas',          'puesto' => 'Gestor de proyectos'],
            ['name' => 'Fernanda Luján',         'email' => 'mafer@bespokeadvertising.com.mx',               'area' => 'Cuentas',          'puesto' => 'Gestor de proyectos'],
            // Diseño
            ['name' => 'Luis Cervantes',         'email' => 'luis@bespokeadvertising.com.mx',                'area' => 'Diseño',           'puesto' => 'Director de Arte'],
            ['name' => 'Itzel Salome',           'email' => 'itzel@bespokeadvertising.com.mx',               'area' => 'Diseño',           'puesto' => 'Diseñador Sr.'],
            ['name' => 'Cecilia Espinosa',       'email' => 'cecilia@bespokeadvertising.com.mx',             'area' => 'Diseño',           'puesto' => 'Diseñador Sr.'],
            ['name' => 'Jacob Correa',           'email' => 'jacob@bespokeadvertising.com.mx',               'area' => 'Diseño',           'puesto' => 'Animador'],
            ['name' => 'Enrique Hernández',      'email' => 'enrique@bespokeadvertising.com.mx',             'area' => 'Diseño',           'puesto' => 'Diseñador Sr.'],
            ['name' => 'Pablo Percastegui',      'email' => 'pablo_percastegui@bespokeadvertising.com.mx',  'area' => 'Diseño',           'puesto' => 'Diseñador Sr.'],
            ['name' => 'Rafael Camarillo',       'email' => 'rafael@bespokeadvertising.com.mx',              'area' => 'Diseño',           'puesto' => 'Diseñador Web'],
            ['name' => 'Carlos Ramírez',         'email' => 'carlos@bespokeadvertising.com.mx',              'area' => 'Diseño',           'puesto' => 'Diseñador Jr.'],
            ['name' => 'Victor Hugo López',      'email' => 'victor_hugo@bespokeadvertising.com.mx',         'area' => 'Diseño',           'puesto' => 'Diseñador Jr.'],
            // Médico
            ['name' => 'Alejandro Lira',         'email' => 'alejandrolira@bespokeadvertising.com.mx',       'area' => 'Médico',           'puesto' => 'Redactor médico'],
            ['name' => 'Alejandro Lujambio',     'email' => 'alejandro_lujambio@bespokeadvertising.com.mx', 'area' => 'Médico',           'puesto' => 'Redactor médico'],
            ['name' => 'Andrea Cervantes',       'email' => 'andrea@bespokeadvertising.com.mx',              'area' => 'Médico',           'puesto' => 'Redactor médico'],
            ['name' => 'Daniela Cruz',           'email' => 'daniela@bespokeadvertising.com.mx',             'area' => 'Médico',           'puesto' => 'Redactor médico'],
            // Redacción
            ['name' => 'Arturo López',           'email' => 'arturodiaz@bespokeadvertising.com.mx',          'area' => 'Redacción',        'puesto' => 'Redacción / Corrección'],
            // Redes sociales
            ['name' => 'Eduardo Gutiérrez',      'email' => 'eduardo@bespokeadvertising.com.mx',             'area' => 'Redes sociales',   'puesto' => 'Responsable de redes sociales'],
            ['name' => 'Daniela Vélez',          'email' => 'danielavelez@bespokeadvertising.com.mx',        'area' => 'Redes sociales',   'puesto' => 'Gestor de comunidad'],
            ['name' => 'Monserrat Barragán',     'email' => 'Monserrath@bespokeadvertising.com.mx',          'area' => 'Redes sociales',   'puesto' => 'Gestor de comunidad'],
            // Digital
            ['name' => 'Marco Torres',           'email' => 'marco@bespokeadvertising.com.mx',               'area' => 'Digital',          'puesto' => 'Gerente de innovación'],
            // Dirección General
            ['name' => 'Sonia Luján',            'email' => 'sony@bespokeadvertising.com.mx',                'area' => 'Dirección General', 'puesto' => 'Dirección general'],
        ];

        foreach ($colaboradores as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'area'              => $data['area'],
                    'puesto'            => $data['puesto'],
                    'daily_capacity_minutes' => 480,
                    'password'          => $password,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
