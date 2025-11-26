<?php
require_once __DIR__ . '/includes/header.php';
global $pdo;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = ($_POST['role'] ?? 'corredor') === 'organizador' ? 'organizador' : 'corredor';

    if (!$name || !$email || !$password) $errors[] = "Preencha todos os campos.";

    if (!$errors) {
        // Verifica email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "E-mail já cadastrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
            $stmt->execute([$name,$email,$hash,$role]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: index.php'); exit;
        }
    }
}
?>

<h2>Registrar</h2>
<div class="card">
  <?php if ($errors): ?>
    <div class="card">
      <ul>
      <?php foreach($errors as $e) echo "<li>".esc($e)."</li>"; ?>
      </ul>
    </div>
  <?php endif; ?>
  <form method="post">
    <div class="form-row">
      <label>Nome completo</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-row">
      <label>E-mail</label>
      <input type="email" name="email" required>
    </div>
    <div class="form-row">
      <label>Senha</label>
      <input type="password" name="password" required>
    </div>
    <div class="form-row">
      <label>Tipo de usuário</label>
      <select name="role">
        <option value="corredor">Corredor</option>
        <option value="organizador">Organizador</option>
      </select>
    </div>
    <button class="btn" type="submit">Registrar</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>