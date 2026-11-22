<?php
// Вхід: логін, пароль і категорія (радіокнопки).
require_once __DIR__ . '/nazarchuk_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = strtolower(trim($_POST['login'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $cat   = $_POST['category'] ?? '';

    $u = db_row("SELECT * FROM nazarchuk_users WHERE login = '" . esc($login) . "' AND category = '" . esc($cat) . "'");
    if ($u && hash_equals($u['password'], $pass)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        $_SESSION['name'] = $u['first_name'] . ' ' . $u['last_name'];
        $_SESSION['category'] = $u['category'];
        header('Location: nazarchuk_shop.php');
    } else {
        header('Location: index.php?m=login_err');   // залишаємось на стартовій сторінці
    }
    exit;
}

page_head('Вхід');
?>
<form method="post" class="box">
    <label>Логін (e-mail)
        <input type="text" name="login" required></label>
    <label>Пароль
        <input type="password" name="password" required></label>
    <label>Категорія</label>
    <label class="opt"><input type="radio" name="category" value="<?= CAT_BUYER ?>" checked> Покупець</label>
    <label class="opt"><input type="radio" name="category" value="<?= CAT_SELLER ?>"> Продавець</label>
    <p><button class="btn" type="submit">Увійти</button>
        <a class="btn gray" href="https://nazarchuk.infinityfree.io/index.php ">На стартову сторінку</a>
    </p>
</form>
<?php page_foot(); ?>