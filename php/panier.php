<?php

require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

ob_start(); 

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'ajouter') {
        $idLivre = (int) ($_GET['id'] ?? 0);

        if ($idLivre > 0) {
            if (!isset($_SESSION['panier'])) {
                $_SESSION['panier'] = [];
            }

            if (isset($_SESSION['panier'][$idLivre])) {
                $_SESSION['panier'][$idLivre]['quantite'] += 1;
            } else {
                $_SESSION['panier'][$idLivre] = ['quantite' => 1];
            }
            $_SESSION['message_panier'] = 'Article ajouté au panier !';
            header('Location: ../index.php?ajout=ok');
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idLivre = isset($_POST['idLivre']) ? (int) $_POST['idLivre'] : 0;

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'modifier') {
            $quantite = max(0, (int) $_POST['quantite']);
            if ($quantite === 0) {
                unset($_SESSION['panier'][$idLivre]);
            } else {
                $_SESSION['panier'][$idLivre]['quantite'] = $quantite;
            }
        } elseif ($_POST['action'] === 'supprimer') {
            unset($_SESSION['panier'][$idLivre]);
        } elseif ($_POST['action'] === 'commander') {
	    // Vérifie si l'utilisateur est connecté
    		if (!isset($_SESSION['cliID'])) {
        	// On sauvegarde qu’il voulait commander, pour y revenir après connexion
        	$_SESSION['retour_commande'] = true;
        	header('Location: connexion.php');
        	exit();
    }

	     $adresse = trim($_POST['adresse'] ?? '');




    		// Vérification basique du format de l'adresse :
    		// Exemple: doit contenir au moins un chiffre, un mot pour rue, une ville et un code postal 5 		chiffres
    		// Cette regex vérifie :prefixe + numéro + texte + ville + code postal +pays (simplifié)
    		$pattern = '/^[a-zA-Z0-9\/\s\-\']*\s*-\s*\d+\s+[a-zA-Z0-9\s\'\-,]+\s+\d{5}\s+[a-zA-Z\s\-]+$/';


    		if (empty($adresse)) {
        		$_SESSION['message_commande'] = 'Veuillez saisir une adresse de livraison.';
        		header('Location: panier.php');
        		exit();
    		} elseif (!preg_match($pattern, $adresse)) {
        		$_SESSION['message_commande'] = 'Format d\'adresse invalide. Veuillez indiquer numéro  nom de rue, ville, code postal(5 chiffres) et pays .';
        		header('Location: panier.php');
        		exit();
    		}
    		$bd = bdConnect();
            // Insertion dans la table commande
            $sqlCommande = "INSERT INTO commande (coIDClient, coDate) VALUES (?, NOW())";
            $stmtCommande = mysqli_prepare($bd, $sqlCommande);
            mysqli_stmt_bind_param($stmtCommande, "i", $_SESSION['cliID']);
            mysqli_stmt_execute($stmtCommande);

            $idCommande = mysqli_insert_id($bd);

            mysqli_stmt_close($stmtCommande);

            // Insertion dans compo_commande
            $sqlCompo = "INSERT INTO compo_commande (ccIDCommande, ccIDLivre, ccQuantite) VALUES (?, ?, ?)";
            $stmtCompo = mysqli_prepare($bd, $sqlCompo);

            foreach ($_SESSION['panier'] as $idLivre => $details) {
                $quantite = $details['quantite'];
                mysqli_stmt_bind_param($stmtCompo, "iii", $idCommande, $idLivre, $quantite);
                mysqli_stmt_execute($stmtCompo);
            }
            mysqli_stmt_close($stmtCompo);
	    
	    // Commande validée -> vider le panier
    	    unset($_SESSION['panier']);
            $_SESSION['message_commande_succes'] = 'Votre commande a bien été prise en compte. Merci !';

            header('Location: panier.php');
            exit();

	}
}
}

