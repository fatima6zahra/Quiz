<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$succes  = '';
$erreur  = '';

// Envoyer une demande d'ami
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'envoyer_demande') {
        $email = trim($_POST['email'] ?? '');
        $stmt  = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        $ami = $stmt->fetch();

        if (!$ami) {
            $erreur = "Aucun utilisateur trouvé avec cet email.";
        } else {
            // Vérifier si une demande existe déjà
            $stmt = $pdo->prepare("
                SELECT id FROM amis
                WHERE (demandeur_id = ? AND receveur_id = ?)
                OR (demandeur_id = ? AND receveur_id = ?)
            ");
            $stmt->execute([$user_id, $ami['id'], $ami['id'], $user_id]);

            if ($stmt->fetch()) {
                $erreur = "Une demande existe déjà avec cet utilisateur.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO amis (demandeur_id, receveur_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $ami['id']]);
                $succes = "Demande d'ami envoyée !";
            }
        }
    }

    elseif ($_POST['action'] === 'accepter') {
        $ami_id = intval($_POST['ami_id']);
        $stmt   = $pdo->prepare("UPDATE amis SET statut = 'accepte' WHERE id = ?");
        $stmt->execute([$ami_id]);
        $succes = "Demande acceptée !";
    }

    elseif ($_POST['action'] === 'refuser') {
        $ami_id = intval($_POST['ami_id']);
        $stmt   = $pdo->prepare("UPDATE amis SET statut = 'refuse' WHERE id = ?");
        $stmt->execute([$ami_id]);
        $succes = "Demande refusée.";
    }

    elseif ($_POST['action'] === 'defier') {
        $receveur_id = intval($_POST['receveur_id']);
        $quiz_id     = intval($_POST['quiz_id']);
        $stmt        = $pdo->prepare("
            INSERT INTO defis (envoyeur_id, receveur_id, quiz_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $receveur_id, $quiz_id]);
        $succes = "Défi envoyé ! 🎯";
    }
}

