  $(function() {
    const Toast = Swal.mixin({
      toast: true,
      position: 'bottomLeft',
      showConfirmButton: false,
      timer: 3000
    });

        $('.toastsDefaultBottomLeft').click(function() {
      $(document).Toasts('create', {
        title: 'New Investor',
        position: 'bottomLeft',
       body: '<ul class="mb-0">' +
    '<li>Pending PO এখানে প্রদর্শিত হচ্ছে।</li>' +
    '<li>User এর জন্য edit,delete,view অপশন রয়েছে।</li>' +
    '<li>যেসব অর্ডার ইতোমধ্যে RECEIVE হয়েছে, সেগুলো আপডেট করা যাবে না।</li>' +
    '<li>সুপারঅ্যাডমিন ব্যবহারকারীরা অর্ডার  Force Completed / Delete (ইতোমধ্যে RECEIVE হয়েহে এমন ছাড়া ) করতে পারেন। </li>' +
    '</ul>'

      })
    });
  });


document.getElementById("start_profit_month").addEventListener("change", function() {
    let startValue = this.value; // format: YYYY-MM
    if (startValue) {
        let [year, month] = startValue.split("-");
        let newYear = parseInt(year) + 5;

        // format back to YYYY-MM
        let endValue = newYear + "-" + month;
        document.getElementById("end_profit_month").value = endValue;
    }
});



const form = document.getElementById('myForm');

form.addEventListener('submit', (event) => {
  event.preventDefault();

  const formData = new FormData(form);

  fetch('includes/NewInvestor.inc.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json()) // Parse JSON response
  .then(data => {
      if (data.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: data.message,
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true
        }).then(() => {
          // Redirect only after the message disappears
          window.location.href = "dynamic-page.php?page=New-Investor";
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: data.message,
          showConfirmButton: false,
          timer: 2000
        });
      }
  })
  .catch(error => {
      Swal.fire({
        icon: "error",
        title: "Something went wrong!",
        text: error.message,
        showConfirmButton: false,
        timer: 2000
      });
  });
});


