<?php

namespace App\Services\Loans;

use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class LoanBookNormalizerService
{
    public function text($value)
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value === '' ? null : $value;
    }

    public function upperText($value)
    {
        $value = $this->text($value);

        return $value === null ? null : strtoupper($value);
    }

    public function account($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value) && strpos((string) $value, '.') !== false) {
            $value = sprintf('%.0f', $value);
        }

        $value = trim((string) $value);
        $value = preg_replace('/\s+/', '', $value);

        return $value === '' ? null : $value;
    }

    public function customerId($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value) && strpos((string) $value, '.') !== false) {
            $value = sprintf('%.0f', $value);
        }

        $value = trim((string) $value);
        $value = preg_replace('/\s+/', '', $value);

        return $value === '' ? null : $value;
    }

    public function amount($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $lower = strtolower($raw);

        if (in_array($lower, ['n/a', 'na', 'null', '-', '--'])) {
            return null;
        }

        $isParenthesesNegative = preg_match('/^\(.*\)$/', $raw) === 1;
        $isTrailingNegative = substr($raw, -1) === '-';

        $clean = str_replace(',', '', $raw);
        $clean = preg_replace('/[^\d\.\-\(\)]/', '', $clean);
        $clean = str_replace(['(', ')'], '', $clean);

        if ($clean === '' || $clean === '-') {
            return null;
        }

        $amount = (float) $clean;

        if ($isParenthesesNegative || $isTrailingNegative) {
            $amount = abs($amount) * -1;
        }

        return round($amount, 2);
    }

    public function decimal($value, $precision = 6)
    {
        $amount = $this->amount($value);

        if ($amount === null) {
            return null;
        }

        return round($amount, $precision);
    }

    public function date($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    public function comparisonName($value)
    {
        $value = $this->text($value);

        if ($value === null) {
            return null;
        }

        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]+/', '', $value);

        return $value;
    }

    public function key($relatedAccount, $relatedCustomerId)
    {
        return $relatedAccount . '|' . $relatedCustomerId;
    }
}