// Récupérer les demandes en attente reçues
$stmt = $pdo->prepare("
    SELECT a.id, a.date_demande, u.prenom, u.nom, u.email
    FROM amis a
    JOIN utilisateurs u ON a.demandeur_id = u.id
    WHERE a.receveur_id = ? AND a.statut = 'en_attente'
");
$stmt->execute([$user_id]);
$demandes = $stmt->fetchAll();

// Récupérer mes amis
$stmt = $pdo->prepare("
    SELECT u.id, u.prenom, u.nom, u.email, u.points_total
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

// Récupérer les quiz pour défier
$stmt  = $pdo->query("SELECT id, titre FROM quiz ORDER BY titre");
$quiz_list = $stmt->fetchAll();

// Résultats des amis
$resultats_amis = [];
foreach ($amis as $ami) {
    $stmt = $pdo->prepare("
        SELECT p.*, q.titre, q.categorie
        FROM participations p
        JOIN quiz q ON p.quiz_id = q.id
        WHERE p.utilisateur_id = ?
        ORDER BY p.date_participation DESC
        LIMIT 3
    ");
    $stmt->execute([$ami['id']]);
    $resultats_amis[$ami['id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amis — QuizMaster</title>
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
            <li><a href="amis.php" class="active">Amis
                <?php if (count($demandes) > 0): ?>
                    <span class="notif-badge"><?= count($demandes) ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="dashboard.php">Mon Espace</a></li>
            <li><a href="creer_quiz.php" class="nav-btn">+ Créer</a></li>
            <li><a href="logout.php" class="btn-danger">Déconnexion</a></li>
        </ul>
    </nav>

    <div class="page-header">
        <h1>MES AMIS</h1>
        <p>Défiez vos amis et comparez vos scores !</p>
    </div>

    <section class="section" style="padding-top:20px; position:relative; z-index:1;">
        <div style="max-width:1000px; margin:0 auto;">

            <?php if ($succes): ?>
                <div class="succes"><?= $succes ?></div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alerte"><?= $erreur ?></div>
            <?php endif; ?>

            <div class="grid-2">

                <!-- Ajouter un ami -->
                <div class="card">
                    <h2 style="font-family:'Orbitron',monospace; font-size:1rem; margin-bottom:20px; color:#6c63ff;">
                        ➕ Ajouter un ami
                    </h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="envoyer_demande">
                        <div class="champ">
                            <label>Email de l'ami</label>
                            <input type="email" name="email"
                                   placeholder="ami@email.fr" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;">
                            Envoyer la demande
                        </button>
                    </form>
                </div>

                <!-- Demandes reçues -->
                <div class="card">
                    <h2 style="font-family:'Orbitron',monospace; font-size:1rem; margin-bottom:20px; color:#ffd700;">
                        📬 Demandes reçues (<?= count($demandes) ?>)
                    </h2>
                    <?php if (empty($demandes)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:20px;">
                            Aucune demande en attente
                        </p>
                    <?php else: ?>
                        <?php foreach ($demandes as $d): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; background:rgba(255,215,0,0.05); border:1px solid rgba(255,215,0,0.2); border-radius:12px; margin-bottom:10px;">
                                <div>
                                    <div style="font-weight:700;"><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></div>
                                    <div style="color:var(--text-muted); font-size:0.82rem;"><?= htmlspecialchars($d['email']) ?></div>
                                </div>
                                <div style="display:flex; gap:8px;">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="accepter">
                                        <input type="hidden" name="ami_id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn-success" style="padding:6px 15px;">✓</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="refuser">
                                        <input type="hidden" name="ami_id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn-danger" style="padding:6px 15px;">✕</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Liste des amis -->
            <div class="card" style="margin-top:25px;">
                <h2 style="font-family:'Orbitron',monospace; font-size:1rem; margin-bottom:25px; color:#43e97b;">
                    👥 Mes amis (<?= count($amis) ?>)
                </h2>

                <?php if (empty($amis)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">
                        Vous n'avez pas encore d'amis.<br>
                        Ajoutez des amis pour voir leurs résultats !
                    </p>
                <?php else: ?>
                    <?php foreach ($amis as $ami): ?>
                        <div style="background:rgba(108,99,255,0.05); border:1px solid rgba(108,99,255,0.2); border-radius:15px; padding:20px; margin-bottom:20px;">

                            <!-- Infos ami -->
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <div style="width:50px; height:50px; border-radius:50%; background:linear-gradient(135deg,#6c63ff,#ff6584); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem;">
                                        <?= strtoupper(substr($ami['prenom'],0,1).substr($ami['nom'],0,1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; font-size:1rem;"><?= htmlspecialchars($ami['prenom'].' '.$ami['nom']) ?></div>
                                        <div style="color:var(--text-muted); font-size:0.82rem;"><?= $ami['points_total'] ?> points</div>
                                    </div>
                                </div>

                                <!-- Défier -->
                                <div>
                                    <button onclick="toggleDefi(<?= $ami['id'] ?>)" class="btn-primary" style="padding:8px 20px; font-size:0.82rem;">
                                        ⚔️ Défier
                                    </button>
                                </div>
                            </div>

                            <!-- Formulaire de défi -->
                            <div id="defi-<?= $ami['id'] ?>" style="display:none; background:rgba(108,99,255,0.1); border-radius:10px; padding:15px; margin-bottom:15px;">
                                <form method="POST">
                                    <input type="hidden" name="action" value="defier">
                                    <input type="hidden" name="receveur_id" value="<?= $ami['id'] ?>">
                                    <div style="display:flex; gap:10px; align-items:flex-end;">
                                        <div class="champ" style="flex:1; margin:0;">
                                            <label>Choisir un quiz</label>
                                            <select name="quiz_id" required>
                                                <?php foreach ($quiz_list as $q): ?>
                                                    <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['titre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn-primary" style="padding:14px 20px;">
                                            Envoyer le défi ⚔️
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Résultats récents de l'ami -->
                            <?php if (!empty($resultats_amis[$ami['id']])): ?>
                                <div style="font-size:0.78rem; letter-spacing:1px; text-transform:uppercase; color:var(--text-muted); margin-bottom:10px;">
                                    Dernières parties
                                </div>
                                <?php foreach ($resultats_amis[$ami['id']] as $r): ?>
                                    <div style="display:flex; justify-content:space-between; padding:8px 12px; background:rgba(255,255,255,0.03); border-radius:8px; margin-bottom:5px; font-size:0.88rem;">
                                        <span><?= htmlspecialchars($r['titre']) ?></span>
                                        <span style="color:#6c63ff; font-family:'Orbitron',monospace; font-weight:700;"><?= $r['score'] ?> pts</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <footer class="footer">
        <p> QuizMaster © 2026</p>
    </footer>

    <script>
        function toggleDefi(amiId) {
            const div = document.getElementById(`defi-${amiId}`);
            div.style.display = div.style.display === 'none' ? 'block' : 'none';
        }
    </script>

</body>
</html>