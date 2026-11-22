<?php
// Аудит складу: загальна вартість усіх товарів, що є в наявності (лише продавець).
require_once __DIR__ . '/nazarchuk_init.php';
$user = require_login(CAT_SELLER);

$rows = db_rows("SELECT * FROM nazarchuk_storage ORDER BY id");
$total = 0;
page_head(user_message(), false);
echo "<h2>Аудит складу</h2><table class='data'><tr><th>Найменування</th><th>Ціна за одиницю</th><th>Кількість</th><th>Вартість</th></tr>";
foreach ($rows as $r) {
    $sum = $r['price'] * $r['qty'];
    $total += $sum;
    echo "<tr><td>" . h($r['name']) . "</td><td>" . money($r['price']) . "</td><td>" . (int)$r['qty'] . " " . h($r['unit'])
        . "</td><td>" . money($sum) . "</td></tr>";
}
echo "</table><div class='total'>Загальна вартість товарів на складі: " . money($total) . "</div>";
echo "<div class='menu'>" . btn('nazarchuk_shop.php', 'Продовжити покупки', 'gray') . "</div>";
page_foot();
