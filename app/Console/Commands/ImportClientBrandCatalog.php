<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Client;
use App\Support\SimpleXlsxReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportClientBrandCatalog extends Command
{
    protected $signature = 'catalog:import-clients-brands
        {path : Ruta del archivo .xlsx con columnas Laboratorio y Marca}
        {--dry-run : Analiza el archivo sin guardar cambios}';

    protected $description = 'Importa clientes y marcas desde el catálogo de laboratorios.';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $records = $this->recordsFromRows(SimpleXlsxReader::rows($path));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($records === []) {
            $this->warn('No se encontraron relaciones Laboratorio / Marca para importar.');

            return self::SUCCESS;
        }

        $summary = $dryRun
            ? $this->previewImport($records)
            : DB::transaction(fn () => $this->persistImport($records));

        $this->table(['Métrica', 'Total'], [
            ['Relaciones válidas en archivo', $summary['records']],
            ['Relaciones duplicadas en archivo', $summary['file_duplicates']],
            [$dryRun ? 'Clientes por crear' : 'Clientes creados', $summary['clients_created']],
            ['Clientes ya existentes', $summary['clients_existing']],
            [$dryRun ? 'Marcas por crear' : 'Marcas creadas', $summary['brands_created']],
            ['Marcas ya existentes', $summary['brands_existing']],
        ]);

        $this->info($dryRun ? 'Dry-run terminado. No se guardaron cambios.' : 'Catálogo importado correctamente.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{client: string, brand: string, row: int, duplicate: bool}>
     */
    private function recordsFromRows(array $rows): array
    {
        $headerFound = false;
        $currentClient = null;
        $seen = [];
        $records = [];

        foreach ($rows as $rowNumber => $row) {
            $clientName = $this->clean($row[1] ?? '');
            $brandName = $this->clean($row[2] ?? '');

            if (! $headerFound) {
                $headerFound = $this->isHeaderRow($clientName, $brandName);

                continue;
            }

            if ($clientName !== '') {
                $currentClient = $clientName;
            }

            if ($currentClient === null || $brandName === '') {
                continue;
            }

            $key = $this->normalize($currentClient).'|'.$this->normalize($brandName);
            $duplicate = isset($seen[$key]);
            $seen[$key] = true;

            $records[] = [
                'client' => $currentClient,
                'brand' => $brandName,
                'row' => (int) $rowNumber,
                'duplicate' => $duplicate,
            ];
        }

        if (! $headerFound) {
            throw new RuntimeException('No se encontró la fila de encabezados Laboratorio / Marca.');
        }

        return $records;
    }

    /**
     * @param  array<int, array{client: string, brand: string, row: int, duplicate: bool}>  $records
     * @return array<string, int>
     */
    private function previewImport(array $records): array
    {
        return $this->runImport($records, false);
    }

    /**
     * @param  array<int, array{client: string, brand: string, row: int, duplicate: bool}>  $records
     * @return array<string, int>
     */
    private function persistImport(array $records): array
    {
        return $this->runImport($records, true);
    }

    /**
     * @param  array<int, array{client: string, brand: string, row: int, duplicate: bool}>  $records
     * @return array<string, int>
     */
    private function runImport(array $records, bool $persist): array
    {
        $clientMap = Client::query()
            ->get()
            ->mapWithKeys(fn (Client $client) => [$this->normalize($client->name) => $client])
            ->all();

        $brandMap = Brand::query()
            ->with('client')
            ->get()
            ->mapWithKeys(fn (Brand $brand) => [$this->normalize($brand->client?->name ?? '').'|'.$this->normalize($brand->name) => true])
            ->all();

        $summary = [
            'records' => 0,
            'file_duplicates' => 0,
            'clients_created' => 0,
            'clients_existing' => 0,
            'brands_created' => 0,
            'brands_existing' => 0,
        ];

        $countedExistingClients = [];
        $createdClientKeys = [];

        foreach ($records as $record) {
            if ($record['duplicate']) {
                $summary['file_duplicates']++;

                continue;
            }

            $summary['records']++;

            $clientKey = $this->normalize($record['client']);
            $brandKey = $clientKey.'|'.$this->normalize($record['brand']);

            if (! array_key_exists($clientKey, $clientMap)) {
                $summary['clients_created']++;
                $createdClientKeys[$clientKey] = true;

                $clientMap[$clientKey] = $persist
                    ? Client::query()->create([
                        'name' => $record['client'],
                        'status' => 'active',
                    ])
                    : null;
            } elseif (! isset($createdClientKeys[$clientKey]) && ! isset($countedExistingClients[$clientKey])) {
                $summary['clients_existing']++;
                $countedExistingClients[$clientKey] = true;
            }

            if (array_key_exists($brandKey, $brandMap)) {
                $summary['brands_existing']++;

                continue;
            }

            $summary['brands_created']++;
            $brandMap[$brandKey] = true;

            if ($persist && $clientMap[$clientKey] instanceof Client) {
                Brand::query()->create([
                    'client_id' => $clientMap[$clientKey]->id,
                    'name' => $record['brand'],
                    'status' => 'active',
                ]);
            }
        }

        return $summary;
    }

    private function isHeaderRow(string $clientName, string $brandName): bool
    {
        return $this->normalize($clientName) === 'laboratorio'
            && $this->normalize($brandName) === 'marca';
    }

    private function clean(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function normalize(string $value): string
    {
        return mb_strtolower($this->clean($value), 'UTF-8');
    }
}
