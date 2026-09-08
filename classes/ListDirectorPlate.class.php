<?php 


class ListDirectorPlate {

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
                  <th>Director</th> <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php   

                $sl = 1; 
                  $List = New NewDirector();
                  foreach($List->ListData()  AS $row) {

                ?>                     
                        
                    <tr>
                        <td style="text-align:center;"><?php echo $sl++; ?></td>
                        <td style="text-align:left;"><?php echo $row['name']; ?></b></td>

                        <td style="text-align:center;">  
                        <a href="dynamic-page.php?page=New-Director&id=<?php echo $row['id']; ?>"> <i class="fa fa-edit" style="color:green;"></i> </a> 
                      

                    

                        </td>
                    </tr>   

                <?php  } // while ?>
                </tbody>
               
              </table>
            </div>
        </div>

       
        <?php
        $content = ob_get_clean(); // Get buffered output
        print $content;
    }





}