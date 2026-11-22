<?php
// Вихід: завершуємо сеанс і повертаємося на стартову сторінку.
require_once __DIR__ . '/nazarchuk_init.php';
do_logout();
header('Location: index.php');
exit;
