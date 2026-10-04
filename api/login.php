<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

// Accepter uniquement les formulaires POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/connexion.php');
    exit;
}

// Récupérer les informations du formulaire
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $password === ''
) {
    $_SESSION['erreur'] = 'Veuillez saisir une adresse e-mail et un mot de passe valides.';
    header('Location: ../auth/connexion.php');
    exit;
}

try {
    // Rechercher l'utilisateur
    $sql = 'SELECT id, nom, email, telephone, password, role, disponible
            FROM users
            WHERE email = :email
            LIMIT 1';

    $requete = $pdo->prepare($sql);
    $requete->execute(['email' => $email]);

    $utilisateur = $requete->fetch();

    // Vérifier le mot de passe
    if (!$utilisateur || !password_verify($password, $utilisateur['password'])) {
        $_SESSION['erreur'] = 'Adresse e-mail ou mot de passe incorrect.';
        header('Location: ../auth/connexion.php');
        exit;
    }

    // Vérifier le rôle du compte
    if (!in_array($utilisateur['role'], ['passager', 'chauffeur'], true)) {
        $_SESSION['erreur'] = 'Le rôle de ce compte est invalide.';
        header('Location: ../auth/connexion.php');
        exit;
    }

    // Renouveler l'identifiant de session après connexion
    session_regenerate_id(true);

    // Enregistrer les informations utiles en session
    $_SESSION['user_id'] = (int) $utilisateur['id'];
    $_SESSION['nom'] = $utilisateur['nom'];
    $_SESSION['email'] = $utilisateur['email'];
    $_SESSION['role'] = $utilisateur['role'];

    // Rediriger selon le rôle
    if ($utilisateur['role'] === 'chauffeur') {
        header('Location: ../dashboard/chauffeur.php');
    } else {
        header('Location: ../dashboard/passager.php');
    }

    exit;

} catch (PDOException $e) {
    error_log('Erreur de connexion utilisateur : ' . $e->getMessage());

    $_SESSION['erreur'] = 'Une erreur est survenue. Réessayez plus tard.';
    header('Location: ../auth/connexion.php');
    exit;
}