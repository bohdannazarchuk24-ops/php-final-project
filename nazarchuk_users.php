<?php
// Усі зареєстровані користувачі (разом із паролями).
require_once __DIR__ . '/nazarchuk_init.php';

$users = db_rows("SELECT * FROM nazarchuk_users ORDER BY id");
page_head('Зареєстровані користувачі');
?>
<table class="data">
    <tr>
        <th>№</th>
        <th>Ім'я</th>
        <th>Прізвище</th>
        <th>Логін</th>
        <th>Пароль</th>
        <th>Категорія</th>
    </tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= h($u['first_name']) ?></td>
            <td><?= h($u['last_name']) ?></td>
            <td><?= h($u['login']) ?></td>
            <td><?= h($u['password']) ?></td>
            <td><?= h($u['category']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr>
            <td colspan="6">Користувачів ще немає</td>
        </tr><?php endif; ?>
</table>
<p><a class="btn gray" href="https://nazarchuk.infinityfree.io/index.php ">На стартову сторінку</a></p>
<?php page_foot(); ?>