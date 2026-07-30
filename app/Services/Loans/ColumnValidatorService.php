<?php

namespace App\Services\Loans;

use Exception;

class ColumnValidatorService
{
    public function validate(array $availableColumns, array $requiredColumns, $reportName)
    {
        $missing = array_values(array_diff($requiredColumns, $availableColumns));

        if (!empty($missing)) {
            throw new Exception(
                $reportName . ' is missing required column(s): ' . implode(', ', $this->humanizeColumns($missing))
            );
        }

        return true;
    }

    protected function humanizeColumns(array $columns)
    {
        return array_map(function ($column) {
            return ucwords(str_replace('_', ' ', $column));
        }, $columns);
    }
}
