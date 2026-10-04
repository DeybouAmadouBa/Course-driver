<?php

declare(strict_types=1);

session_start();

// Vider les données de session
$_SESSION = [];

// Supprimer le cookie de session s'il est utilisé
if (ini_get('session.use_cookies')) {
    $parametres = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametres['path'],
        $parametres['domain'],
        $parametres['secure'],
        $parametres['httponly']
    );
}

// Détruire la session
session_destroy();

// Retourner à la page de connexion
header('Location: connexion.php');
exit;