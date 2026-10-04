<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

// Seul un passager connecté peut commander une course.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'passager'
) {
    $_SESSION['erreur'] = 'Connectez-vous avec un compte passager pour commander.';
    header('Location: ../auth/connexion.php');
    exit;
}

// Accepter uniquement les requêtes POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard/commander.php');
    exit;
}

// Récupérer les données du formulaire.
$depart = trim($_POST['depart'] ?? '');
$destination = trim($_POST['destination'] ?? '');
$distance = filter_var(
    $_POST['distance'] ?? null,
    FILTER_VALIDATE_FLOAT
);
$prix = filter_var(
    $_POST['prix'] ?? null,
    FILTER_VALIDATE_FLOAT
);

// Vérifier les informations.
if (
    $depart === '' ||
    $destination === '' ||
    strlen($depart) > 255 ||
    strlen($destination) > 255 ||
    $distance === false ||
    $distance === null ||
    $distance <= 0 ||
    $prix === false ||
    $prix === null ||
    $prix < 0
) {
    $_SESSION['erreur'] = 'Veuillez vérifier le départ, la destination, la distance et le prix.';
    header('Location: ../dashboard/commander.php');
    exit;
}

try {
    // Vérifier que le passager existe toujours.
    $verification = $pdo->prepare(
        'SELECT id FROM users
         WHERE id = :id AND role = :role
         LIMIT 1'
    );

    $verification->execute([
        'id' => (int) $_SESSION['user_id'],
        'role' => 'passager',
    ]);

    if (!$verification->fetch()) {
        $_SESSION['erreur'] = 'Votre compte passager est introuvable.';
        header('Location: ../auth/logout.php');
        exit;
    }

    // Enregistrer la course en attente d'un chauffeur.
    $sql = 'INSERT INTO rides
            (passager_id, depart, destination, distance, prix, statut)
            VALUES
            (:passager_id, :depart, :destination, :distance, :prix, :statut)';

    $requete = $pdo->prepare($sql);

    $requete->execute([
        'passager_id' => (int) $_SESSION['user_id'],
        'depart' => $depart,
        'destination' => $destination,
        'distance' => $distance,
        'prix' => $prix,
        'statut' => 'en_attente',
    ]);

    $_SESSION['succes'] = 'Votre course a été commandée avec succès. En attente d’un chauffeur.';
    header('Location: ../dashboard/mes_courses.php');
    exit;

} catch (PDOException $e) {
    error_log('Erreur création course : ' . $e->getMessage());

    $_SESSION['erreur'] = 'Impossible de commander la course pour le moment.';
    header('Location: ../dashboard/commander.php');
    exit;
}