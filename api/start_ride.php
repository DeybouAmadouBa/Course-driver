<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est connecté comme chauffeur
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'chauffeur'
) {
    $_SESSION['erreur'] = "Vous devez être connecté comme chauffeur.";
    header('Location: ../auth/connexion.php');
    exit;
}

// Accepter uniquement les requêtes POST
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

    // Verrouiller la course pour éviter deux démarrages simultanés
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
            "Cette course ne vous est pas attribuée."
        );
    }

    if ($ride['statut'] !== 'acceptee') {
        throw new RuntimeException(
            "Seule une course acceptée peut être démarrée."
        );
    }

    // Démarrer la course uniquement si elle est encore acceptée
    $stmt = $pdo->prepare(
        "UPDATE rides
         SET statut = 'en_cours'
         WHERE id = ?
           AND chauffeur_id = ?
           AND statut = 'acceptee'"
    );
    $stmt->execute([$rideId, $driverId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Impossible de démarrer cette course."
        );
    }

    $pdo->commit();

    $_SESSION['succes'] = "Course démarrée avec succès.";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['erreur'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : "Une erreur est survenue lors du démarrage de la course.";

    error_log("Erreur start_ride.php : " . $e->getMessage());
}

header('Location: ../dashboard/chauffeur.php');
exit;