<?php
// includes/functions.php
require_once __DIR__ . '/config.php';

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Sempre busca os campos principais do usuário diretamente do banco.
 * Inclui plan_paid, plan_type e plan_expires para evitar warnings.
 */
function current_user() {
    global $pdo;
    if (!is_logged_in()) return null;

    $stmt = $pdo->prepare("SELECT id, name, email, role, plan_paid, plan_type, plan_expires FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function is_organizer() {
    $u = current_user();
    return $u && $u['role'] === 'organizador';
}

function generate_code($length = 8) {
    $bytes = random_bytes($length);
    return substr(strtoupper(bin2hex($bytes)), 0, $length);
}

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* ---------- Imagem / Thumbnail util (GD) ---------- */

function create_thumbnail_gd($srcFullPath, $destFullPath, $maxWidth = 360, $maxHeight = 220) {
    if (!extension_loaded('gd')) return false;
    $info = getimagesize($srcFullPath);
    if (!$info) return false;
    list($width, $height, $type) = $info;

    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newW = (int)($width * $ratio);
    $newH = (int)($height * $ratio);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = imagecreatefromjpeg($srcFullPath);
            break;
        case IMAGETYPE_PNG:
            $srcImg = imagecreatefrompng($srcFullPath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $srcImg = imagecreatefromwebp($srcFullPath);
            } else return false;
            break;
        default:
            return false;
    }

    $thumb = imagecreatetruecolor($newW, $newH);
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefilledrectangle($thumb, 0, 0, $newW, $newH, $transparent);
    }
    imagecopyresampled($thumb, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height);

    $saved = false;
    if ($type == IMAGETYPE_PNG) {
        $saved = imagepng($thumb, $destFullPath, 6);
    } elseif ($type == IMAGETYPE_WEBP && function_exists('imagewebp')) {
        $saved = imagewebp($thumb, $destFullPath, 80);
    } else {
        $saved = imagejpeg($thumb, $destFullPath, 85);
    }

    imagedestroy($srcImg);
    imagedestroy($thumb);
    return $saved;
}

function save_uploaded_image($fileField) {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['image_url' => null, 'thumb_url' => null];
    }
    $f = $_FILES[$fileField];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;

    if ($f['size'] > 5 * 1024 * 1024) return null;

    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) return null;

    $ext = $allowed[$mime];
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    $destPath = UPLOAD_BLOGS . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $destPath)) return null;

    $thumbName = 'thumb_' . $name;
    $thumbDest = UPLOAD_BLOGS . '/' . $thumbName;
    $thumbCreated = create_thumbnail_gd($destPath, $thumbDest, 360, 220);
    $thumbRel = $thumbCreated ? 'uploads/blogs/' . $thumbName : null;

    return [
        'image_url' => 'uploads/blogs/' . $name,
        'thumb_url' => $thumbRel
    ];
}

function delete_file_if_exists($relativePath) {
    if (!$relativePath) return;
    $full = __DIR__ . '/../' . ltrim($relativePath, '/');
    if (file_exists($full) && is_file($full)) {
        @unlink($full);
    }
}
?>