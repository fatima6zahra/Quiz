<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: liste_quiz.php');
    exit;
}

$user_id        = $_SESSION['user_id'];
$quiz_id        = intval($_POST['quiz_id'] ?? 0);
$defi_id        = intval($_POST['defi_id'] ?? 0);
$score          = intval($_POST['score'] ?? 0);
$bonnes_reponses= intval($_POST['bonnes_reponses'] ?? 0);
$reponses_json  = $_POST['reponses_json'] ?? '[]';
$reponses       = json_decode($reponses_json, true);

// Récupérer le quiz
$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: liste_quiz.php');
    exit;
}

// Enregistrer la participation
$stmt = $pdo->prepare("
    INSERT INTO participations (utilisateur_id, quiz_id, score, nb_bonnes_reponses)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$user_id, $quiz_id, $score, $bonnes_reponses]);
$participation_id = $pdo->lastInsertId();

// Enregistrer les réponses
foreach ($reponses as $r) {
    $stmt = $pdo->prepare("
        INSERT INTO reponses (participation_id, question_id, reponse_donnee, est_correcte)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $participation_id,
        $r['question_id'],
        $r['reponse'],
        $r['correcte']
    ]);
}

// Mettre à jour les points utilisateur
$stmt = $pdo->prepare("UPDATE utilisateurs SET points_total = points_total + ? WHERE id = ?");
$stmt->execute([$score, $user_id]);

// Marquer le défi comme accepté
if ($defi_id > 0) {
    $stmt = $pdo->prepare("UPDATE defis SET statut = 'accepte' WHERE id = ?");
    $stmt->execute([$defi_id]);
}

// Récupérer les questions avec bonnes réponses
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Calculer le pourcentage
$nb_questions = count($reponses);
$pourcentage  = $nb_questions > 0 ? round(($bonnes_reponses / $nb_questions) * 100) : 0;

// Message selon score
if ($pourcentage >= 80) {
    $message = "🏆 Excellent ! Vous êtes un champion !";
    $couleur  = "#43e97b";
} elseif ($pourcentage >= 60) {
    $message = "👍 Bien joué ! Continuez comme ça !";
    $couleur  = "#ffd700";
} elseif ($pourcentage >= 40) {
    $message = "😊 Pas mal ! Vous pouvez faire mieux !";
    $couleur  = "#f093fb";
} else {
    $message = "💪 Courage ! Réessayez pour vous améliorer !";
    $couleur  = "#ff6584";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="liste_quiz.php">Quiz</a></li>
            <li><a href="dashboard.php">Mon Espace</a></li>
            <li><a href="logout.php" class="btn-danger">Déconnexion</a></li>
        </ul>
    </nav>

    <section class="section" style="padding-top:100px; position:relative; z-index:1;">
        <div style="max-width:800px; margin:0 auto;">

            <!-- Score principal -->
            <div class="resultat-hero">
                <div class="score-cercle" style="border-color:<?= $couleur ?>; box-shadow:0 0 50px <?= $couleur ?>44;">
                    <div class="score-nombre" style="background:linear-gradient(135deg,<?= $couleur ?>,#6c63ff); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">
                        <?= $pourcentage ?>%
                    </div>
                    <div class="score-label"><?= $bonnes_reponses ?>/<?= $nb_questions ?> bonnes</div>
                </div>

                <h1 style="font-family:'Orbitron',monospace; font-size:2rem; margin-bottom:10px;">
                    <?= htmlspecialchars($quiz['titre']) ?>
                </h1>
                <p style="color:<?= $couleur ?>; font-size:1.2rem; font-weight:700; margin-bottom:10px;">
                    <?= $message ?>
                </p>
                <p style="color:var(--text-muted); font-size:1rem; margin-bottom:30px;">
                    Score total : <span style="color:#6c63ff; font-family:'Orbitron',monospace; font-weight:700;">
                        <?= $score ?> pts
                    </span>
                </p>

                <div style="display:flex; gap:15px; justify-content:center; flex-wrap:wrap;">
                    <a href="jouer_quiz.php?id=<?= $quiz_id ?>" class="btn-primary">
                        🔄 Rejouer
                    </a>
                    <a href="liste_quiz.php" class="btn-secondary">
                        🎮 Autres quiz
                    </a>
                    <a href="dashboard.php" class="btn-secondary">
                        📊 Mon espace
                    </a>
                </div>
            </div>

            <!-- Détail des réponses -->
            <div class="card" style="margin-top:40px;">
                <h2 style="font-family:'Orbitron',monospace; font-size:1.1rem; margin-bottom:25px; color:#6c63ff;">
                    📋 Détail des réponses
                </h2>

                <?php
                // Créer un index des réponses par question_id
                $reponses_index = [];
                foreach ($reponses as $r) {
                    $reponses_index[$r['question_id']] = $r;
                }
                ?>

                <?php foreach ($questions as $i => $q):
                    $rep = $reponses_index[$q['id']] ?? null;
                    $est_correcte = $rep && $rep['correcte'];
                ?>
                    <div style="
                        padding: 20px;
                        border-radius: 15px;
                        margin-bottom: 15px;
                        border: 1px solid <?= $est_correcte ? 'rgba(67,233,123,0.3)' : 'rgba(255,101,132,0.3)' ?>;
                        background: <?= $est_correcte ? 'rgba(67,233,123,0.05)' : 'rgba(255,101,132,0.05)' ?>;
                    ">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                            <span style="font-size:0.75rem; letter-spacing:2px; text-transform:uppercase; color:var(--text-muted);">
                                Question <?= $i + 1 ?>
                            </span>
                            <span style="
                                padding: 3px 12px;
                                border-radius: 20px;
                                font-size: 0.75rem;
                                font-weight: 700;
                                background: <?= $est_correcte ? 'rgba(67,233,123,0.2)' : 'rgba(255,101,132,0.2)' ?>;
                                color: <?= $est_correcte ? '#43e97b' : '#ff6584' ?>;
                            ">
                                <?= $est_correcte ? '✅ Correct' : '❌ Incorrect' ?>
                            </span>
                        </div>

                        <p style="font-weight:600; margin-bottom:12px; font-size:1rem;">
                            <?= htmlspecialchars($q['contenu']) ?>
                        </p>

                        <?php if (!$est_correcte): ?>
                            <div style="font-size:0.88rem; color:var(--text-muted); margin-bottom:5px;">
                                Votre réponse :
                                <span style="color:#ff6584; font-weight:700;">
                                    <?= !empty($rep['reponse']) ? htmlspecialchars($rep['reponse']) : '(temps écoulé)' ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div style="font-size:0.88rem; color:var(--text-muted);">
                            Bonne réponse :
                            <span style="color:#43e97b; font-weight:700;">
                                <?= htmlspecialchars($q['bonne_reponse']) ?>
                            </span>
                            <span style="color:#6c63ff; margin-left:10px;">
                                +<?= $q['points'] ?> pts
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <footer class="footer">
        <p>⚡ QuizMaster © 2026</p>
    </footer>

</body>
</html>