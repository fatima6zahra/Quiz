<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$erreurs = [];
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre      = trim($_POST['titre']      ?? '');
    $description= trim($_POST['description']?? '');
    $categorie  = $_POST['categorie']       ?? '';
    $difficulte = $_POST['difficulte']      ?? '';
    $temps      = intval($_POST['temps']    ?? 30);
    $questions  = $_POST['questions']       ?? [];

    // Validation
    if (empty($titre))      $erreurs['titre']      = "Le titre est obligatoire.";
    if (empty($categorie))  $erreurs['categorie']  = "La catégorie est obligatoire.";
    if (empty($difficulte)) $erreurs['difficulte'] = "La difficulté est obligatoire.";

    // Validation questions
    $questions_valides = [];
    foreach ($questions as $q) {
        if (!empty($q['contenu']) && !empty($q['bonne_reponse']) &&
            !empty($q['reponse2']) && !empty($q['reponse3']) && !empty($q['reponse4'])) {
            $questions_valides[] = $q;
        }
    }

    if (count($questions_valides) < 5) {
        $erreurs['questions'] = "Minimum 5 questions valides requises.";
    }

    if (empty($erreurs)) {
        // Insertion quiz
        $stmt = $pdo->prepare("
            INSERT INTO quiz (titre, description, categorie, difficulte, createur_id, temps_limite)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$titre, $description, $categorie, $difficulte, $_SESSION['user_id'], $temps]);
        $quiz_id = $pdo->lastInsertId();

        // Insertion questions
        foreach ($questions_valides as $q) {
            $stmt = $pdo->prepare("
                INSERT INTO questions (quiz_id, contenu, type, bonne_reponse, reponse2, reponse3, reponse4, points)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $quiz_id,
                $q['contenu'],
                $q['type'] ?? 'texte',
                $q['bonne_reponse'],
                $q['reponse2'],
                $q['reponse3'],
                $q['reponse4'],
                intval($q['points'] ?? 10)
            ]);
        }

        header('Location: liste_quiz.php?succes=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Quiz — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="liste_quiz.php">Quiz</a></li>
            <li><a href="dashboard.php">Mon Espace</a></li>
            <li><a href="creer_quiz.php" class="active nav-btn">+ Créer</a></li>
            <li><a href="logout.php" class="btn-danger">Déconnexion</a></li>
        </ul>
    </nav>

    <div class="page-header">
        <h1>CRÉER UN QUIZ</h1>
        <p>Partagez vos connaissances !</p>
    </div>

    <section class="section" style="padding-top:20px; position:relative; z-index:1;">
        <div style="max-width:800px; margin:0 auto;">

            <?php if (!empty($erreurs)): ?>
                <div class="alerte" style="margin-bottom:20px;">
                    ⚠️ Veuillez corriger les erreurs ci-dessous.
                </div>
            <?php endif; ?>

            <form method="POST" action="creer_quiz.php" id="form-quiz">

                <!-- Infos générales -->
                <div class="card" style="margin-bottom:25px;">
                    <h2 style="font-family:'Orbitron',monospace; font-size:1.1rem; margin-bottom:25px; color:#6c63ff;">
                        📋 Informations générales
                    </h2>

                    <div class="champ">
                        <label>Titre du quiz *</label>
                        <input type="text" name="titre"
                               value="<?= htmlspecialchars($titre ?? '') ?>"
                               placeholder="Ex: Culture Générale #1" required>
                        <?php if (isset($erreurs['titre'])): ?>
                            <span class="erreur"><?= $erreurs['titre'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="champ">
                        <label>Description</label>
                        <textarea name="description" rows="3"
                                  placeholder="Décrivez votre quiz..."
                        ><?= htmlspecialchars($description ?? '') ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
                        <div class="champ">
                            <label>Catégorie *</label>
                            <select name="categorie" required>
                                <option value="">Choisir...</option>
                                <option value="sport"          <?= ($categorie??'') === 'sport'          ? 'selected':'' ?>>🏆 Sport</option>
                                <option value="culture"        <?= ($categorie??'') === 'culture'        ? 'selected':'' ?>>📚 Culture</option>
                                <option value="science"        <?= ($categorie??'') === 'science'        ? 'selected':'' ?>>🔬 Science</option>
                                <option value="histoire"       <?= ($categorie??'') === 'histoire'       ? 'selected':'' ?>>🏛️ Histoire</option>
                                <option value="divertissement" <?= ($categorie??'') === 'divertissement' ? 'selected':'' ?>>🎬 Divertissement</option>
                                <option value="geographie"     <?= ($categorie??'') === 'geographie'     ? 'selected':'' ?>>🌍 Géographie</option>
                                <option value="autre"          <?= ($categorie??'') === 'autre'          ? 'selected':'' ?>>✨ Autre</option>
                            </select>
                            <?php if (isset($erreurs['categorie'])): ?>
                                <span class="erreur"><?= $erreurs['categorie'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="champ">
                            <label>Difficulté *</label>
                            <select name="difficulte" required>
                                <option value="">Choisir...</option>
                                <option value="facile"    <?= ($difficulte??'') === 'facile'    ? 'selected':'' ?>>🟢 Facile</option>
                                <option value="moyen"     <?= ($difficulte??'') === 'moyen'     ? 'selected':'' ?>>🟡 Moyen</option>
                                <option value="difficile" <?= ($difficulte??'') === 'difficile' ? 'selected':'' ?>>🔴 Difficile</option>
                            </select>
                            <?php if (isset($erreurs['difficulte'])): ?>
                                <span class="erreur"><?= $erreurs['difficulte'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="champ">
                            <label>Temps/question (sec)</label>
                            <input type="number" name="temps"
                                   value="<?= $temps ?? 30 ?>"
                                   min="10" max="120">
                        </div>
                    </div>
                </div>

                <!-- Questions -->
                <div class="card" style="margin-bottom:25px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                        <h2 style="font-family:'Orbitron',monospace; font-size:1.1rem; color:#6c63ff;">
                            ❓ Questions <span id="compteur">(0/5 minimum)</span>
                        </h2>
                        <button type="button" onclick="ajouterQuestion()" class="btn-success">
                            + Ajouter une question
                        </button>
                    </div>

                    <?php if (isset($erreurs['questions'])): ?>
                        <div class="alerte"><?= $erreurs['questions'] ?></div>
                    <?php endif; ?>

                    <div id="questions-container"></div>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; padding:18px; font-size:1.1rem;">
                    🚀 Publier le Quiz
                </button>

            </form>
        </div>
    </section>

    <footer class="footer">
        <p>⚡ QuizMaster © 2026</p>
    </footer>

    <script>
        let nbQuestions = 0;

        function ajouterQuestion() {
            nbQuestions++;
            const container = document.getElementById('questions-container');
            const div = document.createElement('div');
            div.id = `question-${nbQuestions}`;
            div.style.cssText = `
                background: rgba(108,99,255,0.05);
                border: 1px solid rgba(108,99,255,0.2);
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 20px;
                position: relative;
            `;
            div.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="font-family:'Orbitron',monospace; font-size:0.9rem; color:#6c63ff;">
                        QUESTION ${nbQuestions}
                    </h3>
                    <button type="button" onclick="supprimerQuestion(${nbQuestions})"
                            style="background:rgba(255,101,132,0.2); color:#ff6584; border:1px solid rgba(255,101,132,0.3); border-radius:8px; padding:5px 12px; cursor:pointer; font-family:'Rajdhani',sans-serif;">
                        ✕ Supprimer
                    </button>
                </div>

                <div class="champ">
                    <label>Question *</label>
                    <textarea name="questions[${nbQuestions}][contenu]" rows="2"
                              placeholder="Écrivez votre question..." required></textarea>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div class="champ" style="margin:0;">
                        <label>✅ Bonne réponse *</label>
                        <input type="text" name="questions[${nbQuestions}][bonne_reponse]"
                               placeholder="Réponse correcte" required
                               style="border-color:rgba(67,233,123,0.5);">
                    </div>
                    <div class="champ" style="margin:0;">
                        <label>❌ Réponse 2 *</label>
                        <input type="text" name="questions[${nbQuestions}][reponse2]"
                               placeholder="Mauvaise réponse" required>
                    </div>
                    <div class="champ" style="margin:0;">
                        <label>❌ Réponse 3 *</label>
                        <input type="text" name="questions[${nbQuestions}][reponse3]"
                               placeholder="Mauvaise réponse" required>
                    </div>
                    <div class="champ" style="margin:0;">
                        <label>❌ Réponse 4 *</label>
                        <input type="text" name="questions[${nbQuestions}][reponse4]"
                               placeholder="Mauvaise réponse" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="champ" style="margin:0;">
                        <label>Type</label>
                        <select name="questions[${nbQuestions}][type]">
                            <option value="texte">📝 Texte</option>
                            <option value="image">🖼️ Image</option>
                            <option value="video">🎬 Vidéo</option>
                            <option value="son">🎵 Son</option>
                        </select>
                    </div>
                    <div class="champ" style="margin:0;">
                        <label>Points</label>
                        <input type="number" name="questions[${nbQuestions}][points]"
                               value="10" min="5" max="50">
                    </div>
                </div>
            `;
            container.appendChild(div);
            mettreAJourCompteur();
        }

        function supprimerQuestion(id) {
            document.getElementById(`question-${id}`).remove();
            mettreAJourCompteur();
        }

        function mettreAJourCompteur() {
            const nb = document.querySelectorAll('#questions-container > div').length;
            const compteur = document.getElementById('compteur');
            compteur.textContent = `(${nb}/5 minimum)`;
            compteur.style.color = nb >= 5 ? '#43e97b' : '#ff6584';
        }

        // Ajouter 5 questions par défaut
        for (let i = 0; i < 5; i++) ajouterQuestion();
    </script>

</body>
</html>