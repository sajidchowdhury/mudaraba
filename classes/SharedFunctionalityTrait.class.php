<?php 

trait SharedFunctionalityTrait {





  public function clean($field_one) {
  
    if ( addslashes(strip_tags(trim($field_one))) ) {
        $result = true; 
    }else{
        $result = false; 
    }
    return $result ; 
}


    public function emptyInputLogin($field_one) {
  
        if (empty($field_one) ) {
            $result = false; 
        }else{
            $result = true; 
        }
        return $result ; 
    }


    public function hasHtmlEntities($field_one) {
  
     
        if ( htmlspecialchars($field_one)) {
            $result = true; 
        }else{
            $result = false; 
        }
        return $result ; 
    }


    public function isValidPassword($field_one) {
        // Use a regular expression to check the password format
        $pattern = '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        return preg_match($pattern, $field_one) === 1;
    }





    public function isMatched($field_one, $field_two) {
    
        if (
            ($field_one !== $field_two) 
            ) {
            $result = false ; 
        }else{
            $result = true; 
        }
        return $result ; 
    }



    
    public function generateCode($table) {
    // Get the last `code` from `purchase_invoices`
    $stmt = $this->connect()->query("SELECT code FROM {$table} ORDER BY id DESC LIMIT 1");
    $lastCode = $stmt->fetchColumn();

    if (!$lastCode) {
    return "0001"; // Start from 0001 if no record exists
    }

    // Increment the last code (convert to integer, add 1, then format as 4 digits)
    return str_pad((int)$lastCode + 1, 4, '0', STR_PAD_LEFT);
    }



    public function calculateTotalAmount($cart_data) { 
    $total = 0;
    foreach ($cart_data as $item) {
    $total += $item['quantity'] * $item['price'];
    }
    return $total;
    }


public function checkEditPermission($type, $link) {

    if (!isset($_SESSION)) {
        session_start();
    }

    // Ensure employee_id is set
    if (!isset($_SESSION['employee_id'])) {
        return false; // Or handle it as needed
    }

    $info = new User();
    $userPermissions = $info->getUserPermissions($_SESSION['employee_id'], $link);

    // Default to true, will set false if any required permission is missing
    $result = true;

    // Check each permission type in the array
    foreach ($type as $permission) {
        if ($permission === 'edit' && empty($userPermissions['can_edit'])) {
            $result = false;
        }
        if ($permission === 'delete' && empty($userPermissions['can_delete'])) {
            $result = false;
        }
        if ($permission === 'backdate' && empty($userPermissions['can_backdate'])) {
            $result = false;
        }
    }

    if($link == 'EditUSer.php' ){
   $result = true;
    }

    return $result;
}



        public function LoadExportScript($ReportName,$condition = 'true') {

    $content = <<<SCRIPT
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#example')) {
      $('#example').DataTable().destroy();
    }

    $('#example').DataTable({
      dom: 'Bfrtip',
        paging: $condition,
        searching: $condition,
        info: $condition,
        ordering: $condition,

      buttons: [
  {
    extend: 'copy',
    footer: true,
    title: "$ReportName"
  },
  {
    extend: 'excelHtml5',
    footer: true,
    title: "$ReportName"
  },
  {
    extend: 'pdfHtml5',
    footer: true,
    title: "$ReportName",
    customize: function (doc) {
      doc.styles.title = {
        color: 'red',
        fontSize: 15,
        alignment: 'center'
      };
    }
  },
  {
    extend: 'print',
    footer: true,
    title: '',
    customize: function (win) {
      $(win.document.body)
        .prepend('<h2 style="text-align:center;color:red;font-size:15pt;">$ReportName</h2>');
    },
    exportOptions: {
      columns: ':not(.no-export)',
      footer: true
    }
  }
],
      pageLength: 100
    });
  });
</script>
<style>
  tfoot {
    display: table-footer-group !important;
  }
</style>
SCRIPT;


    return $content;


    }


        public function ClaculateCTN( $quantity, $pcs_in_ctn) {

        $quantity = (float)$quantity;
        $pcsInCtn = (int)$pcs_in_ctn;

        $ctn = ($pcsInCtn > 0) ? (int) ceil($quantity / $pcsInCtn) : 0;

        return $ctn;

        }


        public function logAction( $action, $details) {

        if (!isset($_SESSION)) {
        session_start();
        }

        // Ensure employee_id is set
        if (!isset($_SESSION['employee_id'])) {
        return false; // Or handle it as needed
        }
 

        $info = new ActiveLog();
        $result = $info->CreatLog($_SESSION['employee_id'] , $action, $details);
        return $result;

    }


 

    

}

