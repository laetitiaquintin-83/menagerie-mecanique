<?php
include 'connexion.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $type = $_POST['type'];
    $projet = htmlspecialchars($_POST['projet']);
    $budget = intval($_POST['budget']);

    $ins = $db->prepare("INSERT INTO commandes_speciales (nom_client, email_client, type_chimere, description_projet, budget_estime) VALUES (?,?,?,?,?)");
    $ins->execute([$nom, $email, $type, $projet, $budget]);

    echo "<script>alert('Votre commande a été déposée sur le bureau de l\'Inventrice !'); window.location.href='index.php';</script>";
}
?>