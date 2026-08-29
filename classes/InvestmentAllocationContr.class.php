<?php 
class InvestmentAllocationContr extends InvestmentAllocation {

    use SharedFunctionalityTrait;

    private $sector_id; 
    private $amount; 
    private $remarks; 
    private $type; 
    private $transaction_date; 
    private $related_id; 



    public function __construct($sector_id, $amount, $remarks , $type ,$transaction_date, $related_id) {
        $this->sector_id = $sector_id;
        $this->amount = $amount;
        $this->remarks = $remarks;
        $this->type = $type;
        $this->transaction_date = $transaction_date;
        $this->related_id = $related_id;        

    }

    public function Action() {


        date_default_timezone_set('Asia/Dhaka');


        // Validate inputs
        if (
            !$this->clean($this->sector_id) ||
            !$this->clean($this->remarks) ||
            !$this->clean($this->related_id)
        ) {
            return ["status" => "error", "message" => "Invalid input values"];
        }

        // Ensure amount and profit_rate are valid numbers and greater than or equal to 0
        if ($this->amount == 0 || $this->amount < 0 || !is_numeric($this->amount)) {
            return ["status" => "error", "message" => "Invalid  amount"];
        }

        if ($this->transaction_date == '') {
            return ["status" => "error", "message" => "Date can not empty "];
        }

        if ($this->related_id === 'New') {
        return  $this->CreateData(
                $this->sector_id, 
                $this->amount, 
                $this->remarks, 
                $this->type ,
                $this->transaction_date

        );
        }



   
    }
}
