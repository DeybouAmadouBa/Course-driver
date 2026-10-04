Fichier : "dashboard/add_vehicle.php"

<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Seuls les chauffeurs connectés peuvent ajouter un véhicule.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'chauffeur'
) {
    header('Location: ../auth/connexion.php');
    exit;
}

$erreurs = [];
$marque = '';
$modele = '';
$immatriculation = '';
$type = 'confort';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $immatriculation = strtoupper(trim($_POST['immatriculation'] ?? ''));
    $type = $_POST['type'] ?? 'confort';

    $typesAutorises = ['economique', 'confort', 'premium', 'moto'];

    if ($marque === '' || mb_strlen($marque) > 100) {
        $erreurs[] = 'La marque est obligatoire et ne doit pas dépasser 100 caractères.';
    }

    if ($modele === '' || mb_strlen($modele) > 100) {
        $erreurs[] = 'Le modèle est obligatoire et ne doit pas dépasser 100 caractères.';
    }

    if (
        $immatriculation === '' ||
        mb_strlen($immatriculation) > 30
    ) {
        $erreurs[] = 'Veuillez saisir une immatriculation valide.';
    }

    if (!in_array($type, $typesAutorises, true)) {
        $erreurs[] = 'Veuillez choisir une catégorie valide.';
    }

    if (empty($erreurs)) {
        try {
            // Vérifier si l'immatriculation existe déjà.
            $verification = $pdo->prepare(
                'SELECT id
                 FROM vehicles
                 WHERE immatriculation = ?
                 LIMIT 1'
            );

            $verification->execute([$immatriculation]);

            if ($verification->fetch()) {
                $erreurs[] = 'Cette immatriculation est déjà enregistrée.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO vehicles
                        (chauffeur_id, marque, modele, immatriculation, type)
                     VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    (int) $_SESSION['user_id'],
                    $marque,
                    $modele,
                    $immatriculation,
                    $type
                ]);

                $_SESSION['message'] = 'Votre véhicule a été ajouté avec succès.';
                $_SESSION['type_message'] = 'success';

                header('Location: vehicle.php');
                exit;
            }
        } catch (PDOException $e) {
            // Une contrainte UNIQUE en base protège aussi contre
            // deux ajouts simultanés de la même immatriculation.
            if ($e->getCode() === '23000') {
                $erreurs[] = 'Cette immatriculation existe déjà ou une contrainte de la base a été violée.';
            } else {
                error_log($e->getMessage());
                $erreurs[] = 'Une erreur est survenue lors de l’enregistrement du véhicule.';
            }
        }
    }
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ajouter un véhicule - Course Driver</title>

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
            margin-left: 15px;
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
            margin-bottom: 24px;
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

        .form-group input:focus,
        .form-group select:focus {
            outline: 2px solid #93c5fd;
            border-color: #2563eb;
        }

        .btn {
            display: inline-block;
            padding: 12px 17px;
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

        .alert-error ul {
            margin-bottom: 0;
        }

        @media (max-width: 500px) {
            .form-card {
                padding: 18px;
            }

            .btn-secondary {
                margin-left: 0;
                margin-top: 10px;
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
        <h1>Ajouter un véhicule</h1>

        <p class="intro">
            Renseignez les informations de votre véhicule pour compléter
            votre profil chauffeur.
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

        <form action="" method="POST">

            <div class="form-group">
                <label for="marque">Marque du véhicule</label>
                <input
                    type="text"
                    id="marque"
                    name="marque"
                    maxlength="100"
                    placeholder="Ex. : Toyota"
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
                    placeholder="Ex. : Corolla"
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
                    placeholder="Ex. : DK-2026-AA"
                    value="<?= h($immatriculation) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="type">Catégorie du véhicule</label>

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
                Enregistrer le véhicule
            </button>

            <a href="vehicle.php" class="btn btn-secondary">
                Retour
            </a>

        </form>
    </div>

</main>

</body>
</html>