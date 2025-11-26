<?php
require_once __DIR__ . '/includes/header.php';
global $pdo;

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo "<p>Post não encontrado.</p>"; require_once __DIR__ . '/includes/footer.php'; exit; }

$stmt = $pdo->prepare("SELECT b.*, u.name as organizer_name, e.name as event_name, e.id as event_id FROM blogs b JOIN users u ON u.id = b.organizer_id LEFT JOIN events e ON e.id = b.event_id WHERE b.id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) { echo "<p>Post não encontrado.</p>"; require_once __DIR__ . '/includes/footer.php'; exit; }
?>

<h2><?php echo esc($post['title']); ?></h2>
<div class="card">
  <p class="small">Por <?php echo esc($post['organizer_name']); ?> — <?php echo date('d/m/Y', strtotime($post['publish_date'] ? $post['publish_date'] : $post['created_at'])); ?></p>

  <div style="margin-top:0.8rem; color:var(--text);">
    <?php
      // Conteúdo já é HTML do TinyMCE; em produção sanitize com HTMLPurifier
      echo $post['content'];
    ?>
  </div>

  <?php if ($post['event_name']): ?>
    <p class="small" style="margin-top:0.8rem;">Relacionado ao evento: <a href="view_event.php?id=<?php echo esc($post['event_id']); ?>"><?php echo esc($post['event_name']); ?></a></p>
  <?php endif; ?>

  <p style="margin-top:0.8rem;"><a href="blogs.php">Voltar</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>