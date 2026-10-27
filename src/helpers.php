<?php
// src/helpers.php — маленькие функции, которые нужны на каждой странице.

declare(strict_types=1);

session_start();                     // включаем сессии: так сервер "помнит", кто вошёл

// e() = escape. Любой текст из БД или от пользователя выводим ТОЛЬКО через e(),
// иначе кто-то впишет в заголовок <script> и он выполнится у посетителей (XSS).
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Форматирование цены по-чешски: 8900000 → "8 900 000 Kč"
function price(int $value): string
{
    return number_format($value, 0, ',', ' ') . ' Kč';
}

// Кто сейчас вошёл? Возвращает id пользователя или null.
function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

// Ставим на страницы, куда нельзя без входа.
function require_login(): void
{
    if (current_user_id() === null) {
        header('Location: login.php');  // перенаправляем на вход
        exit;                           // ОБЯЗАТЕЛЬНО: иначе код ниже всё равно выполнится
    }
}

// CSRF-токен: случайная строка в сессии + скрытое поле в форме.
// Защищает от того, чтобы чужой сайт отправил форму от имени нашего пользователя.
function csrf_token(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); // создаём один раз за сессию
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { // hash_equals — сравнение без утечки по времени
        http_response_code(400);
        exit('Neplatný požadavek.');
    }
}
