<?php 
class InvestorLedgerReportContr extends InvestorLedgerReport {

    use SharedFunctionalityTrait;

    private $report_type; 
    private $date_from; 
    private $date_to; 
    private $investor_id; 


    public function __construct($report_type, $investor_id , $date_from, $date_to ) {
        $this->report_type = $report_type;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->investor_id = $investor_id;        

    }
 
    public function Report() {

        if ($this->report_type === 'Investor-Wise') {  // Fixed typo from "Summery" to "Summary"
            return $this->InvestorWiseMerged($this->date_from,$this->date_to,$this->investor_id);
        } 



     return json_encode(["status" => "error", "message" => "Invalid report type."]);
    }




}
?>
