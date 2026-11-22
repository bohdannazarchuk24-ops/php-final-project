<?php
// Реєстрація нового користувача (покупця або продавця).
require_once __DIR__ . '/nazarchuk_init.php';

$errors = [];
$v = ['first_name' => '', 'last_name' => '', 'login' => '', 'category' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['first_name'] = trim($_POST['first_name'] ?? '');
    $v['last_name']  = trim($_POST['last_name'] ?? '');
    $v['login']      = strtolower(trim($_POST['login'] ?? ''));
    $v['category']   = $_POST['category'] ?? '';
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!valid_name($v['first_name'])) {
        $errors['first_name'] = "Ім'я — лише латиницею, кожне слово з великої літери (наприклад, Bohdan)";
    }
    if (!valid_name($v['last_name'])) {
        $errors['last_name'] = 'Прізвище — лише латиницею, кожне слово з великої літери (наприклад, Nazarchuk)';
    }
    if (!valid_login($v['login'])) {
        $errors['login'] = 'Логін — коректна e-mail адреса на домені ' . ALLOWED_DOMAIN . ' (наприклад, name@' . ALLOWED_DOMAIN . ')';
    }
    if (!in_array($v['category'], [CAT_BUYER, CAT_SELLER], true)) {
        $errors['category'] = 'Оберіть категорію: Продавець або Покупець';
    }
    if (!valid_password($pass)) {
        $errors['password'] = 'Пароль: щонайменше 6 символів, хоча б одна латинська літера, одна цифра та один спецсимвол з набору ' . SPECIAL_CHARS;
    }
    if ($pass2 !== $pass || $pass2 === '') {
        $errors['password2'] = 'Паролі не збігаються';
    }
    // унікальність логіна в межах категорії
    if (!isset($errors['login']) && !isset($errors['category'])) {
        $dup = db_row("SELECT id FROM nazarchuk_users WHERE login = '" . esc($v['login']) . "' AND category = '" . esc($v['category']) . "'");
        if ($dup) {
            $errors['login'] = 'Користувач з таким логіном уже зареєстрований у категорії «' . $v['category'] . '»';
        }
    }

    if (!$errors) {
        $ok = db_run(sprintf(
            "INSERT INTO nazarchuk_users (first_name, last_name, login, password, category) VALUES ('%s','%s','%s','%s','%s')",
            esc($v['first_name']),
            esc($v['last_name']),
            esc($v['login']),
            esc($pass),
            esc($v['category'])
        ));
        // у будь-якому випадку повертаємось на стартову сторінку з повідомленням
        header('Location: index.php?m=' . ($ok ? 'reg_ok' : 'reg_fail'));
        exit;
    }
}

/** Класи та підказка для поля */
function fld_err(array $errors, string $k): string
{
    return isset($errors[$k]) ? "<div class='err'>" . h($errors[$k]) . "</div>" : '';
}
function fld_cls(array $errors, string $k): string
{
    return isset($errors[$k]) ? " class='invalid'" : '';
}

page_head('Реєстрація');
if ($errors) {
    echo "<div class='msg-err'>Помилка реєстрації, спробуйте ще раз</div>";
}
?>
<form method="post" class="box" autocomplete="off">
    <label>Ім'я (латиницею)
        <input type="text" name="first_name" value="<?= h($v['first_name']) ?>" <?= fld_cls($errors, 'first_name') ?>></label>
    <?= fld_err($errors, 'first_name') ?>

    <label>Прізвище (латиницею)
        <input type="text" name="last_name" value="<?= h($v['last_name']) ?>" <?= fld_cls($errors, 'last_name') ?>></label>
    <?= fld_err($errors, 'last_name') ?>

    <label>Логін (e-mail на домені <?= h(ALLOWED_DOMAIN) ?>)
        <input type="text" name="login" value="<?= h($v['login']) ?>" <?= fld_cls($errors, 'login') ?>></label>
    <?= fld_err($errors, 'login') ?>

    <label>Пароль
        <input type="password" name="password" <?= fld_cls($errors, 'password') ?>></label>
    <div class="hint">Не менше 6 символів: латинська літера, цифра і спецсимвол з набору <?= h(SPECIAL_CHARS) ?></div>
    <?= fld_err($errors, 'password') ?>

    <label>Повторіть пароль
        <input type="password" name="password2" <?= fld_cls($errors, 'password2') ?>></label>
    <?= fld_err($errors, 'password2') ?>

    <label>Категорія</label>
    <label class="opt"><input type="radio" name="category" value="<?= CAT_SELLER ?>" <?= $v['category'] === CAT_SELLER ? 'checked' : '' ?>> Продавець</label>
    <label class="opt"><input type="radio" name="category" value="<?= CAT_BUYER ?>" <?= $v['category'] === CAT_BUYER ? 'checked' : '' ?>> Покупець</label>
    <?= fld_err($errors, 'category') ?>

    <p><button class="btn" type="submit">Зареєструватися</button>
        <a class="btn gray" href="https://nazarchuk.infinityfree.io/index.php ">На стартову сторінку</a>
    </p>
</form>
<?php page_foot(); ?>