<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est un chauffeur connecté.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'chauffeur'
) {
    $_SESSION['erreur'] = 'Connectez-vous avec un compte chauffeur.';
    header('Location: ../auth/connexion.php');
    exit;
}

// Accepter uniquement les requêtes POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard/chauffeur.php');
    exit;
}

$rideId = filter_input(INPUT_POST, 'ride_id', FILTER_VALIDATE_INT);

if (!$rideId || $rideId < 1) {
    $_SESSION['erreur'] = 'Identifiant de course invalide.';
    header('Location: ../dashboard/chauffeur.php');
    exit;
}

$chauffeurId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Verrouiller le compte du chauffeur pour éviter deux acceptations simultanées.
    $sqlChauffeur = 'SELECT id, disponible
                     FROM users
                     WHERE id = :id AND role = :role
                     FOR UPDATE';

    $stmtChauffeur = $pdo->prepare($sqlChauffeur);
    $stmtChauffeur->execute([
        'id' => $chauffeurId,
        'role' => 'chauffeur',
    ]);

    $chauffeur = $stmtChauffeur->fetch();

    if (!$chauffeur) {
        $pdo->rollBack();
        $_SESSION['erreur'] = 'Compte chauffeur introuvable.';
        header('Location: ../auth/logout.php');
        exit;
    }

    if ((int) $chauffeur['disponible'] !== 1) {
        $pdo->rollBack();
        $_SESSION['erreur'] = 'Vous devez être disponible pour accepter une course.';
        header('Location: ../dashboard/chauffeur.php');
        exit;
    }

    // Vérifier que le chauffeur n'a pas déjà une course active.
    $sqlActive = "SELECT id
                  FROM rides
                  WHERE chauffeur_id = :chauffeur_id
                    AND statut IN ('acceptee', 'en_cours')
                  LIMIT 1
                  FOR UPDATE";

    $stmtActive = $pdo->prepare($sqlActive);
    $stmtActive->execute(['chauffeur_id' => $chauffeurId]);

    if ($stmtActive->fetch()) {
        $pdo->rollBack();
        $_SESSION['erreur'] = 'Terminez votre course actuelle avant d’en accepter une autre.';
        header('Location: ../dashboard/chauffeur.php');
        exit;
    }

    // Verrouiller la course demandée.
    $sqlCourse = "SELECT id, statut
                  FROM rides
                  WHERE id = :ride_id
                  FOR UPDATE";

    $stmtCourse = $pdo->prepare($sqlCourse);
    $stmtCourse->execute(['ride_id' => $rideId]);

    $course = $stmtCourse->fetch();

    if (!$course || $course['statut'] !== 'en_attente') {
        $pdo->rollBack();
        $_SESSION['erreur'] = 'Cette course n’est plus disponible.';
        header('Location: ../dashboard/chauffeur.php');
        exit;
    }

    // Affecter la course au chauffeur.
    $sqlUpdate = "UPDATE rides
                  SET chauffeur_id = :chauffeur_id,
                      statut = 'acceptee'
                  WHERE id = :ride_id
                    AND statut = 'en_attente'";

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        'chauffeur_id' => $chauffeurId,
        'ride_id' => $rideId,
    ]);

    if ($stmtUpdate->rowCount() !== 1) {
        $pdo->rollBack();
        $_SESSION['erreur'] = 'La course a déjà été acceptée par un autre chauffeur.';
        header('Location: ../dashboard/chauffeur.php');
        exit;
    }

    // Rendre le chauffeur indisponible pendant la course.
    $stmtDisponibilite = $pdo->prepare(
        'UPDATE users SET disponible = 0 WHERE id = :id'
    );
    $stmtDisponibilite->execute(['id' => $chauffeurId]);

    $pdo->commit();

    $_SESSION['succes'] = 'Course acceptée avec succès.';
    header('Location: ../dashboard/chauffeur.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erreur acceptation course : ' . $e->getMessage());

    $_SESSION['erreur'] = 'Impossible d’accepter cette course pour le moment.';
    header('Location: ../dashboard/chauffeur.php');
    exit;
}