<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier la connexion du chauffeur
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'chauffeur'
) {
    $_SESSION['erreur'] = "Connectez-vous avec un compte chauffeur.";
    header('Location: ../auth/connexion.php');
    exit;
}

// Autoriser uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

// Vérifier l'identifiant de la course
$rideId = filter_input(INPUT_POST, 'ride_id', FILTER_VALIDATE_INT);

if (!$rideId || $rideId <= 0) {
    $_SESSION['erreur'] = "Identifiant de course invalide.";
    header('Location: ../dashboard/chauffeur.php');
    exit;
}

$driverId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Verrouiller la course pendant la transaction
    $stmt = $pdo->prepare(
        "SELECT id, chauffeur_id, statut
         FROM rides
         WHERE id = ?
         FOR UPDATE"
    );
    $stmt->execute([$rideId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        throw new RuntimeException("Cette course n'existe pas.");
    }

    if ((int) $ride['chauffeur_id'] !== $driverId) {
        throw new RuntimeException(
            "Vous n'êtes pas le chauffeur de cette course."
        );
    }

    if ($ride['statut'] !== 'en_cours') {
        throw new RuntimeException(
            "Cette course n'est pas en cours."
        );
    }

    // Marquer la course comme terminée
    $stmt = $pdo->prepare(
        "UPDATE rides
         SET statut = 'terminee'
         WHERE id = ?
           AND chauffeur_id = ?
           AND statut = 'en_cours'"
    );
    $stmt->execute([$rideId, $driverId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Impossible de terminer cette course."
        );
    }

    // Rendre le chauffeur disponible pour une nouvelle course
    $stmt = $pdo->prepare(
        "UPDATE users
         SET disponible = 1
         WHERE id = ?
           AND role = 'chauffeur'"
    );
    $stmt->execute([$driverId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Impossible de mettre à jour la disponibilité du chauffeur."
        );
    }

    $pdo->commit();

    $_SESSION['succes'] =
        "Course terminée avec succès. Vous êtes de nouveau disponible.";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['erreur'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : "Une erreur est survenue lors de la fin de la course.";

    error_log("Erreur finish_ride.php : " . $e->getMessage());
}

header('Location: ../dashboard/chauffeur.php');
exit;