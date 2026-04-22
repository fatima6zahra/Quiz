<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$quiz_id = intval($_GET['id'] ?? 0);
$defi_id = intval($_GET['defi'] ?? 0);

// Récupérer le quiz
$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: liste_quiz.php');
    exit;
}

// Récupérer questions aléatoires
$stmt = $pdo->prepare("
    SELECT * FROM questions
    WHERE quiz_id = ?
    ORDER BY RAND()
    LIMIT 5
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    header('Location: liste_quiz.php');
    exit;
}

// Mélanger les réponses pour chaque question
foreach ($questions as &$q) {
    $reponses = [
        $q['bonne_reponse'],
        $q['reponse2'],
        $q['reponse3'],
        $q['reponse4']
    ];
    shuffle($reponses);
    $q['reponses_melangees'] = $reponses;
}
unset($q);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['titre']) ?> — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="liste_quiz.php">← Quitter</a></li>
        </ul>
    </nav>

    <div class="quiz-game" style="padding-top:100px;">

        <!-- Progress -->
        <div class="quiz-progress">
            <div class="progress-info">
                <span id="question-actuelle">Question 1 / <?= count($questions) ?></span>
                <span id="score-actuel">Score : 0 pts</span>
            </div>
            <div class="progress-barre">
                <div class="progress-remplissage" id="progress-bar"
                     style="width:<?= (1/count($questions))*100 ?>%"></div>
            </div>
        </div>

        <!-- Timer -->
        <div class="timer">
            <div class="timer-cercle" id="timer"><?= $quiz['temps_limite'] ?></div>
        </div>

        <!-- Questions -->
        <?php foreach ($questions as $index => $q): ?>
        <div class="question-slide" id="question-<?= $index ?>"
             style="display:<?= $index === 0 ? 'block' : 'none' ?>;"
             data-bonne-reponse="<?= htmlspecialchars($q['bonne_reponse']) ?>"
             data-points="<?= $q['points'] ?>">

            <div class="question-card">
                <div class="question-numero">Question <?= $index + 1 ?> / <?= count($questions) ?></div>
                <div class="question-texte"><?= htmlspecialchars($q['contenu']) ?></div>
            </div>

            <div class="reponses-grid">
                <?php
                $lettres = ['A', 'B', 'C', 'D'];
                foreach ($q['reponses_melangees'] as $i => $reponse):
                ?>
                <button class="reponse-btn"
                        onclick="choisirReponse(this, '<?= htmlspecialchars($reponse, ENT_QUOTES) ?>', <?= $index ?>)">
                    <span class="reponse-lettre"><?= $lettres[$i] ?></span>
                    <?= htmlspecialchars($reponse) ?>
                </button>
                <?php endforeach; ?>
            </div>

        </div>
        <?php endforeach; ?>

        <!-- Bouton suivant -->
        <div style="text-align:center; margin-top:30px;">
            <button class="btn-primary" id="btn-suivant"
                    style="display:none; padding:15px 50px; font-size:1rem;"
                    onclick="questionSuivante()">
                Question suivante →
            </button>
            <button class="btn-primary" id="btn-terminer"
                    style="display:none; padding:15px 50px; font-size:1rem;"
                    onclick="terminerQuiz()">
                🏆 Voir mes résultats
            </button>
        </div>

    </div>

    <!-- Formulaire caché pour envoyer les résultats -->
    <form id="form-resultats" method="POST" action="resultat_quiz.php" style="display:none;">
        <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
        <input type="hidden" name="defi_id" value="<?= $defi_id ?>">
        <input type="hidden" name="score" id="input-score">
        <input type="hidden" name="bonnes_reponses" id="input-bonnes">
        <input type="hidden" name="reponses_json" id="input-reponses">
    </form>

    <script>
        // Données des questions
        const questions = <?= json_encode(array_map(function($q) {
            return [
                'id' => $q['id'],
                'contenu' => $q['contenu'],
                'bonne_reponse' => $q['bonne_reponse'],
                'points' => $q['points']
            ];
        }, $questions)) ?>;

        const tempsLimite = <?= $quiz['temps_limite'] ?>;
        let questionActuelle = 0;
        let score = 0;
        let bonnesReponses = 0;
        let reponsesDonnees = [];
        let timer;
        let tempsRestant = tempsLimite;
        let reponduCetteQuestion = false;

        // Démarrer le timer
        function demarrerTimer() {
            tempsRestant = tempsLimite;
            const timerEl = document.getElementById('timer');
            timerEl.textContent = tempsRestant;
            timerEl.classList.remove('urgent');

            timer = setInterval(() => {
                tempsRestant--;
                timerEl.textContent = tempsRestant;

                if (tempsRestant <= 5) {
                    timerEl.classList.add('urgent');
                }

                if (tempsRestant <= 0) {
                    clearInterval(timer);
                    if (!reponduCetteQuestion) {
                        // Temps écoulé
                        reponduCetteQuestion = true;
                        reponsesDonnees.push({
                            question_id: questions[questionActuelle].id,
                            reponse: '',
                            correcte: 0
                        });
                        afficherBonneReponse();
                        afficherBoutonSuivant();
                    }
                }
            }, 1000);
        }

        // Choisir une réponse
        function choisirReponse(btn, reponse, qIndex) {
            if (reponduCetteQuestion) return;
            reponduCetteQuestion = true;
            clearInterval(timer);

            const bonneReponse = questions[qIndex].bonne_reponse;
            const points = questions[qIndex].points;
            const estCorrecte = reponse === bonneReponse;

            if (estCorrecte) {
                btn.classList.add('correcte');
                // Bonus points selon temps restant
                const bonus = Math.floor(tempsRestant / tempsLimite * points);
                score += points + bonus;
                bonnesReponses++;
            } else {
                btn.classList.add('incorrecte');
                afficherBonneReponse();
            }

            reponsesDonnees.push({
                question_id: questions[qIndex].id,
                reponse: reponse,
                correcte: estCorrecte ? 1 : 0
            });

            document.getElementById('score-actuel').textContent = `Score : ${score} pts`;
            afficherBoutonSuivant();
        }

        // Afficher la bonne réponse
        function afficherBonneReponse() {
            const slide = document.getElementById(`question-${questionActuelle}`);
            const btns = slide.querySelectorAll('.reponse-btn');
            const bonneReponse = questions[questionActuelle].bonne_reponse;

            btns.forEach(btn => {
                if (btn.textContent.trim().includes(bonneReponse)) {
                    btn.classList.add('correcte');
                }
            });
        }

        // Afficher bouton suivant
        function afficherBoutonSuivant() {
            const estDerniere = questionActuelle >= questions.length - 1;
            document.getElementById('btn-suivant').style.display = estDerniere ? 'none' : 'inline-block';
            document.getElementById('btn-terminer').style.display = estDerniere ? 'inline-block' : 'none';
        }

        // Question suivante
        function questionSuivante() {
            document.getElementById(`question-${questionActuelle}`).style.display = 'none';
            questionActuelle++;
            document.getElementById(`question-${questionActuelle}`).style.display = 'block';
            document.getElementById('btn-suivant').style.display = 'none';
            document.getElementById('btn-terminer').style.display = 'none';
            document.getElementById('question-actuelle').textContent =
                `Question ${questionActuelle + 1} / ${questions.length}`;

            // Mettre à jour la barre de progression
            const progress = ((questionActuelle + 1) / questions.length) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';

            reponduCetteQuestion = false;
            demarrerTimer();
        }

        // Terminer le quiz
        function terminerQuiz() {
            document.getElementById('input-score').value = score;
            document.getElementById('input-bonnes').value = bonnesReponses;
            document.getElementById('input-reponses').value = JSON.stringify(reponsesDonnees);
            document.getElementById('form-resultats').submit();
        }

        // Démarrer
        demarrerTimer();
    </script>

</body>
</html>