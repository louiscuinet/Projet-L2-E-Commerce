<?php

require_once 'bibli_generale.php';
require_once ('bibli_bookshop.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// Redirection si utilisateur pas connecté
if (!isset($_SESSION['cliID'])) {
    header('Location: connexion.php');
    exit();
}

$cliID = $_SESSION['cliID'];
$bd = bdConnect();

affDebutEnseigneEntete('BookShop | Mes Commandes');

echo '<h1>Mes commandes</h1>';

// Requête pour récupérer les commandes de l'utilisateur
$sql = "SELECT coID, coDate
        FROM commande
        WHERE coIDClient = $cliID
        ORDER BY coDate DESC";

$res = bdSendRequest($bd, $sql);

if (mysqli_num_rows($res) === 0) {
    echo '<p>Vous n\'avez encore passé aucune commande.</p>';
} else {
    while ($commande = mysqli_fetch_assoc($res)) {
        $idCom = $commande['coID'];
        echo '<div style="border: 1px solid #ccc; padding: 10px; margin: 15px 0;">';
        echo '<strong>Commande n°' . $idCom . '</strong><br>';
        echo 'Date : ' . date('d/m/Y H:i', strtotime($commande['coDate'])) . '<br>';

        // Requête pour les détails (livres + quantité + prix unitaire)
        $sqlDetails = "SELECT liTitre, liPrix, ccQuantite
                       FROM compo_commande
                       JOIN livre ON ccIDLivre = liID
                       WHERE ccIDCommande = $idCom";

        $resDetails = bdSendRequest($bd, $sqlDetails);
        $montantTotal = 0;

        echo '<ul>';
        while ($ligne = mysqli_fetch_assoc($resDetails)) {
            $titre = htmlspecialchars($ligne['liTitre']);
            $prix = $ligne['liPrix'];
            $qte = $ligne['ccQuantite'];
            $sousTotal = $prix * $qte;
            $montantTotal += $sousTotal;
            echo "<li>$titre — Quantité : $qte — Prix unitaire : " , number_format($prix, 2, ',', ' ') , " €</li>",
        }
        '</ul>',

        '<strong>Montant total : ' , number_format($montantTotal, 2, ',', ' ') , ' €</strong>',
        '</div>';

        mysqli_free_result($resDetails);
    }
}

mysqli_free_result($res);
mysqli_close($bd);

affPiedFin();
ob_end_flush();
