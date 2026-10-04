<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/inscription.php');
    exit;
}

// Récupération des données du formulaire
$nom = trim($_POST['nom'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$telephone = trim($_POST['telephone'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'passager';

// Validation
if (
    $nom === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($password) < 8 ||
    !in_array($role, ['passager', 'chauffeur'], true)
) {
    $_SESSION['erreur'] =
        'Vérifiez les informations saisies. Le mot de passe doit contenir au moins 8 caractères.';

    header('Location: ../auth/inscription.php');
    exit;
}

if (strlen($nom) > 100 || strlen($email) > 150 || strlen($telephone) > 30) {
    $_SESSION['erreur'] = 'Certaines informations sont trop longues.';
    header('Location: ../auth/inscription.php');
    exit;
}

try {
    // Vérifier si l'adresse e-mail existe déjà
    $verification = $pdo->prepare(
        'SELECT id FROM users WHERE email = :email LIMIT 1'
    );

    $verification->execute(['email' => $email]);

    if ($verification->fetch()) {
        $_SESSION['erreur'] = 'Cette adresse e-mail est déjà utilisée.';
        header('Location: ../auth/inscription.php');
        exit;
    }

    // Hacher le mot de passe avant de le stocker
    $motDePasseHache = password_hash($password, PASSWORD_DEFAULT);

    // Créer le compte
    $sql = 'INSERT INTO users
            (nom, email, telephone, password, role, disponible)
            VALUES
            (:nom, :email, :telephone, :password, :role, 0)';

    $requete = $pdo->prepare($sql);

    $requete->execute([
        'nom' => $nom,
        'email' => $email,
        'telephone' => $telephone,
        'password' => $motDePasseHache,
        'role' => $role,
    ]);

    $_SESSION['succes'] = 'Votre compte a été créé. Vous pouvez vous connecter.';
    header('Location: ../auth/connexion.php');
    exit;

} catch (PDOException $e) {
    error_log('Erreur inscription : ' . $e->getMessage());

    $_SESSION['erreur'] = 'Une erreur est survenue pendant la création du compte.';
    header('Location: ../auth/inscription.php');
    exit;
}