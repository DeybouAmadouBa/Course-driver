<?php

declare(strict_types=1);

session_start();

// Rediriger les utilisateurs déjà connectés
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'chauffeur') {
        header('Location: ../dashboard/chauffeur.php');
    } else {
        header('Location: ../dashboard/passager.php');
    }
    exit;
}

$erreur = $_SESSION['erreur'] ?? '';
unset($_SESSION['erreur']);

$succes = $_SESSION['succes'] ?? '';
unset($_SESSION['succes']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | Course Driver</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<main class="auth-container">
    <section class="auth-card">
        <h1>Connexion</h1>
        <p>Connectez-vous pour commander ou effectuer une course.</p>

        <?php if ($erreur !== ''): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($succes !== ''): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($succes, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="../api/login.php" method="POST">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn-primary">
                Se connecter
            </button>
        </form>

        <p class="auth-footer">
            Tu n'as pas encore de compte ?
            <a href="inscription.php">Créer un compte</a>
        </p>

        <p class="auth-footer">
            <a href="../index.php">Retour à l'accueil</a>
        </p>
    </section>
</main>

</body>
</html>