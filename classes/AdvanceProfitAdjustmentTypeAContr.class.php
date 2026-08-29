<?php 
class AdvanceProfitAdjustmentTypeAContr extends AdvanceProfitAdjustmentTypeA {

    use SharedFunctionalityTrait;

    private $adv_adjust; 
    private $investors; 
    private $sectors; 

    public function __construct($adv_adjust, $investors, $sectors) {
        $this->adv_adjust = $adv_adjust;
        $this->investors = $investors;
        $this->sectors = $sectors;

    }
 
    public function saveAdjustment() {



      return $this->createAdj($this->investors, $this->sectors, $this->adv_adjust);


    }




}
?>

