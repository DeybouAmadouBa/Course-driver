Fichier : "dashboard/edit_vehicle.php"

<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est un chauffeur connecté.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'chauffeur'
) {
    header('Location: ../auth/connexion.php');
    exit;
}

$chauffeurId = (int) $_SESSION['user_id'];

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Récupérer et valider l'identifiant du véhicule.
$vehicleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vehicleId || $vehicleId < 1) {
    $_SESSION['message'] = 'Identifiant du véhicule invalide.';
    $_SESSION['type_message'] = 'error';

    header('Location: vehicle.php');
    exit;
}

// Vérifier que le véhicule appartient à ce chauffeur.
$stmt = $pdo->prepare(
    'SELECT id, marque, modele, immatriculation, type
     FROM vehicles
     WHERE id = ? AND chauffeur_id = ?
     LIMIT 1'
);

$stmt->execute([$vehicleId, $chauffeurId]);
$vehicule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicule) {
    $_SESSION['message'] = 'Véhicule introuvable ou accès non autorisé.';
    $_SESSION['type_message'] = 'error';

    header('Location: vehicle.php');
    exit;
}

$erreurs = [];

$marque = $vehicule['marque'];
$modele = $vehicule['modele'];
$immatriculation = $vehicule['immatriculation'];
$type = $vehicule['type'];

$typesAutorises = ['economique', 'confort', 'premium', 'moto'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $immatriculation = strtoupper(trim($_POST['immatriculation'] ?? ''));
    $type = $_POST['type'] ?? '';

    if ($marque === '' || mb_strlen($marque) > 100) {
        $erreurs[] = 'La marque est obligatoire et limitée à 100 caractères.';
    }

    if ($modele === '' || mb_strlen($modele) > 100) {
        $erreurs[] = 'Le modèle est obligatoire et limité à 100 caractères.';
    }

    if (
        $immatriculation === '' ||
        mb_strlen($immatriculation) > 30
    ) {
        $erreurs[] = 'Veuillez saisir une immatriculation valide.';
    }

    if (!in_array($type, $typesAutorises, true)) {
        $erreurs[] = 'La catégorie sélectionnée est invalide.';
    }

    if (empty($erreurs)) {
        try {
            // Vérifier que l'immatriculation n'appartient pas
            // à un autre véhicule.
            $verification = $pdo->prepare(
                'SELECT id
                 FROM vehicles
                 WHERE immatriculation = ?
                   AND id <> ?
                 LIMIT 1'
            );

            $verification->execute([$immatriculation, $vehicleId]);

            if ($verification->fetch()) {
                $erreurs[] = 'Cette immatriculation est déjà utilisée par un autre véhicule.';
            } else {
                $update = $pdo->prepare(
                    'UPDATE vehicles
                     SET marque = ?,
                         modele = ?,
                         immatriculation = ?,
                         type = ?
                     WHERE id = ?
                       AND chauffeur_id = ?'
                );

                $update->execute([
                    $marque,
                    $modele,
                    $immatriculation,
                    $type,
                    $vehicleId,
                    $chauffeurId
                ]);

                $_SESSION['message'] = 'Les informations du véhicule ont été mises à jour.';
                $_SESSION['type_message'] = 'success';

                header('Location: vehicle.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());

            if ($e->getCode() === '23000') {
                $erreurs[] = 'Cette immatriculation existe déjà.';
            } else {
                $erreurs[] = 'Une erreur est survenue lors de la modification.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Modifier un véhicule - Course Driver</title>

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

        .topbar a {
            color: white;
            text-decoration: none;
            margin-left: 14px;
        }

        .container {
            width: 92%;
            max-width: 650px;
            margin: 35px auto;
        }

        .form-card {
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .intro {
            color: #657084;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
            background: white;
        }

        .btn {
            display: inline-block;
            padding: 12px 17px;
            margin-top: 8px;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #253044;
            margin-left: 8px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
            padding: 13px 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 500px) {
            .form-card {
                padding: 18px;
            }

            .btn-secondary {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <strong>Course Driver</strong>

    <nav>
        <a href="chauffeur.php">Tableau de bord</a>
        <a href="vehicle.php">Mes véhicules</a>
        <a href="../auth/logout.php">Déconnexion</a>
    </nav>
</header>

<main class="container">

    <div class="form-card">
        <h1>Modifier mon véhicule</h1>

        <p class="intro">
            Modifiez les informations ci-dessous puis enregistrez vos changements.
        </p>

        <?php if (!empty($erreurs)): ?>
            <div class="alert-error">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul>
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= h($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_vehicle.php?id=<?= (int) $vehicleId ?>">

            <div class="form-group">
                <label for="marque">Marque</label>
                <input
                    type="text"
                    id="marque"
                    name="marque"
                    maxlength="100"
                    value="<?= h($marque) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="modele">Modèle</label>
                <input
                    type="text"
                    id="modele"
                    name="modele"
                    maxlength="100"
                    value="<?= h($modele) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="immatriculation">Immatriculation</label>
                <input
                    type="text"
                    id="immatriculation"
                    name="immatriculation"
                    maxlength="30"
                    value="<?= h($immatriculation) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="type">Catégorie</label>

                <select id="type" name="type" required>
                    <option value="economique" <?= $type === 'economique' ? 'selected' : '' ?>>
                        Économique
                    </option>

                    <option value="confort" <?= $type === 'confort' ? 'selected' : '' ?>>
                        Confort
                    </option>

                    <option value="premium" <?= $type === 'premium' ? 'selected' : '' ?>>
                        Premium
                    </option>

                    <option value="moto" <?= $type === 'moto' ? 'selected' : '' ?>>
                        Moto
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Enregistrer les modifications
            </button>

            <a href="vehicle.php" class="btn btn-secondary">
                Annuler
            </a>

        </form>
    </div>

</main>

</body>
</html>