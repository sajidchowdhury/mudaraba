const form = document.getElementById('myForm');
let isSubmitting = false;

form.addEventListener('submit', (event) => {
  event.preventDefault();

  if (isSubmitting) return; // prevent double submission
  isSubmitting = true;

  const submitButton = document.getElementById('kt_submit_button');
  submitButton.disabled = true;
  submitButton.value = "Submitting...";

  const formData = new FormData(form);


  const pageName = $('#pageName').val(); 
  
  fetch('includes/Opening.inc.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    isSubmitting = false;
    submitButton.disabled = false;
    submitButton.value = "Submit";

    if (data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: data.message,
        showConfirmButton: false,
        timer: 2000
      }).then(() => {
        window.location.href = "dynamic-page.php?page=" + pageName ;
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
    isSubmitting = false;
    submitButton.disabled = false;
    submitButton.value = "Submit";

    Swal.fire({
      icon: "error",
      title: "Something went wrong!",
      text: error.message,
      showConfirmButton: false,
      timer: 2000
    });
  });
});
