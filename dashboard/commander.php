<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est un passager connecté
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'passager'
) {
    $_SESSION['erreur'] = "Connectez-vous avec un compte passager.";
    header('Location: ../auth/connexion.php');
    exit;
}

$nom = $_SESSION['nom'] ?? 'Passager';
$erreur = '';

function h($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Préremplir les champs en cas d'erreur de validation
$depart = trim($_POST['depart'] ?? '');
$destination = trim($_POST['destination'] ?? '');
$distance = trim($_POST['distance'] ?? '');
$prix = trim($_POST['prix'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $distanceNombre = filter_var($distance, FILTER_VALIDATE_FLOAT);
    $prixNombre = filter_var($prix, FILTER_VALIDATE_FLOAT);

    if ($depart === '' || $destination === '') {
        $erreur = "Le départ et la destination sont obligatoires.";
    } elseif (strlen($depart) > 255 || strlen($destination) > 255) {
        $erreur = "Les adresses ne doivent pas dépasser 255 caractères.";
    } elseif (strcasecmp($depart, $destination) === 0) {
        $erreur = "Le départ et la destination doivent être différents.";
    } elseif (
        $distanceNombre === false ||
        $distanceNombre <= 0 ||
        $distanceNombre > 1000
    ) {
        $erreur = "Saisissez une distance valide comprise entre 0 et 1000 km.";
    } elseif (
        $prixNombre === false ||
        $prixNombre <= 0 ||
        $prixNombre > 10000000
    ) {
        $erreur = "Saisissez un prix valide.";
    } else {
        // Transmettre les données à l'API qui créera la course
        $_SESSION['commande_en_cours'] = [
            'depart' => $depart,
            'destination' => $destination,
            'distance' => (string) $distanceNombre,
            'prix' => (string) $prixNombre
        ];

        // L'API attend des champs POST : on utilise un formulaire HTML.
        $erreur = '';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Commander une course - Course Driver</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .booking-container {
            width: min(760px, 94%);
            margin: 35px auto;
        }

        .booking-header {
            margin-bottom: 25px;
        }

        .booking-header p {
            color: #64748b;
            line-height: 1.7;
        }

        .booking-card {
            padding: 30px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .booking-summary {
            margin-top: 22px;
            padding: 18px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
        }

        .booking-summary strong {
            color: #166534;
        }

        .booking-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        @media (max-width: 500px) {
            .booking-card {
                padding: 20px;
            }

            .booking-actions {
                flex-direction: column;
            }

            .booking-actions .btn {
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
        <a href="passager.php">Mon espace</a>
        <a href="mes_courses.php">Mes courses</a>
        <a href="../auth/logout.php" class="btn btn-primary">
            Déconnexion
        </a>
    </nav>
</header>

<main class="booking-container">

    <div class="booking-header">
        <h1>Commander une course 🚗</h1>
        <p>
            Bonjour <?= h($nom) ?>. Renseignez les informations
            de votre trajet pour créer une demande de course.
        </p>
    </div>

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

    <?php if ($erreur !== ''): ?>
        <div class="alert alert-danger">
            <?= h($erreur) ?>
        </div>
    <?php endif; ?>

    <section class="booking-card">
        <form action="../api/create_ride.php" method="POST">

            <div class="form-group">
                <label for="depart">Point de départ</label>
                <input
                    type="text"
                    id="depart"
                    name="depart"
                    maxlength="255"
                    placeholder="Ex. : Dakar Plateau"
                    value="<?= h($depart) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="destination">Destination</label>
                <input
                    type="text"
                    id="destination"
                    name="destination"
                    maxlength="255"
                    placeholder="Ex. : Almadies"
                    value="<?= h($destination) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="distance">Distance estimée (km)</label>
                <input
                    type="number"
                    id="distance"
                    name="distance"
                    min="0.1"
                    max="1000"
                    step="0.1"
                    placeholder="Ex. : 8"
                    value="<?= h($distance) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="prix">Prix estimé (FCFA)</label>
                <input
                    type="number"
                    id="prix"
                    name="prix"
                    min="1"
                    max="10000000"
                    step="1"
                    placeholder="Ex. : 6800"
                    value="<?= h($prix) ?>"
                    required
                >
            </div>

            <div class="booking-summary">
                <strong>À savoir :</strong>
                le prix saisi est une estimation. Dans une version
                de production, il devra être calculé ou vérifié côté
                serveur pour éviter les modifications abusives.
            </div>

            <div class="booking-actions">
                <button type="submit" class="btn btn-primary">
                    Confirmer la demande
                </button>

                <a href="passager.php" class="btn btn-secondary">
                    Retour
                </a>
            </div>

        </form>
    </section>

</main>

<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Course Driver. Tous droits réservés.</p>
</footer>

</body>
</html>