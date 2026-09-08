<?php

class NewSectorContr extends NewSector
{
    use SharedFunctionalityTrait;

    private $sector_name;
    private $mobile;
    private $address;
    private $related_id;

    public function __construct(
        $sector_name,
        $mobile,
        $address,
        $related_id
    ) {
        $this->sector_name  = $sector_name;
        $this->mobile         = $mobile;
        $this->address        = $address;
        $this->related_id     = $related_id;
    }

    public function Action()
    {

        date_default_timezone_set('Asia/Dhaka');



        // Create new customer record
        if ($this->related_id === 'New') {
            return $this->CreateData(
                $this->sector_name,
                $this->mobile,
                $this->address
            );
        }


        // Update existing customer record
        return $this->UpdateData(
            $this->sector_name,
            $this->mobile,
            $this->address,
            $this->related_id
        );
    }
}
