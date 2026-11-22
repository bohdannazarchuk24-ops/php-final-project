<?php
// Кошик покупця: перелік товарів, суми, видалення без повернення на склад.
require_once __DIR__ . '/nazarchuk_init.php';
$user = require_login(CAT_BUYER);
$uid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    // видаляємо рядок кошика цього покупця; на склад кількість НЕ повертається
    db_run("DELETE FROM nazarchuk_cart WHERE user_id = $uid AND product_id = " . (int)$_POST['remove']);
    flash_set('ok', 'Товар видалено з кошика');
    header('Location: nazarchuk_cart.php');
    exit;
}

$lines = db_rows("SELECT c.product_id, c.qty AS cqty, s.name, s.image, s.price, s.unit
                  FROM nazarchuk_cart c JOIN nazarchuk_storage s ON s.id = c.product_id
                  WHERE c.user_id = $uid ORDER BY c.id");
$total = 0;

page_head(user_message(), false);
echo "<h2>Кошик</h2>";
flash_show();
if (!$lines) {
    echo "<p>Кошик порожній.</p>";
} else {
    echo "<table class='data'><tr><th>Найменування</th><th>Зображення</th><th>Ціна за одиницю</th><th>Кількість</th><th>Вартість</th><th></th></tr>";
    foreach ($lines as $l) {
        $sum = $l['price'] * $l['cqty'];
        $total += $sum;
        echo "<tr><td><a href='nazarchuk_product.php?id=" . (int)$l['product_id'] . "'>" . h($l['name']) . "</a></td>"
            . "<td><img src='" . h(image_url($l['image'])) . "' alt=''></td>"
            . "<td>" . money($l['price']) . " / " . h($l['unit']) . "</td>"
            . "<td>" . (int)$l['cqty'] . " " . h($l['unit']) . "</td>"
            . "<td>" . money($sum) . "</td>"
            . "<td><form method='post'><input type='hidden' name='remove' value='" . (int)$l['product_id'] . "'>"
            . "<button class='btn danger' type='submit'>Видалити</button></form></td></tr>";
    }
    echo "</table><div class='total'>Загальна вартість: " . money($total) . "</div>";
}
echo "<div class='menu'>" . btn('nazarchuk_shop.php', 'Продовжити покупки', 'gray') . "</div>";
page_foot();
