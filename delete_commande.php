<?php
session_start();
include 'connexion.php';

// Sécurité : Seul l'admin peut supprimer
if(!isset($_SESSION['admin'])) { exit("Accès refusé"); }

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $req = $db->prepare("DELETE FROM commandes_speciales WHERE id = ?");
    $req->execute([$id]);
}

// On revient au courrier
header('Location: commandes.php');
exit();
?>