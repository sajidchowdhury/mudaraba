<?php 
class SectorLedgerReportContr extends SectorLedgerReport {

    use SharedFunctionalityTrait;

    private $report_type; 
    private $date_from; 
    private $date_to; 
    private $sector_id; 




    public function __construct($report_type, $sector_id , $date_from, $date_to) {
        $this->report_type = $report_type;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->sector_id = $sector_id;        

    }
 
    public function Report() {

        if ($this->report_type === 'Sector-Wise') {  // Fixed typo from "Summery" to "Summary"
            return $this->SectorWise($this->date_from,$this->date_to,$this->sector_id);
        } 

       if ($this->report_type === 'All') {  // Fixed typo from "Summery" to "Summary"
            return $this->AllSectorWise($this->date_from,$this->date_to);
        } 



     return json_encode(["status" => "error", "message" => "Invalid report type."]);
    }




}
?>

