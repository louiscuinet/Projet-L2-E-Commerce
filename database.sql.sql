-- Script de reconstruction de la base BookShop
-- Usage : Importer dans MySQL avant de lancer l'application

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS aut_livre, livre, auteur, editeur, ligne_commande;
SET FOREIGN_KEY_CHECKS = 1;

-- Table Editeur
CREATE TABLE editeur (
    edID INT AUTO_INCREMENT PRIMARY KEY,
    edNom VARCHAR(50) NOT NULL,
    edWeb VARCHAR(100)
) ENGINE=InnoDB;

-- Table Auteur
CREATE TABLE auteur (
    auID INT AUTO_INCREMENT PRIMARY KEY,
    auPrenom VARCHAR(50) NOT NULL,
    auNom VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- Table Livre (Structure conforme à details.php et recherche.php)
CREATE TABLE livre (
    liID INT AUTO_INCREMENT PRIMARY KEY,
    liTitre VARCHAR(255) NOT NULL,
    liIDEditeur INT,
    liAnnee INT,
    liNbPages INT,
    liPrix DECIMAL(10,2),
    liLangue VARCHAR(20),
    liISBN13 VARCHAR(13),
    liResume TEXT,
    FOREIGN KEY (liIDEditeur) REFERENCES editeur(edID)
) ENGINE=InnoDB;

-- Table de liaison Auteur/Livre
CREATE TABLE aut_livre (
    al_IDLivre INT,
    al_IDAuteur INT,
    PRIMARY KEY (al_IDLivre, al_IDAuteur),
    FOREIGN KEY (al_IDLivre) REFERENCES livre(liID) ON DELETE CASCADE,
    FOREIGN KEY (al_IDAuteur) REFERENCES auteur(auID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table pour le calcul des Top Ventes
CREATE TABLE ligne_commande (
    lcIDLivre INT,
    lcIDCommande INT,
    lcQuantite INT,
    PRIMARY KEY (lcIDLivre, lcIDCommande),
    FOREIGN KEY (lcIDLivre) REFERENCES livre(liID)
) ENGINE=InnoDB;

-- Insertion des données de test (8 livres pour l'accueil)
INSERT INTO editeur VALUES (1, 'Gallimard', 'www.gallimard.fr'), (2, 'Folio', 'www.folio.fr'), (3, 'Pocket', 'www.pocket.fr');
INSERT INTO auteur VALUES (1, 'George', 'Orwell'), (2, 'Aldous', 'Huxley'), (3, 'J.R.R.', 'Tolkien'), (4, 'Albert', 'Camus');

INSERT INTO livre (liID, liTitre, liIDEditeur, liAnnee, liNbPages, liPrix, liLangue, liISBN13, liResume) VALUES 
(1, '1984', 1, 1949, 328, 8.50, 'Français', '9782070368228', 'Big Brother vous regarde.'),
(2, 'Le Meilleur des Mondes', 2, 1932, 285, 9.20, 'Français', '9782070415951', 'Utopie terrifiante.'),
(3, 'La Ferme des animaux', 1, 1945, 150, 7.50, 'Français', '9782070375165', 'Tous les animaux sont égaux.'),
(4, 'Le Seigneur des Anneaux', 3, 1954, 1100, 25.00, 'Français', '9782266154116', 'Un anneau pour les gouverner.'),
(5, 'L''Étranger', 2, 1942, 160, 8.20, 'Français', '9782070360024', 'Maman est morte aujourd''hui.'),
(6, 'Le Hobbit', 3, 1937, 310, 12.00, 'Français', '9782253049418', 'Une aventure inattendue.'),
(7, 'La Peste', 1, 1947, 352, 9.50, 'Français', '9782070360420', 'Chronique d''une épidémie.'),
(8, 'Le Mythe de Sisyphe', 2, 1942, 190, 7.80, 'Français', '9782070322886', 'L''absurde selon Camus.');

INSERT INTO aut_livre VALUES (1, 1), (2, 2), (3, 1), (4, 3), (5, 4), (6, 3), (7, 4), (8, 4);

-- Simulation de ventes pour les Top Ventes
INSERT INTO ligne_commande VALUES (1, 101, 10), (2, 102, 8), (3, 103, 7), (4, 104, 6);