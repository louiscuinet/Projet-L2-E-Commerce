<?php

require_once './php/bibli_generale.php';
require_once ('./php/bibli_bookshop.php');

affDebutEnseigneEntete('BookShop | Bienvenue', false, '.');

affContenuL();

affPiedFin();

// ----------  Fonctions locales au script ----------- //

/**
 *  Affichage du contenu de la page
 *
 * @return void
 */
function affContenuL() : void {
    echo
        '<h1>Bienvenue sur BookShop !</h1>',
        '<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site. </p>',
        '<p>Nouveau venu sur BookShop ? Consultez notre <a href="./php/presentation.php">page de présentation</a> !</p>';


    $derniersAjouts = array(
        array(  'id'      => 42,
                'auteurs' => array( array('prenom' => 'George', 'nom' => 'Orwell')),
                'titre'   => '1984'),
        array(  'id'      => 41,
                'auteurs' => array( array('prenom' => 'Robert', 'nom' => 'Kirkman'),
                                    array('prenom' => 'Charlie', 'nom' => 'Adlard')),
                'titre'   => 'The Walking Dead - T16 Un vaste monde'),
        array(  'id'      => 40,
                'auteurs' => array( array('prenom' => 'Ray', 'nom' => 'Bradbury')),
                'titre'   => 'L\'homme illustré'),
        array(  'id'      => 39,
                'auteurs' => array( array('prenom' => 'Alan', 'nom' => 'Moore'),
                                    array('prenom' => 'David', 'nom' => 'Lloyd')),
                'titre'   => 'V pour Vendetta'),
              );

    $p = 'Voici les 4 derniers articles ajoutés dans notre boutique en ligne :';
    affSectionLivresL('Dernières nouveautés', $p, $derniersAjouts);


    $meilleuresVentes = array(
        array(  'id'      => 20,
                'auteurs' => array( array('prenom' => 'Alan', 'nom' => 'Moore'),
                                    array('prenom' => 'Dave', 'nom' => 'Gibbons')),
                'titre'   => 'Watchmen'),
        array(  'id'      => 39,
                'auteurs' => array( array('prenom' => 'Alan', 'nom' => 'Moore'),
                                    array('prenom' => 'David', 'nom' => 'Lloyd')),
                'titre'   => 'V pour Vendetta'),
        array(  'id'      => 27,
                'auteurs' => array( array('prenom' => 'Robert', 'nom' => 'Kirkman'),
                                    array('prenom' => 'Jay', 'nom' => 'Bonansinga')),
                'titre'   => 'The Walking Dead - La route de Woodbury'),
        array(  'id'      => 34,
                'auteurs' => array( array('prenom' => 'Aldous', 'nom' => 'Huxley')),
                'titre'   => 'Le meilleur des mondes'),

              );
    affSectionLivresL('Top des ventes', 'Voici les 4 articles les plus vendus :', $meilleuresVentes);
}

/**
 *  Affichage d'une section de livres
 *
 * @param  string  $h2         titre de la section (contenu de l'élément h2)
 * @param  string  $p          contenu de l'élément p
 * @param  array   $livres     tableau contenant un élément pour chaque livre (tableau associatif avec id, auteurs(nom, prenom), titre)
 *
 * @return void
 */
function affSectionLivresL(string $h2, string $p, array $livres): void {
    echo
        '<section>',
            '<h2>', $h2, '</h2>',
            '<p>', $p, '</p>';

    foreach ($livres as $livre) {
        echo
            '<figure>',
                // TODO : à modifier pour le projet
                '<a class="addToCart" href="#" title="Ajouter au panier"></a>',
                '<a class="addToWishlist" href="#" title="Ajouter à la liste de cadeaux"></a>',
                '<a href="php/details.php?article=', $livre['id'], '" title="Voir détails"><img src="./images/livres/',
                $livre['id'], '_mini.jpg" alt="', $livre['titre'],'"></a>',
                '<figcaption>';
        $auteurs = $livre['auteurs'];
        $i = 0;
        foreach ($livre['auteurs'] as $auteur) {
            if ($i > 0) {
                echo ', ';
            }
            ++$i;
            echo    '<a title="Rechercher l\'auteur" href="php/recherche.php?type=auteur&amp;quoi=', urlencode($auteur['nom']), '">',
                    mb_substr($auteur['prenom'], 0, 1, encoding:'UTF-8'), '. ', $auteur['nom'], '</a>';
        }
        echo        '<br>',
                    '<strong>', $livre['titre'], '</strong>',
                '</figcaption>',
            '</figure> '; // ajout de l'espace pour obtenir un rendu identique à celui du fichier index_TP3.html
    }
    echo
        '</section>';
}
