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

$chauffeurId = (int) $_SESSION['user_id'];
$nom = $_SESSION['nom'] ?? 'Chauffeur';

function h($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statutLabel(string $statut): string
{
    $labels = [
        'en_attente' => 'En attente',
        'acceptee' => 'Acceptée',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée'
    ];

    return $labels[$statut] ?? $statut;
}

try {
    // Charger les informations du chauffeur
    $stmt = $pdo->prepare(
        "SELECT nom, telephone, disponible
         FROM users
         WHERE id = ?
           AND role = 'chauffeur'
         LIMIT 1"
    );
    $stmt->execute([$chauffeurId]);
    $chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chauffeur) {
        throw new RuntimeException("Compte chauffeur introuvable.");
    }

    // Compter les courses du chauffeur
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN statut = 'acceptee' THEN 1 ELSE 0 END) AS acceptees,
            SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) AS en_cours,
            SUM(CASE WHEN statut = 'terminee' THEN 1 ELSE 0 END) AS terminees
         FROM rides
         WHERE chauffeur_id = ?"
    );
    $stmt->execute([$chauffeurId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les courses encore disponibles
    $stmt = $pdo->query(
        "SELECT
            r.id,
            r.passager_id,
            r.depart,
            r.destination,
            r.distance,
            r.prix,
            r.created_at,
            u.nom AS passager_nom
         FROM rides r
         INNER JOIN users u ON u.id = r.passager_id
         WHERE r.statut = 'en_attente'
         ORDER BY r.id ASC
         LIMIT 50"
    );
    $coursesDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les courses de ce chauffeur non terminées
    $stmt = $pdo->prepare(
        "SELECT
            r.id,
            r.depart,
            r.destination,
            r.distance,
            r.prix,
            r.statut,
            u.nom AS passager_nom,
            u.telephone AS passager_telephone
         FROM rides r
         INNER JOIN users u ON u.id = r.passager_id
         WHERE r.chauffeur_id = ?
           AND r.statut IN ('acceptee', 'en_cours')
         ORDER BY r.id DESC"
    );
    $stmt->execute([$chauffeurId]);
    $mesCoursesActives = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('Erreur dashboard chauffeur : ' . $e->getMessage());

    $chauffeur = [
        'nom' => $nom,
        'telephone' => '',
        'disponible' => 0
    ];

    $stats = [
        'total' => 0,
        'acceptees' => 0,
        'en_cours' => 0,
        'terminees' => 0
    ];

    $coursesDisponibles = [];
    $mesCoursesActives = [];

    $_SESSION['erreur'] =
        "Impossible de charger toutes les informations. Vérifiez la base de données.";
}

$disponible = (int) ($chauffeur['disponible'] ?? 0) === 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Espace chauffeur - Course Driver</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .driver-banner {
            padding: 28px;
            margin-bottom: 28px;
            color: #fff;
            background: linear-gradient(135deg, #166534, #15803d);
            border-radius: 16px;
        }

        .driver-banner h1 {
            margin-top: 0;
        }

        .availability {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-weight: 700;
        }

        .availability-on {
            color: #166534;
            background: #dcfce7;
        }

        .availability-off {
            color: #991b1b;
            background: #fee2e2;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin: 24px 0 35px;
        }

        .stat-card {
            padding: 22px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .stat-card p {
            margin: 0;
            color: #64748b;
        }

        .stat-card strong {
            display: block;
            margin-top: 8px;
            color: #166534;
            font-size: 28px;
        }

        .ride-card {
            margin-bottom: 18px;
            padding: 22px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .ride-card h3 {
            margin-top: 0;
        }

        .ride-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 18px;
            margin: 18px 0;
        }

        .ride-info p {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .ride-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .empty-state {
            padding: 25px 15px;
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 750px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 500px) {
            .stats-grid,
            .ride-info {
                grid-template-columns: 1fr;
            }

            .ride-actions {
                flex-direction: column;
            }

            .ride-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="site-header">
    <a href="../index.php" class="brand">
        Course<span>Driver</span>
    </a>

    <nav class="nav-links">
        <a href="chauffeur.php">Tableau de bord</a>
        <a href="vehicle.php">Mes véhicules</a>
        <a href="../auth/logout.php" class="btn btn-primary">
            Déconnexion
        </a>
    </nav>
</header>

<main class="page-container">

    <?php if (!empty($_SESSION['succes'])): ?>
        <div class="alert alert-success">
            <?= h($_SESSION['succes']) ?>
        </div>
        <?php unset($_SESSION['succes']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['erreur'])): ?>
        <div class="alert alert-danger">
            <?= h($_SESSION['erreur']) ?>
        </div>
        <?php unset($_SESSION['erreur']); ?>
    <?php endif; ?>

    <section class="driver-banner">
        <h1>Bonjour <?= h($chauffeur['nom'] ?? $nom) ?> ! 🚗</h1>

        <p>
            Bienvenue dans votre espace chauffeur.
            Consultez les demandes, gérez vos courses et suivez votre activité.
        </p>

        <?php if ($disponible): ?>
            <span class="availability availability-on">
                ● Disponible
            </span>
        <?php else: ?>
            <span class="availability availability-off">
                ● Indisponible
            </span>
        <?php endif; ?>

        <p>
            Téléphone :
            <?= h($chauffeur['telephone'] ?? 'Non renseigné') ?>
        </p>
    </section>

    <section>
        <h2>Mon activité</h2>

        <div class="stats-grid">
            <article class="stat-card">
                <p>Total attribué</p>
                <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
            </article>

            <article class="stat-card">
                <p>Acceptées</p>
                <strong><?= (int) ($stats['acceptees'] ?? 0) ?></strong>
            </article>

            <article class="stat-card">
                <p>En cours</p>
                <strong><?= (int) ($stats['en_cours'] ?? 0) ?></strong>
            </article>

            <article class="stat-card">
                <p>Terminées</p>
                <strong><?= (int) ($stats['terminees'] ?? 0) ?></strong>
            </article>
        </div>
    </section>

    <section>
        <h2>Mes courses actives</h2>

        <?php if (empty($mesCoursesActives)): ?>
            <div class="card empty-state">
                <p>Vous n'avez aucune course active pour le moment.</p>
            </div>
        <?php else: ?>

            <?php foreach ($mesCoursesActives as $course): ?>
                <article class="ride-card">
                    <h3>Course n°<?= (int) $course['id'] ?></h3>

                    <span class="status status-<?= h($course['statut']) ?>">
                        <?= h(statutLabel($course['statut'])) ?>
                    </span>

                    <div class="ride-info">
                        <p>
                            <strong>Passager :</strong>
                            <?= h($course['passager_nom']) ?>
                        </p>

                        <p>
                            <strong>Téléphone :</strong>
                            <?= h($course['passager_telephone']) ?>
                        </p>

                        <p>
                            <strong>Départ :</strong>
                            <?= h($course['depart']) ?>
                        </p>

                        <p>
                            <strong>Destination :</strong>
                            <?= h($course['destination']) ?>
                        </p>

                        <p>
                            <strong>Distance :</strong>
                            <?= h($course['distance']) ?> km
                        </p>

                        <p>
                            <strong>Prix :</strong>
                            <?= number_format((float) $course['prix'], 0, ',', ' ') ?>
                            FCFA
                        </p>
                    </div>

                    <div class="ride-actions">
                        <?php if ($course['statut'] === 'acceptee'): ?>
                            <form action="../api/start_ride.php" method="POST">
                                <input
                                    type="hidden"
                                    name="ride_id"
                                    value="<?= (int) $course['id'] ?>"
                                >

                                <button type="submit" class="btn btn-primary">
                                    ▶ Démarrer la course
                                </button>
                            </form>
                        <?php elseif ($course['statut'] === 'en_cours'): ?>
                            <form
                                action="../api/finish_ride.php"
                                method="POST"
                                onsubmit="return confirm('Confirmer la fin de cette course ?');"
                            >
                                <input
                                    type="hidden"
                                    name="ride_id"
                                    value="<?= (int) $course['id'] ?>"
                                >

                                <button type="submit" class="btn btn-success">
                                    ✓ Terminer la course
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <section>
        <h2>Demandes de courses disponibles</h2>

        <?php if (!$disponible): ?>
            <div class="alert alert-warning">
                Vous êtes actuellement indisponible.
                Terminez votre course active avant d'en accepter une nouvelle.
            </div>

        <?php elseif (empty($coursesDisponibles)): ?>
            <div class="card empty-state">
                <p>Aucune nouvelle demande de course pour le moment.</p>
                <p>Revenez un peu plus tard pour consulter les demandes.</p>
            </div>

        <?php else: ?>

            <?php foreach ($coursesDisponibles as $course): ?>
                <article class="ride-card">
                    <h3>Demande n°<?= (int) $course['id'] ?></h3>

                    <div class="ride-info">
                        <p>
                            <strong>Passager :</strong>
                            <?= h($course['passager_nom']) ?>
                        </p>

                        <p>
                            <strong>Départ :</strong>
                            <?= h($course['depart']) ?>
                        </p>

                        <p>
                            <strong>Destination :</strong>
                            <?= h($course['destination']) ?>
                        </p>

                        <p>
                            <strong>Distance :</strong>
                            <?= h($course['distance']) ?> km
                        </p>

                        <p>
                            <strong>Prix :</strong>
                            <?= number_format((float) $course['prix'], 0, ',', ' ') ?>
                            FCFA
                        </p>
                    </div>

                    <div class="ride-actions">
                        <form
                            action="../api/accept_ride.php"
                            method="POST"
                            onsubmit="return confirm('Voulez-vous accepter cette course ?');"
                        >
                            <input
                                type="hidden"
                                name="ride_id"
                                value="<?= (int) $course['id'] ?>"
                            >

                            <button type="submit" class="btn btn-primary">
                                ✓ Accepter la course
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <section class="dashboard-actions">
        <a href="vehicle.php" class="btn btn-secondary">
            🚘 Gérer mes véhicules
        </a>
    </section>

</main>

<footer class="site-footer">
    <p>
        &copy; <?= date('Y') ?> Course Driver.
        Tous droits réservés.
    </p>
</footer>

</body>
</html>