-- schema.sql — структура базы и тестовые данные (PostgreSQL)
-- Запуск:
--   createdb -U postgres realty                         -- создать базу (один раз)
--   psql -U postgres -d realty -f sql/schema.sql         -- выполнить этот файл
-- (или в pgAdmin: выбрать базу realty → Query Tool → вставить → F5)

SET client_encoding = 'UTF8';  -- текст из файла читаем как UTF-8, чтобы č, ř, ž не превратились в мусор

-- Чтобы скрипт можно было запускать повторно. CASCADE удалит и зависимые объекты
DROP TABLE IF EXISTS listings, users CASCADE;

-- Таблица пользователей (агенты/владельцы, которые публикуют объявления)
CREATE TABLE users (
    id            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- номер растёт сам (аналог AUTO_INCREMENT, стандарт SQL)
    email         VARCHAR(190) NOT NULL UNIQUE,                     -- UNIQUE: два аккаунта с одним email невозможны
    password_hash VARCHAR(255) NOT NULL,                            -- храним ХЭШ пароля, никогда сам пароль
    name          VARCHAR(100) NOT NULL,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()                -- TIMESTAMPTZ = дата+время с часовым поясом
);

-- Таблица объявлений
CREATE TABLE listings (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- внешний ключ: удалили пользователя → удалились объявления
    title       VARCHAR(150) NOT NULL,
    description TEXT,
    type        VARCHAR(10) NOT NULL CHECK (type IN ('prodej', 'pronajem')), -- CHECK вместо ENUM: только два значения
    city        VARCHAR(80)  NOT NULL,
    price       INTEGER  NOT NULL CHECK (price > 0),             -- цена в Kč; в Postgres нет UNSIGNED, поэтому CHECK
    area_m2     SMALLINT NOT NULL CHECK (area_m2 > 0),
    rooms       VARCHAR(10) NOT NULL,                            -- '2+kk', '3+1' — чешский формат, поэтому строка
    image       VARCHAR(255),                                    -- имя файла фото (по умолчанию NULL)
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Индекс создаётся отдельной командой (в MySQL — внутри CREATE TABLE)
CREATE INDEX idx_listings_city_type ON listings (city, type);  -- ускоряет фильтр по городу и типу

-- Тестовые данные. Пароль у демо-пользователя: demo1234
INSERT INTO users (email, password_hash, name) VALUES
('demo@example.cz', '$2y$10$0iSb9xQ5kEl3H2JtunV3S.vL6JJncZaicelcGcOSFOfF4rc9C1xMy', 'Demo Agent');
-- Хэш получен командой: php -r "echo password_hash('demo1234', PASSWORD_DEFAULT);"

INSERT INTO listings (user_id, title, description, type, city, price, area_m2, rooms) VALUES
(1, 'Světlý byt u metra', 'Po rekonstrukci, 5 min od metra.', 'pronajem', 'Praha', 22000, 54, '2+kk'),
(1, 'Rodinný dům se zahradou', 'Klidná lokalita, garáž.', 'prodej', 'Brno', 8900000, 140, '5+1'),
(1, 'Garsonka v centru', 'Ideální pro studenty.', 'pronajem', 'Praha', 14500, 28, '1+kk'),
(1, 'Byt s balkonem', 'Nový dům, sklep.', 'prodej', 'Olomouc', 4200000, 68, '3+kk');
