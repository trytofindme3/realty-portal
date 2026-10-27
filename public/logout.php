<?php
// public/logout.php — выход: очищаем сессию и уходим на главную.
require __DIR__ . '/../src/helpers.php';  // внутри session_start()

$_SESSION = [];                          // стираем все данные сессии
session_destroy();                       // удаляем саму сессию на сервере
header('Location: index.php');
exit;
