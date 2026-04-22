<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Historique participations
$stmt = $pdo->prepare("
    SELECT p.*, q.titre, q.categorie, q.difficulte
    FROM participations p
    JOIN quiz q ON p.quiz_id = q.id
    WHERE p.utilisateur_id = ?
    ORDER BY p.date_participation DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$participations = $stmt->fetchAll();

// Quiz créés
$stmt = $pdo->prepare("
    SELECT q.*, COUNT(p.id) as nb_participations
    FROM quiz q
    LEFT JOIN participations p ON q.id = p.quiz_id
    WHERE q.createur_id = ?
    GROUP BY q.id
    ORDER BY q.date_creation DESC
");
$stmt->execute([$user_id]);
$mes_quiz = $stmt->fetchAll();

// Défis reçus
$stmt = $pdo->prepare("
    SELECT d.*, q.titre as quiz_titre,
    u.prenom as envoyeur_prenom, u.nom as envoyeur_nom
    FROM defis d
    JOIN quiz q ON d.quiz_id = q.id
    JOIN utilisateurs u ON d.envoyeur_id = u.id
    WHERE d.receveur_id = ? AND d.statut = 'en_attente'
");
$stmt->execute([$user_id]);
$defis = $stmt->fetchAll();

// Amis
$stmt = $pdo->prepare("
    SELECT u.*, a.statut
    FROM amis a
    JOIN utilisateurs u ON (
        CASE WHEN a.demandeur_id = ? THEN a.receveur_id
        ELSE a.demandeur_id END = u.id
    )
    WHERE (a.demandeur_id = ? OR a.receveur_id = ?)
    AND a.statut = 'accepte'
");
$stmt->execute([$user_id, $user_id, $user_id]);
$amis = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="liste_quiz.php">Quiz</a></li>
            <li><a href="classement.php">Classement</a></li>
            <li><a href="amis.php">Amis
                <?php
                $notifs = $pdo->prepare("SELECT COUNT(*) FROM amis WHERE receveur_id = ? AND statut = 'en_attente'");
                $notifs->execute([$user_id]);
                $nb_notifs = $notifs->fetchColumn();
                if ($nb_notifs > 0): ?>
                    <span class="notif-badge"><?= $nb_notifs ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="dashboard.php" class="active">Mon Espace</a></li>
            <li><a href="creer_quiz.php" class="nav-btn">+ Créer</a></li>
            <li><a href="logout.php" class="btn-danger">Déconnexion</a></li>
        </ul>
    </nav>

    <div class="page-header">
        <h1>MON ESPACE</h1>
        <p>Bienvenue, <?= htmlspecialchars($_SESSION['user_prenom']) ?> !</p>
    </div>

    <div class="dashboard-grid" style="position:relative; z-index:1;">

        <!-- Profil -->
        <div>
            <div class="profil-card">
                <div class="profil-avatar">
                    <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                </div>
                <div class="profil-nom"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                <div class="profil-email"><?= htmlspecialchars($user['email']) ?></div>
                <div class="profil-points">
                    <div class="profil-points-nombre"><?= $user['points_total'] ?></div>
                    <div class="profil-points-label">Points totaux</div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px;">
                    <div style="background:rgba(108,99,255,0.1); border:1px solid rgba(108,99,255,0.3); border-radius:10px; padding:12px; text-align:center;">
                        <div style="font-family:'Orbitron',monospace; font-size:1.3rem; font-weight:700; color:#6c63ff;"><?= count($participations) ?></div>
                        <div style="font-size:0.72rem; color:var(--text-muted); letter-spacing:1px;">PARTIES</div>
                    </div>
                    <div style="background:rgba(255,101,132,0.1); border:1px solid rgba(255,101,132,0.3); border-radius:10px; padding:12px; text-align:center;">
                        <div style="font-family:'Orbitron',monospace; font-size:1.3rem; font-weight:700; color:#ff6584;"><?= count($mes_quiz) ?></div>
                        <div style="font-size:0.72rem; color:var(--text-muted); letter-spacing:1px;">QUIZ CRÉÉS</div>
                    </div>
                </div>
                <a href="creer_quiz.php" class="btn-primary" style="display:block; text-align:center; margin-bottom:10px;">
                    + Créer un quiz
                </a>
                <a href="liste_quiz.php" class="btn-secondary" style="display:block; text-align:center;">
                    🎮 Jouer
                </a>
            </div>
        </div>

        <!-- Contenu principal -->
        <div>

            <!-- Défis reçus -->
            <?php if (!empty($defis)): ?>
            <div class="card" style="margin-bottom:25px;">
                <h2 style="font-family:'Orbitron',monospace; font-size:1.1rem; margin-bottom:20px; color:#ffd700;">
                    ⚔️ Défis reçus (<?= count($defis) ?>)
                </h2>
                <?php foreach ($defis as $defi): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; background:rgba(255,215,0,0.05); border:1px solid rgba(255,215,0,0.2); border-radius:12px; margin-bottom:10px;">
                        <div>
                            <div style="font-weight:700;"><?= htmlspecialchars($defi['quiz_titre']) ?></div>
                            <div style="color:var(--text-muted); font-size:0.85rem;">
                                Défi de <?= htmlspecialchars($defi['envoyeur_prenom'] . ' ' . $defi['envoyeur_nom']) ?>
                            </div>
                        </div>
                        <a href="jouer_quiz.php?id=<?= $defi['quiz_id'] ?>&defi=<?= $defi['id'] ?>" class="btn-primary" style="padding:8px 20px; font-size:0.8rem;">
                            ⚔️ Relever le défi
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Historique -->
            <div class="card" style="margin-bottom:25px;">
                <h2 style="font-family:'Orbitron',monospace; font-size:1.1rem; margin-bottom:20px;">
                    📊 Historique des parties
                </h2>
                <?php if (empty($participations)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:30px;">
                        Aucune partie jouée pour l'instant.<br>
                        <a href="liste_quiz.php" style="color:#6c63ff;">Jouer maintenant →</a>
                    </p>
                <?php else: ?>
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Catégorie</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participations as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['titre']) ?></td>
                                    <td><span class="quiz-categorie cat-<?= $p['categorie'] ?>"><?= ucfirst($p['categorie']) ?></span></td>
                                    <td style="font-family:'Orbitron',monospace; color:#6c63ff; font-weight:700;"><?= $p['score'] ?> pts</td>
                                    <td style="color:var(--text-muted); font-size:0.85rem;"><?= date('d/m/Y', strtotime($p['date_participation'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Mes quiz -->
            <div class="card">
                <h2 style="font-family:'Orbitron',monospace; font-size:1.1rem; margin-bottom:20px;">
                    🎯 Mes Quiz créés
                </h2>
                <?php if (empty($mes_quiz)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:30px;">
                        Aucun quiz créé.<br>
                        <a href="creer_quiz.php" style="color:#6c63ff;">Créer mon premier quiz →</a>
                    </p>
                <?php else: ?>
                    <?php foreach ($mes_quiz as $q): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; background:rgba(108,99,255,0.05); border:1px solid rgba(108,99,255,0.1); border-radius:12px; margin-bottom:10px;">
                            <div>
                                <div style="font-weight:700;"><?= htmlspecialchars($q['titre']) ?></div>
                                <div style="color:var(--text-muted); font-size:0.85rem;">
                                    <?= $q['nb_participations'] ?> parties · <?= ucfirst($q['categorie']) ?> · <?= ucfirst($q['difficulte']) ?>
                                </div>
                            </div>
                            <a href="jouer_quiz.php?id=<?= $q['id'] ?>" class="btn-secondary" style="padding:8px 20px; font-size:0.8rem;">
                                Voir →
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <footer class="footer">
        <p>⚡ QuizMaster © 2026</p>
    </footer>

</body>
</html>