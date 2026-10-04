<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est connecté comme passager
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'passager'
) {
    $_SESSION['erreur'] = "Connectez-vous avec un compte passager.";
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
    header('Location: ../dashboard/mes_courses.php');
    exit;
}

$passengerId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Verrouiller la course et vérifier son propriétaire
    $stmt = $pdo->prepare(
        "SELECT id, chauffeur_id, statut
         FROM rides
         WHERE id = ?
           AND passager_id = ?
         FOR UPDATE"
    );

    $stmt->execute([$rideId, $passengerId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        throw new RuntimeException(
            "Cette course n'existe pas ou ne vous appartient pas."
        );
    }

    // Une course commencée ou terminée ne peut pas être annulée ici
    if (!in_array($ride['statut'], ['en_attente', 'acceptee'], true)) {
        throw new RuntimeException(
            "Cette course ne peut plus être annulée."
        );
    }

    // Annuler la course
    $stmt = $pdo->prepare(
        "UPDATE rides
         SET statut = 'annulee'
         WHERE id = ?
           AND passager_id = ?
           AND statut IN ('en_attente', 'acceptee')"
    );

    $stmt->execute([$rideId, $passengerId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "L'annulation de la course a échoué."
        );
    }

    // Libérer le chauffeur si la course avait déjà été acceptée
    if (
        $ride['statut'] === 'acceptee' &&
        !empty($ride['chauffeur_id'])
    ) {
        $stmt = $pdo->prepare(
            "UPDATE users
             SET disponible = 1
             WHERE id = ?
               AND role = 'chauffeur'"
        );

        $stmt->execute([(int) $ride['chauffeur_id']]);
    }

    $pdo->commit();

    $_SESSION['succes'] = "Votre course a été annulée avec succès.";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['erreur'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : "Une erreur est survenue lors de l'annulation de la course.";

    error_log("Erreur cancel_ride.php : " . $e->getMessage());
}

header('Location: ../dashboard/mes_courses.php');
exit;