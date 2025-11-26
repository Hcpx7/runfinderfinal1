<?php
// create_blog.php - Blog sem API e sem upload de imagens (apenas texto via Quill)
require_once __DIR__ . '/includes/header.php';
global $pdo;
$me = current_user();

if (!$me || $me['role'] !== 'organizador') {
    echo "<p>Acesso negado. Apenas organizadores.</p>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
if (!$me['plan_paid']) {
    echo "<p>Você precisa de um plano ativo para criar posts. <a href='plan.php'>Assinar plano</a></p>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$errors = [];
$evs_stmt = $pdo->prepare("SELECT id,name,date_event FROM events WHERE organizer_id = ? ORDER BY date_event DESC");
$evs_stmt->execute([$me['id']]);
$organizer_events = $evs_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? ''); // HTML from Quill
    $published = isset($_POST['published']) ? 1 : 0;
    $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
    $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;

    if (!$title || !$content) $errors[] = "Preencha título e conteúdo.";

    if (empty($errors)) {
        $ins = $pdo->prepare("INSERT INTO blogs (organizer_id,title,content,published,event_id,publish_date) VALUES (?,?,?,?,?,?)");
        $ins->execute([$me['id'],$title,$content,$published,$event_id,$publish_date]);
        header('Location: manage_blogs.php'); exit;
    }
}
?>

<h2>Criar post de blog</h2>
<div class="card">
  <?php if ($errors) foreach($errors as $e) echo "<p class='small' style='color:#ffb5b5;'>".esc($e)."</p>"; ?>

  <form method="post" id="blogForm">
    <div class="form-row">
      <label>Título</label>
      <input type="text" name="title" required>
    </div>

    <div class="form-row">
      <label>Data de publicação (opcional)</label>
      <input type="date" name="publish_date" value="<?php echo date('Y-m-d'); ?>">
    </div>

    <div class="form-row">
      <label>Associar a evento (opcional)</label>
      <select name="event_id">
        <option value="">Nenhum</option>
        <?php foreach($organizer_events as $oe): ?>
          <option value="<?php echo $oe['id']; ?>"><?php echo esc($oe['name']).' — '.esc($oe['date_event']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <label>Conteúdo (texto)</label>
      <!-- Quill editor -->
      <div id="quillEditor"></div>
      <textarea name="content" id="contentField" style="display:none;"></textarea>
    </div>

    <div class="form-row">
      <label><input type="checkbox" name="published" checked> Publicado</label>
    </div>

    <button class="btn" type="submit">Salvar post</button>
  </form>
</div>

<script>
// Inicializa Quill (não requer API)
const quill = new Quill('#quillEditor', {
  theme: 'snow',
  placeholder: 'Escreva seu post aqui...',
  modules: {
    toolbar: [
      ['bold','italic','underline'],
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      ['link'],
      ['clean']
    ]
  }
});

// Ao submeter, copia HTML do editor para textarea
document.getElementById('blogForm').addEventListener('submit', function(e){
  document.getElementById('contentField').value = quill.root.innerHTML.trim();
  // basic client validation
  if (!document.getElementById('contentField').value) {
    e.preventDefault();
    alert('Escreva algum conteúdo antes de salvar.');
    return false;
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>