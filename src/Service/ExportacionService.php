<?php
declare(strict_types=1);

namespace App\Service;

use ZipArchive;

class ExportacionService
{
    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @return array{content: string, contentType: string, filename: string}
     */
    public function exportarCsv(array $headers, array $rows, string $filename): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [
                'content' => '',
                'contentType' => 'text/csv; charset=UTF-8',
                'filename' => $this->normalizarNombre($filename, 'csv'),
            ];
        }

        // BOM UTF-8 para compatibilidad con Excel.
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($stream, array_map([$this, 'celdaCsv'], $row), ',', '"', '\\');
        }

        rewind($stream);
        $content = stream_get_contents($stream) ?: '';
        fclose($stream);

        return [
            'content' => $content,
            'contentType' => 'text/csv; charset=UTF-8',
            'filename' => $this->normalizarNombre($filename, 'csv'),
        ];
    }

    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @return array{content: string, contentType: string, filename: string}
     */
    public function exportarXlsx(array $headers, array $rows, string $filename): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'saf_xlsx_');
        if ($tmp === false) {
            return [
                'content' => '',
                'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'filename' => $this->normalizarNombre($filename, 'xlsx'),
            ];
        }

        $zipPath = $tmp . '.xlsx';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return [
                'content' => '',
                'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'filename' => $this->normalizarNombre($filename, 'xlsx'),
            ];
        }

        $sheetData = $this->construirSheetXml(array_merge([$headers], $rows));

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetData);
        $zip->close();

        $content = (string)file_get_contents($zipPath);
        @unlink($zipPath);

        return [
            'content' => $content,
            'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'filename' => $this->normalizarNombre($filename, 'xlsx'),
        ];
    }

    /**
     * Alias conveniente para respuestas HTTP.
     *
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @return array{content: string, contentType: string, filename: string}
     */
    public function prepararDescarga(string $formato, array $headers, array $rows, string $filename): array
    {
        return match (strtolower($formato)) {
            'xlsx', 'excel' => $this->exportarXlsx($headers, $rows, $filename),
            default => $this->exportarCsv($headers, $rows, $filename),
        };
    }

    private function normalizarNombre(string $filename, string $extension): string
    {
        $base = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($filename, PATHINFO_FILENAME)) ?: 'export';
        $base = trim($base, '._-');
        if ($base === '') {
            $base = 'export';
        }

        return $base . '.' . $extension;
    }

    private function celdaCsv(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        return (string)$valor;
    }

    /**
     * @param list<list<mixed>> $matrix
     */
    private function construirSheetXml(array $matrix): string
    {
        $rowsXml = '';
        $rowNum = 1;
        foreach ($matrix as $fila) {
            $cellsXml = '';
            $colNum = 1;
            foreach ($fila as $celda) {
                $ref = $this->columnaDesdeIndice($colNum) . $rowNum;
                $cellsXml .= $this->celdaXml($ref, $celda);
                $colNum++;
            }
            $rowsXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
            $rowNum++;
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    private function celdaXml(string $ref, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '<c r="' . $ref . '" t="inlineStr"><is><t></t></is></c>';
        }

        if (is_int($valor) || (is_string($valor) && is_numeric($valor) && !str_contains($valor, '.'))) {
            return '<c r="' . $ref . '"><v>' . htmlspecialchars((string)(int)$valor, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</v></c>';
        }

        if (is_float($valor) || (is_string($valor) && is_numeric($valor))) {
            return '<c r="' . $ref . '"><v>' . htmlspecialchars((string)(float)$valor, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</v></c>';
        }

        if ($valor instanceof \DateTimeInterface) {
            $valor = $valor->format('Y-m-d H:i:s');
        } elseif (is_bool($valor)) {
            $valor = $valor ? 'Sí' : 'No';
        } else {
            $valor = (string)$valor;
        }

        $texto = htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $texto . '</t></is></c>';
    }

    private function columnaDesdeIndice(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Datos" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }
}
