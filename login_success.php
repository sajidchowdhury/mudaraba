<?php 
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_temp_user_id'])) {
    header("Location: login.php");
    exit();
}

// Set OTP start time on first load
if (!isset($_SESSION['otp_start_time'])) {
    $_SESSION['otp_start_time'] = time();
}


?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>OTP Verification</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Styles -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>

<body class="hold-transition lockscreen">
  <div class="lockscreen-wrapper">
    <div class="lockscreen-logo">
      <a href="login.php"><b>Time</b> Plus</a>
    </div>

    <div class="lockscreen-name"><b class="ml-5">Get your OTP from Boss!!</b></div>

    <div class="lockscreen-item">
      <div class="lockscreen-image">
        <img src="dist/img/otp.png" alt="User Image">
      </div>

      <form class="lockscreen-credentials" onsubmit="event.preventDefault(); submitOTP();">
        <div class="input-group">
          <input type="text" name="otp" id="otp_input" class="form-control" placeholder="Enter OTP">
          <div class="input-group-append">
            <button type="button" onclick="submitOTP()" class="btn btn-warning" style="background-color: #38a9fc;">
              <i class="fas fa-check"></i>
            </button>
          </div>
        </div>
      </form>
    </div>



    <div class="lockscreen-footer text-center">
      Copyright &copy; 2025<br>
      <a href="http://mycreativecode.com" class="text-black">mycreativecode.com</a>
    </div>
  </div>

  <!-- Scripts -->
  <script src="plugins/jquery/jquery.min.js"></script>
  <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="plugins/sweetalert2/sweetalert2.min.js"></script>

  <script>
   

    function submitOTP() {
      const otp = document.getElementById("otp_input").value.trim();

      if (otp === '') {
        Swal.fire("Error", "OTP cannot be empty", "error");
        return;
      }

      const deviceData = {
        lang: navigator.language || '',
        tz_offset: new Date().getTimezoneOffset(),
        screen_res: `${screen.width}x${screen.height}`
      };

      const payload = {
        user_id: <?= json_encode($_SESSION['admin_temp_user_id']) ?>,
        otp: otp,
        device_data: deviceData,
        page: 'otp_verify'
      };

      fetch("includes/log_device_info.inc.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === "success") {
          // Reset OTP session on success
          fetch('includes/reset_otp_session.php').then(() => {
            Swal.fire({
              icon: "success",
              title: "OTP verified",
              showConfirmButton: false,
              timer: 1500
            }).then(() => {
              window.location.href = "home.php";
            });
          });
        } else {
          Swal.fire("Error", data.message || "Invalid OTP", "error");
        }
      })
      .catch(err => {
        console.error("Error:", err);
        Swal.fire("Error", "Network error", "error");
      });
    }
  </script>
</body>
</html>
