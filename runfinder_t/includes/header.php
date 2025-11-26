<?php
// includes/header.php - RunFider Dark+ header (Quill editor integrated, no API keys)
require_once __DIR__ . '/functions.php';
$me = current_user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>RunFider</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

  <!-- Main CSS -->
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- Quill (WYSIWYG) — free, no API key -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Leaflet Routing Machine -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

  <meta name="theme-color" content="#e31b23">
  <style>
    /* small adjustments to better integrate Quill with dark theme */
    .ql-toolbar.ql-snow { border-radius: 10px 10px 0 0; border: 1px solid rgba(255,255,255,0.04); background: rgba(255,255,255,0.02); }
    .ql-container.ql-snow { border-radius: 0 0 10px 10px; border: 1px solid rgba(255,255,255,0.04); background: rgba(20,20,20,0.92); color: #f6f6f6; min-height:240px; }
    .ql-editor{ color: #f6f6f6; font-family: 'Roboto', sans-serif; font-size: 15px; }
    .suggestions-list li { padding:8px; cursor:pointer; border-radius:8px; transition:background .12s; }
    .suggestions-list li:hover { background: rgba(255,255,255,0.03); }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="index.php" aria-label="RunFider Home">
        <svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><rect width="48" height="48" rx="10" fill="#e31b23"></rect><path d="M12 32 L20 12 L28 32 Z" fill="#fff"/></svg>
        <div class="brand-text">
          <div class="brand-name">RunFider</div>
          <div class="brand-sub">Dark+ • Corridas & Rotas</div>
        </div>
      </a>

      <nav class="main-nav" role="navigation" aria-label="Main">
        <a href="index.php">Eventos</a>
        <a href="blogs.php">Blogs</a>
        <?php if ($me && $me['role'] === 'organizador'): ?>
          <a href="create_event.php">Criar evento</a>
          <a href="my_events.php">Meus eventos</a>
          <a href="manage_blogs.php">Meus posts</a>
        <?php endif; ?>
        <a href="plan.php">Planos</a>
        <?php if (!$me): ?>
          <a class="btn-ghost" href="register.php">Registrar</a>
          <a class="btn" href="login.php">Entrar</a>
        <?php else: ?>
          <div style="display:flex;align-items:center;gap:0.6rem;margin-left:10px;">
            <span class="nav-user">Olá, <?php echo esc($me['name']); ?></span>
            <a class="btn-ghost" href="logout.php">Sair</a>
          </div>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="wrap main-content">