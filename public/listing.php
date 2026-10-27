<?php
// public/listing.php — карточка одного объявления: listing.php?id=3

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/layout.php';

$id = (int)($_GET['id'] ?? 0);                        // id из URL, приводим к числу

// JOIN — берём объявление и сразу имя автора из таблицы users
$stmt = $pdo->prepare(
    'SELECT l.*, u.name AS author
     FROM listings l
     JOIN users u ON u.id = l.user_id
     WHERE l.id = ?'                                  // ? — безымянный плейсхолдер
);
$stmt->execute([$id]);
$l = $stmt->fetch();                                  // одна строка или false

if (!$l) {                                            // нет такого id → честная ошибка 404
    http_response_code(404);
    layout_start('Nenalezeno');
    echo '<p>Inzerát neexistuje.</p>';
    layout_end();
    exit;
}

layout_start($l['title']);
?>
<article class="detail">
    <?php if ($l['image']): ?>
        <img src="uploads/<?= e($l['image']) ?>" alt="<?= e($l['title']) ?>">
    <?php endif; ?>
    <h1><?= e($l['title']) ?></h1>
    <p class="price"><?= price((int)$l['price']) ?><?= $l['type'] === 'pronajem' ? ' / měsíc' : '' ?></p>
    <ul>
        <li>Město: <?= e($l['city']) ?></li>
        <li>Dispozice: <?= e($l['rooms']) ?></li>
        <li>Plocha: <?= (int)$l['area_m2'] ?> m²</li>
        <li>Inzerent: <?= e($l['author']) ?></li>
    </ul>
    <p><?= nl2br(e($l['description'])) ?></p>        <!-- nl2br: переносы строк → <br>, ПОСЛЕ экранирования -->
    <a href="index.php">← Zpět na výpis</a>
</article>
<?php layout_end();
