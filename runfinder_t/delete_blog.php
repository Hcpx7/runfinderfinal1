<?php
require_once __DIR__ . '/includes/functions.php';
if (!is_logged_in()) {
    header('Location: login.php'); exit;
}
$me = current_user();
if ($me['role'] !== 'organizador') {
    die("Acesso negado.");
}

$id = intval($_POST['id'] ?? 0);
if (!$id) { header('Location: manage_blogs.php'); exit; }

global $pdo;
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post || $post['organizer_id'] != $me['id']) {
    die("Post inválido ou sem permissão.");
}

// Remove imagem e thumb se existir
if ($post['image_url']) delete_file_if_exists($post['image_url']);
if ($post['image_thumb_url']) delete_file_if_exists($post['image_thumb_url']);

// Remove registro
$del = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
$del->execute([$id]);

header('Location: manage_blogs.php');
exit;