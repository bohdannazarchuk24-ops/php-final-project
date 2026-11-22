<?php
// Стартова сторінка: користувач завжди «гість» (будь-який попередній сеанс завершується).
require_once __DIR__ . '/nazarchuk_init.php';
do_logout();

// Повідомлення, які передаються з інших сторінок через параметр ?m=...
$messages = [
    'reg_ok'    => ['ok',  'Реєстрація пройшла успішно'],
    'reg_fail'  => ['err', 'Помилка реєстрації, спробуйте ще раз'],
    'login_err' => ['err', 'Введено невірний логін чи пароль'],
];
$m = $_GET['m'] ?? '';

page_head('Інтернет-магазин ' . SHOP_OWNER . ' ' . SHOP_THEME);

if (isset($messages[$m])) {
    echo "<div class='msg-" . $messages[$m][0] . "'>" . h($messages[$m][1]) . "</div>";
}

echo "<div class='menu'>"
    . btn('nazarchuk_register.php', 'Реєстрація')
    . btn('nazarchuk_login.php', 'Вхід')
    . btn('nazarchuk_forgot.php', 'Забули пароль')
    . btn('nazarchuk_users.php', 'Зареєстровані користувачі')
    . btn('nazarchuk_sklad.php', 'Склад')
    . "</div>";

// 4 випадкові товари зі складу (для гостя: назва, зображення, ціна за одиницю; без посилань)
$products = db_rows("SELECT * FROM nazarchuk_storage ORDER BY RAND() LIMIT 4");
echo "<h3>Товари</h3><div class='grid'>";
foreach ($products as $p) {
    echo product_card($p, false);
}
echo "</div>";

page_foot(false);   // на стартовій сторінці кнопки «Вихід» немає
