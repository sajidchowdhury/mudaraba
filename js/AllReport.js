function ReportWiseData(type) {


    if (type === '') { 
        document.getElementById('load-date-content').innerHTML = '';
        document.getElementById('load-report-content').innerHTML = '';
        return false; 
    }

    // Get the selected option
    var selectElement = document.getElementById('report_type'); 
    var selectedOption = selectElement.options[selectElement.selectedIndex];

    // Get the custom data attribute value
    var customData = selectedOption.getAttribute('data-custom');

    // Construct report type object
    var report_type = {
        type: type,
        customData: customData
    };

    // Send AJAX request
    $.ajax({
        type: 'POST',
        url: 'includes/reportTypewiseData.inc.php',
        data: { report_type: JSON.stringify(report_type) }, 
        dataType: 'json', 
        success: function(response) {
            document.getElementById('load-date-content').innerHTML = response.date_content || '';
            document.getElementById('load-report-content').innerHTML = response.content || '';
                        $('.select2').select2();
                   $('#reservation').daterangepicker({

 locale: {
        format: 'DD/MM/YYYY'
      }      
    })
        },
        error: function(xhr, status, error) {
            console.log("AJAX Error:", error);
        }
    });
}


$(document).ready(function () {

    $("#searchReport").click(function () {
        var reportType = $("#report_type").val(); // Get selected report type
         var report_name = $("#report_name").val(); 
        if (reportType === '') {
            alert("Please select a report type!");
            return;
        }

        // Check if date range input exists
        var dateInput = $("#reservation").val(); 
        var singleDate = $("#sdate").val();  

        var date_from = "";
        var date_to = "";

        if (dateInput) {
            var dates = dateInput.split(" - "); // Split date range
            date_from = dates[0]; 
            date_to = dates[1]; 
        } else if (singleDate) {
            date_from = singleDate; 
            date_to = singleDate; 
        }

        var related_id = $("#related_id").val() || ""; 
        var relatedid = $('#'+ related_id).val();


        // Prepare data object
        var requestData = {
            report_name: report_name,
            report_type: reportType,
            date_from: date_from,
            date_to: date_to,
            relatedid: relatedid
        };

        // Send AJAX request
        $.ajax({
            type: "POST",
            url: "includes/getReportData.inc.php",  
            data: requestData,  
            success: function (response) {
    $("#load_data").html(response); 

},
            error: function (xhr, status, error) {
                console.log("Error:", error);
            }
        });
    });
});




