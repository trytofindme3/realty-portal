<?php
// public/delete.php — удаление своего объявления. Принимает только POST.

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {          // открыли адрес в браузере (GET) → отказ
    http_response_code(405);                          // 405 = Method Not Allowed
    exit('Pouze POST.');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);

// DELETE ... RETURNING image: удаляем строку и сразу узнаём имя её фото
$stmt = $pdo->prepare('DELETE FROM listings WHERE id = ? AND user_id = ? RETURNING image');
$stmt->execute([$id, current_user_id()]);
$row = $stmt->fetch();                                // false, если ничего не удалили (чужое или нет такого)

if ($row && $row['image']) {
    $path = __DIR__ . '/uploads/' . $row['image'];
    if (is_file($path)) {
        unlink($path);                                // удаляем файл фото с диска
    }
}

header('Location: my.php');
exit;
