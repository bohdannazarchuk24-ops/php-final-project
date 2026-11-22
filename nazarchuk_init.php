<?php

/**
 * Спільний файл ініціалізації інтернет-магазину (підсумковий проєкт).
 * Підключається на початку КОЖНОГО файлу проєкту (через require_once).
 *
 *  1) буферизація виводу (ob_start) — щоб session_start() і header("Location: ...")
 *     працювали, навіть якщо config.php / db.php вже щось вивели;
 *  2) підключає ../config.php (час останньої зміни файлу) та ../db.php (з'єднання з БД);
 *  3) створює таблиці nazarchuk_storage, nazarchuk_users, nazarchuk_cart (якщо їх ще немає)
 *     і заповнює склад початковими товарами;
 *  4) містить допоміжні функції: валідація, робота з користувачем, оформлення сторінок.
 */

ob_start();
header('Content-Type: text/html; charset=utf-8');
mysqli_report(MYSQLI_REPORT_OFF);
session_start();

// Налаштування 
const SHOP_OWNER   = 'Назарчук Богдан';
const SHOP_THEME   = "Комп'ютерна техніка";
const ALLOWED_DOMAIN = 'cnu.edu.ua';           // дозволений домен e-mail 
const SPECIAL_CHARS  = '_-.@#$%^&!?*';         // дозволені спеціальні символи пароля
const CAT_BUYER  = 'Покупець';
const CAT_SELLER = 'Продавець';

require_once __DIR__ . '/../config.php';

$db_server = null;
ob_start();
require_once __DIR__ . '/../db.php';
ob_end_clean();
if (!$db_server) {
    die('Помилка: не вдалося підключитися до бази даних.');
}
mysqli_set_charset($db_server, 'utf8mb4');

// Таблиці 
mysqli_query($db_server, "CREATE TABLE IF NOT EXISTS nazarchuk_storage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    image VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    unit VARCHAR(10) NOT NULL DEFAULT 'шт.',
    qty INT NOT NULL DEFAULT 0,
    added_at DATETIME NOT NULL
) DEFAULT CHARSET=utf8mb4");

mysqli_query($db_server, "CREATE TABLE IF NOT EXISTS nazarchuk_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    login VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    category VARCHAR(20) NOT NULL,
    UNIQUE (login, category)
) DEFAULT CHARSET=utf8mb4");

mysqli_query($db_server, "CREATE TABLE IF NOT EXISTS nazarchuk_cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    UNIQUE (user_id, product_id)
) DEFAULT CHARSET=utf8mb4");

// Початкове наповнення складу (7 товарів), лише якщо таблиця порожня 
$cnt = db_row("SELECT COUNT(*) AS c FROM nazarchuk_storage");
if ($cnt && (int)$cnt['c'] === 0) {
    $seed = [
        ['Ноутбук 15.6" (Core i5, 16 ГБ, SSD 512 ГБ)', 'nazarchuk_laptop.svg',   24999.00, 'шт.', 8],
        ['Монітор 27" IPS Full HD',                     'nazarchuk_monitor.svg',  8999.00, 'шт.', 12],
        ['Клавіатура механічна ігрова',                 'nazarchuk_keyboard.svg', 3299.00, 'шт.', 20],
        ['Миша бездротова оптична',                     'nazarchuk_mouse.svg',     799.00, 'шт.', 35],
        ['SSD-накопичувач 1 ТБ NVMe',                   'nazarchuk_ssd.svg',      3499.00, 'шт.', 15],
        ['Відеокарта 8 ГБ GDDR6',                       'nazarchuk_gpu.svg',     17999.00, 'шт.', 5],
        ['Веб-камера Full HD з мікрофоном',             'nazarchuk_webcam.svg',   2199.00, 'шт.', 10],
    ];
    foreach ($seed as $p) {
        db_run(sprintf(
            "INSERT INTO nazarchuk_storage (name, image, price, unit, qty, added_at) VALUES ('%s','%s',%s,'%s',%d,'%s')",
            esc($p[0]),
            esc($p[1]),
            number_format($p[2], 2, '.', ''),
            esc($p[3]),
            $p[4],
            date('Y-m-d H:i:s')
        ));
    }
}

//Допоміжні функції

/** Екранування HTML для безпечного виводу */
function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Екранування рядка для SQL */
function esc($s): string
{
    global $db_server;
    return mysqli_real_escape_string($db_server, (string)$s);
}

/** Виконати запит (INSERT/UPDATE/DELETE) */
function db_run(string $sql): bool
{
    global $db_server;
    return (bool)mysqli_query($db_server, $sql);
}

