<?php
require_once __DIR__ . '/includes/header.php';
global $pdo;

if (!$me || $me['role'] !== 'organizador') {
    echo "<p>Acesso negado.</p>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = ? ORDER BY date_event DESC");
$stmt->execute([$me['id']]);
$events = $stmt->fetchAll();
?>

<h2>Meus eventos</h2>
<div class="event-list">
<?php foreach($events as $ev): ?>
  <div class="event-item card">
    <h3><?php echo esc($ev['name']); ?></h3>
    <p class="small">Data: <?php echo esc($ev['date_event']); ?> — Status: <?php echo esc($ev['status']); ?></p>
    <p class="small">Distância: <?php echo $ev['distance_km'] ? esc($ev['distance_km']).' km':'—'; ?></p>
    <a class="btn" href="view_event.php?id=<?php echo $ev['id']; ?>">Ver</a>
    <a class="btn" href="create_event.php?id=<?php echo $ev['id']; ?>">Editar</a>
  </div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>