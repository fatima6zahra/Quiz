<?php
session_start();
require_once 'db.php';

// Filtres
$categorie  = $_GET['categorie']  ?? '';
$difficulte = $_GET['difficulte'] ?? '';
$recherche  = $_GET['recherche']  ?? '';

// Construction requête dynamique
$sql = "
    SELECT q.*, u.prenom, u.nom,
    COUNT(DISTINCT p.id) as nb_participations
    FROM quiz q
    LEFT JOIN utilisateurs u ON q.createur_id = u.id
    LEFT JOIN participations p ON q.id = p.quiz_id
    WHERE 1=1
";
$params = [];

if (!empty($categorie)) {
    $sql .= " AND q.categorie = ?";
    $params[] = $categorie;
}

if (!empty($difficulte)) {
    $sql .= " AND q.difficulte = ?";
    $params[] = $difficulte;
}

if (!empty($recherche)) {
    $sql .= " AND (q.titre LIKE ? OR q.description LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

$sql .= " GROUP BY q.id ORDER BY nb_participations DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quiz_liste = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="liste_quiz.php" class="active">Quiz</a></li>
            <li><a href="classement.php">Classement</a></li>
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
        <h1>TOUS LES QUIZ</h1>
        <p><?= count($quiz_liste) ?> quiz disponibles</p>
    </div>

    <section class="section" style="padding-top:20px;">

        <!-- Barre de recherche et filtres -->
        <form method="GET" action="liste_quiz.php">
            <div class="recherche-bar">
                <input type="text" name="recherche"
                       value="<?= htmlspecialchars($recherche) ?>"
                       placeholder="🔍 Rechercher un quiz...">

                <select name="categorie">
                    <option value="">Toutes les catégories</option>
                    <option value="sport"          <?= $categorie === 'sport'          ? 'selected' : '' ?>>🏆 Sport</option>
                    <option value="culture"        <?= $categorie === 'culture'        ? 'selected' : '' ?>>📚 Culture</option>
                    <option value="science"        <?= $categorie === 'science'        ? 'selected' : '' ?>>🔬 Science</option>
                    <option value="histoire"       <?= $categorie === 'histoire'       ? 'selected' : '' ?>>🏛️ Histoire</option>
                    <option value="divertissement" <?= $categorie === 'divertissement' ? 'selected' : '' ?>>🎬 Divertissement</option>
                    <option value="geographie"     <?= $categorie === 'geographie'     ? 'selected' : '' ?>>🌍 Géographie</option>
                    <option value="autre"          <?= $categorie === 'autre'          ? 'selected' : '' ?>>✨ Autre</option>
                </select>

                <select name="difficulte">
                    <option value="">Toutes les difficultés</option>
                    <option value="facile"   <?= $difficulte === 'facile'   ? 'selected' : '' ?>>🟢 Facile</option>
                    <option value="moyen"    <?= $difficulte === 'moyen'    ? 'selected' : '' ?>>🟡 Moyen</option>
                    <option value="difficile"<?= $difficulte === 'difficile'? 'selected' : '' ?>>🔴 Difficile</option>
                </select>

                <button type="submit" class="btn-primary" style="padding:14px 30px;">
                    Filtrer
                </button>

                <?php if (!empty($categorie) || !empty($difficulte) || !empty($recherche)): ?>
                    <a href="liste_quiz.php" class="btn-secondary" style="padding:14px 30px;">
                        Réinitialiser
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Liste des quiz -->
        <?php if (empty($quiz_liste)): ?>
            <div style="text-align:center; padding:80px 20px; color:var(--text-muted);">
                <div style="font-size:4rem; margin-bottom:20px;">🔍</div>
                <h3 style="font-family:'Orbitron',monospace; margin-bottom:10px;">Aucun quiz trouvé</h3>
                <p>Essayez d'autres filtres ou <a href="creer_quiz.php" style="color:#6c63ff;">créez le premier !</a></p>
            </div>
        <?php else: ?>
            <div class="grid-3">
                <?php foreach ($quiz_liste as $quiz): ?>
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
                            <div>
                                <div class="quiz-info">👤 <?= htmlspecialchars($quiz['prenom'] . ' ' . $quiz['nom']) ?></div>
                                <div class="quiz-info" style="margin-top:4px;">🎮 <?= $quiz['nb_participations'] ?> parties · ⏱ <?= $quiz['temps_limite'] ?>s/question</div>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <a href="jouer_quiz.php?id=<?= $quiz['id'] ?>" class="btn-primary" style="padding:8px 20px; font-size:0.8rem; text-align:center;">
                                    Jouer →
                                </a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="amis.php?defi_quiz=<?= $quiz['id'] ?>" class="btn-secondary" style="padding:6px 15px; font-size:0.75rem; text-align:center;">
                                        ⚔️ Défier
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </section>

    <footer class="footer">
        <p>⚡ QuizMaster © 2026</p>
    </footer>

</body>
</html>