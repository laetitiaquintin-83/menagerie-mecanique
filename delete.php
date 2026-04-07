<?php include 'auth.php'; ?>
<?php
include 'connexion.php';

// On vérifie qu'on a bien un ID à supprimer
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // La commande SQL pour supprimer
    $req = $db->prepare("DELETE FROM creatures WHERE id = ?");
    $req->execute([$id]);
}

// Une fois fini, on repart direct à l'accueil
header('Location: index.php');
exit();
?>