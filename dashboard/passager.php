<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier la connexion du passager
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'passager'
) {
    $_SESSION['erreur'] = "Connectez-vous avec un compte passager.";
    header('Location: ../auth/connexion.php');
    exit;
}

$passagerId = (int) $_SESSION['user_id'];
$nom = $_SESSION['nom'] ?? 'Passager';

try {
    // Compter les courses du passager
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) AS en_attente,
            SUM(CASE WHEN statut = 'acceptee' THEN 1 ELSE 0 END) AS acceptees,
            SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) AS en_cours,
            SUM(CASE WHEN statut = 'terminee' THEN 1 ELSE 0 END) AS terminees
         FROM rides
         WHERE passager_id = ?"
    );

    $stmt->execute([$passagerId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les cinq courses les plus récentes
    $stmt = $pdo->prepare(
        "SELECT
            r.id,
            r.depart,
            r.destination,
            r.prix,
            r.statut,
            r.created_at,
            u.nom AS chauffeur_nom
         FROM rides r
         LEFT JOIN users u ON u.id = r.chauffeur_id
         WHERE r.passager_id = ?
         ORDER BY r.id DESC
         LIMIT 5"
    );

    $stmt->execute([$passagerId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Erreur dashboard passager : ' . $e->getMessage());

    $stats = [
        'total' => 0,
        'en_attente' => 0,
        'acceptees' => 0,
        'en_cours' => 0,
        'terminees' => 0
    ];

    $courses = [];
    $_SESSION['erreur'] =
        "Impossible de charger vos courses pour le moment.";
}

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Espace passager - Course Driver</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .welcome-banner {
            padding: 30px;
            margin-bottom: 28px;
            color: #fff;
            background: linear-gradient(135deg, #166534, #15803d);
            border-radius: 16px;
        }

        .welcome-banner h1 {
            margin-top: 0;
        }

        .welcome-banner p {
            line-height: 1.7;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 35px;
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
            font-size: 30px;
        }

        .dashboard-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 22px 0 35px;
        }

        .ride-item {
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .ride-item:last-child {
            border-bottom: 0;
        }

        .ride-route {
            margin: 10px 0;
            overflow-wrap: anywhere;
        }

        .ride-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .empty-state {
            padding: 30px 15px;
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 750px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 450px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .welcome-banner {
                padding: 22px;
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
        <a href="passager.php">Accueil</a>
        <a href="commander.php">Commander</a>
        <a href="mes_courses.php">Mes courses</a>
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

    <section class="welcome-banner">
        <h1>Bonjour <?= h($nom) ?> ! 👋</h1>

        <p>
            Bienvenue dans votre espace passager.
            Commandez une course et retrouvez ici le suivi de vos déplacements.
        </p>

        <a href="commander.php" class="btn btn-secondary">
            🚗 Commander une course
        </a>
    </section>

    <section>
        <h2>Vue d'ensemble de vos courses</h2>

        <div class="stats-grid">
            <article class="stat-card">
                <p>Total des courses</p>
                <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
            </article>

            <article class="stat-card">
                <p>En attente</p>
                <strong><?= (int) ($stats['en_attente'] ?? 0) ?></strong>
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
        <div class="ride-meta">
            <h2>Vos dernières courses</h2>
            <a href="mes_courses.php">Voir tout l'historique →</a>
        </div>

        <div class="card">
            <?php if (empty($courses)): ?>

                <div class="empty-state">
                    <p>Vous n'avez pas encore commandé de course.</p>

                    <a href="commander.php" class="btn btn-primary">
                        Commander ma première course
                    </a>
                </div>

            <?php else: ?>

                <?php foreach ($courses as $course): ?>
                    <article class="ride-item">
                        <div class="ride-meta">
                            <strong>
                                Course n°<?= (int) $course['id'] ?>
                            </strong>

                            <span class="status status-<?= h($course['statut']) ?>">
                                <?= h(statutLabel($course['statut'])) ?>
                            </span>
                        </div>

                        <p class="ride-route">
                            <strong>Départ :</strong>
                            <?= h($course['depart']) ?>
                            <br>

                            <strong>Destination :</strong>
                            <?= h($course['destination']) ?>
                        </p>

                        <div class="ride-meta">
                            <span>
                                Chauffeur :
                                <?= !empty($course['chauffeur_nom'])
                                    ? h($course['chauffeur_nom'])
                                    : 'Pas encore attribué' ?>
                            </span>

                            <strong class="price">
                                <?= number_format((float) $course['prix'], 0, ',', ' ') ?>
                                FCFA
                            </strong>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="dashboard-actions">
                    <a href="mes_courses.php" class="btn btn-secondary">
                        Consulter toutes mes courses
                    </a>
                </div>

            <?php endif; ?>
        </div>
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