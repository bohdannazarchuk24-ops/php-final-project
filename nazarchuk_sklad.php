<?php
// Склад: уся таблиця nazarchuk_storage.
require_once __DIR__ . '/nazarchuk_init.php';

$rows = db_rows("SELECT * FROM nazarchuk_storage ORDER BY id");
page_head('Склад');
?>
<table class="data">
    <tr>
        <th>№</th>
        <th>Найменування</th>
        <th>Зображення</th>
        <th>Ціна за одиницю</th>
        <th>Кількість</th>
        <th>Дата додавання</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= h($r['name']) ?></td>
            <td><img src="<?= h(image_url($r['image'])) ?>" alt=""></td>
            <td><?= money($r['price']) ?> / <?= h($r['unit']) ?></td>
            <td><?= (int)$r['qty'] ?> <?= h($r['unit']) ?></td>
            <td><?= h($r['added_at']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<p><a class="btn gray" href="<?= current_user() ? 'nazarchuk_shop.php' : 'index.php' ?>"><?= current_user() ? 'До каталогу' : 'На стартову сторінку' ?></a></p>
<?php page_foot(); ?>