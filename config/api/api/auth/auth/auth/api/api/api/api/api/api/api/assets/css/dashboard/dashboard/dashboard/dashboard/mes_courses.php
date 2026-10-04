Fichier : "dashboard/mes_courses.php"

<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'passager') {
    header('Location: ../auth/connexion.php');
    exit;
}

$passagerId = (int) $_SESSION['user_id'];
$message = $_SESSION['message'] ?? '';
$typeMessage = $_SESSION['type_message'] ?? '';

unset($_SESSION['message'], $_SESSION['type_message']);

// Récupérer toutes les courses du passager
$sql = "
    SELECT
        r.id,
        r.depart,
        r.destination,
        r.distance,
        r.prix,
        r.statut,
        r.created_at,
        r.chauffeur_id,
        d.nom AS chauffeur_nom,
        d.telephone AS chauffeur_telephone,
        rt.id AS rating_id,
        rt.note AS rating_note,
        rt.comment AS rating_comment
    FROM rides r
    LEFT JOIN users d ON d.id = r.chauffeur_id
    LEFT JOIN ratings rt
        ON rt.ride_id = r.id
        AND rt.passenger_id = r.passager_id
    WHERE r.passager_id = ?
    ORDER BY r.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$passagerId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traduction des statuts
$statuts = [
    'en_attente' => 'En attente',
    'acceptee'   => 'Acceptée',
    'en_cours'   => 'En cours',
    'terminee'   => 'Terminée',
    'annulee'    => 'Annulée'
];

