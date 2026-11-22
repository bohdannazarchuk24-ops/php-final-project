<?php
// Нагадування пароля: e-mail + одна або кілька категорій.
require_once __DIR__ . '/nazarchuk_init.php';

$result = [];
$error = '';
$login = '';
$cats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = strtolower(trim($_POST['login'] ?? ''));
    $cats = array_values(array_intersect([CAT_BUYER, CAT_SELLER], (array)($_POST['categories'] ?? [])));
    if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введіть коректну e-mail адресу';
    } elseif (!$cats) {
        $error = 'Оберіть хоча б одну категорію';
    } else {
        foreach ($cats as $c) {
            $u = db_row("SELECT password FROM nazarchuk_users WHERE login = '" . esc($login) . "' AND category = '" . esc($c) . "'");
            $result[$c] = $u ? $u['password'] : null;
        }
    }
}

page_head('Забули пароль');
if ($error) {
    echo "<div class='msg-err'>" . h($error) . "</div>";
}
foreach ($result as $c => $pw) {
    if ($pw !== null) {
        echo "<div class='msg-ok'>Пароль (" . h($c) . "): <b>" . h($pw) . "</b></div>";
    } else {
        echo "<div class='msg-err'>Користувача " . h($login) . " у категорії «" . h($c) . "» не знайдено</div>";
    }
}
?>
<form method="post" class="box">
    <label>Логін (e-mail)
        <input type="text" name="login" value="<?= h($login) ?>"></label>
    <label>Категорія (можна обрати обидві)</label>
    <label class="opt"><input type="checkbox" name="categories[]" value="<?= CAT_BUYER ?>" <?= in_array(CAT_BUYER, $cats) ? 'checked' : '' ?>> Покупець</label>
    <label class="opt"><input type="checkbox" name="categories[]" value="<?= CAT_SELLER ?>" <?= in_array(CAT_SELLER, $cats) ? 'checked' : '' ?>> Продавець</label>
    <p><button class="btn" type="submit">Нагадати</button>
        <a class="btn gray" href="https://nazarchuk.infinityfree.io/index.php ">На стартову сторінку</a>
    </p>
</form>
<?php page_foot(); ?>