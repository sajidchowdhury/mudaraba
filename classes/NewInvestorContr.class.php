<?php

class NewInvestorContr extends NewInvestor
{
    use SharedFunctionalityTrait;

    private $investor_name;
    private $mobile;
    private $address;
    private $related_id;
    private $profit;
    private $start_profit_month;
    private $end_profit_month;
    private $reference;

    public function __construct(
        $investor_name,
        $reference,
        $mobile,
        $address,
        $profit,
        $start_profit_month,
        $end_profit_month,
        $related_id
    ) {
        $this->investor_name  = $investor_name;
        $this->reference  = $reference;
        $this->mobile         = $mobile;
        $this->address        = $address;
        $this->profit     = $profit;
        $this->start_profit_month     = $start_profit_month;
        $this->end_profit_month     = $end_profit_month;
        $this->related_id     = $related_id;

    }

    public function Action()
    {

        date_default_timezone_set('Asia/Dhaka');



        // Create new customer record
        if ($this->related_id === 'New') {
            return $this->CreateData(
                $this->investor_name,
                $this->reference,
                $this->mobile,
                $this->address,
                $this->profit,
                $this->start_profit_month,
                $this->end_profit_month
            );
        }


        // Update existing customer record
        return $this->UpdateData(
            $this->investor_name,
            $this->reference,
            $this->mobile,
            $this->address,
            $this->profit,
            $this->start_profit_month,
            $this->end_profit_month,
            $this->related_id
        );
    }
}
