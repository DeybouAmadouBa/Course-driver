<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est un chauffeur connecté
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

// Vérifier l'identifiant du véhicule
$vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);

if (!$vehicleId || $vehicleId <= 0) {
    $_SESSION['erreur'] = "Identifiant de véhicule invalide.";
    header('Location: ../dashboard/vehicle.php');
    exit;
}

$driverId = (int) $_SESSION['user_id'];

try {
    // Supprimer uniquement un véhicule appartenant au chauffeur connecté
    $stmt = $pdo->prepare(
        "DELETE FROM vehicles
         WHERE id = ?
           AND chauffeur_id = ?"
    );

    $stmt->execute([$vehicleId, $driverId]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['erreur'] =
            "Véhicule introuvable ou vous n'en êtes pas le propriétaire.";
    } else {
        $_SESSION['succes'] = "Le véhicule a été supprimé avec succès.";
    }

} catch (PDOException $e) {
    error_log("Erreur delete_vehicle.php : " . $e->getMessage());

    $_SESSION['erreur'] =
        "Impossible de supprimer ce véhicule. Vérifiez qu'il n'est pas utilisé par une autre donnée.";
}

header('Location: ../dashboard/vehicle.php');
exit;