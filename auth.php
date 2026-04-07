<?php
session_start();

// Si le badge 'admin' n'existe pas, on renvoie vers le login
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}
?>