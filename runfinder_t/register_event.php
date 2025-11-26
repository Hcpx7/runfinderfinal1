<?php
require_once __DIR__ . '/includes/functions.php';
if (!is_logged_in()) {
    header('Location: login.php'); exit;
}
$me = current_user();
if ($me['role'] !== 'corredor') {
    die("Apenas corredores podem se inscrever.");
}

$event_id = intval($_POST['event_id'] ?? 0);
if (!$event_id) { header('Location: index.php'); exit; }

global $pdo;
$stmt = $pdo->prepare("SELECT id, date_event, status FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$ev = $stmt->fetch();
if (!$ev) { die("Evento inválido."); }
if ($ev['status'] === 'finalizado') { die("Evento finalizado."); }

// Verifica já inscrito
$s = $pdo->prepare("SELECT id,code FROM registrations WHERE user_id = ? AND event_id = ?");
$s->execute([$me['id'],$event_id]);
$already = $s->fetch();
if ($already) {
    // já inscrito — mostra código existente
    $_SESSION['reg_code'] = $already['code'];
    $_SESSION['reg_event_id'] = $event_id;
    header("Location: view_event.php?id=$event_id");
    exit;
}

$code = generate_code(8);
$ins = $pdo->prepare("INSERT INTO registrations (user_id,event_id,code,paid) VALUES (?,?,?,0)");
$ins->execute([$me['id'],$event_id,$code]);

// flash code for display on view_event
$_SESSION['reg_code'] = $code;
$_SESSION['reg_event_id'] = $event_id;

header("Location: view_event.php?id=$event_id");
exit;