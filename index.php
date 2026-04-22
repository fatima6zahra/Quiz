<?php
session_start();
require_once 'db.php';

// Statistiques pour la page d'accueil
$nb_quiz = $pdo->query("SELECT COUNT(*) FROM quiz")->fetchColumn();
$nb_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$nb_participations = $pdo->query("SELECT COUNT(*) FROM participations")->fetchColumn();

// Quiz populaires
$quiz_populaires = $pdo->query("
    SELECT q.*, u.prenom, u.nom,
    COUNT(p.id) as nb_participations
    FROM quiz q
    LEFT JOIN utilisateurs u ON q.createur_id = u.id
    LEFT JOIN participations p ON q.id = p.quiz_id
    GROUP BY q.id
    ORDER BY nb_participations DESC
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizMaster — Défiez vos amis !</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Accueil</a></li>
            <li><a href="liste_quiz.php">Quiz</a></li>
            <li><a href="classement.php">Classement</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="amis.php">Amis
                    <?php
                    $notifs = $pdo->prepare("SELECT COUNT(*) FROM amis WHERE receveur_id = ? AND statut = 'en_attente'");
                    $notifs->execute([$_SESSION['user_id']]);
                    $nb_notifs = $notifs->fetchColumn();
                    if ($nb_notifs > 0): ?>
                        <span class="notif-badge"><?= $nb_notifs ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="dashboard.php">Mon Espace</a></li>
                <li><a href="creer_quiz.php" class="nav-btn">+ Créer</a></li>
                <li><a href="logout.php" class="btn-danger">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php" class="nav-btn">S'inscrire</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-contenu">
            <div class="hero-badge">🎮 La plateforme de quiz ultime</div>
            <h1 class="hero-titre">DÉFIEZ<br>VOS LIMITES</h1>
            <p class="hero-sous-titre">
                Créez, partagez et jouez à des quiz sur tous les sujets.<br>
                Défiez vos amis et grimpez dans le classement mondial !
            </p>
            <div class="hero-btns">
                <a href="liste_quiz.php" class="btn-primary">🚀 Jouer maintenant</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn-secondary">Créer un compte</a>
                <?php else: ?>
                    <a href="creer_quiz.php" class="btn-secondary">+ Créer un quiz</a>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-nombre"><?= $nb_quiz ?>+</div>
                    <div class="stat-label">Quiz disponibles</div>
                </div>
                <div class="stat-item">
                    <div class="stat-nombre"><?= $nb_users ?>+</div>
                    <div class="stat-label">Joueurs actifs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-nombre"><?= $nb_participations ?>+</div>
                    <div class="stat-label">Parties jouées</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quiz populaires -->
    <section class="section">
        <h2 class="section-titre">Quiz <span>Populaires</span></h2>
        <div class="grid-3">
            <?php foreach ($quiz_populaires as $quiz): ?>
                <div class="quiz-card <?= $quiz['categorie'] ?>">
                    <div class="quiz-card-header">
                        <span class="quiz-categorie cat-<?= $quiz['categorie'] ?>">
                            <?= ucfirst($quiz['categorie']) ?>
                        </span>
                        <span class="quiz-difficulte diff-<?= $quiz['difficulte'] ?>">
                            <?= ucfirst($quiz['difficulte']) ?>
                        </span>
                    </div>
                    <h3 class="quiz-titre"><?= htmlspecialchars($quiz['titre']) ?></h3>
                    <p class="quiz-desc"><?= htmlspecialchars($quiz['description']) ?></p>
                    <div class="quiz-footer">
                        <span class="quiz-info">👤 <?= htmlspecialchars($quiz['prenom']) ?></span>
                        <span class="quiz-info">🎮 <?= $quiz['nb_participations'] ?> parties</span>
                        <a href="jouer_quiz.php?id=<?= $quiz['id'] ?>" class="btn-primary" style="padding:8px 20px; font-size:0.8rem;">
                            Jouer →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>⚡ QuizMaster © 2026 — Défiez vos limites</p>
        <div class="footer-links">
            <a href="liste_quiz.php">Quiz</a>
            <a href="classement.php">Classement</a>
            <a href="register.php">S'inscrire</a>
        </div>
    </footer>

</body>
</html>