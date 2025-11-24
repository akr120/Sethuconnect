<?php
// config.php - DB config + basic settings
// Choose driver: sqlite or mysql
return (object)[
    'db_driver' => 'sqlite', // 'sqlite' or 'mysql'
    // sqlite config
    'sqlite_path' => __DIR__ . '/dpi.db',
    // mysql config (used if db_driver == 'mysql')
    'mysql_dsn' => 'mysql:host=127.0.0.1;dbname=dpi_db;charset=utf8mb4',
    'mysql_user' => 'root',
    'mysql_pass' => '',
    'base_url' => '/', // adjust if served in subfolder, e.g. '/dpi-php-demo/'
    'secret' => 'dev_secret_change_in_prod'
];
