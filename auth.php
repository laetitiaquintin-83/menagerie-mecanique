<?php
session_start();
require_once 'helpers.php';

// Si le badge 'admin' n'existe pas, on renvoie vers le login
require_admin_connection();
?>