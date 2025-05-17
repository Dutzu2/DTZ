document.getElementById("registerForm").addEventListener("submit", function (e) {
  const password = this.password.value.trim();
  const confirm = this.confirm_password.value.trim();
  if (password !== confirm) {
    e.preventDefault();
    document.getElementById("error-message").textContent = "Parolele nu coincid.";
  }
});