affDebutEnseigneEntete('BookShop | Votre panier');

affContenu();

affPiedFin();



function affContenu(): void {
    echo '<h1>Votre panier</h1>';
    
    if (isset($_SESSION['message_commande_succes'])) {
        echo '<p style="color:green;">' , htmlspecialchars($_SESSION['message_commande_succes']) , '</p>';
        unset($_SESSION['message_commande_succes']);
        return;
    }

    if (isset($_SESSION['message_commande'])) {
    	echo '<p style="color:red;">' . htmlspecialchars($_SESSION['message_commande']) . '</p>';
    	unset($_SESSION['message_commande']);
	}


    if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
        echo '<p>Votre panier est vide.</p>';
        return;
    }

    $bd = bdConnect();  // Connexion unique
    $adresseLivraison = '';
if (isset($_SESSION['cliID'])) {
    $cliID = (int)$_SESSION['cliID'];
    $sql = "SELECT cliAdresse, cliCP, cliVille, cliPays  FROM client WHERE cliID = ?";
    $stmt = mysqli_prepare($bd, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $cliID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($client = mysqli_fetch_assoc($result)) {
        // Construire une adresse complète similaire à compte.php
        $adresseLivraison = trim($client['cliAdresse'] . ' ' . $client['cliVille'] . ' ' . $client['cliCP'] . ' ' . $client['cliPays']);
    }
    mysqli_free_result($result);
}
    echo '<ul>';

    $totalPanier = 0;

    foreach ($_SESSION['panier'] as $idLivre => $details) {
        $sql = 'SELECT liTitre, liPrix FROM livre WHERE liID = ?';
        $stmt = mysqli_prepare($bd, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $idLivre);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($livre = mysqli_fetch_assoc($result)) {
            $prixUnitaire = (float) $livre['liPrix'];
            $quantite = $details['quantite'];
            $totalLigne = $prixUnitaire * $quantite;
            $totalPanier += $totalLigne;

            echo '<li>',
                '<img src="../images/livres/' , $idLivre , '_mini.jpg" alt="' , htmlspecialchars($livre['liTitre']) , '" style="height:80px; vertical-align:middle; margin-right:10px;">',
                '<strong>', htmlspecialchars($livre['liTitre']), '</strong><br>',
                'Prix unitaire : ', number_format($prixUnitaire, 2), ' €<br>',
                'Total : ', number_format($totalLigne, 2), ' €',
                '<form action="panier.php" method="post">',
                '<input type="hidden" name="idLivre" value="' , $idLivre , '">',
                'Quantité : <input type="number" name="quantite" min="0" value="' , $quantite , '" style="width:40px;">',
                '<button type="submit" name="action" value="modifier">Mettre à jour</button> ',
                '<button type="submit" name="action" value="supprimer">Supprimer</button>',
                '</form>',
                '</li>';
        }

        mysqli_free_result($result);
    }

    echo '</ul>',
        '<p><strong>Total du panier : ' , number_format($totalPanier, 2) , ' €</strong></p>';
        
        if (isset($_SESSION['cliID'])) {
	    echo '<form action="panier.php" method="post">',
            '<p><strong>Veuillez saisir votre adresse de livraison complète (numéro, nom de rue, ville, code postal et pays) :</strong></p>',
            '<textarea id="adresse" name="adresse" rows="3" cols="40" required>',
            htmlspecialchars($adresseLivraison),
            '</textarea><br><br>',
	     '<button type="submit" name="action" value="commander">Passer commande</button>',
	    '</form>';
	} else {
	    echo '<p><em>Veuillez vous connecter ou vous inscrire pour saisir votre adresse de livraison et passer commande.</em></p>',
	     '<form action="connexion.php" method="get">',
	     '<button type="submit">Se connecter / S\'inscrire</button>',
	     '</form>';
}

    mysqli_close($bd);  // Fermeture unique après boucle
}
ob_end_flush();
