<?php
// public/index.php — главная: список объявлений + фильтр по городу, типу и цене.

declare(strict_types=1);
require __DIR__ . '/../src/db.php';       // даёт переменную $pdo
require __DIR__ . '/../src/helpers.php';  // e(), price(), сессия
require __DIR__ . '/../src/layout.php';   // шапка/подвал

// 1) Читаем фильтры из адресной строки: index.php?city=Praha&type=pronajem&max=20000
$city = trim($_GET['city'] ?? '');                    // ?? '' — если параметра нет, пустая строка
$type = $_GET['type'] ?? '';
$max  = (int)($_GET['max'] ?? 0);                     // (int) превращает мусор в 0

// 2) Собираем SQL по кусочкам. Значения НЕ вклеиваем в строку — только плейсхолдеры :name
$sql    = 'SELECT id, title, type, city, price, area_m2, rooms FROM listings WHERE 1=1';
$params = [];                                         // сюда кладём значения для плейсхолдеров

if ($city !== '') {
    $sql .= ' AND city = :city';
    $params['city'] = $city;
}
if (in_array($type, ['prodej', 'pronajem'], true)) {  // принимаем только разрешённые значения
    $sql .= ' AND type = :type';
    $params['type'] = $type;
}
if ($max > 0) {
    $sql .= ' AND price <= :max';
    $params['max'] = $max;
}
$sql .= ' ORDER BY created_at DESC';                  // новые сверху

// 3) Prepared statement: база получает запрос и данные отдельно → SQL-инъекция невозможна
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();                        // массив строк

// Список городов для выпадающего меню (DISTINCT = без повторов)
$cities = $pdo->query('SELECT DISTINCT city FROM listings ORDER BY city')->fetchAll(PDO::FETCH_COLUMN);

layout_start('Inzeráty');
?>
<form method="get" class="filter">                    <!-- GET: фильтр попадает в URL, ссылкой можно поделиться -->
    <select name="city">
        <option value="">Všechna města</option>
        <?php foreach ($cities as $c): ?>
            <option <?= $c === $city ? 'selected' : '' ?>><?= e($c) ?></option> <!-- запоминаем выбор -->
        <?php endforeach; ?>
    </select>
    <select name="type">
        <option value="">Prodej i pronájem</option>
        <option value="prodej"   <?= $type === 'prodej' ? 'selected' : '' ?>>Prodej</option>
        <option value="pronajem" <?= $type === 'pronajem' ? 'selected' : '' ?>>Pronájem</option>
    </select>
    <input type="number" name="max" placeholder="Max. cena" value="<?= $max ?: '' ?>">
    <button>Filtrovat</button>
</form>

<p class="muted">Nalezeno: <?= count($listings) ?></p>

<div class="grid">
    <?php foreach ($listings as $l): ?>
        <a class="card" href="listing.php?id=<?= $l['id'] ?>">  <!-- id — число из БД, экранировать не нужно -->
            <span class="badge"><?= $l['type'] === 'prodej' ? 'Prodej' : 'Pronájem' ?></span>
            <h3><?= e($l['title']) ?></h3>                    <!-- текст из БД → всегда через e() -->
            <p><?= e($l['city']) ?> · <?= e($l['rooms']) ?> · <?= (int)$l['area_m2'] ?> m²</p>
            <strong><?= price((int)$l['price']) ?></strong>
        </a>
    <?php endforeach; ?>
</div>
<?php layout_end();
