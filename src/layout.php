<?php
// src/layout.php — общая "шапка" и "подвал", чтобы не копировать HTML в каждый файл.

function layout_start(string $title): void
{ ?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- нормальный вид на телефоне -->
    <title><?= e($title) ?> · RealtyPortal</title>                       <?php /* <?= ... ?> — короткая запись echo */ ?>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
    <a href="index.php" class="logo">RealtyPortal</a>
    <nav>
        <?php if (current_user_id()): ?>                <!-- меню зависит от того, вошёл ли пользователь -->
            <a href="create.php">+ Přidat inzerát</a>
            <a href="logout.php">Odhlásit</a>
        <?php else: ?>
            <a href="login.php">Přihlásit</a>
        <?php endif; ?>
    </nav>
</header>
<main>
<?php }

function layout_end(): void
{ ?>
</main>
</body>
</html>
<?php }
