<?php 
class DirectorLedgerReportContr extends DirectorLedgerReport {

    use SharedFunctionalityTrait;

    private $report_type; 
    private $date_from; 
    private $date_to; 
    private $director_id; 




    public function __construct($report_type, $director_id , $date_from, $date_to) {
        $this->report_type = $report_type;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->director_id = $director_id;        

    }
 
    public function Report() {

        if ($this->report_type === 'My-Report') {  // Fixed typo from "Summery" to "Summary"
            return $this->MonthWiseReceivable($this->date_from,$this->date_to);
        } 

       if ($this->report_type === 'All') {  // Fixed typo from "Summery" to "Summary"
            return $this->AllDirector($this->date_from,$this->date_to);
        } 



     return json_encode(["status" => "error", "message" => "Invalid report type."]);
    }




}
?>

