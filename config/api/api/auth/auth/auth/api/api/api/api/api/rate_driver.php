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

// Accepter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

// Récupérer et valider les données
$rideId = filter_input(INPUT_POST, 'ride_id', FILTER_VALIDATE_INT);
$note = filter_input(INPUT_POST, 'note', FILTER_VALIDATE_INT);
$commentaire = trim($_POST['commentaire'] ?? '');

if (!$rideId || $rideId <= 0) {
    $_SESSION['erreur'] = "Identifiant de course invalide.";
    header('Location: ../dashboard/mes_courses.php');
    exit;
}

if ($note === false || $note === null || $note < 1 || $note > 5) {
    $_SESSION['erreur'] = "La note doit être comprise entre 1 et 5.";
    header('Location: ../dashboard/mes_courses.php');
    exit;
}

if (strlen($commentaire) > 1000) {
    $_SESSION['erreur'] = "Le commentaire ne doit pas dépasser 1000 caractères.";
    header('Location: ../dashboard/mes_courses.php');
    exit;
}

$passengerId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Vérifier que la course appartient au passager et est terminée
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

    if ($ride['statut'] !== 'terminee') {
        throw new RuntimeException(
            "Vous pouvez noter uniquement une course terminée."
        );
    }

    if (empty($ride['chauffeur_id'])) {
        throw new RuntimeException(
            "Aucun chauffeur n'est associé à cette course."
        );
    }

    // Empêcher une seconde évaluation de la même course
    $stmt = $pdo->prepare(
        "SELECT id
         FROM ratings
         WHERE ride_id = ?
           AND passenger_id = ?
         LIMIT 1"
    );
    $stmt->execute([$rideId, $passengerId]);

    if ($stmt->fetch()) {
        throw new RuntimeException(
            "Vous avez déjà noté cette course."
        );
    }

    // Enregistrer la note et le commentaire
    $stmt = $pdo->prepare(
        "INSERT INTO ratings
            (ride_id, passenger_id, driver_id, note, comment)
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $rideId,
        $passengerId,
        (int) $ride['chauffeur_id'],
        $note,
        $commentaire !== '' ? $commentaire : null
    ]);

    $pdo->commit();

    $_SESSION['succes'] = "Merci ! Votre avis a été enregistré.";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['erreur'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : "Une erreur est survenue lors de l'enregistrement de votre avis.";

    error_log("Erreur rate_driver.php : " . $e->getMessage());
}

header('Location: ../dashboard/mes_courses.php');
exit;