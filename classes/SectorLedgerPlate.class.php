<?php 


class SectorLedgerPlate {

  public function SetupForm(){ 
    


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


                  
                        
                      <div class="col-md-6" id="load-report-content">';

 $info = new NewSector();
        $content .= '<input type="hidden" id="related_id" value="sector_id" > <div class="form-group">
                        <label>Sector Name</label>
                        <select class="form-control select2" id="sector_id" name="sector_id">
                            <option value="">Select One</option>';
        
        foreach ($info->ListData() as $fetch) {
            $content .= '<option value="'.$fetch['id'].'">'.$fetch['name'].'</option>';
        }
        $content .= '</select>
                    </div>';


                        $content .= '</div>
                      

                      <div class="col-md-6" id="load-date-content">

<div class="form-group">
                            <label>Select Date</label>
                            <input type="text" class="form-control" name="reservation" id="reservation" value="' . date("Y-m-d") . '">
                        </div>

                      </div>
                      


                      <input type="hidden" id="report_name" name="report_name" value="Sector Ledger Report">
                      <input type="hidden" id="report_type" name="report_type" value="Sector-Wise">


                
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