/** Усі рядки результату SELECT */
function db_rows(string $sql): array
{
    global $db_server;
    $res = mysqli_query($db_server, $sql);
    $rows = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

/** Один рядок результату SELECT (або null) */
function db_row(string $sql): ?array
{
    $rows = db_rows($sql);
    return $rows[0] ?? null;
}

/** Гроші: 12 345,00 грн */
function money($x): string
{
    return number_format((float)$x, 2, ',', ' ') . ' грн';
}

/** URL зображення товару (усі файли лежать у папці images/) */
function image_url(string $file): string
{
    return 'images/' . rawurlencode($file);
}

// Користувач і сесія 

/** Поточний авторизований користувач (рядок із nazarchuk_users) або null.
 *  У сесії зберігається лише id користувача; дані щоразу беремо з БД. */
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $cache = null;
    if (!empty($_SESSION['user_id'])) {
        $cache = db_row("SELECT * FROM nazarchuk_users WHERE id = " . (int)$_SESSION['user_id']);
    }
    return $cache;
}

/** Повідомлення «Ви увійшли як …» (використовує дані сесії) */
function user_message(): string
{
    $u = current_user();
    if (!$u) {
        return 'Ви увійшли як гість';
    }
    // ім'я та категорія беремо зі змінних сесії, записаних при вході
    $cat = mb_strtolower($_SESSION['category'] ?? $u['category'], 'UTF-8');
    $name = $_SESSION['name'] ?? ($u['first_name'] . ' ' . $u['last_name']);
    return 'Ви увійшли як ' . $cat . ' під іменем ' . $name;
}

/** Для сторінок лише для авторизованих (за потреби — певної категорії) */
function require_login(?string $category = null): array
{
    $u = current_user();
    if (!$u) {
        header('Location: index.php');           // не авторизований → стартова сторінка
        exit;
    }
    if ($category !== null && $u['category'] !== $category) {
        flash_set('err', 'Ця сторінка недоступна для вашої категорії користувача');
        header('Location: nazarchuk_shop.php');  // авторизований, але не та категорія
        exit;
    }
    return $u;
}

/** Повне завершення сеансу */
function do_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Одноразове повідомлення між запитами (зберігається в сесії) */
function flash_set(string $type, string $text): void
{
    $_SESSION['flash'] = [$type, $text];
}

function flash_show(): void
{
    if (!empty($_SESSION['flash'])) {
        [$t, $m] = $_SESSION['flash'];
        echo "<div class='msg-$t'>" . h($m) . "</div>";
        unset($_SESSION['flash']);
    }
}

// Валідація реєстрації

/** Ім'я/прізвище: латиниця, кожне слово з великої літери (допускаються пробіл і дефіс між словами) */
function valid_name(string $s): bool
{
    return (bool)preg_match('/^[A-Z][a-z]+([ \-][A-Z][a-z]+)*$/', $s);
}

/** Логін: коректна e-mail адреса на домені ALLOWED_DOMAIN */
function valid_login(string $s): bool
{
    if (!filter_var($s, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $parts = explode('@', $s);
    return strtolower(end($parts)) === ALLOWED_DOMAIN;
}

/** Пароль: ≥ 6 символів, ≥ 1 латинська літера, ≥ 1 цифра, ≥ 1 спецсимвол із SPECIAL_CHARS */
function valid_password(string $s): bool
{
    return strlen($s) >= 6
        && preg_match('/[A-Za-z]/', $s)
        && preg_match('/\d/', $s)
        && preg_match('/[' . preg_quote(SPECIAL_CHARS, '/') . ']/', $s);
}

// Оформлення сторінок 

/** Кнопка-посилання */
function btn(string $href, string $text, string $class = ''): string
{
    return "<a class='btn $class' href='" . h($href) . "'>" . h($text) . "</a>";
}

/** Початок сторінки: стилі, повідомлення про користувача, заголовок */
function page_head(string $title, bool $with_banner = true): void
{
    echo "<link rel='stylesheet' href='nazarchuk_style.css'><div class='wrap'>";
    if ($with_banner) {
        echo "<div class='banner'>" . h(user_message()) . "</div>";
    }
    echo "<h1>" . h($title) . "</h1>";
}

/** Кінець сторінки. $exit — показати кнопку «Вихід» (на всіх сторінках, крім стартової) */
function page_foot(bool $exit = true): void
{
    echo "<div class='footer'>";
    if ($exit) {
        echo btn('nazarchuk_logout.php', 'Вихід', 'danger');
    }
    echo "</div></div>";
}

/** Картка товару в каталозі (назва, зображення, ціна за одиницю).
 *  $link — чи робити зображення і назву посиланнями на сторінку товару */
function product_card(array $p, bool $link): string
{
    $img = "<img src='" . h(image_url($p['image'])) . "' alt='" . h($p['name']) . "'>";
    $name = h($p['name']);
    if ($link) {
        $url = 'nazarchuk_product.php?id=' . (int)$p['id'];
        $img = "<a href='$url'>$img</a>";
        $name = "<a href='$url'>$name</a>";
    }
    return "<div class='card'>$img<div class='card-name'>$name</div>"
        . "<div class='card-price'>" . money($p['price']) . " / " . h($p['unit']) . "</div></div>";
}
