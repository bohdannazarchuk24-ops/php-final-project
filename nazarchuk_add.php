<?php
// Додавання нового товару на склад (лише продавець) із завантаженням зображення на сервер.
require_once __DIR__ . '/nazarchuk_init.php';
$user = require_login(CAT_SELLER);

$errors = [];
$v = ['name' => '', 'price' => '', 'unit' => 'шт.', 'qty' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['name']  = trim($_POST['name'] ?? '');
    $v['price'] = trim(str_replace(',', '.', $_POST['price'] ?? ''));
    $v['unit']  = $_POST['unit'] ?? '';
    $v['qty']   = trim($_POST['qty'] ?? '');

    if ($v['name'] === '' || mb_strlen($v['name'], 'UTF-8') > 150) {
        $errors['name'] = 'Вкажіть найменування (до 150 символів)';
    }
    if (!is_numeric($v['price']) || $v['price'] <= 0 || $v['price'] > 99999999) {
        $errors['price'] = 'Ціна — додатне число';
    }
    if (!in_array($v['unit'], ['шт.', 'кг'], true)) {
        $errors['unit'] = 'Оберіть одиницю виміру';
    }
    if (!ctype_digit($v['qty']) || (int)$v['qty'] > 1000000) {
        $errors['qty'] = 'Кількість — ціле невід’ємне число';
    }

    // Зображення: перевіряємо помилку завантаження, розмір, розширення та реальний тип файлу
    $ext = '';
    $f = $_FILES['image'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
        $errors['image'] = 'Оберіть файл із зображенням товару';
    } elseif ($f['size'] > 2 * 1024 * 1024) {
        $errors['image'] = 'Файл завеликий (максимум 2 МБ)';
    } else {
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        $info = @getimagesize($f['tmp_name']);
        if (!$info || !isset($allowed[$info[2]]) || $info[0] < 1 || $info[1] < 1) {
            $errors['image'] = 'Дозволені зображення JPG, PNG, GIF або WEBP';
        } else {
            $ext = $allowed[$info[2]];
        }
    }

    if (!$errors) {
        // власне ім'я файлу (з префіксом nazarchuk_), щоб файли не перезаписували один одного
        $fname = 'nazarchuk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (!is_dir(__DIR__ . '/images')) {
            mkdir(__DIR__ . '/images', 0755, true);
        }
        if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/images/' . $fname)) {
            $errors['image'] = 'Не вдалося зберегти файл на сервері (перевірте права на папку images)';
        } else {
            $ok = db_run(sprintf(
                "INSERT INTO nazarchuk_storage (name, image, price, unit, qty, added_at) VALUES ('%s','%s',%s,'%s',%d,'%s')",
                esc($v['name']),
                esc($fname),
                number_format((float)$v['price'], 2, '.', ''),
                esc($v['unit']),
                (int)$v['qty'],
                date('Y-m-d H:i:s')
            ));
            if ($ok) {
                flash_set('ok', 'Товар «' . $v['name'] . '» додано на склад');
                header('Location: nazarchuk_product.php?id=' . mysqli_insert_id($db_server));
                exit;
            }
            $errors['name'] = 'Помилка запису в базу даних';
        }
    }
}

function e(array $errors, string $k): string
{
    return isset($errors[$k]) ? "<div class='err'>" . h($errors[$k]) . "</div>" : '';
}

page_head(user_message(), false);
echo "<h2>Додати новий товар</h2>";
if ($errors) {
    echo "<div class='msg-err'>Товар не додано: виправте позначені поля</div>";
}
?>
<form method="post" enctype="multipart/form-data" class="box">
    <label>Найменування
        <input type="text" name="name" value="<?= h($v['name']) ?>"></label><?= e($errors, 'name') ?>
    <label>Ціна за одиницю, грн
        <input type="text" name="price" value="<?= h($v['price']) ?>"></label><?= e($errors, 'price') ?>
    <label>Одиниця виміру
        <select name="unit">
            <option value="шт." <?= $v['unit'] === 'шт.' ? 'selected' : '' ?>>шт.</option>
            <option value="кг" <?= $v['unit'] === 'кг' ? 'selected' : '' ?>>кг</option>
        </select></label><?= e($errors, 'unit') ?>
    <label>Кількість на складі
        <input type="text" name="qty" value="<?= h($v['qty']) ?>"></label><?= e($errors, 'qty') ?>
    <label>Зображення товару (JPG, PNG, GIF, WEBP до 2 МБ)
        <input type="file" name="image" accept="image/*"></label><?= e($errors, 'image') ?>
    <p><button class="btn" type="submit">Додати товар</button></p>
</form>
<div class="menu"><?= btn('nazarchuk_shop.php', 'Продовжити покупки', 'gray') ?></div>
<?php page_foot(); ?>