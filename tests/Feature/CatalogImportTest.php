<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_brand_catalog_can_be_imported_from_xlsx(): void
    {
        $path = storage_path('framework/testing/catalogo-clientes-marcas.xlsx');
        $this->writeWorkbook($path);

        $this->artisan('catalog:import-clients-brands', [
            'path' => $path,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, Client::count());
        $this->assertSame(0, Brand::count());

        $this->artisan('catalog:import-clients-brands', [
            'path' => $path,
        ])->assertSuccessful();

        $this->assertDatabaseHas('clients', ['name' => 'Exeltis']);
        $this->assertDatabaseHas('clients', ['name' => 'Roche diagnóstica']);
        $this->assertDatabaseHas('brands', ['name' => 'Gynophillus restore']);
        $this->assertDatabaseHas('brands', ['name' => 'Inofolic HP']);
        $this->assertDatabaseHas('brands', ['name' => 'Accu-Chek']);

        $this->artisan('catalog:import-clients-brands', [
            'path' => $path,
        ])->assertSuccessful();

        $this->assertSame(2, Client::count());
        $this->assertSame(3, Brand::count());
    }

    private function writeWorkbook(string $path): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Hoja1" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/worksheets/sheet1.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>Laboratorio</t></is></c>
      <c r="B2" t="inlineStr"><is><t>Marca</t></is></c>
    </row>
    <row r="3">
      <c r="A3" t="inlineStr"><is><t>Exeltis</t></is></c>
      <c r="B3" t="inlineStr"><is><t>Gynophillus restore</t></is></c>
    </row>
    <row r="4">
      <c r="B4" t="inlineStr"><is><t>Inofolic HP</t></is></c>
    </row>
    <row r="5">
      <c r="A5" t="inlineStr"><is><t>Roche diagnóstica</t></is></c>
      <c r="B5" t="inlineStr"><is><t>Accu-Chek</t></is></c>
    </row>
  </sheetData>
</worksheet>
XML);

        $zip->close();
    }
}
