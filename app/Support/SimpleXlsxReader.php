<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class SimpleXlsxReader
{
    private const SPREADSHEET_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const RELATIONSHIPS_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_RELATIONSHIPS_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * @return array<int, array<int, string>>
     */
    public static function rows(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("No se encontró el archivo: {$path}");
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException("No se pudo abrir el archivo XLSX: {$path}");
        }

        try {
            $sharedStrings = self::sharedStrings($zip);
            $worksheetPath = self::firstWorksheetPath($zip);
            $sheet = self::xmlFromZip($zip, $worksheetPath);

            if (! $sheet) {
                throw new RuntimeException("No se encontró la hoja principal en {$path}");
            }

            $sheet->registerXPathNamespace('main', self::SPREADSHEET_NS);
            $rows = [];

            foreach ($sheet->xpath('//main:sheetData/main:row') ?: [] as $row) {
                $rowNumber = (int) ($row['r'] ?? (count($rows) + 1));
                $rows[$rowNumber] = [];
                $row->registerXPathNamespace('main', self::SPREADSHEET_NS);

                foreach ($row->xpath('main:c') ?: [] as $cell) {
                    $column = self::columnIndex((string) $cell['r']);

                    if ($column === null) {
                        continue;
                    }

                    $rows[$rowNumber][$column] = self::cellValue($cell, $sharedStrings);
                }
            }

            ksort($rows);

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = self::xmlFromZip($zip, 'xl/sharedStrings.xml');

        if (! $xml) {
            return [];
        }

        $xml->registerXPathNamespace('main', self::SPREADSHEET_NS);

        return array_map(
            fn (SimpleXMLElement $item) => self::textFromXml($item),
            $xml->xpath('//main:si') ?: [],
        );
    }

    private static function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbook = self::xmlFromZip($zip, 'xl/workbook.xml');
        $relationships = self::xmlFromZip($zip, 'xl/_rels/workbook.xml.rels');

        if (! $workbook || ! $relationships) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook->registerXPathNamespace('main', self::SPREADSHEET_NS);
        $sheets = $workbook->xpath('//main:sheets/main:sheet') ?: [];
        $firstSheet = $sheets[0] ?? null;

        if (! $firstSheet) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) $firstSheet->attributes(self::RELATIONSHIPS_NS)['id'];

        if ($relationshipId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationships->registerXPathNamespace('rel', self::PACKAGE_RELATIONSHIPS_NS);

        foreach ($relationships->xpath('//rel:Relationship') ?: [] as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];

            if ($target === '') {
                break;
            }

            return str_starts_with($target, '/')
                ? ltrim($target, '/')
                : 'xl/'.ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) ($cell->children(self::SPREADSHEET_NS)->v ?? -1);

            return trim($sharedStrings[$index] ?? '');
        }

        if ($type === 'inlineStr') {
            return trim(self::textFromXml($cell));
        }

        return trim((string) ($cell->children(self::SPREADSHEET_NS)->v ?? ''));
    }

    private static function textFromXml(SimpleXMLElement $xml): string
    {
        $xml->registerXPathNamespace('main', self::SPREADSHEET_NS);
        $parts = $xml->xpath('.//main:t') ?: [];

        return implode('', array_map(fn (SimpleXMLElement $part) => (string) $part, $parts));
    }

    private static function columnIndex(string $cellReference): ?int
    {
        if (! preg_match('/^([A-Z]+)\d+$/i', $cellReference, $matches)) {
            return null;
        }

        $index = 0;

        foreach (str_split(strtoupper($matches[1])) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index;
    }

    private static function xmlFromZip(ZipArchive $zip, string $path): ?SimpleXMLElement
    {
        $content = $zip->getFromName($path);

        if ($content === false) {
            return null;
        }

        $xml = simplexml_load_string($content);

        if (! $xml) {
            throw new RuntimeException("No se pudo leer XML interno: {$path}");
        }

        return $xml;
    }
}
