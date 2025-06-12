<?php
session_start();

// Redirects users who are not logged in as a 'Gestionnaire' or if user ID is missing.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gestionnaire' || !isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

require_once 'db_connect.php';

$id_batiment_gestionnaire = (int)$_SESSION['user_id'];
$nom_gestionnaire = $_SESSION['username'];

// CORRECTION 1: Récupérer le capteur sélectionné avec le bon nom de paramètre
$capteur_selectionne = isset($_GET['capteur']) ? $_GET['capteur'] : ''; // 'capteur' au lieu de 'capteurs'

// CORRECTION 2: Définir correctement les dates au format MySQL
$date_fin = (new DateTime())->format('Y-m-d H:i:s'); // Format MySQL au lieu de d-m-Y H:i:s
$date_debut = (new DateTime())->modify('-24 hours')->format('Y-m-d H:i:s');

// Récupérer les capteurs disponibles
$capteurs_disponibles = [];
$stmt_capteurs = mysqli_prepare($conn, "
    SELECT DISTINCT C.nom_capteur, C.type, C.unite
    FROM capteurs C
    JOIN salles S ON C.nom_salle = S.nom_salle
    WHERE S.id_batiment = ?
");

if ($stmt_capteurs) {
    mysqli_stmt_bind_param($stmt_capteurs, "i", $id_batiment_gestionnaire);
    mysqli_stmt_execute($stmt_capteurs);
    $result_capteurs = mysqli_stmt_get_result($stmt_capteurs);
    $capteurs_disponibles = mysqli_fetch_all($result_capteurs, MYSQLI_ASSOC);
    mysqli_free_result($result_capteurs);
    mysqli_stmt_close($stmt_capteurs);
} else {
    error_log("Erreur de préparation de la requête des capteurs : " . mysqli_error($conn));
}

// CORRECTION 3: Récupérer les mesures avec une requête simplifiée
$mesures = [];
if ($capteur_selectionne) {
    $stmt_mesures = mysqli_prepare($conn, "
        SELECT 
            mesures.valeur, 
            CONCAT(mesures.date, ' ', mesures.horaire) as date_heure,
            mesures.date,
            mesures.horaire,
            capteurs.unite
        FROM mesures
        JOIN capteurs ON mesures.nom_capteur = capteurs.nom_capteur
        WHERE mesures.nom_capteur = ? 
        AND CONCAT(mesures.date, ' ', mesures.horaire) >= ?
        AND CONCAT(mesures.date, ' ', mesures.horaire) <= ?
        ORDER BY mesures.date DESC, mesures.horaire DESC
        LIMIT 100
    ");

    if ($stmt_mesures) {
        mysqli_stmt_bind_param($stmt_mesures, "sss", $capteur_selectionne, $date_debut, $date_fin);
        mysqli_stmt_execute($stmt_mesures);
        $result_mesures = mysqli_stmt_get_result($stmt_mesures);
        $mesures = mysqli_fetch_all($result_mesures, MYSQLI_ASSOC);
        mysqli_free_result($result_mesures);
        mysqli_stmt_close($stmt_mesures);
    } else {
        error_log("Erreur de préparation de la requête des mesures : " . mysqli_error($conn));
    }
}

// CORRECTION 4: Corriger la requête des statistiques (il y avait des fautes de frappe)
$stats_salles = [];
$stmt_stats = mysqli_prepare($conn, "
    SELECT
        salles.nom_salle,
        capteurs.nom_capteur,
        capteurs.type,
        capteurs.unite,
        AVG(mesures.valeur) AS moyenne_valeur,
        MIN(mesures.valeur) AS min_valeur,
        MAX(mesures.valeur) AS max_valeur,
        COUNT(mesures.valeur) AS nb_mesures
    FROM salles
    JOIN capteurs ON salles.nom_salle = capteurs.nom_salle
    LEFT JOIN mesures ON capteurs.nom_capteur = mesures.nom_capteur
    WHERE salles.id_batiment = ?
    AND CONCAT(mesures.date, ' ', mesures.horaire) >= ?
    AND CONCAT(mesures.date, ' ', mesures.horaire) <= ?
    GROUP BY salles.nom_salle, capteurs.nom_capteur, capteurs.type, capteurs.unite
    HAVING nb_mesures > 0
    ORDER BY salles.nom_salle, capteurs.nom_capteur
");

if ($stmt_stats) {
    mysqli_stmt_bind_param($stmt_stats, "iss", $id_batiment_gestionnaire, $date_debut, $date_fin);
    mysqli_stmt_execute($stmt_stats);
    $result_stats = mysqli_stmt_get_result($stmt_stats);
    while ($row = mysqli_fetch_assoc($result_stats)) {
        $stats_salles[$row['nom_salle']][] = $row;
    }
    mysqli_free_result($result_stats);
    mysqli_stmt_close($stmt_stats);
} else {
    error_log("Erreur de préparation de la requête des statistiques : " . mysqli_error($conn));
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion - SAÉ23 IoT</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <h1>Panneau de Gestionnaire : <?php echo htmlspecialchars($nom_gestionnaire); ?></h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administration'): ?>
                    <li><a href="admin.php">Administration</a></li>
                <?php endif; ?>
                <li><a href="gestion.php" class="active">Gestion</a></li>
                <li><a href="deconnexion.php">Déconnexion</a></li>
                <li><a href="gestion_projet.php">Gestion Projet</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Mesures des Capteurs de Votre Bâtiment (24 dernières heures)</h2>
            
            <!-- CORRECTION 5: Afficher des informations de debug temporaires -->
            <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
                <strong>Debug Info:</strong><br>
                ID Bâtiment: <?php echo $id_batiment_gestionnaire; ?><br>
                Nombre de capteurs disponibles: <?php echo count($capteurs_disponibles); ?><br>
                Capteur sélectionné: <?php echo htmlspecialchars($capteur_selectionne); ?><br>
                Période: du <?php echo $date_debut; ?> au <?php echo $date_fin; ?><br>
                Nombre de mesures trouvées: <?php echo count($mesures); ?>
            </div>

            <form action="gestion.php" method="GET">
                <label for="capteur">Sélectionner un Capteur :</label>
                <select name="capteur" id="capteur">
                    <option value="">-- Tous les capteurs --</option>
                    <?php foreach ($capteurs_disponibles as $capteur): ?>
                        <option value="<?php echo htmlspecialchars($capteur['nom_capteur']); ?>"
                            <?php echo ($capteur_selectionne == $capteur['nom_capteur']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($capteur['nom_capteur'] . " (" . $capteur['type'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Afficher les Mesures">
            </form>

            <?php if (!empty($capteur_selectionne)): ?>
                <h3>Historique des Mesures pour <?php echo htmlspecialchars($capteur_selectionne); ?></h3>
                <?php if (!empty($mesures)): ?>
                    <div style="width: 80%; margin: auto;">
                        <canvas id="graphiqueMesures"></canvas>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Valeur</th>
                                <th>Unité</th>
                                <th>Date</th>
                                <th>Heure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mesures as $mesure): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mesure['valeur']); ?></td>
                                    <td><?php echo htmlspecialchars($mesure['unite']); ?></td>
                                    <td><?php echo htmlspecialchars($mesure['date']); ?></td>
                                    <td><?php echo htmlspecialchars($mesure['horaire']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <script>
                        const ctx = document.getElementById('graphiqueMesures').getContext('2d');
                        const mesuresData = <?php echo json_encode(array_reverse($mesures)); ?>;
                        const labels = mesuresData.map(m => {
                            const date = new Date(m.date_heure);
                            return date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
                        });
                        const valeurs = mesuresData.map(m => parseFloat(m.valeur));
                        const unite = mesuresData.length > 0 ? mesuresData[0].unite : '';

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: `Valeur (${unite})`,
                                    data: valeurs,
                                    borderColor: 'rgb(75, 192, 192)',
                                    tension: 0.1,
                                    fill: false
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Évolution des Mesures du Capteur'
                                    }
                                },
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Heure'
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Valeur'
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                <?php else: ?>
                    <p class="message info">Aucune mesure trouvée pour ce capteur dans les 24 dernières heures.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="message info">Sélectionnez un capteur pour afficher ses mesures.</p>
            <?php endif; ?>

            <hr>

            <h2>Statistiques des Salles de Votre Bâtiment (24 dernières heures)</h2>
            <?php if (!empty($stats_salles)): ?>
                <?php foreach ($stats_salles as $nom_salle => $capteurs_stats): ?>
                    <h3>Salle : <?php echo htmlspecialchars($nom_salle); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Capteur</th>
                                <th>Type</th>
                                <th>Unité</th>
                                <th>Nb Mesures</th>
                                <th>Moyenne</th>
                                <th>Min</th>
                                <th>Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($capteurs_stats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['nom_capteur']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['type']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['unite']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['nb_mesures']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($stat['moyenne_valeur'] ?? 0, 2)); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($stat['min_valeur'] ?? 0, 2)); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($stat['max_valeur'] ?? 0, 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="message info">Aucune statistique de salle trouvée pour ce bâtiment dans les 24 dernières heures.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>