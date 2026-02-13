<?php
session_start();

function sanatizeString($str)
{
    $dirty_string = $str;

    // Supprimer les balises HTML et PHP
    $clean_string = strip_tags($dirty_string);

    // Convertir les caractères spéciaux en entités HTML
    $clean_string = htmlspecialchars($clean_string);

    // Supprimer les espaces en début et fin de chaîne
    $clean_string = trim($clean_string);

    // Utiliser le filtre FILTER_SANITIZE_STRING pour enlever les balises HTML et PHP et convertir les caractères spéciaux en entités HTML
    $clean_string = filter_var($clean_string, FILTER_SANITIZE_STRING);

    // Utiliser la chaîne nettoyée dans votre application
    return $clean_string;
}

function connectDb()
{
    $serveur = "193.203.168.143";
    $base = "u224136748_baba";
    $user = "u224136748_baba";
    $pass = '5A&z;yZ9iam';

    $bdd = new PDO('mysql:host=' . $serveur . ';dbname=' . $base, $user, $pass, array('charset' => 'utf8'));
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $bdd->query("SET CHARACTER SET utf8");


    return $bdd;
}

$bdd = connectDb();
$_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
