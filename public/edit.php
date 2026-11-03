<?php
// public/edit.php — редактирование своего объявления: edit.php?id=5

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/layout.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

// Берём объявление ТОЛЬКО если оно принадлежит текущему пользователю.
// Проверка в SQL — главная защита: скрытая кнопка не помешает вписать чужой id в адрес.
$stmt = $pdo->prepare('SELECT * FROM listings WHERE id = ? AND user_id = ?');
$stmt->execute([$id, current_user_id()]);
$listing = $stmt->fetch();

if (!$listing) {                                      // нет такого или чужое → 404 (не выдаём, что оно существует)
    http_response_code(404);
    layout_start('Nenalezeno');
    echo '<p>Inzerát neexistuje.</p>';
    layout_end();
    exit;
}

$errors = [];
$old    = $listing;                                   // по умолчанию форма заполнена данными из БД

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = array_merge($listing, $_POST);             // при ошибке показываем то, что ввёл пользователь

    $title = trim($_POST['title'] ?? '');
    $city  = trim($_POST['city'] ?? '');
    $type  = $_POST['type'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $area  = (int)($_POST['area_m2'] ?? 0);
    $rooms = trim($_POST['rooms'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    // Та же валидация, что в create.php
    if (mb_strlen($title) < 5)                          $errors[] = 'Název musí mít aspoň 5 znaků.';
    if ($city === '')                                   $errors[] = 'Vyplňte město.';
    if (!in_array($type, ['prodej', 'pronajem'], true)) $errors[] = 'Neplatný typ.';
    if ($price <= 0)                                    $errors[] = 'Cena musí být kladná.';
    if ($area <= 0)                                     $errors[] = 'Plocha musí být kladná.';
    if (!preg_match('/^\d\+(kk|\d)$/', $rooms))         $errors[] = 'Dispozice ve tvaru 2+kk nebo 3+1.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE listings
             SET title = ?, description = ?, type = ?, city = ?, price = ?, area_m2 = ?, rooms = ?
             WHERE id = ? AND user_id = ?'            // user_id и здесь — на всякий случай ещё раз
        );
        $stmt->execute([$title, $desc, $type, $city, $price, $area, $rooms, $id, current_user_id()]);

        header('Location: listing.php?id=' . $id);
        exit;
    }
}

layout_start('Upravit inzerát');
?>
<form method="post" class="form">
    <h1>Upravit inzerát</h1>
    <?php foreach ($errors as $err): ?><p class="error"><?= e($err) ?></p><?php endforeach; ?>
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <label>Název <input name="title" value="<?= e((string)$old['title']) ?>" required></label>
    <label>Město <input name="city" value="<?= e((string)$old['city']) ?>" required></label>
    <label>Typ
        <select name="type">
            <option value="prodej">Prodej</option>
            <option value="pronajem" <?= $old['type'] === 'pronajem' ? 'selected' : '' ?>>Pronájem</option>
        </select>
    </label>
    <label>Cena (Kč) <input type="number" name="price" min="1" value="<?= e((string)$old['price']) ?>" required></label>
    <label>Plocha (m²) <input type="number" name="area_m2" min="1" value="<?= e((string)$old['area_m2']) ?>" required></label>
    <label>Dispozice <input name="rooms" value="<?= e((string)$old['rooms']) ?>" required></label>
    <label>Popis <textarea name="description" rows="4"><?= e((string)$old['description']) ?></textarea></label>
    <button>Uložit změny</button>
    <a href="my.php">Zrušit</a>
</form>
<?php layout_end();
