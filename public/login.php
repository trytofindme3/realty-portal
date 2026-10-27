<?php
// public/login.php — вход по email и паролю.

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/layout.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {          // форму отправили (а не просто открыли страницу)
    csrf_check();

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([trim($_POST['email'] ?? '')]);
    $user = $stmt->fetch();

    // password_verify сравнивает введённый пароль с хэшем из БД
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);                  // новый id сессии после входа — защита от session fixation
        $_SESSION['user_id'] = (int)$user['id'];      // запоминаем, кто вошёл
        header('Location: index.php');
        exit;
    }
    $error = 'Špatný e-mail nebo heslo.';             // одна ошибка на оба случая — не подсказываем, что именно неверно
}

layout_start('Přihlášení');
?>
<form method="post" class="form">                     <!-- POST: пароль не попадает в URL и историю браузера -->
    <h1>Přihlášení</h1>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>E-mail <input type="email" name="email" required></label>
    <label>Heslo <input type="password" name="password" required></label>
    <button>Přihlásit</button>
    <p class="muted">Demo: demo@example.cz / demo1234</p>
</form>
<?php layout_end();
