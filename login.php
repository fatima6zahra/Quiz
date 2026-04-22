<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (empty($email)) {
        $erreurs['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = "Format d'email invalide.";
    }

    if (empty($mdp)) {
        $erreurs['mdp'] = "Le mot de passe est obligatoire.";
    }

    if (empty($erreurs)) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
            $erreurs['global'] = "Email ou mot de passe incorrect.";
        } else {
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nom']    = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — QuizMaster</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">⚡ QUIZMASTER</div>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="login.php" class="active">Connexion</a></li>
            <li><a href="register.php" class="nav-btn">S'inscrire</a></li>
        </ul>
    </nav>

    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:120px 20px 60px; position:relative; z-index:1;">
        <div class="form-container">
            <h1 class="form-titre">CONNEXION</h1>
            <p class="form-sous-titre">Content de vous revoir ! 👋</p>

            <?php if (isset($erreurs['global'])): ?>
                <div class="alerte"><?= $erreurs['global'] ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>

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
                           placeholder="Votre mot de passe" required>
                    <?php if (isset($erreurs['mdp'])): ?>
                        <span class="erreur"><?= $erreurs['mdp'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; margin-top:10px;">
                    ⚡ Se connecter
                </button>

                <p style="text-align:center; margin-top:20px; color:var(--text-muted); font-size:0.9rem;">
                    Pas encore de compte ?
                    <a href="register.php" style="color:#6c63ff; font-weight:700;">S'inscrire</a>
                </p>

            </form>
        </div>
    </div>

    <footer class="footer">
        <p>⚡ QuizMaster © 2026</p>
    </footer>

</body>
</html>