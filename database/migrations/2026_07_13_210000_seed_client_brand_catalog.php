<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $clients = DB::table('clients')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (object $client) => [$this->normalize($client->name) => $client->id])
            ->all();

        $brands = DB::table('brands')
            ->join('clients', 'clients.id', '=', 'brands.client_id')
            ->get(['brands.name as brand_name', 'clients.name as client_name'])
            ->mapWithKeys(fn (object $brand) => [
                $this->normalize($brand->client_name).'|'.$this->normalize($brand->brand_name) => true,
            ])
            ->all();

        foreach ($this->catalog() as $clientName => $brandNames) {
            $clientKey = $this->normalize($clientName);

            if (! isset($clients[$clientKey])) {
                $clients[$clientKey] = DB::table('clients')->insertGetId([
                    'name' => $clientName,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($brandNames as $brandName) {
                $brandKey = $clientKey.'|'.$this->normalize($brandName);

                if (isset($brands[$brandKey])) {
                    continue;
                }

                DB::table('brands')->insert([
                    'client_id' => $clients[$clientKey],
                    'name' => $brandName,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $brands[$brandKey] = true;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Catalog data is intentionally kept to avoid deleting brands already used by projects.
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function catalog(): array
    {
        return [
            'Exeltis' => [
                'Gynophillus restore',
                'Inofolic HP',
                'Inofolic Combi',
                'Inofolics',
                'Slinda',
                'Kelzy',
                'Gynotran',
                'Ginorelle',
                'Bonjesta',
                'Xanelle',
                'Gineco 1',
                'Gineco 2',
                'Obstetricia',
                'SNC',
            ],
            'Grunenthal' => [
                'Dicynone',
                'Urovaxom',
                'Nebido',
                'Salud integral',
                'Doxium',
                'Doxiproct',
                'Broncho Vaxom',
                'Xeomeen',
                'Hepamerz',
                'Dimoflax',
                'Transtec',
                'Palexia',
            ],
            'Prometis Pharma' => [
                'Rebagit',
            ],
            'PharmaPro' => [
                'PharmaPro',
            ],
            'Besins' => [
                'Inversion Femme',
                'Gummies',
            ],
            'Merz DP Latam' => [
                'Layerz',
                'Ultherapy',
                'Radiesse',
                'Belotero general',
                'Belotero Ulips',
                'Belotero Revive',
                'Eventos',
                'Corporativo',
                'Redes',
                'Página web',
                'Xeomin',
            ],
            'Roche' => [
                'Phesgo',
                'Kadcyla',
                'Perjeta',
                'Vabysmo',
                'Evrisdy',
                'Ocrevus',
                'Rosenkranz',
            ],
            'Roche diagnóstica' => [
                'Soluciones laboratorio',
                'Consultoria / Automatización',
                'Oncología',
                'Consultoria',
                'Banco de sangre',
                'Áreas terapéuticas',
                'Molecular',
                'Enfermedades infecciosas',
                'NPC',
                'Corporativo',
                'Soluciones digitales',
                'Cardio',
            ],
            'Amstrong' => [
                'Angiotrofin',
                'Isorbid',
            ],
            'Bespoke' => [
                'Redes',
                'Página web',
                'Procesos',
                'Corporativo',
            ],
            'Carnot' => [
                'Corporativo',
                'Repafet',
                'Solvopret',
            ],
            'M8' => [
                'Eroxon',
                'Eroxon Latam',
                'Mokbio',
                'Lamisil',
                'Barlo',
                'Xuzal',
                'Nootropil',
                'Neurontin',
                'Nubrenza',
                'Seroquel',
                'Dymista',
                'M8 club salud',
                'Virlix',
                'Legalon',
                'Abcito',
                'Secnidal',
            ],
            'Panalab' => [
                'Leraco',
                'Vitanoin',
                'Mineral Safe',
            ],
            'Grin' => [
                'Grin & Science',
                'Glaucoma',
            ],
            'Corne' => [
                'Condrosulf',
                'Cystistat',
                'Caregyn',
                'Muvment',
                'Renehavis',
                'Portafolio Trauma',
            ],
            'Nebucor' => [
                'Nebucor',
                'Nebulizadores',
                'Baumanometros',
            ],
        ];
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? ''), 'UTF-8');
    }
};
