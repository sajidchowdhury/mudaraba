<?php 


class NewSectorPlate {

    private $id; 

    public function __construct($id = 'New') {
        $this->id = $id; 
    }

    public function SetupForm() { 
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $csrf_token = isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : '';


        // Default blank values
         $sector_name = $address = '' ;
         $mobile  = time() ; 

        // Fetch existing data if editing
        if ($this->id !== 'New') {
            $fetch = new NewSector();
            $data = $fetch->SingleData($this->id);

            if ($data) { 
                $sector_name   = htmlspecialchars($data['name']);
                $mobile = htmlspecialchars($data['mobile']);
                $address = htmlspecialchars($data['address']);

            }
        }

        ?>

        <div class="row">
            <div class="col-md-12" style="margin-bottom: 0px!important;">
                <form id="myForm" class="login100-form validate-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="related_id" value="<?= htmlspecialchars($this->id) ?>">
                    <input type="hidden" name="poster" value="<?= $poster ?>">
                    <input type="hidden" name="mobile" value="<?= $mobile ?>">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary">
                                <div class="card-body">
                                    <div class="row">



                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="sector_name">Sector Name</label>
                                                <input  type="text" required class="form-control" name="sector_name" id="sector_name" value="<?=
                                                  $sector_name ?>">
                                            </div>
                                        </div>







                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="address">Address</label>
                                                <input  type="text" required class="form-control" name="address" id="address" value="<?=
                                                  $address ?>">
                                            </div> 
                                        </div>

                                    </div>
                                </div>
                                <div class="card-footer">
                                    <input type="submit" name="kt_submit_button" id="kt_submit_button" class="btn btn-primary" value="Submit">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

       
        <?php
        $content = ob_get_clean(); // Get buffered output
        print $content;
    }





}