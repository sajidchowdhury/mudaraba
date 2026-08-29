<?php

class NewDirectorContr extends NewDirector
{
    use SharedFunctionalityTrait;

    private $director_name;
    private $mobile;
    private $related_id;

    public function __construct(
        $director_name,
        $mobile,
        $related_id
    ) {
        $this->director_name  = $director_name;
        $this->mobile         = $mobile;
        $this->related_id     = $related_id;
    }

    public function Action()
    {




        // Create new customer record
        if ($this->related_id === 'New') {
            return $this->CreateData(
                $this->director_name,
                $this->mobile
            );
        }


        // Update existing customer record
        return $this->UpdateData(
            $this->director_name,
            $this->mobile,
            $this->related_id
        );
    }
}
