<?php

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

ob_start(); //démarre la bufferisation

affDebutEnseigneEntete('BookShop | Inscription', false);

affContenuS();

affPiedFin();

// fin du script --> envoi de la page
ob_end_flush();

function affContenuS():void{
    echo '<h1> Réception des données soumises</h1>',
         '<h2> Avec une boucle foreach</h2>',
         '<ul>';
    foreach($_POST as $key => $value){
        echo '<li>cle=', $key,',valeur=', $value,'</li>';
    }
    echo '</ul>',
    '<h2> Avec var_dump()</h2>',
    '<pre>',var_dump($_POST),'</pre>';
    echo '<h2> Avec print_r()</h2>',
        '<pre>',print_r($_POST,true),'</pre>';

}
