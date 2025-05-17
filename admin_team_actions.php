<?php
session_start();
// Verifică dacă ești admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Acces interzis");
}

// Conexiune DB
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "cybergames";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Eroare la conectarea la DB: " . $conn->connect_error);
}

// Interogare useri + echipa (dacă are)
$sql = "SELECT u.id, u.username, t.name AS team_name
        FROM users u
        LEFT JOIN team_members tm ON u.id = tm.user_id
        LEFT JOIN teams t ON tm.team_id = t.id
        ORDER BY u.username";
$result = $conn->query($sql);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - Gestionare utilizatori și echipe</title>
    <style>
        body { background:#121212; color:#eee; font-family: Arial, sans-serif; }
        .user-list { max-width: 600px; margin: 20px auto; }
        .user-item { padding:10px; border-bottom: 1px solid #333; display:flex; justify-content:space-between; align-items:center; }
        button { background:#00ff99; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; color:#111; font-weight:bold; }
        button:hover { background:#00cc77; }
    </style>
</head>
<body>

<h1 style="text-align:center;">Admin Dashboard</h1>
<div class="user-list">
    <?php foreach ($users as $user): ?>
        <div class="user-item">
            <div><?= htmlspecialchars($user['username']) ?></div>
            <button onclick="openUserModal(
                <?= $user['id'] ?>, 
                '<?= addslashes(htmlspecialchars($user['username'])) ?>', 
                '<?= addslashes(htmlspecialchars($user['team_name'] ?? '')) ?>'
            )">Detalii</button>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Popout User -->
<div id="userModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
background:#222; color:#eee; padding:20px; border-radius:10px; width:350px; box-shadow:0 0 10px #00ff99; z-index:1000;">
  <h3 id="modalUsername"></h3>
  <p id="modalTeam"></p>

  <button id="removeFromTeamBtn" style="background:#e74c3c; color:#fff; border:none; padding:8px 12px; border-radius:5px; cursor:pointer; margin-right:10px;">Elimină din echipă</button>

  <div style="margin-top:10px;">
    <select id="addToTeamSelect" style="width:100%; padding:5px; border-radius:5px; margin-bottom:8px;">
      <option value="">-- Alege echipă --</option>
      <!-- Opțiunile se vor popula din JS -->
    </select>
    <button id="addToTeamBtn" style="background:#00ff99; color:#111; border:none; padding:8px 12px; border-radius:5px; cursor:pointer; width:100%;">Adaugă în echipă</button>
  </div>

  <button onclick="closeModal()" style="margin-top:15px; background:#555; color:#eee; border:none; padding:6px 12px; border-radius:5px; cursor:pointer;">Închide</button>
</div>

<!-- Background overlay -->
<div id="modalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:999;" onclick="closeModal()"></div>

<script>
  let currentUserId = null;

  function openUserModal(userId, username, teamName) {
    currentUserId = userId;
    document.getElementById('modalUsername').textContent = username;
    document.getElementById('modalTeam').textContent = teamName ? 'Echipa: ' + teamName : 'Fără echipă';

    // Afișează/ascunde butonul de eliminare în funcție de echipă
    document.getElementById('removeFromTeamBtn').style.display = teamName ? 'inline-block' : 'none';

    // Populează dropdown-ul echipelor (de obicei îl aduci din PHP la încărcare)
    let teams = <?php
      $result = $conn->query("SELECT id, name FROM teams");
      $arr = [];
      while($row = $result->fetch_assoc()) {
        $arr[] = $row;
      }
      echo json_encode($arr);
    ?>;

    let select = document.getElementById('addToTeamSelect');
    select.innerHTML = '<option value="">-- Alege echipă --</option>';
    teams.forEach(team => {
      let option = document.createElement('option');
      option.value = team.id;
      option.textContent = team.name;
      select.appendChild(option);
    });

    document.getElementById('userModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
  }

  function closeModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
    currentUserId = null;
  }

  // Buton eliminare din echipă
  document.getElementById('removeFromTeamBtn').onclick = function() {
    if (!currentUserId) return;
    if (confirm('Sigur vrei să elimini acest utilizator din echipa sa?')) {
      fetch('admin_team_actions.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=remove&user_id=' + encodeURIComponent(currentUserId)
      }).then(res => res.json()).then(data => {
        alert(data.message);
        if (data.success) location.reload();
      });
    }
  };

  // Buton adăugare în echipă
  document.getElementById('addToTeamBtn').onclick = function() {
    if (!currentUserId) return;
    let teamId = document.getElementById('addToTeamSelect').value;
    if (!teamId) {
      alert('Alege o echipă!');
      return;
    }
    fetch('admin_team_actions.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'action=add&user_id=' + encodeURIComponent(currentUserId) + '&team_id=' + encodeURIComponent(teamId)
    }).then(res => res.json()).then(data => {
      alert(data.message);
      if (data.success) location.reload();
    });
  };
</script>

</body>
</html>
