CREATE DATABASE IF NOT EXISTS quiz_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quiz_db;

-- Table utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    points_total INT DEFAULT 0,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table quiz
CREATE TABLE IF NOT EXISTS quiz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    categorie ENUM('sport','culture','divertissement','science','histoire','geographie','autre') NOT NULL,
    difficulte ENUM('facile','moyen','difficile') NOT NULL,
    createur_id INT NOT NULL,
    nb_questions_min INT DEFAULT 5,
    temps_limite INT DEFAULT 30,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (createur_id) REFERENCES utilisateurs(id)
);

-- Table questions
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    contenu TEXT NOT NULL,
    type ENUM('texte','image','video','son') DEFAULT 'texte',
    media VARCHAR(255) DEFAULT NULL,
    bonne_reponse VARCHAR(255) NOT NULL,
    reponse2 VARCHAR(255) NOT NULL,
    reponse3 VARCHAR(255) NOT NULL,
    reponse4 VARCHAR(255) NOT NULL,
    points INT DEFAULT 10,
    FOREIGN KEY (quiz_id) REFERENCES quiz(id)
);

-- Table participations
CREATE TABLE IF NOT EXISTS participations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT DEFAULT 0,
    nb_bonnes_reponses INT DEFAULT 0,
    date_participation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (quiz_id) REFERENCES quiz(id)
);

-- Table reponses
CREATE TABLE IF NOT EXISTS reponses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participation_id INT NOT NULL,
    question_id INT NOT NULL,
    reponse_donnee VARCHAR(255),
    est_correcte TINYINT(1) DEFAULT 0,
    FOREIGN KEY (participation_id) REFERENCES participations(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- Table amis
CREATE TABLE IF NOT EXISTS amis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demandeur_id INT NOT NULL,
    receveur_id INT NOT NULL,
    statut ENUM('en_attente','accepte','refuse') DEFAULT 'en_attente',
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (demandeur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (receveur_id) REFERENCES utilisateurs(id)
);

-- Table defis
CREATE TABLE IF NOT EXISTS defis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    envoyeur_id INT NOT NULL,
    receveur_id INT NOT NULL,
    quiz_id INT NOT NULL,
    statut ENUM('en_attente','accepte','refuse') DEFAULT 'en_attente',
    date_defi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (envoyeur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (receveur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (quiz_id) REFERENCES quiz(id)
);

-- Quelques quiz de démonstration
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe) VALUES
('Admin', 'Site', 'admin@quiz.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO quiz (titre, description, categorie, difficulte, createur_id) VALUES
('Culture Générale #1', 'Testez vos connaissances générales !', 'culture', 'facile', 1),
('Sport & Champions', 'Quiz sur les grands champions du sport', 'sport', 'moyen', 1),
('Science & Nature', 'Explorez le monde scientifique', 'science', 'difficile', 1);

INSERT INTO questions (quiz_id, contenu, bonne_reponse, reponse2, reponse3, reponse4, points) VALUES
(1, 'Quelle est la capitale de la France ?', 'Paris', 'Lyon', 'Marseille', 'Bordeaux', 10),
(1, 'Combien de continents y a-t-il sur Terre ?', '7', '5', '6', '8', 10),
(1, 'Qui a peint la Joconde ?', 'Léonard de Vinci', 'Michel-Ange', 'Raphaël', 'Botticelli', 10),
(1, 'Quelle est la plus grande planète du système solaire ?', 'Jupiter', 'Saturne', 'Neptune', 'Uranus', 10),
(1, 'En quelle année a eu lieu la Révolution française ?', '1789', '1799', '1776', '1804', 10),
(2, 'Quel pays a remporté la Coupe du Monde 2018 ?', 'France', 'Brésil', 'Allemagne', 'Argentine', 10),
(2, 'Qui détient le record de buts en Ligue des Champions ?', 'Cristiano Ronaldo', 'Lionel Messi', 'Raul', 'Benzema', 10),
(2, 'Dans quel sport utilise-t-on un volant ?', 'Badminton', 'Tennis', 'Squash', 'Padel', 10),
(2, 'Combien de joueurs dans une équipe de basketball ?', '5', '6', '7', '4', 10),
(2, 'Quel athlète a remporté 8 médailles d\'or aux JO 2008 ?', 'Michael Phelps', 'Usain Bolt', 'Carl Lewis', 'Mark Spitz', 10),
(3, 'Quelle est la formule chimique de l\'eau ?', 'H2O', 'CO2', 'O2', 'H2SO4', 10),
(3, 'Combien d\'os a le corps humain adulte ?', '206', '208', '200', '212', 10),
(3, 'Quelle planète est la plus proche du Soleil ?', 'Mercure', 'Vénus', 'Mars', 'Terre', 10),
(3, 'Quel est le symbole chimique de l\'or ?', 'Au', 'Ag', 'Fe', 'Cu', 10),
(3, 'À quelle vitesse voyage la lumière ?', '300 000 km/s', '150 000 km/s', '450 000 km/s', '200 000 km/s', 10);