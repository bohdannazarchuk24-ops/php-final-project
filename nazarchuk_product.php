<?php
// Сторінка товару: уся інформація, лічильник кількості,
// «Додати в кошик» (покупець) або «Поповнити склад» (продавець).
require_once __DIR__ . '/nazarchuk_init.php';
$user = require_login();
$is_buyer = $user['category'] === CAT_BUYER;

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$product = db_row("SELECT * FROM nazarchuk_storage WHERE id = $id");
if (!$product) {
    flash_set('err', 'Такого товару не існує');
    header('Location: nazarchuk_shop.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = max(1, min(100000, (int)($_POST['qty'] ?? 1)));
    $name = $product['name'];

    if ($is_buyer && ($_POST['action'] ?? '') === 'add') {
        // Атомарне зменшення: умова qty >= n гарантує, що кількість не піде в мінус
        db_run("UPDATE nazarchuk_storage SET qty = qty - $n WHERE id = $id AND qty >= $n");
        if (mysqli_affected_rows($db_server) === 1) {
            $uid = (int)$user['id'];
            $line = db_row("SELECT id FROM nazarchuk_cart WHERE user_id = $uid AND product_id = $id");
            if ($line) {
                db_run("UPDATE nazarchuk_cart SET qty = qty + $n WHERE id = " . (int)$line['id']);
            } else {
                db_run("INSERT INTO nazarchuk_cart (user_id, product_id, qty) VALUES ($uid, $id, $n)");
            }
            flash_set('ok', "Додано в кошик: $name — $n {$product['unit']}");
        } else {
            $left = db_row("SELECT qty FROM nazarchuk_storage WHERE id = $id");
            flash_set('err', "Недостатньо товару на складі: доступно {$left['qty']} {$product['unit']}");
        }
    } elseif (!$is_buyer && ($_POST['action'] ?? '') === 'restock') {
        db_run("UPDATE nazarchuk_storage SET qty = qty + $n WHERE id = $id");
        flash_set('ok', "Склад поповнено: $name — +$n {$product['unit']}");
    }
    header('Location: nazarchuk_product.php?id=' . $id);   // POST → redirect → GET (без повторної відправки)
    exit;
}

page_head(user_message(), false);
flash_show();
?>
<div class="product">
    <img src="<?= h(image_url($product['image'])) ?>" alt="<?= h($product['name']) ?>">
    <div>
        <h2><?= h($product['name']) ?></h2>
        <table class="data">
            <tr>
                <th>Код товару</th>
                <td><?= (int)$product['id'] ?></td>
            </tr>
            <tr>
                <th>Найменування</th>
                <td><?= h($product['name']) ?></td>
            </tr>
            <tr>
                <th>Файл зображення</th>
                <td><?= h($product['image']) ?></td>
            </tr>
            <tr>
                <th>Ціна за одиницю</th>
                <td><?= money($product['price']) ?> / <?= h($product['unit']) ?></td>
            </tr>
            <tr>
                <th>Наявна кількість</th>
                <td><b><?= (int)$product['qty'] ?></b> <?= h($product['unit']) ?></td>
            </tr>
            <tr>
                <th>Дата додавання</th>
                <td><?= h($product['added_at']) ?></td>
            </tr>
        </table>

        <form method="post">
            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
            <input type="hidden" name="action" value="<?= $is_buyer ? 'add' : 'restock' ?>">
            <div class="qty">
                <button type="button" class="btn gray" onclick="chg(-1)">−</button>
                <input type="number" id="qty" name="qty" value="1" min="1">
                <button type="button" class="btn gray" onclick="chg(1)">+</button>
                <span><?= h($product['unit']) ?></span>
            </div>
            <button class="btn" type="submit"><?= $is_buyer ? 'Додати в кошик' : 'Поповнити склад' ?></button>
        </form>
    </div>
</div>
<script>
    // лічильник вибору кількості: не менше 1
    function chg(d) {
        var el = document.getElementById('qty');
        el.value = Math.max(1, (parseInt(el.value, 10) || 1) + d);
    }
</script>
<div class="menu">
    <?= btn('nazarchuk_shop.php', 'Продовжити покупки', 'gray') ?>
    <?php if ($is_buyer) echo btn('nazarchuk_cart.php', 'Кошик'); ?>
</div>
<?php page_foot(); ?>