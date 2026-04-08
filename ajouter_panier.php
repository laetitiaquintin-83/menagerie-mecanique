<?php
session_start();
if (isset($_GET['id'])) {
    $_SESSION['panier'][(int)$_GET['id']] = [
        'nom' => $_GET['nom'],
        'prix' => (float)$_GET['prix']
    ];
}
header('Location: panier.php');