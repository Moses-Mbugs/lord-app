<?php

namespace App\Services\Loans;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelParserService
{
    public function parse($file, array $aliases, array $requiredColumns)
    {
        $path = $this->getFilePath($file);

        // Loading large workbooks pulls the whole cell model into memory;
        // readDataOnly skips styles/formatting we never use here, and the
        // raised limit is scoped to this request only (not a server-wide change).
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, false);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($rows)) {
            throw new Exception('The uploaded Excel file appears to be empty.');
        }

        $headerInfo = $this->findHeaderRow($rows, $aliases, $requiredColumns);

        if (!$headerInfo) {
            throw new Exception('Could not find a valid header row in the uploaded Excel file.');
        }

        $headerRowIndex = $headerInfo['row_index'];
        $headerMap = $headerInfo['header_map'];

        $dataRows = [];

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $rawRow = $rows[$i];

            if (!is_array($rawRow)) {
                continue;
            }

            $mappedRow = [];

            foreach ($headerMap as $columnIndex => $canonicalName) {
                $mappedRow[$canonicalName] = array_key_exists($columnIndex, $rawRow)
                    ? $rawRow[$columnIndex]
                    : null;
            }

            if (!$this->isBlankRow($mappedRow)) {
                $dataRows[] = $mappedRow;
            }
        }

        return [
            'headers' => array_values(array_unique(array_values($headerMap))),
            'rows' => $dataRows,
            'header_row_number' => $headerRowIndex + 1,
            'data_row_count' => count($dataRows),
        ];
    }

    protected function getFilePath($file)
    {
        if (is_string($file)) {
            return $file;
        }

        if (method_exists($file, 'getRealPath')) {
            return $file->getRealPath();
        }

        throw new Exception('Invalid file supplied for Excel parsing.');
    }



    protected function findHeaderRow(array $rows, array $aliases, array $requiredColumns)
    {
        $labelMap = $this->buildLabelMap($aliases);

        $maxRowsToScan = min(50, count($rows));

        $minimumMatches = count($requiredColumns) <= 5 ? 3 : 6;
        $minimumMatches = min($minimumMatches, count($requiredColumns));

        for ($rowIndex = 0; $rowIndex < $maxRowsToScan; $rowIndex++) {
            $row = $rows[$rowIndex];

            if (!is_array($row)) {
                continue;
            }

            $headerMap = [];

            foreach ($row as $columnIndex => $cellValue) {
                $normalizedHeader = $this->normalizeHeader($cellValue);

                if ($normalizedHeader === '') {
                    continue;
                }

                if (isset($labelMap[$normalizedHeader])) {
                    $headerMap[$columnIndex] = $labelMap[$normalizedHeader];
                }
            }

            $matchedRequiredColumns = array_intersect($requiredColumns, array_values($headerMap));

            if (count($matchedRequiredColumns) >= $minimumMatches) {
                return [
                    'row_index' => $rowIndex,
                    'header_map' => $headerMap,
                ];
            }
        }

        return null;
    }

    protected function buildLabelMap(array $aliases)
    {
        $map = [];

        foreach ($aliases as $canonicalName => $labels) {
            foreach ($labels as $label) {
                $map[$this->normalizeHeader($label)] = $canonicalName;
            }
        }

        return $map;
    }

    protected function normalizeHeader($value)
    {
        $value = trim((string) $value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '', $value);

        return $value;
    }

    protected function isBlankRow(array $row)
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
