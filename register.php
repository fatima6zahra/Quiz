<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreurs = [];
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom      = trim($_POST['nom']      ?? '');
    $prenom   = trim($_POST['prenom']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $mdp      = $_POST['mot_de_passe']  ?? '';
    $mdp_conf = $_POST['confirmation']  ?? '';

    if (empty($nom))    $erreurs['nom']    = "Le nom est obligatoire.";
    if (empty($prenom)) $erreurs['prenom'] = "Le prénom est obligatoire.";

    if (empty($email)) {
        $erreurs['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = "Email invalide.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $erreurs['email'] = "Cet email est déjà utilisé.";
    }

    if (empty($mdp)) {
        $erreurs['mdp'] = "Le mot de passe est obligatoire.";
    } elseif (strlen($mdp) < 8) {
        $erreurs['mdp'] = "Minimum 8 caractères.";
    } elseif (!preg_match('/[A-Z]/', $mdp)) {
        $erreurs['mdp'] = "Au moins une majuscule requise.";
    } elseif (!preg_match('/[0-9]/', $mdp)) {
        $erreurs['mdp'] = "Au moins un chiffre requis.";
    }

    if ($mdp !== $mdp_conf) {
        $erreurs['confirmation'] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($erreurs)) {
        $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nom, $prenom, $email, $mdp_hash]);

        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id']  = $user_id;
        $_SESSION['user_nom'] = $prenom . ' ' . $nom;
        $_SESSION['user_prenom'] = $prenom;

        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="login.php">Connexion</a></li>
            <li><a href="register.php" class="nav-btn active">S'inscrire</a></li>
        </ul>
    </nav>

    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:120px 20px 60px; position:relative; z-index:1;">
        <div class="form-container">
            <h1 class="form-titre">CRÉER UN COMPTE</h1>
            <p class="form-sous-titre">Rejoignez des milliers de joueurs !</p>

            <form method="POST" action="register.php" novalidate>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="champ">
                        <label>Nom *</label>
                        <input type="text" name="nom"
                               value="<?= htmlspecialchars($nom ?? '') ?>"
                               placeholder="Dupont" required>
                        <?php if (isset($erreurs['nom'])): ?>
                            <span class="erreur"><?= $erreurs['nom'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="champ">
                        <label>Prénom *</label>
                        <input type="text" name="prenom"
                               value="<?= htmlspecialchars($prenom ?? '') ?>"
                               placeholder="Jean" required>
                        <?php if (isset($erreurs['prenom'])): ?>
                            <span class="erreur"><?= $erreurs['prenom'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="champ">
                    <label>Email *</label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($email ?? '') ?>"
                           placeholder="jean@email.fr" required>
                    <?php if (isset($erreurs['email'])): ?>
                        <span class="erreur"><?= $erreurs['email'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="champ">
                    <label>Mot de passe *</label>
                    <input type="password" name="mot_de_passe"
                           placeholder="Min. 8 car., 1 majuscule, 1 chiffre" required>
                    <?php if (isset($erreurs['mdp'])): ?>
                        <span class="erreur"><?= $erreurs['mdp'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="champ">
                    <label>Confirmer le mot de passe *</label>
                    <input type="password" name="confirmation"
                           placeholder="Répétez votre mot de passe" required>
                    <?php if (isset($erreurs['confirmation'])): ?>
                        <span class="erreur"><?= $erreurs['confirmation'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; margin-top:10px;">
                    🚀 Créer mon compte
                </button>

                <p style="text-align:center; margin-top:20px; color:var(--text-muted); font-size:0.9rem;">
                    Déjà un compte ?
                    <a href="login.php" style="color:#6c63ff; font-weight:700;">Se connecter</a>
                </p>

            </form>
        </div>
    </div>

    <footer class="footer">
        <p>⚡ QuizMaster © 2026</p>
    </footer>

</body>
</html>