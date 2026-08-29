<?php 
class SectorProfitContr extends SectorProfit {
    use SharedFunctionalityTrait;

    private $profit_month;
    private $items;
    private $related_id;

    public function __construct($profit_month, $items, $related_id) {
        $this->profit_month = $profit_month;
        $this->items = $items;
        $this->related_id = $related_id;
    }

    public function Action() {



        date_default_timezone_set('Asia/Dhaka');

        if (!$this->clean($this->profit_month) || !$this->clean($this->related_id)) {
            return ["status" => "error", "message" => "Invalid input values"];
        }

        if ($this->profit_month === '') {
            return ["status" => "error", "message" => "Date cannot be empty"];
        }

 
            return $this->CreateData($this->profit_month, $this->items);
      
    

        return ["status" => "error", "message" => "Unsupported operation"];
    }
}
