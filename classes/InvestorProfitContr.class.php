<?php
// classes/InvestorProfitContr.php

class InvestorProfitContr extends InvestorProfit {
    private array $entries;
    private string $month;
    private ?float $estimatedprofit;
    private ?float $actualprofit;
    private ?float $MyAmount;

    public function __construct(array $entries, string $month, ?float $estimatedprofit, ?float $actualprofit, ?float $MyAmount) {
        $this->entries = $entries;
        $this->month = $month;
        $this->estimatedprofit = $estimatedprofit;
        $this->actualprofit = $actualprofit;
        $this->MyAmount = $MyAmount;

    }

    public function Action(): array {
        // Basic server-side validation for entries
        if (!preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            throw new Exception('Invalid month format in controller.');
        }

        if (!is_array($this->entries) || empty($this->entries)) {
            throw new Exception('No investor entries provided.');
        }

        // pass to model
        return $this->CreateOrUpdateData($this->entries, $this->month, $this->estimatedprofit, $this->actualprofit,$this->MyAmount);
    }
}
