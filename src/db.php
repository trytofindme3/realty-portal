<?php
// src/db.php — одно место, где создаётся подключение к PostgreSQL.
// Подключаем его в других файлах через: require __DIR__ . '/../src/db.php';

declare(strict_types=1);            // строгие типы: PHP не будет молча превращать "5" в 5

$config = require __DIR__ . '/config.php';   // берём логин/пароль из отдельного файла (он в .gitignore)

$dsn = sprintf(                      // DSN — "адрес" базы для PDO; префикс pgsql: = драйвер PostgreSQL
    'pgsql:host=%s;port=%d;dbname=%s',
    $config['host'],
    $config['port'],
    $config['name']
);

$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // ошибки SQL → исключения, а не тихий false
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // строки приходят как ['title' => ..., ...]
    PDO::ATTR_EMULATE_PREPARES   => false,                  // настоящие prepared statements на стороне сервера
]);

$pdo->exec("SET client_encoding TO 'UTF8'");  // в pgsql нет charset в DSN — кодировку задаём так
