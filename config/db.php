<?php
$host = "localhost";
$user = "growfast_grow_crm";
$pass = 'Qt5vX$sa)onWJ1kG';
$db   = "growfast_grow_crm";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>