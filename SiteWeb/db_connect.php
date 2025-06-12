<?php
// db_connect.php
// This file manages the connection to your MySQL database.

$serveur = "localhost"; // Usually 'localhost' if MySQL is on the same machine.
$utilisateur = "root"; // Replaced with new MySQL username.
$mot_de_passe = "passroot"; // Replaced with new MySQL password.
$nom_base_de_donnees = "SAe23"; // The name of your database.

// Create a connection to the database.
$conn = mysqli_connect($serveur, $utilisateur, $mot_de_passe, $nom_base_de_donnees);

// Check if the connection failed. If it did, stop the script and show an error.
if (!$conn) {
    die("Échec de la connexion à la base de données : " . mysqli_connect_error());
}

// Set the character encoding to UTF-8 to avoid issues with special characters (like accents).
mysqli_set_charset($conn, "utf8");

// This file doesn't return any data; it simply sets up the database connection.
?>
