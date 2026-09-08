$.ajax({
    url: 'includes/dashboardSalesData.inc.php', // API endpoint
    method: 'GET',
    dataType: 'json',
    success: function(response) {
        console.log("Received Response:", response); // Debugging

        // Ensure the expected structure exists
        if (!response.salesData || !response.transactionSummary) {
            console.error("Invalid response structure:", response);
            return;
        }

        // Check nested structures
        if (!Array.isArray(response.salesData.labels) || 
            !Array.isArray(response.salesData.this_week_sales) || 
            !Array.isArray(response.salesData.last_week_sales)) {
            console.error("Invalid salesData structure:", response.salesData);
            return;
        }

        if (typeof response.transactionSummary.customer_receive === "undefined" ||
            typeof response.transactionSummary.supplier_payment === "undefined" ||
            typeof response.transactionSummary.income_voucher === "undefined" ||
            typeof response.transactionSummary.expense_voucher === "undefined") {
            console.error("Invalid transactionSummary structure:", response.transactionSummary);
            return;
        }


            $("#sales-percentage").text(`${response.salesData.overall_change}%`);



        // **Update Chart**
        var ctx = $('#visitors-chart');
        var visitorsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: response.salesData.labels || [],
                datasets: [
                    {
                        label: "This Week",
                        data: response.salesData.this_week_sales || [],
                        backgroundColor: 'transparent',
                        borderColor: '#007bff',
                        pointBorderColor: '#007bff',
                        pointBackgroundColor: '#007bff',
                        fill: false
                    },
                    {
                        label: "Last Week",
                        data: response.salesData.last_week_sales || [],
                        backgroundColor: 'transparent',
                        borderColor: '#ced4da',
                        pointBorderColor: '#ced4da',
                        pointBackgroundColor: '#ced4da',
                        fill: false
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        grid: {
                            display: true,
                            lineWidth: 1,
                            color: 'rgba(0, 0, 0, .2)',
                            zeroLineColor: 'transparent'
                        },
                        beginAtZero: true,
                        suggestedMax: Math.max(...response.salesData.this_week_sales, ...response.salesData.last_week_sales) + 50
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // **Update Transaction Summary**
        $("#todayCustomerReceive").text(response.transactionSummary.customer_receive);
        $("#todaySupplierPayment").text(response.transactionSummary.supplier_payment);
        $("#todayIncomeVoucher").text(response.transactionSummary.income_voucher);
        $("#todayExpenseVoucher").text(response.transactionSummary.expense_voucher);
    },
    error: function(xhr, status, error) {
        console.error("AJAX Error:", error);
    }
});
