<?php
// public/register.php — регистрация нового пользователя.

declare(strict_types=1);
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/layout.php';

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';             // пароль НЕ trim-уем: пробелы могут быть частью пароля

    // Валидация
    if (mb_strlen($name) < 2)                          $errors[] = 'Jméno musí mít aspoň 2 znaky.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Neplatný e-mail.';  // встроенная проверка формата email
    if (strlen($password) < 8)                         $errors[] = 'Heslo musí mít aspoň 8 znaků.';
    if ($password !== ($_POST['password2'] ?? ''))     $errors[] = 'Hesla se neshodují.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?) RETURNING id'
            );
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$stmt->fetchColumn();  // сразу логиним нового пользователя
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {          // 23505 = unique_violation: такой email уже есть
                $errors[] = 'Tento e-mail už je zaregistrovaný.';
            } else {
                throw $e;                             // другая ошибка — пусть PHP покажет её как есть
            }
        }
    }
}

layout_start('Registrace');
?>
<form method="post" class="form">
    <h1>Registrace</h1>
    <?php foreach ($errors as $err): ?><p class="error"><?= e($err) ?></p><?php endforeach; ?>
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Jméno <input name="name" value="<?= e($name) ?>" required></label>
    <label>E-mail <input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label>Heslo <input type="password" name="password" minlength="8" required></label>
    <label>Heslo znovu <input type="password" name="password2" minlength="8" required></label>
    <button>Zaregistrovat</button>
    <p class="muted">Už máte účet? <a href="login.php">Přihlaste se</a></p>
</form>
<?php layout_end();