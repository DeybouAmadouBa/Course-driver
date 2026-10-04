Fichier : "dashboard/vehicle.php"

<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Réserver cette page aux chauffeurs connectés
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'chauffeur'
) {
    header('Location: ../auth/connexion.php');
    exit;
}

$chauffeurId = (int) $_SESSION['user_id'];

$message = $_SESSION['message'] ?? '';
$typeMessage = $_SESSION['type_message'] ?? '';

unset($_SESSION['message'], $_SESSION['type_message']);

// Récupérer les véhicules du chauffeur
$stmt = $pdo->prepare(
    'SELECT id, marque, modele, immatriculation, type
     FROM vehicles
     WHERE chauffeur_id = ?
     ORDER BY id DESC'
);

$stmt->execute([$chauffeurId]);
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$types = [
    'economique' => 'Économique',
    'confort' => 'Confort',
    'premium' => 'Premium',
    'moto' => 'Moto'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes véhicules - Course Driver</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            color: #253044;
            font-family: Arial, sans-serif;
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

        .topbar nav {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .topbar a {
            color: white;
            text-decoration: none;
        }

        .container {
            width: 92%;
            max-width: 1000px;
            margin: 30px auto;
        }

        .intro {
            color: #657084;
            margin-bottom: 25px;
        }

        .vehicle-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .vehicle-card h2 {
            margin-top: 0;
        }

        .vehicle-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .detail {
            padding: 13px;
            background: #f8fafc;
            border-radius: 8px;
            overflow-wrap: anywhere;
        }

        .detail small {
            display: block;
            color: #657084;
            margin-bottom: 7px;
        }

        .badge {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            margin-top: 15px;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
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

        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            background: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 12px;
        }

        @media (max-width: 600px) {
            .vehicle-details {
                grid-template-columns: 1fr;
            }

            .vehicle-card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <strong>Course Driver</strong>

    <nav>
        <a href="chauffeur.php">Tableau de bord</a>
        <a href="add_vehicle.php">Ajouter un véhicule</a>
        <a href="../auth/logout.php">Déconnexion</a>
    </nav>
</header>

<main class="container">

    <h1>Mes véhicules</h1>

    <p class="intro">
        Consultez les véhicules enregistrés sur votre compte chauffeur.
    </p>

    <?php if ($message !== ''): ?>
        <div class="alert <?= $typeMessage === 'success' ? 'success' : 'error' ?>">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <a href="add_vehicle.php" class="btn btn-primary">
        + Ajouter un véhicule
    </a>

    <br><br>

    <?php if (empty($vehicules)): ?>

        <div class="empty">
            <h2>Aucun véhicule enregistré</h2>
            <p>Ajoutez votre véhicule pour compléter votre profil chauffeur.</p>

            <a href="add_vehicle.php" class="btn btn-primary">
                Ajouter mon premier véhicule
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($vehicules as $vehicule): ?>

            <article class="vehicle-card">

                <h2>
                    <?= h($vehicule['marque']) ?>
                    <?= h($vehicule['modele']) ?>
                </h2>

                <span class="badge">
                    <?= h($types[$vehicule['type']] ?? $vehicule['type']) ?>
                </span>

                <div class="vehicle-details" style="margin-top: 18px;">

                    <div class="detail">
                        <small>Marque</small>
                        <strong><?= h($vehicule['marque']) ?></strong>
                    </div>

                    <div class="detail">
                        <small>Modèle</small>
                        <strong><?= h($vehicule['modele']) ?></strong>
                    </div>

                    <div class="detail">
                        <small>Immatriculation</small>
                        <strong><?= h($vehicule['immatriculation']) ?></strong>
                    </div>

                    <div class="detail">
                        <small>Catégorie</small>
                        <strong>
                            <?= h($types[$vehicule['type']] ?? $vehicule['type']) ?>
                        </strong>
                    </div>

                </div>

                <div>
                    <a
                        href="edit_vehicle.php?id=<?= (int) $vehicule['id'] ?>"
                        class="btn btn-primary"
                    >
                        Modifier
                    </a>

                    <form
                        action="../api/delete_vehicle.php"
                        method="POST"
                        style="display: inline-block;"
                        onsubmit="return confirm('Voulez-vous vraiment supprimer ce véhicule ?');"
                    >
                        <input
                            type="hidden"
                            name="vehicle_id"
                            value="<?= (int) $vehicule['id'] ?>"
                        >

                        <button type="submit" class="btn btn-danger">
                            Supprimer
                        </button>
                    </form>
                </div>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>