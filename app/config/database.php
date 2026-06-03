<?php
return [
    'host' => getenv('SPMT_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('SPMT_DB_PORT') ?: '3306',
    'database' => getenv('SPMT_DB_NAME') ?: 'db_spmt_app_backend',
    'username' => getenv('SPMT_DB_USER') ?: 'root',
    'password' => getenv('SPMT_DB_PASS') ?: '',
    'charset' => getenv('SPMT_DB_CHARSET') ?: 'utf8mb4',
];
