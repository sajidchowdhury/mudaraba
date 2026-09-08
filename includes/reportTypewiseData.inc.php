<?php
include_once "autoloader.inc.php";

if (isset($_POST['report_type'])) {
    $report_type = json_decode($_POST['report_type'], true); 

    if ($report_type === null) {
        echo json_encode(["error" => "Invalid JSON format"]);
        exit;
    }

    $type = $report_type['type'] ?? '';
    $customData = $report_type['customData'] ?? '';

    $date_content = '';
    $content = '';

    // Generate date fields
    if ($customData == 'Need-Two-Date') {

        $date_content = '<div class="form-group">
                            <label>Select Date</label>
                            <input type="text" class="form-control" name="reservation" id="reservation" value="' . date("Y-m-d") . '">
                        </div>';


    } elseif ($customData == 'Need-Single-Date') {


        $date_content = '<div class="form-group">
                            <label>Select Date</label>
                            <input type="date" class="form-control" name="sdate" id="sdate" value="' . date("Y-m-d") . '">
                        </div>';

    } elseif ($customData == 'Need-Month') {


        $date_content = '<div class="form-group">
                            <label>Till Month</label>
                            <input type="month" class="form-control" name="month" id="sdate" value="' . date("Y-m-d") . '">
                        </div>';


    }else{

         $date_content = '<div  class="form-group">
                            <label>Select Date</label>
                            <input type="date" class="form-control" name="sdate" id="sdate" value="">
                        </div>';
    }






    if ($type == 'Investor-Wise' || $type == 'Investor-Wise-Investment' || $type == 'Investor-Wise-Profit') {

        $info = new NewInvestor();
        $content = '<input type="hidden" id="related_id" value="investor_id" > <div class="form-group">
                        <label>Investor Name</label>
                        <select class="form-control select2" id="investor_id" name="investor_id">
                            <option value="">Select One</option>';
        
        foreach ($info->ListData() as $fetch) {

                    $name = (!empty($fetch['reference'])) ? $fetch['name'] . ' (' .$fetch['reference'].')' : $fetch['name'];

            $content .= '<option value="'.$fetch['id'].'">'.$name.'</option>';
        }
        $content .= '</select>
                    </div>';

                        echo json_encode([
        "date_content" => $date_content,
        "content" => $content
    ]);
    exit;


    }


    if ($type == 'Sector-Wise') {

        $info = new NewSector();
        $content = '<input type="hidden" id="related_id" value="sector_id" > <div class="form-group">
                        <label>Sector Name</label>
                        <select class="form-control select2" id="sector_id" name="sector_id">
                            <option value="">Select One</option>';
        
        foreach ($info->ListData() as $fetch) {
            $content .= '<option value="'.$fetch['id'].'">'.$fetch['name'].'</option>';
        }
        $content .= '</select>
                    </div>';

                        echo json_encode([
        "date_content" => $date_content,
        "content" => $content
    ]);
    exit;


    }


    if ($type == 'Director-Wise') {

        $info = new NewDirector();
        $content = '<input type="hidden" id="related_id" value="director_id" > <div class="form-group">
                        <label>Director Name</label>
                        <select class="form-control select2" id="director_id" name="director_id">
                            <option value="">Select One</option>';
        
        foreach ($info->ListData() as $fetch) {
            $content .= '<option value="'.$fetch['id'].'">'.$fetch['name'].'</option>';
        }
        $content .= '</select>
                    </div>';

                        echo json_encode([
        "date_content" => $date_content,
        "content" => $content
    ]);
    exit;


    }



    


        $content = '<input type="hidden" id="related_id" value="All" >
        <input type="hidden" id="All" value="All" >';

                        echo json_encode([
        "date_content" => $date_content,
        "content" => $content
    ]);
    exit;



    // Send JSON response

} else {
    echo json_encode(["error" => "No data received"]);
}
?>
