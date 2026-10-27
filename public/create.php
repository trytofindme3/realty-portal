<?php
// public/create.php — форма добавления объявления (только для вошедших) + загрузка фото.

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/layout.php';

require_login();                                      // гость → редирект на login.php

$errors = [];
$old = $_POST;                                        // чтобы при ошибке не терять введённое

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1) Валидация на сервере. HTML-атрибут required легко обойти, поэтому проверяем здесь.
    $title = trim($_POST['title'] ?? '');
    $city  = trim($_POST['city'] ?? '');
    $type  = $_POST['type'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $area  = (int)($_POST['area_m2'] ?? 0);
    $rooms = trim($_POST['rooms'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    if (mb_strlen($title) < 5)                        $errors[] = 'Název musí mít aspoň 5 znaků.';
    if ($city === '')                                 $errors[] = 'Vyplňte město.';
    if (!in_array($type, ['prodej', 'pronajem'], true)) $errors[] = 'Neplatný typ.';
    if ($price <= 0)                                  $errors[] = 'Cena musí být kladná.';
    if ($area <= 0)                                   $errors[] = 'Plocha musí být kladná.';
    if (!preg_match('/^\d\+(kk|\d)$/', $rooms))       $errors[] = 'Dispozice ve tvaru 2+kk nebo 3+1.'; // регулярка формата

    // 2) Фото (необязательно). Проверяем реальный тип файла, а не расширение из имени.
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $file    = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime    = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); // тип по содержимому файла

        if ($file['error'] !== UPLOAD_ERR_OK)         $errors[] = 'Chyba při nahrávání.';
        elseif (!isset($allowed[$mime]))              $errors[] = 'Povolené formáty: JPG, PNG, WEBP.';
        elseif ($file['size'] > 2 * 1024 * 1024)      $errors[] = 'Max. velikost 2 MB.';
        else {
            $imageName = bin2hex(random_bytes(8)) . '.' . $allowed[$mime]; // своё случайное имя, не имя от пользователя
        }
    }

    // 3) Всё ок → сохраняем
    if (!$errors) {
        if ($imageName) {
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/uploads/' . $imageName);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO listings (user_id, title, description, type, city, price, area_m2, rooms, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             RETURNING id'                                         // фишка Postgres: INSERT сразу возвращает id новой строки
        );
        $stmt->execute([current_user_id(), $title, $desc, $type, $city, $price, $area, $rooms, $imageName]);
        $newId = (int)$stmt->fetchColumn();                        // читаем этот id как результат запроса

        header('Location: listing.php?id=' . $newId);
        exit;                                                      // паттерн PRG: после POST — редирект
    }
}

layout_start('Nový inzerát');
?>
<form method="post" enctype="multipart/form-data" class="form"> <!-- enctype обязателен для загрузки файлов -->
    <h1>Nový inzerát</h1>
    <?php foreach ($errors as $err): ?><p class="error"><?= e($err) ?></p><?php endforeach; ?>
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <label>Název <input name="title" value="<?= e($old['title'] ?? '') ?>" required></label>
    <label>Město <input name="city" value="<?= e($old['city'] ?? '') ?>" required></label>
    <label>Typ
        <select name="type">
            <option value="prodej">Prodej</option>
            <option value="pronajem" <?= ($old['type'] ?? '') === 'pronajem' ? 'selected' : '' ?>>Pronájem</option>
        </select>
    </label>
    <label>Cena (Kč) <input type="number" name="price" min="1" value="<?= e($old['price'] ?? '') ?>" required></label>
    <label>Plocha (m²) <input type="number" name="area_m2" min="1" value="<?= e($old['area_m2'] ?? '') ?>" required></label>
    <label>Dispozice <input name="rooms" placeholder="2+kk" value="<?= e($old['rooms'] ?? '') ?>" required></label>
    <label>Popis <textarea name="description" rows="4"><?= e($old['description'] ?? '') ?></textarea></label>
    <label>Foto <input type="file" name="image" accept="image/*"></label>
    <button>Uložit</button>
</form>
<?php layout_end();
