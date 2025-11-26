<?php
// index.php - Página inicial consolidada (use no root do projeto)
// Substitua o index.php atual por este. Certifique-se de ter includes/header.php e includes/footer.php corretos.

require_once __DIR__ . '/includes/header.php';
global $pdo;
$me = current_user();

// Atualiza status localmente (opcional, precisa de permissões)
try {
    $pdo->prepare("UPDATE events SET status='finalizado' WHERE date_event < CURDATE() AND status = 'ativo'")->execute();
} catch (Exception $e) {
    // não quebrar a página se algo falhar
}

// Busca eventos ativos (limit 6)
$events = [];
try {
    $stmt = $pdo->prepare("SELECT e.*, u.name as organizer_name FROM events e JOIN users u ON u.id = e.organizer_id WHERE e.status = 'ativo' ORDER BY e.date_event ASC LIMIT 6");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $events = [];
}

// Busca blogs publicados (limit 6)
$blogs = [];
try {
    $b = $pdo->prepare("SELECT b.*, u.name as organizer_name, e.name as event_name, b.publish_date, b.created_at FROM blogs b JOIN users u ON u.id = b.organizer_id LEFT JOIN events e ON e.id = b.event_id WHERE b.published = 1 ORDER BY COALESCE(b.publish_date, b.created_at) DESC LIMIT 6");
    $b->execute();
    $blogs = $b->fetchAll();
} catch (Exception $e) {
    $blogs = [];
}

// helper esc (se não existir)
if (!function_exists('esc')) {
    function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
?>

<section class="hero">
  <div class="hero-card">
    <h1>Encontre corridas, trace rotas e conecte-se com a comunidade Runner</h1>
    <p class="small">RunFider ajuda você a descobrir corridas, acompanhar distâncias e inscrever-se com facilidade. Crie eventos, publique notícias e gerencie inscrições — tudo em um só lugar.</p>

    <div class="hero-ctas">
      <a class="btn" href="index.php#events">Ver eventos</a>
      <a class="btn btn-ghost" href="blogs.php">Ler posts</a>
      <?php if ($me && $me['role'] === 'organizador'): ?>
        <a class="btn btn-ghost" href="create_event.php">Criar evento</a>
      <?php else: ?>
        <a class="btn btn-ghost" href="register.php">Criar conta</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="hero-map card">
    <div id="homeMap" class="map"></div>
  </div>
</section>

<!-- Próximos eventos -->
<section id="events" class="section">
  <div class="section-header">
    <h2>Próximos eventos</h2>
    <a class="see-all" href="index.php">Ver todos</a>
  </div>

  <div class="cards-grid" aria-live="polite">
    <?php if (empty($events)): ?>
      <div class="card-event card-square center" style="padding:24px;"><div class="card-title">Nenhum evento ativo encontrado.</div></div>
    <?php else: foreach($events as $ev): ?>
      <article class="card-square event-card" role="article" aria-labelledby="ev-<?php echo $ev['id']; ?>">
        <?php if (!empty($ev['image_url'])): ?>
          <div class="card-figure" style="background-image:url('<?php echo esc($ev['image_url']); ?>');"></div>
        <?php else: ?>
          <div class="card-figure placeholder" aria-hidden="true">
            <div class="card-placeholder-icon">🏃</div>
          </div>
        <?php endif; ?>

        <div class="card-body">
          <div>
            <div id="ev-<?php echo $ev['id']; ?>" class="card-title"><?php echo esc($ev['name']); ?></div>
            <div class="card-meta">Cidade: <?php echo esc($ev['city']); ?> — <?php echo date('d/m/Y', strtotime($ev['date_event'])); ?></div>
            <div class="card-meta">Organizador: <?php echo esc($ev['organizer_name']); ?> • Distância: <?php echo $ev['distance_km'] ? esc($ev['distance_km']).' km' : '—'; ?></div>
          </div>

          <div class="card-actions">
            <div class="card-badge small"><?php echo $ev['price'] ? 'R$ '.number_format($ev['price'],2,',','.') : 'Grátis'; ?></div>
            <div style="margin-left:auto"><a class="btn-card" href="view_event.php?id=<?php echo $ev['id']; ?>">Ver evento</a></div>
          </div>
        </div>
      </article>
    <?php endforeach; endif; ?>
  </div>
</section>

<!-- Últimos posts -->
<section id="blogs" class="section">
  <div class="section-header">
    <h2>Últimos posts</h2>
    <a class="see-all" href="blogs.php">Ver todos</a>
  </div>

  <div class="cards-grid">
    <?php if (empty($blogs)): ?>
      <div class="card-event card-square center" style="padding:24px;"><div class="card-title">Nenhum post encontrado.</div></div>
    <?php else: foreach($blogs as $post): ?>
      <article class="card-square blog-card" role="article" aria-labelledby="post-<?php echo $post['id']; ?>">
        <div class="card-figure placeholder" aria-hidden="true">
          <div class="card-placeholder-icon">✍️</div>
        </div>

        <div class="card-body">
          <div>
            <div id="post-<?php echo $post['id']; ?>" class="card-title"><?php echo esc($post['title']); ?></div>
            <div class="card-meta">Por <?php echo esc($post['organizer_name']); ?> — <?php echo date('d/m/Y', strtotime($post['publish_date'] ? $post['publish_date'] : $post['created_at'])); ?></div>
            <p class="card-excerpt"><?php echo esc(mb_strimwidth(strip_tags($post['content']), 0, 160, '...')); ?></p>
          </div>

          <div class="card-actions">
            <div class="card-badge small"><?php echo $post['event_name'] ? esc($post['event_name']) : 'Blog'; ?></div>
            <div style="margin-left:auto"><a class="btn-card" href="view_blog.php?id=<?php echo $post['id']; ?>">Ler post</a></div>
          </div>
        </div>
      </article>
    <?php endforeach; endif; ?>
  </div>
</section>

<script>
// Map init (only if Leaflet is present)
if (typeof L !== 'undefined') {
  try {
    const homeMap = L.map('homeMap', { zoomControl:true, scrollWheelZoom:false }).setView([-19.9167, -43.9345], 10);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; OpenStreetMap contributors &copy; CartoDB', maxZoom:20 }).addTo(homeMap);

    const events = <?php echo json_encode($events); ?>;
    events.forEach(ev => {
      if (!ev.lat || !ev.lng) return;
      const icon = L.icon({ iconUrl: 'https://cdn.jsdelivr.net/gh/pointhi/leaflet-color-markers@master/img/marker-icon-2x-red.png', iconSize: [25,41], iconAnchor: [12,41] });
      const m = L.marker([ev.lat, ev.lng], {icon}).addTo(homeMap);
      m.bindPopup(`<strong>${ev.name}</strong><br>${ev.city} — ${ev.date_event}<br><a href="view_event.php?id=${ev.id}">Ver evento</a>`);
    });

    const latlngs = events.filter(e=>e.lat && e.lng).map(e=>[parseFloat(e.lat), parseFloat(e.lng)]);
    if (latlngs.length) homeMap.fitBounds(latlngs, {padding:[60,60]});
  } catch(e) {
    console.warn('Map init error', e);
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>