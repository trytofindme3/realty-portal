<?php
// public/my.php — «Moje inzeráty»: объявления текущего пользователя + кнопки «изменить» и «удалить».

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/layout.php';

require_login();

$stmt = $pdo->prepare(
    'SELECT id, title, city, price, type FROM listings WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->execute([current_user_id()]);                  // только СВОИ объявления
$listings = $stmt->fetchAll();

layout_start('Moje inzeráty');
?>
<h1>Moje inzeráty</h1>

<?php if (!$listings): ?>
    <p>Zatím nemáte žádné inzeráty. <a href="create.php">Přidat první</a></p>
<?php else: ?>
    <table class="table">
        <tr><th>Název</th><th>Město</th><th>Cena</th><th></th></tr>
        <?php foreach ($listings as $l): ?>
            <tr>
                <td><a href="listing.php?id=<?= $l['id'] ?>"><?= e($l['title']) ?></a></td>
                <td><?= e($l['city']) ?></td>
                <td><?= price((int)$l['price']) ?></td>
                <td class="actions">
                    <a href="edit.php?id=<?= $l['id'] ?>">Upravit</a>
                    <!-- Удаление — это ФОРМА с POST, а не ссылка: GET-ссылку может «нажать» кто угодно -->
                    <form method="post" action="delete.php" onsubmit="return confirm('Opravdu smazat?')">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <button class="danger">Smazat</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<?php layout_end();