function h($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes courses - Course Driver</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #253044;
        }

        .topbar {
            background: #172b4d;
            color: white;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .topbar a {
            color: white;
            text-decoration: none;
            margin-left: 14px;
        }

        .container {
            width: 92%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .page-title {
            margin-bottom: 8px;
        }

        .intro {
            color: #657084;
            margin-bottom: 25px;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #d9fbe5;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .course-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .course-header h2 {
            font-size: 19px;
            margin: 0;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            background: #e5e7eb;
        }

        .status-en_attente {
            background: #fef3c7;
            color: #92400e;
        }

        .status-acceptee {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-en_cours {
            background: #ede9fe;
            color: #6d28d9;
        }

        .status-terminee {
            background: #dcfce7;
            color: #166534;
        }

        .status-annulee {
            background: #fee2e2;
            color: #991b1b;
        }

        .route {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        .route-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 13px;
            overflow-wrap: anywhere;
        }

        .route-box small {
            display: block;
            color: #657084;
            margin-bottom: 7px;
        }

        .details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 15px 0;
        }

        .details strong {
            color: #172b4d;
        }

        .driver-info, .rating-info {
            padding: 13px;
            margin-top: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .rating-form {
            border-top: 1px solid #e5e7eb;
            margin-top: 18px;
            padding-top: 18px;
        }

        .rating-form label {
            display: block;
            margin: 12px 0 6px;
            font-weight: bold;
        }

        .rating-form select,
        .rating-form textarea {
            box-sizing: border-box;
            width: 100%;
            max-width: 500px;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        .rating-form textarea {
            min-height: 85px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 7px;
            padding: 11px 16px;
            margin-top: 12px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .empty {
            background: white;
            padding: 35px 20px;
            text-align: center;
            border-radius: 12px;
        }

        @media (max-width: 600px) {
            .route {
                grid-template-columns: 1fr;
            }

            .container {
                width: 94%;
            }

            .course-card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <strong>Course Driver</strong>

    <nav>
        <a href="passager.php">Tableau de bord</a>
        <a href="commander.php">Commander</a>
        <a href="../auth/logout.php">Déconnexion</a>
    </nav>
</header>

<main class="container">

    <h1 class="page-title">Mes courses</h1>
    <p class="intro">
        Retrouvez vos trajets, suivez leur statut et donnez votre avis après une course.
    </p>

    <?php if ($message !== ''): ?>
        <div class="alert <?= $typeMessage === 'success' ? 'success' : 'error' ?>">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($courses)): ?>

        <div class="empty">
            <h2>Vous n'avez pas encore de course</h2>
            <p>Commandez votre premier trajet en quelques étapes.</p>
            <a class="btn btn-primary" href="commander.php">
                Commander une course
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($courses as $course): ?>
            <?php
                $statut = $course['statut'];
                $statutTexte = $statuts[$statut] ?? $statut;
            ?>

            <article class="course-card">

                <div class="course-header">
                    <h2>Course n°<?= (int) $course['id'] ?></h2>

                    <span class="status status-<?= h($statut) ?>">
                        <?= h($statutTexte) ?>
                    </span>
                </div>

                <div class="route">
                    <div class="route-box">
                        <small>Départ</small>
                        <strong><?= h($course['depart']) ?></strong>
                    </div>

                    <div class="route-box">
                        <small>Destination</small>
                        <strong><?= h($course['destination']) ?></strong>
                    </div>
                </div>

                <div class="details">
                    <span>
                        Distance :
                        <strong><?= h($course['distance']) ?> km</strong>
                    </span>

                    <span>
                        Prix :
                        <strong><?= number_format((float) $course['prix'], 0, ',', ' ') ?> FCFA</strong>
                    </span>

                    <span>
                        Date :
                        <strong><?= h($course['created_at'] ?? 'Non renseignée') ?></strong>
                    </span>
                </div>

                <?php if (!empty($course['chauffeur_id'])): ?>
                    <div class="driver-info">
                        <strong>Informations du chauffeur</strong>
                        <p>
                            Nom : <?= h($course['chauffeur_nom'] ?? 'Non renseigné') ?>
                        </p>

                        <?php if (!empty($course['chauffeur_telephone'])): ?>
                            <p>
                                Téléphone :
                                <?= h($course['chauffeur_telephone']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($statut === 'en_attente'): ?>
                    <p>Un chauffeur doit encore accepter votre demande.</p>
                <?php endif; ?>

                <?php if (in_array($statut, ['en_attente', 'acceptee'], true)): ?>
                    <form
                        action="../api/cancel_ride.php"
                        method="POST"
                        onsubmit="return confirm('Voulez-vous vraiment annuler cette course ?');"
                    >
                        <input
                            type="hidden"
                            name="ride_id"
                            value="<?= (int) $course['id'] ?>"
                        >

                        <button type="submit" class="btn btn-danger">
                            Annuler la course
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (
                    $statut === 'terminee'
                    && !empty($course['chauffeur_id'])
                    && empty($course['rating_id'])
                ): ?>

                    <form
                        class="rating-form"
                        action="../api/rate_driver.php"
                        method="POST"
                    >
                        <h3>Évaluer votre chauffeur</h3>
                        <p>Votre avis aidera les autres passagers.</p>

                        <input
                            type="hidden"
                            name="ride_id"
                            value="<?= (int) $course['id'] ?>"
                        >

                        <label for="note-<?= (int) $course['id'] ?>">
                            Votre note
                        </label>

                        <select
                            id="note-<?= (int) $course['id'] ?>"
                            name="note"
                            required
                        >
                            <option value="">Choisir une note</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Très bien</option>
                            <option value="3">3 - Bien</option>
                            <option value="2">2 - Moyen</option>
                            <option value="1">1 - Insatisfaisant</option>
                        </select>

                        <label for="commentaire-<?= (int) $course['id'] ?>">
                            Commentaire (facultatif)
                        </label>

                        <textarea
                            id="commentaire-<?= (int) $course['id'] ?>"
                            name="commentaire"
                            maxlength="1000"
                            placeholder="Décrivez votre expérience..."
                        ></textarea>

                        <button type="submit" class="btn btn-primary">
                            Envoyer mon avis
                        </button>
                    </form>

                <?php elseif (!empty($course['rating_id'])): ?>

                    <div class="rating-info">
                        <strong>Votre évaluation : </strong>
                        <?= (int) $course['rating_note'] ?>/5
                        <p><?= h($course['rating_comment'] ?? '') ?></p>
                    </div>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>