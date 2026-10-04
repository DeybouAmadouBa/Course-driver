<?php
session_start();

$connecte = isset($_SESSION['user_id']);
$nom = $_SESSION['nom'] ?? '';

$dashboard = '../index.php';

if ($connecte) {
    if (($_SESSION['role'] ?? '') === 'chauffeur') {
        $dashboard = 'dashboard/chauffeur.php';
    } elseif (($_SESSION['role'] ?? '') === 'passager') {
        $dashboard = 'dashboard/passager.php';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Course Driver - Vos déplacements simplifiés</title>

    <meta
        name="description"
        content="Course Driver facilite vos déplacements. Commandez une course ou devenez chauffeur."
    >

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #172033;
            background: #f7f9fc;
        }

        a {
            text-decoration: none;
        }

        .site-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 7%;
            background: #ffffff;
            border-bottom: 1px solid #e8edf4;
        }

        .brand {
            font-size: 25px;
            font-weight: 800;
            color: #166534;
        }

        .brand span {
            color: #172033;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .nav-links a {
            color: #374151;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 13px 20px;
            border-radius: 9px;
            font-weight: 700;
            text-align: center;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.85;
        }

        .btn-green {
            background: #15803d;
            color: #ffffff !important;
        }

        .btn-light {
            background: #ffffff;
            color: #166534 !important;
            border: 1px solid #d1d5db;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 50px;
            max-width: 1250px;
            margin: auto;
            padding: 85px 7%;
        }

        .hero-label {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 30px;
            background: #dcfce7;
            color: #166534;
            font-size: 14px;
            font-weight: 700;
        }

        .hero h1 {
            margin: 22px 0;
            font-size: clamp(38px, 5vw, 62px);
            line-height: 1.12;
            letter-spacing: -1.5px;
        }

        .hero h1 span {
            color: #15803d;
        }

        .hero p {
            max-width: 570px;
            color: #64748b;
            font-size: 18px;
            line-height: 1.8;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 30px;
        }

        .hero-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 350px;
            padding: 30px;
            border-radius: 28px;
            background: linear-gradient(145deg, #dcfce7, #bbf7d0);
        }

        .car-card {
            width: 100%;
            max-width: 350px;
            padding: 35px 25px;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 20px 45px rgba(22, 101, 52, 0.12);
            text-align: center;
        }

        .car-icon {
            font-size: 95px;
            line-height: 1.3;
        }

        .car-card h2 {
            margin-bottom: 10px;
        }

        .car-card p {
            margin: 0;
            font-size: 15px;
        }

        .features {
            padding: 65px 7%;
            background: #ffffff;
            text-align: center;
        }

        .features h2 {
            margin-bottom: 12px;
            font-size: 34px;
        }

        .section-description {
            color: #64748b;
            line-height: 1.7;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            max-width: 1100px;
            margin: 40px auto 0;
            text-align: left;
        }

        .feature-card {
            padding: 28px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
        }

        .feature-icon {
            font-size: 35px;
        }

        .feature-card h3 {
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.7;
        }

        .join-section {
            margin: 60px 7%;
            padding: 50px 25px;
            border-radius: 22px;
            background: #166534;
            color: #ffffff;
            text-align: center;
        }

        .join-section h2 {
            font-size: 32px;
        }

        .join-section p {
            line-height: 1.8;
        }

        .join-section .btn {
            margin-top: 15px;
        }

        .site-footer {
            padding: 25px 7%;
            background: #111827;
            color: #d1d5db;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 800px) {
            .site-header {
                flex-wrap: wrap;
                padding: 18px 5%;
            }

            .nav-links {
                flex-wrap: wrap;
                gap: 13px;
            }

            .hero {
                grid-template-columns: 1fr;
                gap: 35px;
                padding: 55px 5%;
            }

            .hero-visual {
                min-height: 280px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .features {
                padding: 50px 5%;
            }

            .join-section {
                margin: 40px 5%;
            }
        }

        @media (max-width: 450px) {
            .brand {
                font-size: 22px;
            }

            .nav-links {
                width: 100%;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="site-header">
    <a href="index.php" class="brand">
        Course<span>Driver</span>
    </a>

    <nav class="nav-links">
        <?php if ($connecte): ?>
            <a href="<?= htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8') ?>">
                Mon espace
            </a>

            <a href="auth/logout.php" class="btn btn-green">
                Déconnexion
            </a>
        <?php else: ?>
            <a href="auth/connexion.php">Connexion</a>
            <a href="auth/inscription.php" class="btn btn-green">
                Créer un compte
            </a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="hero">
        <div>
            <span class="hero-label">Vos déplacements, autrement</span>

            <h1>
                Déplacez-vous en toute simplicité avec
                <span>Course Driver.</span>
            </h1>

            <p>
                Trouvez un chauffeur, commandez votre course et
                rejoignez votre destination plus facilement.
                Course Driver vous met en relation avec des chauffeurs
                pour simplifier vos déplacements.
            </p>

            <div class="hero-actions">
                <?php if ($connecte && ($_SESSION['role'] ?? '') === 'passager'): ?>
                    <a href="dashboard/commander.php" class="btn btn-green">
                        Commander une course
                    </a>
                <?php elseif (!$connecte): ?>
                    <a href="auth/inscription.php" class="btn btn-green">
                        Commencer maintenant
                    </a>

                    <a href="auth/connexion.php" class="btn btn-light">
                        Se connecter
                    </a>
                <?php else: ?>
                    <a href="dashboard/chauffeur.php" class="btn btn-green">
                        Accéder à mon espace chauffeur
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-visual">
            <div class="car-card">
                <div class="car-icon" aria-hidden="true">🚘</div>
                <h2>Votre trajet commence ici</h2>
                <p>
                    Une solution simple pour les passagers
                    et les chauffeurs.
                </p>
            </div>
        </div>
    </section>

    <section class="features">
        <h2>Pourquoi choisir Course Driver ?</h2>

        <p class="section-description">
            Une expérience pensée pour faciliter chaque déplacement.
        </p>

        <div class="feature-grid">
            <article class="feature-card">
                <div class="feature-icon" aria-hidden="true">📍</div>
                <h3>Commandez facilement</h3>
                <p>
                    Indiquez votre point de départ et votre destination
                    pour organiser votre course.
                </p>
            </article>

            <article class="feature-card">
                <div class="feature-icon" aria-hidden="true">🚗</div>
                <h3>Des chauffeurs inscrits</h3>
                <p>
                    Retrouvez les courses dans votre espace et suivez
                    leur progression.
                </p>
            </article>

            <article class="feature-card">
                <div class="feature-icon" aria-hidden="true">⭐</div>
                <h3>Donnez votre avis</h3>
                <p>
                    Après une course terminée, partagez votre expérience
                    en attribuant une note au chauffeur.
                </p>
            </article>
        </div>
    </section>

    <?php if (!$connecte): ?>
        <section class="join-section">
            <h2>Prêt à prendre la route ?</h2>

            <p>
                Créez votre compte pour commander des courses
                ou rejoindre la plateforme comme chauffeur.
            </p>

            <a href="auth/inscription.php" class="btn btn-light">
                Rejoindre Course Driver
            </a>
        </section>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <p>
        &copy; <?= date('Y') ?> Course Driver.
        Tous droits réservés.
    </p>
</footer>

</body>
</html>