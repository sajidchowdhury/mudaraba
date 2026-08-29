<?php 


class InvestorLedgerPlate {

  public function SetupForm(){ 
    
 $info = new NewInvestor();

        $content =  '';


        $content .=  ' <div class="row">
          <!-- left column -->
          <div class="col-md-12">
            <!-- general form elements -->
            <div class="card card-primary">
     
              <!-- /.card-header -->
              <!-- form start -->
              <form role="form" action="purchase_report.php" method="get">

                <div class="card-body">
                <div class="row">

             
                        
                      <div class="col-md-6" id="load-report-content">


                  <input type="hidden" id="related_id" value="investor_id" > <div class="form-group">
                        <label>Investor Name</label>
                        <select class="form-control select2" id="investor_id" name="investor_id">
                            <option value="">Select One</option>';
        
        foreach ($info->ListData() as $fetch) {

                    $name = (!empty($fetch['reference'])) ? $fetch['name'] . ' (' .$fetch['reference'].')' : $fetch['name'];

            $content .= '<option value="'.$fetch['id'].'">'.$name.'</option>';
        }
        $content .= '</select>
                    </div>';



                      $content .= ' </div>
                     <div class="col-md-6" > <div class="form-group">
                            <label>Select Date</label>
                            <input type="text" class="form-control" name="reservation" id="reservation" value="' . date("Y-m-d") . '">
                        </div>

                      </div>
                      <input type="hidden" id="report_name" name="report_name" value="Investor Ledger Report">
                      <input type="hidden" id="report_type" name="report_name" value="Investor-Wise">


                   </div>
                </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
        <button type="button" id="searchReport" class="btn btn-primary">Search</button>
                </div>
              </form>
            </div>
            <!-- /.card -->


          </div>
          <!--/.col (left) -->
        </div>
        <!-- /.row -->

       
            <div class="row"><div class="col-md-12"><div id="load_data"></div></div></div>
            <!-- /.card-body -->
       
      ';


print $content;


    }





}