<?php
declare(strict_types=1);

// Bu dosyayı includes/config.php olarak kopyalayın ve kendi bilgilerinizle düzenleyin.
// includes/config.php git'e eklenmez (.gitignore). Aşağıdaki değerler WAMP varsayılanlarıdır.

define('APP_NAME', 'SiparişTakip Mini');
define('APP_URL', '/siparistakip-mini');

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'siparistakip_mini');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

date_default_timezone_set('Europe/Istanbul');
