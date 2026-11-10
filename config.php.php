<?php
$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=bombo_sacco;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
