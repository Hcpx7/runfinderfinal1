<?php
// edit_blog.php - editar post sem imagens, usando Quill
require_once __DIR__ . '/includes/header.php';
global $pdo;
$me = current_user();

if (!$me || $me['role'] !== 'organizador') {
    echo "<p>Acesso negado.</p>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$id = intval($_GET['id'] ?? ($_POST['id'] ?? 0));
if (!$id) { header('Location: manage_blogs.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post || $post['organizer_id'] != $me['id']) { echo "<p>Post não encontrado ou acesso negado.</p>"; require_once __DIR__ . '/includes/footer.php'; exit; }

$evs_stmt = $pdo->prepare("SELECT id,name,date_event FROM events WHERE organizer_id = ? ORDER BY date_event DESC");
$evs_stmt->execute([$me['id']]);
$organizer_events = $evs_stmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;
    $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
    $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;

    if (!$title || !$content) $errors[] = "Preencha título e conteúdo.";

    if (empty($errors)) {
        $upd = $pdo->prepare("UPDATE blogs SET title = ?, content = ?, published = ?, event_id = ?, publish_date = ? WHERE id = ?");
        $upd->execute([$title,$content,$published,$event_id,$publish_date,$id]);
        header('Location: manage_blogs.php'); exit;
    }
}
?>

<h2>Editar post</h2>
<div class="card">
  <?php if ($errors) foreach($errors as $e) echo "<p class='small' style='color:#ffb5b5;'>".esc($e)."</p>"; ?>

  <form method="post" id="editBlogForm">
    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
    <div class="form-row"><label>Título</label><input type="text" name="title" value="<?php echo esc($post['title']); ?>" required></div>

    <div class="form-row">
      <label>Data de publicação (opcional)</label>
      <input type="date" name="publish_date" value="<?php echo !empty($post['publish_date']) ? esc($post['publish_date']) : ''; ?>">
    </div>

    <div class="form-row">
      <label>Associar a evento (opcional)</label>
      <select name="event_id">
        <option value="">Nenhum</option>
        <?php foreach($organizer_events as $oe): ?>
          <option value="<?php echo $oe['id']; ?>" <?php if ($post['event_id']==$oe['id']) echo 'selected'; ?>><?php echo esc($oe['name']).' — '.esc($oe['date_event']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row"><label>Conteúdo</label>
      <div id="quillEditor"></div>
      <textarea name="content" id="contentField" style="display:none;"><?php echo esc($post['content']); ?></textarea>
    </div>

    <div class="form-row"><label><input type="checkbox" name="published" <?php if ($post['published']) echo 'checked'; ?>> Publicado</label></div>

    <button class="btn" type="submit">Salvar alterações</button>
  </form>
</div>

<script>
const quill = new Quill('#quillEditor', {
  theme: 'snow',
  modules: { toolbar: [['bold','italic','underline'], [{list:'ordered'},{list:'bullet'}], ['link'], ['clean']] },
});
/* Carrega conteúdo existente no editor (HTML) */
const existingHtml = <?php echo json_encode($post['content']); ?> || '';
quill.root.innerHTML = existingHtml;

document.getElementById('editBlogForm').addEventListener('submit', function(e){
  document.getElementById('contentField').value = quill.root.innerHTML.trim();
  if (!document.getElementById('contentField').value) {
    e.preventDefault();
    alert('Escreva algum conteúdo antes de salvar.');
    return false;
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>