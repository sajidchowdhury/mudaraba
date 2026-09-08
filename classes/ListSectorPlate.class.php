<?php 


class ListSectorPlate {

    private $id; 

    public function __construct($id = 'New') {
        $this->id = $id; 
    }

    public function SetupForm() { 
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }      


$data1 = New InvestmentAllocation();
        ?>

        <div class="row">
            <div class="col-md-12" style="margin-bottom: 0px!important;">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>SL</th>
                  <th>Sector</th> <th>Investment</th> <th>Address</th><th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php   

                $sl = 1; 
                $total = 0; 
                  $List = New NewSector();
                  foreach($List->ListData()  AS $row) {

                    $inv = $data1->TotalInvestment($row['id']);
                ?>                     
                        
                    <tr>
                        <td style="text-align:center;"><?php echo $sl++; ?></td>
                        <td style="text-align:left;"><?php echo $row['name']; ?></td>
                        <td style="text-align:left;"><b class="text-info"><?php echo formatNumber($inv); ?></b></td>
                        <td style="text-align:left;"><?php echo $row['address']; ?></td>
                        <td style="text-align:center;">  
                        <a href="dynamic-page.php?page=New-Sector&id=<?php echo $row['id']; ?>"> <i class="fa fa-edit" style="color:green;"></i> </a> 
                      

                    

                        </td>
                    </tr>   

                <?php  
   $total += $inv;; 
            } // while ?>
                </tbody>
                <tfoot>
                    <tr><td></td><th>Total</th><th><?php echo formatNumber($total); ?></th><td></td><td></td></tr>
                    </tfoot>
              </table>
            </div>
        </div>

       
        <?php
        $content = ob_get_clean(); // Get buffered output
        print $content;
    }





}