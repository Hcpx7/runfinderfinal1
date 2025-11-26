<?php
require_once __DIR__ . '/includes/header.php';
global $pdo;

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 8; $offset = ($page-1)*$perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE published = 1");
$countStmt->execute();
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT b.*, u.name as organizer_name, e.name as event_name FROM blogs b JOIN users u ON u.id = b.organizer_id LEFT JOIN events e ON e.id = b.event_id WHERE b.published = 1 ORDER BY COALESCE(b.publish_date, b.created_at) DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$blogs = $stmt->fetchAll();
?>

<h2>Todos os blogs</h2>

<div class="grid">
  <?php foreach($blogs as $post): ?>
    <div class="card-event">
      <h3><?php echo esc($post['title']); ?></h3>
      <div class="small">Por <?php echo esc($post['organizer_name']); ?> — <?php echo date('d/m/Y', strtotime($post['publish_date'] ? $post['publish_date'] : $post['created_at'])); ?></div>
      <p class="small" style="margin-top:8px;"><?php echo esc(mb_strimwidth(strip_tags($post['content']), 0, 260, '...')); ?></p>
      <div style="margin-top:8px;"><a class="btn-small" href="view_blog.php?id=<?php echo $post['id']; ?>">Ler</a></div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
  <div class="card small" style="margin-top:12px;">
    <?php for ($p=1;$p<=$totalPages;$p++): if ($p == $page): ?><strong><?php echo $p; ?></strong><?php else: ?><a href="?page=<?php echo $p; ?>"><?php echo $p; ?></a><?php endif; ?>&nbsp;<?php endfor; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>