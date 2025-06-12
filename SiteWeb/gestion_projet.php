<?php
session_start(); // Start the session to enable dynamic navigation links based on user role.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Projet - SAÉ23 IoT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestion de Projet SAÉ23</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <?php if (isset($_SESSION['role'])): // Check if a user is logged in. ?>
                    <?php if ($_SESSION['role'] === 'administration'): // If the user is an Administrator, show the 'Administration' link. ?>
                        <li><a href="admin.php">Administration</a></li>
                    <?php elseif ($_SESSION['role'] === 'Gestionnaire'): // If the user is a Manager, show the 'Gestion' link. ?>
                        <li><a href="gestion.php">Gestion</a></li>
                    <?php endif; ?>
                    <li><a href="deconnexion.php">Déconnexion</a></li> <?php else: // If no user is logged in, show the 'Connexion' link. ?>
                    <li><a href="connexion.php">Connexion</a></li>
                <?php endif; ?>
                <li><a href="gestion_projet.php" class="active">Gestion Projet</a></li> </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Diagramme de GANTT Final</h2>
            <img style="width: 100%;" src="gantt_final.png" alt="image du gantt final">
        </section>

        <section>
            <h2>Outils Collaboratifs Utilisés</h2>
            <p>Pour la gestion de ce projet, nous avons eu recours à Git et GitHub pour pouvoir s'envoyer rapidement les versions des projets.</p>
            <img src="Github.png" alt="image github" style="width: 100%;">
        </section>

        <section>
            <h2>Synthèse Personnelle et Problèmes Rencontrés</h2>
            <h3>Membre 1 : Zenden Simon</h3>
            <p><strong>Travail effectué :</strong>J'ai réalisé le site web dynamique. Ce site récupère les informations dans la base de données SQL et les affiche sur le site web. On peut également se connecter à ce site. Selon le nom d'utilisateur et le mot de passe. Les connexions ont des accès différents. Le compte admin a accès à la page administration. Le compte rt a accès aux métriques sur le bâtiment E. Et le compte info a accès au batiment B.</p>
            <p><strong>Problèmes rencontrés :</strong> J'ai rencontré plusieurs problèmes, comme par exemple pour réaliser la connexion à la base de données.</p>
            <p><strong>En conclusion :</strong> Je suis satisfait du travail que nous avons réalisé car tout fonctionne correctement. Le site web est plutôt esthétique et nous avons réalisé le projet dans le temps imparti.</p>

            <h3>Membre 2 : Vogt Yanis</h3>
            <p><strong>Travail effectué :</strong>J'ai principalement crée le script de récupération des données MQTT et d'envoi sur la base de données. 
Ce dernier fonctionne donc en s'abonnant au sujets désirés (ici, Am107/byroom/[salle]/data), puis en récupérant les données et les transformant dans le bon format fonctionnant avec la base de données. Ces dernières sont ensuite exploitées par le site géré par Simon.</p>
            <p><strong>Problèmes rencontrés :</strong> J'ai rencontré plusieures difficultés comme la bonne syntaxe en bash et les bons choix de capteurs. J'ai pris l'habitude de débugger le code au fur et à mesure de mon travail, ce qui m'a permis d'avancer de manière plus rapide et efficace.</p>
            <p><strong>En conclusion :</strong> Je suis plutôt satisfait du travail fourni par notre groupe, que ce soit l'approche par NodeRed et Graphana (sur laquelle j'ai pu aider), ou l'approche par site dynamique et serveur Lampp.</p>

            <h3>Membre 3 : Audoin Anaïs</h3>
            <p><strong>Travail effectué :</strong> J'ai créé les différents dockers : mosquittoRT, influxdbRT, noderedRT, grafanaRT. J'ai ensuite installé Node-Red et récupéré les données des capteurs. Pour récupérer les données, je me suis connecté au serveur MQTT de l'IUT via Node-Red et ensuite, je les ai converties du format JSON en caractère. Après ça, j'ai créé une base de données via InfluxDB et, à l'aide de Node-RED, j'y ai inséré les données. Finalement, j'ai fait le dashboard Grafana avec 4 courbes, une pour chaque capteur.</p>
            <p><strong>Problèmes rencontrés :</strong> Lors de mes séances de travail, j'ai rencontré deux principaux problèmes. Le premier fut lors de la récupération de données via Node-Red, je n'arrivais pas à récupérer les données sur le serveur MQTT. Avec l'aide d'un second année et de Yanis, j'ai finalement réussi à récupérer les données ; le problème venait en fait du nom des données que l'on voulait récupérer. Le deuxième problème que j'ai rencontré, c'était dans Grafana, je n'arrivais pas à me connecter à ma base de données. Mais je me suis rendu compte que j'avais simplement oublié de mettre le port d'accès après l'adresse IP (192.168.102.232 au lieu de 192.168.102.232:8086). </p>
            <p><strong>En conclusion :</strong> Je suis plutôt satisfaite de ce que j'ai fait, j'ai réussi à tout faire fonctionner et je n'ai pas rencontré trop de problèmes.</p>
        </section>

        <section>
            <h2>Conclusion et Satisfaction du Cahier des Charges</h2>
            <p>Faites un bilan du projet. Dans quelle mesure la solution finale répond-elle aux exigences du cahier des charges ? Mettez en avant les points forts et les éventuelles améliorations possibles.</p>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>
