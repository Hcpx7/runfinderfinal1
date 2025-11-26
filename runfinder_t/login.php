<?php
require_once __DIR__ . '/includes/header.php';
global $pdo;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id,password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user_id'] = $u['id'];
        header('Location: index.php'); exit;
    } else {
        $errors[] = "E-mail ou senha incorretos.";
    }
}
?>

<h2>Entrar</h2>
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
      <label>E-mail</label>
      <input type="email" name="email" required>
    </div>
    <div class="form-row">
      <label>Senha</label>
      <input type="password" name="password" required>
    </div>
    <button class="btn" type="submit">Entrar</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>