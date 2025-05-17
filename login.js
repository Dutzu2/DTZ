document.getElementById("loginForm").addEventListener("submit", function (e) {
  const username = this.username.value.trim();
  const password = this.password.value.trim();
  if (!username || !password) {
    e.preventDefault();
    document.getElementById("error-message").textContent = "Completează toate câmpurile.";
  }
});
window.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const errorMessage = document.getElementById("error-message");
  const successMessage = document.getElementById("success-message");

  // Mesaj succes la creare cont
  if (params.get("success") === "1" && successMessage) {
    successMessage.textContent = "Cont creat cu succes. Te poți autentifica acum.";
    successMessage.style.display = "block";
  } else if (successMessage) {
    successMessage.style.display = "none";
  }

  // Mesaj de eroare la login
  if (params.get("error") && errorMessage) {
    let msg = "";
    switch (params.get("error")) {
      case "wrong_password":
        msg = "Parolă incorectă. Încearcă din nou.";
        break;
      case "user_not_found":
        msg = "Utilizatorul nu există.";
        break;
      case "empty_fields":
        msg = "Completează toate câmpurile.";
        break;
      default:
        msg = "Eroare necunoscută.";
    }
    errorMessage.textContent = msg;
    errorMessage.style.display = "block";
  } else if (errorMessage) {
    errorMessage.style.display = "none";
  }

  // Curățare URL (fără reload) ca să dispară parametrii GET
  if (window.history.replaceState) {
    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);
  }
});

