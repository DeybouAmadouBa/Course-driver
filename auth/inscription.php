<?php

declare(strict_types=1);

session_start();

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
    <title>Inscription | Course Driver</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<main class="auth-container">
    <section class="auth-card">
        <h1>Créer un compte</h1>
        <p>Rejoignez Course Driver dès aujourd'hui.</p>

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

        <form action="../api/register.php" method="POST">
            <div class="form-group">
                <label for="nom">Nom complet</label>
                <input
                    type="text"
                    id="nom"
                    name="nom"
                    maxlength="100"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input
                    type="tel"
                    id="telephone"
                    name="telephone"
                    maxlength="30"
                    autocomplete="tel"
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
                <small>Au moins 8 caractères.</small>
            </div>

            <div class="form-group">
                <label for="role">Type de compte</label>
                <select id="role" name="role" required>
                    <option value="passager">Passager</option>
                    <option value="chauffeur">Chauffeur</option>
                </select>
            </div>

            <button type="submit" class="btn-primary">
                Créer mon compte
            </button>
        </form>

        <p class="auth-footer">
            Tu as déjà un compte ?
            <a href="connexion.php">Se connecter</a>
        </p>

        <p class="auth-footer">
            <a href="../index.php">Retour à l'accueil</a>
        </p>
    </section>
</main>

</body>
</html>