<?php
// Головна сторінка після входу: усі товари у дві колонки + кнопки відповідно до категорії.
require_once __DIR__ . '/nazarchuk_init.php';
$user = require_login();

page_head(user_message(), false);   // повідомлення з даними сесії — заголовок сторінки
flash_show();

echo "<div class='menu'>";
if ($user['category'] === CAT_BUYER) {
    echo btn('nazarchuk_cart.php', 'Кошик');
} else {
    echo btn('nazarchuk_add.php', 'Додати новий товар') . btn('nazarchuk_audit.php', 'Аудит');
}
echo btn('nazarchuk_sklad.php', 'Склад', 'gray') . "</div>";

$products = db_rows("SELECT * FROM nazarchuk_storage ORDER BY id");
echo "<div class='grid'>";
foreach ($products as $p) {
    echo product_card($p, true);
}
echo "</div>";
page_foot();
