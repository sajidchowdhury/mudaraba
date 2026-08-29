document.addEventListener('DOMContentLoaded', function () {

    const profit_month = $('#profit_month').val();   // <-- jQuery used here
    
    fetchSectorProfitData(profit_month);

    // Trigger calculation on page load and for all inputs
    document.querySelectorAll('input[name="amount[]"], input[name="est_amount[]"]').forEach(input => {
        input.addEventListener('keyup', CalCulateTotal);
    });

    // Trigger AJAX on month change
    document.getElementById('profit_month').addEventListener('change', function () {
        const selectedMonth = this.value;
        fetchSectorProfitData(selectedMonth);
    });

    CalCulateTotal(); // Initial call
});


// Main calculation logic
function CalCulateTotal() {
    let totalEst = 0, totalActual = 0, totalDiff = 0; 
    document.querySelectorAll('.invoice-row').forEach(row => {
        const estInput = row.querySelector('input[name="est_amount[]"]');
        const actInput = row.querySelector('input[name="amount[]"]');
        const diffElem = row.querySelector('#diff');

        const est = parseFloat(estInput.value) || 0;
        const act = parseFloat(actInput.value) || 0;

        const diff = est - act ;

        totalEst += est;
        totalActual += act;
        totalDiff += diff;

        diffElem.textContent = formatNumber(diff);
    });

    document.getElementById("TotalEstProfit").textContent = formatNumber(totalEst);
    document.getElementById("TotalProfit").textContent = formatNumber(totalActual);
    document.getElementById("TotalDiff").textContent = formatNumber(totalDiff);
}

// Fetch data using AJAX
function fetchSectorProfitData(month) {


        document.getElementById('MonthName').innerHTML = month ;
        document.getElementById('related_id').value = month ;

        
        document.getElementById('LinkPD').innerHTML = '<a href="dynamic-page.php?page=Investor-Profit&id='+month+'" class="btn btn-outline-secondary ml-2">Profit Disbursement of '+month+' </a>' ;


    fetch('includes/fetchsectorprofit.inc.php?month=' + encodeURIComponent(month))
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('.invoice-row').forEach(row => {
                const sectorId = row.getAttribute('data-sector_id');
                const sectorData = data[sectorId];

                if (sectorData) {
                    row.querySelector('input[name="est_amount[]"]').value = parseFloat(sectorData.estimated_profit || 0).toFixed(2);
                    row.querySelector('input[name="amount[]"]').value = parseFloat(sectorData.actual_profit || 0).toFixed(2);
                } else {
                    row.querySelector('input[name="est_amount[]"]').value = "0.00";
                    row.querySelector('input[name="amount[]"]').value = "0.00";
                }
            });

            CalCulateTotal();
        })
        .catch(error => {
            console.error("Error loading sector data:", error);
        });
}



       $(document).on('click', '#kt_submit_button', function (e) {
    e.preventDefault();
    const $btn = $(this);
    if ($btn.prop('disabled')) return;

    $btn.prop('disabled', true).text('Processing...');

    const profit_month = $('#profit_month').val();
    const form_token = $('#form_token').val();
    const related_id = $('#related_id').val();

    let productData = [];
    $('.invoice-row').each(function () {
        const $row = $(this);
        productData.push({
            sector_id: $row.data('sector_id'),
            est_amount: $row.find('input[name="est_amount[]"]').val(),
            amount: $row.find('input[name="amount[]"]').val()

                    });
    });

    $.ajax({
        url: 'includes/SectorProfit.inc.php',
        type: 'POST',
        dataType: 'json',
        data: {
            profit_month,
            related_id,
            form_token,
            items: JSON.stringify(productData),
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function (res) {
            console.log("AJAX response:", res); // <-- DEBUG

            Swal.fire({
                icon: res.status === 'success' ? 'success' : 'error',
                title: res.message,
                showConfirmButton: false,
                timer: 2500
            });
            $btn.prop('disabled', false).text('Submit');
        },
        error: function (xhr, status, err) {
            // Catch any unexpected error
            console.error("AJAX request failed", status, err, xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'AJAX request failed. See console for details.',
                showConfirmButton: true
            });
            $btn.prop('disabled', false).text('Submit');
        }
    });
});