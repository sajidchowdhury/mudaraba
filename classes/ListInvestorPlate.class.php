<?php 


class ListInvestorPlate {

    private $id; 

    public function __construct($id = 'New') {
        $this->id = $id; 
    }

    public function SetupForm() { 
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }      

        ?>

        <div class="row">
            <div class="col-md-12" style="margin-bottom: 0px!important;">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>SL</th>
                  <th>Investor</th> <th>Current Investment</th>  <th>Mobile</th><th>Address</th><th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php   

                $sl = 1; 
                  $List = New NewInvestor();
                  $Date = New Investments();
                  $total = 0 ;
                  foreach($List->ListData()  AS $row) {
                    $inv = $Date->TotalInvestmentByInvestor($row['id']);

                    $name = (!empty($row['reference'])) ? $row['name'] . ' (' .$row['reference'].')' : $row['name'];
                ?>                     
                        
                    <tr>
                        <td style="text-align:center;"><?php echo $sl++; ?></td>
                        <td style="text-align:left;"><?php echo $name; ?></b></td>
                        <td style="text-align:left;"><?php echo $inv; ?></b></td>
                        <td style="text-align:left;"><?php echo $row['mobile']; ?></td>
                        <td style="text-align:left;"><?php echo $row['address']; ?></td>

                        <td style="text-align:center;">  
                        <a href="dynamic-page.php?page=New-Investor&id=<?php echo $row['id']; ?>"> <i class="fa fa-edit" style="color:green;"></i> </a> 
                    
                        </td>
                    </tr>   

                <?php  $total += $inv ; } // while ?>
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