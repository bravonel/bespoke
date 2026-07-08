<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceValues('users', 'area', [
            'Copy' => 'Redacción',
            'Medical' => 'Médico',
            'Medico' => 'Médico',
            'Social Media' => 'Redes sociales',
            'Social media' => 'Redes sociales',
            'Direccion General' => 'Dirección General',
            'Diseno' => 'Diseño',
        ]);

        $this->replaceValues('users', 'puesto', [
            'CEO' => 'Dirección general',
            'Community Manager' => 'Gestor de comunidad',
            'Copy / Proofreader' => 'Redacción / Corrección',
            'Innovation Manager' => 'Gerente de innovación',
            'Medical Writer' => 'Redactor médico',
            'Project Manager' => 'Gestor de proyectos',
            'Social Media Manager' => 'Responsable de redes sociales',
        ]);

        $this->replaceValues('projects', 'project_type', [
            'brochure' => 'folleto',
            'campaign' => 'campana',
            'campaña' => 'campana',
            'flyer' => 'volante',
            'material' => 'otro',
            'monografía' => 'monografia',
            'presentation' => 'presentacion',
            'visual_aid' => 'ayuda_visual',
        ]);
    }

    public function down(): void
    {
        // Data normalization is intentionally not reversed.
    }

    /**
     * @param array<string, string> $values
     */
    private function replaceValues(string $table, string $column, array $values): void
    {
        foreach ($values as $from => $to) {
            DB::table($table)->where($column, $from)->update([$column => $to]);
        }
    }
};
