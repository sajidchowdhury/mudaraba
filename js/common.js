function formatNumber(num) {
    num = num.toString();
    let last3 = num.substring(num.length - 3);
    let rest = num.substring(0, num.length - 3);

    if (rest !== '') {
        last3 = ',' + last3;
    }

    rest = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ",");
    return rest + last3;
}




  function InvestorRationWiseData(Ratio,Type) {

    const month = document.getElementById('transaction_month').value;

    if (Ratio == '' ) {
        alert("Please select a ratio first.");

    }


    if (!month) {
        alert("Please select a month first.");
        document.getElementById('investor_id').value = "";
        return;
    }

    // AJAX call to load the partial content
    $.ajax({
        url: 'includes/LoadRatioTypeWiseData.inc.php',
        method: 'POST',
        data: {
            month: month,
            Type: Type,
            Ratio: Ratio
        },
        success: function (html) {

          $('#LoadTypeWiseData').html(html);
          $('.select2').select2()
  
        },
        error: function () {
            alert('Could not load data.');
        }
    });
}

