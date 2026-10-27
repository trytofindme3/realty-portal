<?php
// src/config.example.php — ШАБЛОН настроек.
// Скопируй в src/config.php и впиши свои данные. config.php в Git НЕ коммитим (см. .gitignore).

return [                     // файл просто возвращает массив — db.php получает его через require
    'host' => '127.0.0.1',
    'port' => 5432,          // стандартный порт PostgreSQL
    'name' => 'realty',      // имя базы, созданной через createdb
    'user' => 'postgres',    // пользователь по умолчанию после установки
    'pass' => 'admin',            // пароль, который ты задал при установке PostgreSQL
];
