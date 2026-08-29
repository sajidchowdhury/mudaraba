<?php 
class InvestmentProfitReportContr extends InvestmentProfitReport {

    use SharedFunctionalityTrait;

    private $report_type; 
    private $date_from; 
    private $date_to; 
    private $investor_id; 

    public function __construct($report_type, $investor_id , $date_from, $date_to) {
        $this->report_type = $report_type;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->investor_id = $investor_id;        

    }
 
    public function Report() {

        if ($this->report_type === 'All') {  // Fixed typo from "Summery" to "Summary"
            return $this->AllInvestment($this->date_from);
        } 

       if ($this->report_type === 'Investor-Wise-Investment') {  // Fixed typo from "Summery" to "Summary"
            return $this->InvestorWiseInvestment($this->date_from,$this->investor_id);
        } 





     return json_encode(["status" => "error", "message" => "Invalid report type."]);
    }




}
?>

