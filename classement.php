<?php
session_start();
require_once 'db.php';

// Top joueurs
$top_joueurs = $pdo->query("
    SELECT u.id, u.prenom, u.nom, u.points_total,
    COUNT(DISTINCT p.id) as nb_parties,
    COUNT(DISTINCT q.id) as nb_quiz_crees,
    AVG(p.score) as score_moyen
    FROM utilisateurs u
    LEFT JOIN participations p ON u.id = p.utilisateur_id
    LEFT JOIN quiz q ON u.id = q.createur_id
    GROUP BY u.id
    ORDER BY u.points_total DESC
    LIMIT 10
")->fetchAll();

// Top quiz
$top_quiz = $pdo->query("
    SELECT q.*, u.prenom, u.nom,
    COUNT(p.id) as nb_participations,
    AVG(p.score) as score_moyen
    FROM quiz q
    LEFT JOIN utilisateurs u ON q.createur_id = u.id
    LEFT JOIN participations p ON q.id = p.quiz_id
    GROUP BY q.id
    ORDER BY nb_participations DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="liste_quiz.php">Quiz</a></li>
            <li><a href="classement.php" class="active">Classement</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="amis.php">Amis</a></li>
                <li><a href="dashboard.php">Mon Espace</a></li>
                <li><a href="creer_quiz.php" class="nav-btn">+ Créer</a></li>
                <li><a href="logout.php" class="btn-danger">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php" class="nav-btn">S'inscrire</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="page-header">
        <h1>🏆 CLASSEMENT</h1>
        <p>Les meilleurs joueurs de la plateforme</p>
    </div>

    <section class="section" style="padding-top:20px; position:relative; z-index:1;">
        <div style="max-width:1000px; margin:0 auto;">

            <div class="grid-2">

                <!-- Top joueurs -->
                <div>
                    <h2 class="section-titre" style="text-align:left; font-size:1.3rem;">
                        🥇 Top <span>Joueurs</span>
                    </h2>

                    <?php foreach ($top_joueurs as $i => $joueur): ?>
                        <div class="classement-item">

                            <!-- Rang -->
                            <div class="classement-rang <?= $i === 0 ? 'rang-1' : ($i === 1 ? 'rang-2' : ($i === 2 ? 'rang-3' : '')) ?>">
                                <?php if ($i === 0): ?>🥇
                                <?php elseif ($i === 1): ?>🥈
                                <?php elseif ($i === 2): ?>🥉
                                <?php else: ?>#<?= $i + 1 ?>
                                <?php endif; ?>
                            </div>

                            <!-- Avatar -->
                            <div class="classement-avatar">
                                <?= strtoupper(substr($joueur['prenom'],0,1).substr($joueur['nom'],0,1)) ?>
                            </div>

                            <!-- Infos -->
                            <div class="classement-info">
                                <div class="classement-nom">
                                    <?= htmlspecialchars($joueur['prenom'].' '.$joueur['nom']) ?>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $joueur['id']): ?>
                                        <span style="color:#6c63ff; font-size:0.75rem;">(vous)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="classement-stats">
                                    🎮 <?= $joueur['nb_parties'] ?> parties ·
                                    📝 <?= $joueur['nb_quiz_crees'] ?> quiz créés ·
                                    ⭐ Moy. <?= round($joueur['score_moyen'] ?? 0) ?> pts
                                </div>
                            </div>

                            <!-- Points -->
                            <div class="classement-points">
                                <?= number_format($joueur['points_total']) ?>
                                <span style="font-size:0.7rem; color:var(--text-muted);">pts</span>
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($top_joueurs)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">
                            Aucun joueur pour l'instant.<br>
                            <a href="register.php" style="color:#6c63ff;">Soyez le premier !</a>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Top quiz -->
                <div>
                    <h2 class="section-titre" style="text-align:left; font-size:1.3rem;">
                        🎯 Top <span>Quiz</span>
                    </h2>

                    <?php foreach ($top_quiz as $i => $q): ?>
                        <div class="classement-item" style="cursor:pointer;"
                             onclick="window.location='jouer_quiz.php?id=<?= $q['id'] ?>'">

                            <div class="classement-rang <?= $i === 0 ? 'rang-1' : ($i === 1 ? 'rang-2' : ($i === 2 ? 'rang-3' : '')) ?>">
                                <?php if ($i === 0): ?>🥇
                                <?php elseif ($i === 1): ?>🥈
                                <?php elseif ($i === 2): ?>🥉
                                <?php else: ?>#<?= $i + 1 ?>
                                <?php endif; ?>
                            </div>

                            <div class="classement-info">
                                <div class="classement-nom">
                                    <?= htmlspecialchars($q['titre']) ?>
                                </div>
                                <div class="classement-stats">
                                    <span class="quiz-categorie cat-<?= $q['categorie'] ?>" style="font-size:0.72rem;">
                                        <?= ucfirst($q['categorie']) ?>
                                    </span>
                                    · <?= $q['nb_participations'] ?> parties ·
                                    Par <?= htmlspecialchars($q['prenom']) ?>
                                </div>
                            </div>

                            <div style="text-align:right;">
                                <div style="font-family:'Orbitron',monospace; font-size:1.1rem; font-weight:700; color:#ff6584;">
                                    <?= $q['nb_participations'] ?>
                                </div>
                                <div style="font-size:0.72rem; color:var(--text-muted);">PARTIES</div>
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($top_quiz)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">
                            Aucun quiz pour l'instant.<br>
                            <a href="creer_quiz.php" style="color:#6c63ff;">Créez le premier !</a>
                        </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <footer class="footer">
        <p> QuizMaster © 2026</p>
    </footer>

</body>
</html>