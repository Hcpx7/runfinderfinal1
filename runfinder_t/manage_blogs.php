<?php
require_once __DIR__ . '/includes/header.php';
global $pdo;
$me = current_user();

if (!$me || $me['role'] !== 'organizador') { echo "<p>Acesso negado.</p>"; require_once __DIR__ . '/includes/footer.php'; exit; }

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10; $offset = ($page-1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) as c FROM blogs WHERE organizer_id = ?");
$countStmt->execute([$me['id']]); $total = $countStmt->fetchColumn(); $totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT b.*, e.name as event_name FROM blogs b LEFT JOIN events e ON e.id = b.event_id WHERE b.organizer_id = ? ORDER BY COALESCE(b.publish_date,b.created_at) DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $me['id'], PDO::PARAM_INT); $stmt->bindValue(2, $perPage, PDO::PARAM_INT); $stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$blogs = $stmt->fetchAll();
?>

<h2>Gerenciar meus posts</h2>

<div class="card">
  <a class="btn" href="create_blog.php">Novo post</a>
</div>

<?php foreach($blogs as $post): ?>
  <div class="card" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h3 style="margin:0;"><?php echo esc($post['title']); ?> <?php if (!$post['published']) echo "<span class='small' style='color:var(--muted)'> (Rascunho)</span>"; ?></h3>
        <div class="small">Associado: <?php echo $post['event_name'] ? esc($post['event_name']) : '—'; ?> — Data: <?php echo $post['publish_date'] ? date('d/m/Y', strtotime($post['publish_date'])) : date('d/m/Y', strtotime($post['created_at'])); ?></div>
      </div>
      <div style="display:flex;gap:8px;">
        <a class="btn-small" href="edit_blog.php?id=<?php echo $post['id']; ?>">Editar</a>
        <form method="post" action="delete_blog.php" style="display:inline-block;" onsubmit="return confirm('Excluir este post?');">
          <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
          <button class="btn-small" type="submit" style="background:transparent;color:var(--accent);">Excluir</button>
        </form>
        <a class="btn-small" href="view_blog.php?id=<?php echo $post['id']; ?>">Ver</a>
      </div>
    </div>
    <p class="small" style="margin-top:8px;"><?php echo esc(mb_strimwidth(strip_tags($post['content']), 0, 300, '...')); ?></p>
  </div>
<?php endforeach; ?>

<?php if ($totalPages > 1): ?>
  <div class="card small">
    <?php for ($p=1;$p<=$totalPages;$p++): if ($p == $page): ?><strong><?php echo $p; ?></strong><?php else: ?><a href="?page=<?php echo $p; ?>"><?php echo $p; ?></a><?php endif; ?>&nbsp;<?php endfor; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>