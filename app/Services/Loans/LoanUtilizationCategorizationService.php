<?php

namespace App\Services\Loans;

use App\Models\Loans\LoanUtilizationProductOverride;

class LoanUtilizationCategorizationService
{
    protected ?array $overrides = null;

    public function productName(?string $creditLine, ?string $glName, ?string $industrySegment): string
    {
        $creditLine = trim((string) $creditLine);
        $industrySegment = trim((string) $industrySegment);

        $overrides = $this->loadOverrides();
        if ($creditLine !== '' && isset($overrides[$creditLine])) {
            return $overrides[$creditLine];
        }

        if (preg_match('/^AGRIC/', $industrySegment)) {
            return 'Agriculture';
        }

        if (preg_match('/^EDUCT/', $industrySegment) && !preg_match('/^STAFF|^STFLN/', $creditLine)) {
            return 'School Finance';
        }

        foreach (config('loan_utilization.prefix_rules', []) as $pattern => $category) {
            if (preg_match($pattern, $creditLine)) {
                return $category;
            }
        }

        $glName = (string) $glName;
        if (str_contains($glName, 'MORTGAGE')) {
            return 'Residential and Land Purchase';
        }
        if (str_contains($glName, 'FINANCE LEASE')) {
            return 'EKE Asset Backed';
        }
        if (str_contains($glName, 'TRADE RELATED')) {
            return 'EKE Contract-LPO-Receivable';
        }

        return 'Unmapped - Review';
    }

    public function stage(?string $glName, ?string $status): string
    {
        if ($glName && preg_match('/STAGE\s*(\d)/', $glName, $m)) {
            return 'Stage ' . $m[1];
        }

        if ($status === 'WOFF') {
            return 'Stage 3';
        }

        return 'Stage 1';
    }

    public function performanceStatus(string $stage): string
    {
        return $stage === 'Stage 1' ? 'Performing' : 'Non-Performing';
    }

    public function classificationLabel(?string $status): string
    {
        $status = trim((string) $status);

        return config("loan_utilization.status_labels.$status", $status);
    }

    public function decodeBusiness(?string $businessSegment): string
    {
        $bs = trim((string) $businessSegment);

        if (preg_match('/^CSP/', $bs)) {
            return 'RETAIL';
        }
        if (preg_match('/^CM/', $bs)) {
            return 'COMMERCIAL';
        }
        if (preg_match('/^CB/', $bs)) {
            return 'CORPORATE';
        }
        if (preg_match('/^DB/', $bs)) {
            return 'COMMERCIAL';
        }

        return 'UNMAPPED';
    }

    protected function loadOverrides(): array
    {
        if ($this->overrides === null) {
            $this->overrides = LoanUtilizationProductOverride::query()
                ->pluck('product_name', 'credit_line_code')
                ->all();
        }

        return $this->overrides;
    }
}
