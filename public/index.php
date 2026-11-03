<?php
// public/index.php — главная: список объявлений + поиск, фильтры и сортировка.

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/layout.php';

// 1) Читаем фильтры из адресной строки
$q       = trim($_GET['q'] ?? '');                     // поиск по названию
$city    = trim($_GET['city'] ?? '');
$type    = $_GET['type'] ?? '';
$max     = (int)($_GET['max'] ?? 0);
$minArea = (int)($_GET['min_area'] ?? 0);              // НОВОЕ: минимальная площадь
$sort    = $_GET['sort'] ?? 'new';                     // НОВОЕ: сортировка

// Белый список сортировок: ключ из URL → кусок SQL.
// Колонку в ORDER BY нельзя передать плейсхолдером, поэтому пользователь выбирает только ключ.
$sorts = [
    'new'        => 'created_at DESC',
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'area_desc'  => 'area_m2 DESC',
];
if (!isset($sorts[$sort])) {                           // пришло что-то чужое → сортировка по умолчанию
    $sort = 'new';
}

// 2) Собираем WHERE по кусочкам
$where  = ' WHERE 1=1';
$params = [];

if ($q !== '') {
    $where .= ' AND title ILIKE :q';                   // ILIKE — поиск без учёта регистра (фишка Postgres)
    $params['q'] = '%' . $q . '%';                     // % — «любые символы до и после»
}
if ($city !== '') {
    $where .= ' AND city = :city';
    $params['city'] = $city;
}
if (in_array($type, ['prodej', 'pronajem'], true)) {
    $where .= ' AND type = :type';
    $params['type'] = $type;
}
if ($max > 0) {
    $where .= ' AND price <= :max';
    $params['max'] = $max;
}
if ($minArea > 0) {
    $where .= ' AND area_m2 >= :min_area';
    $params['min_area'] = $minArea;
}

// 3) Пагинация: сколько всего найдено и какую страницу показать
$perPage = 6;                                          // объявлений на страницу
$page    = max(1, (int)($_GET['page'] ?? 1));          // max(1, …) — страница не меньше 1

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM listings' . $where); // тот же WHERE, но считаем строки
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));         // ceil округляет вверх: 7 объявлений → 2 страницы
$page  = min($page, $pages);                           // ?page=999 → последняя страница
$offset = ($page - 1) * $perPage;                      // сколько строк пропустить

// 4) Запрос: WHERE из параметров, ORDER BY из белого списка, LIMIT/OFFSET для страницы
$sql  = 'SELECT id, title, type, city, price, area_m2, rooms FROM listings'
      . $where
      . ' ORDER BY ' . $sorts[$sort]
      . ' LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
$stmt->execute($params + ['limit' => $perPage, 'offset' => $offset]); // + склеивает два массива
$listings = $stmt->fetchAll();

// Ссылка на другую страницу с сохранением всех фильтров
function page_url(int $n): string
{
    return '?' . http_build_query(array_merge($_GET, ['page' => $n])); // берём текущие ?параметры и меняем page
}


$cities = $pdo->query('SELECT DISTINCT city FROM listings ORDER BY city')->fetchAll(PDO::FETCH_COLUMN);

layout_start('Inzeráty');
?>
<form method="get" class="filter">
    <input type="search" name="q" placeholder="Hledat v názvu…" value="<?= e($q) ?>">
    <select name="city">
        <option value="">Všechna města</option>
        <?php foreach ($cities as $c): ?>
            <option <?= $c === $city ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="type">
        <option value="">Prodej i pronájem</option>
        <option value="prodej"   <?= $type === 'prodej' ? 'selected' : '' ?>>Prodej</option>
        <option value="pronajem" <?= $type === 'pronajem' ? 'selected' : '' ?>>Pronájem</option>
    </select>
    <input type="number" name="max" placeholder="Max. cena" value="<?= $max ?: '' ?>">
    <input type="number" name="min_area" placeholder="Min. m²" value="<?= $minArea ?: '' ?>">
    <select name="sort">
        <option value="new"        <?= $sort === 'new' ? 'selected' : '' ?>>Nejnovější</option>
        <option value="price_asc"  <?= $sort === 'price_asc' ? 'selected' : '' ?>>Nejlevnější</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Nejdražší</option>
        <option value="area_desc"  <?= $sort === 'area_desc' ? 'selected' : '' ?>>Největší</option>
    </select>
    <button>Filtrovat</button>
</form>

<p class="muted">Nalezeno: <?= $total ?>

<div class="grid">
    <?php foreach ($listings as $l): ?>
        <a class="card" href="listing.php?id=<?= $l['id'] ?>">
            <span class="badge"><?= $l['type'] === 'prodej' ? 'Prodej' : 'Pronájem' ?></span>
            <h3><?= e($l['title']) ?></h3>
            <p><?= e($l['city']) ?> · <?= e($l['rooms']) ?> · <?= (int)$l['area_m2'] ?> m²</p>
            <strong><?= price((int)$l['price']) ?></strong>
        </a>
    <?php endforeach; ?>
</div>
<?php if ($pages > 1): ?>
    <nav class="pagination">
        <?php if ($page > 1): ?><a href="<?= e(page_url($page - 1)) ?>">← Předchozí</a><?php endif; ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= e(page_url($i)) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= e(page_url($page + 1)) ?>">Další →</a><?php endif; ?>
    </nav>
<?php endif; ?>

<?php layout_end();
