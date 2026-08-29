$(function () {
  $.ajax({
    url: "includes/Home.inc.php",
    type: "GET",
    dataType: "json",
    success: function(response) {
      console.log("AJAX Response:", response); // 🔎 Debug

      // Update summary values
      $("#summary-collection").text(response.summary.collection);
      $("#summary-investment").text(response.summary.investment);
      $("#summary-left").text(response.summary.left);

      //-------------
      //- DONUT CHART (dynamic) -
      //-------------
      var labels = response.donut.map(item => item.label);
      var dataValues = response.donut.map(item => item.data);
      var colors = response.donut.map(item => item.color);

      var donutChartCanvas = $('#donutChart').get(0).getContext('2d');
      var donutData = {
        labels: labels,
        datasets: [
          {
            data: dataValues,
            backgroundColor: colors,
          }
        ]
      };
      var donutOptions = {
        maintainAspectRatio: false,
        responsive: true,
      };

      new Chart(donutChartCanvas, {
        type: 'doughnut',
        data: donutData,
        options: donutOptions
      });
    },
    error: function(xhr, status, error) {
      console.error("Error fetching data:", error);
    }
  });
});
