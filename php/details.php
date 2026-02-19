<?php

require_once 'bibli_generale.php';
require_once ('bibli_bookshop.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affDebutEnseigneEntete('BookShop | Details');

// Vérification que l’ID du livre est passé dans l’URL
if (!isset($_GET['article'])) {
    echo '<p style="color: red; text-align: center;">Aucun livre sélectionné.</p>';
    exit;
}

$idLivre = intval($_GET['article']);  // on force un entier pour sécurité

// Connexion à la base
$bd = bdConnect();

// Requête principale
$sqlLivre = "
    SELECT
        liTitre, liNbPages, liAnnee, liPrix, liResume, liLangue, liISBN13,
        edNom, edWeb
    FROM livre
    JOIN editeur ON liIDEditeur = edID
    WHERE liID = $idLivre
";
$resLivre = bdSendRequest($bd, $sqlLivre);

// Vérifier que  livre existe
if (mysqli_num_rows($resLivre) === 0) {
    echo '<p style="color: red; text-align: center;">Livre introuvable.</p>';
    mysqli_free_result($resLivre);
    mysqli_close($bd);
    exit;
}

// Récupération du livre
$livre = mysqli_fetch_assoc($resLivre);
mysqli_free_result($resLivre);

// Récupération des auteurs
$sqlAuteurs = "
    SELECT auPrenom, auNom
    FROM auteur
    JOIN aut_livre ON auID = al_IDAuteur
    WHERE al_IDLivre = $idLivre
";
$resAuteurs = bdSendRequest($bd, $sqlAuteurs);
$auteurs = [];
while ($auteur = mysqli_fetch_assoc($resAuteurs)) {
    $auteurs[] = $auteur['auPrenom'] . ' ' . $auteur['auNom'];
}
mysqli_free_result($resAuteurs);

// Fermeture BDD
mysqli_close($bd);

echo '
<div style="width: 80%; margin: 50px auto; font-family: sans-serif;">',
    '<h1>' , htmlspecialchars($livre['liTitre']) , '</h1>',
    '<figure class="details">',
        '<img src="../images/livres/' , htmlspecialchars($idLivre) , '_mini.jpg" alt="Couverture du livre" style="float: left; margin-left: 20px; margin-right: 20px; max-width: 200px; height: auto;">',
        '<a class="addToCart" href="./panier.php?action=ajouter&id=', htmlspecialchars($idLivre), '" title="Ajouter au panier"></a>',
        '<a class="addToWishlist" href="#" title="Ajouter à la liste de cadeaux"></a>',
    '</figure>',
    '<p><strong>Auteur(s) :</strong> ';
    
$links = [];
foreach ($auteurs as $nomComplet) {
    $parts = explode(' ', $nomComplet, 2);
    $nom = isset($parts[1]) ? $parts[1] : $parts[0];
    $url = 'recherche.php?type=auteur&quoi=' . urlencode($nom);
    $links[] = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($nomComplet) . '</a>';
}
echo implode(', ', $links);

echo '</p>';
    $urlEdWeb = $livre['edWeb'];
    if ($urlEdWeb && !preg_match('#^https?://#i', $urlEdWeb)) {
    	$urlEdWeb = 'http://' . $urlEdWeb;  // ajoute le protocole http par défaut
    }

    echo '<p><strong>Éditeur :</strong> <a class="lienExterne" href="' , htmlspecialchars($urlEdWeb) , '" target="_blank">' ,     	htmlspecialchars($livre['edNom']) , '</a></p>',

    '<p><strong>Année :</strong> ' , $livre['liAnnee'] , '</p>',
    '<p><strong>Nombre de pages :</strong> ' , $livre['liNbPages'] , '</p>',
    '<p><strong>Prix :</strong> ' , number_format($livre['liPrix'], 2) , ' €</p>',
    '<p><strong>Langue :</strong> ' , htmlspecialchars($livre['liLangue']) , '</p>',
    '<p><strong>ISBN13 :</strong> ' , htmlspecialchars($livre['liISBN13']) , '</p>',
    '<p><strong>Résumé :</strong><br>' , nl2br(htmlspecialchars($livre['liResume'])) , '</p>',
'</div>';

affPiedFin();
ob_end_flush();
