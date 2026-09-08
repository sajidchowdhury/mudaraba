<?php 


class NewInvestorPlate {

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
         $investor_name = $address = $mobile  =  $end_profit_month = $start_profit_month = $reference  = ''; 
         $profit = 0 ;

        // Fetch existing data if editing
        if ($this->id !== 'New') {
            $fetch = new NewInvestor();
            $data = $fetch->SingleData($this->id);

            if ($data) { 
                $investor_name   = htmlspecialchars($data['name']);
                $mobile = htmlspecialchars($data['mobile']);
                $address = htmlspecialchars($data['address']);
                $profit = htmlspecialchars($data['profit']);
                $start_profit_month = htmlspecialchars($data['start_profit_month']);
                $end_profit_month = htmlspecialchars($data['end_profit_month']);
                $reference = htmlspecialchars($data['reference']);


            }
        }

        ?>

        <div class="row">
            <div class="col-md-12" style="margin-bottom: 0px!important;">
                <form id="myForm" class="login100-form validate-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="related_id" value="<?= htmlspecialchars($this->id) ?>">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary">
                                <div class="card-body">
                                    <div class="row">



                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="investor_name">Investor Name</label>
                                                <input  type="text" required class="form-control" name="investor_name" id="investor_name" value="<?=
                                                  $investor_name ?>">
                                            </div>
                                        </div>


 <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="address">Reference</label>
                                                <input  type="text"  class="form-control" name="reference" id="reference" value="<?=
                                                  $reference ?>">
                                            </div> 
                                        </div>




                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="mobile">Mobile</label>
                                                <input  type="text"  class="form-control" name="mobile" id="mobile" value="<?=
                                                  $mobile ?>">
                                            </div>
                                        </div>




                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="address">Address</label>
                                                <input  type="text"  class="form-control" name="address" id="address" value="<?=
                                                  $address ?>">
                                            </div> 
                                        </div>

                                               <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="profit">Profit % </label>
                                                <input  type="number" required class="form-control" name="profit" id="profit" value="<?=
                                                  $profit ?>">
                                            </div> 
                                        </div>

  <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="start_profit_month">Start Profit Month</label>
                                                <input  type="month" required class="form-control" name="start_profit_month" id="start_profit_month" value="<?=
                                                  $start_profit_month ?>">
                                            </div> 
                                        </div>

                                          <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="profit">End Profit Month</label>
                                                <input  type="month" required class="form-control" name="end_profit_month" id="end_profit_month" value="<?=
                                                  $end_profit_month ?>">
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