<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/irsa.css">
    <link rel="stylesheet" href="css/button.css">
    <link rel="stylesheet" href="css/card.css">
    <link rel="stylesheet" href="css/blob.css">
    <link rel="stylesheet" href="css/home.css">
</head>

<body>
    <img src="/ui/blob/blob_home_top_right.svg" alt="decorative blob shape" class="blob" id="blob_home_top_right" aria-hidden="true">
    <header>
        <nav class="tight">
            <h1><a href="/">IRSA</a></h1>
            <ol>
                <li><a href="/">Accueil</a></li>
                <li><a href="/irsa">L'IRSA</a></li>
                <li><a href="/services">Nos services</a></li>
                <li><a href="/ecoles">Ecoles</a></li>
                <li><a href="/contact">Contact</a></li>
                <li><a href="/don">Faire un don</a></li>
            </ol>
        </nav>
    </header>

    <main></main>

    <footer role="contentinfo" lang="fr">
        <div class="tight">

            <h2 class="visually-hidden">Pied de page du site IRSA</h2>

            <div class="footer-grid">
                <!-- Coordonnées -->
                <section aria-labelledby="coords-heading">
                    <h3 id="coords-heading">Coordonnées</h3>
                    <address>
                        <p><strong>IRSA – Institut Royal pour Sourds et Aveugles</strong></p>
                        <p>Chaussée de Waterloo 150<br>1180 Uccle – Belgique</p>
                    </address>
                </section>

                <!-- Liens utiles -->
                <nav aria-labelledby="useful-links-heading">
                    <h3 id="useful-links-heading">Liens utiles</h3>
                    <ul>
                        <li><a href="/a-propos">À propos de l'IRSA</a></li>
                        <li><a href="/services">Nos services</a></li>
                        <li><a href="/don">Faire un don</a></li>
                        <li><a href="/rejoindre">Rejoindre nos équipes</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/plan-du-site">Plan du site</a></li>
                    </ul>
                </nav>

                <!-- Mentions et accessibilité -->
                <nav aria-labelledby="legal-access-heading">
                    <h3 id="legal-access-heading">Mentions et accessibilité</h3>
                    <ul>
                        <li><a href="/mentions-legales">Mentions légales</a></li>
                        <li><a href="/confidentialite">Politique de confidentialité</a></li>
                        <li><a href="/accessibilite">Accessibilité du site</a></li>
                        <li><a href="/cookies">Cookies</a></li>
                        <li><a href="/donnees-personnelles">Gestion des données personnelles</a></li>
                    </ul>
                </nav>

                <!-- Réseaux sociaux -->
                <section aria-labelledby="social-heading">
                    <h3 id="social-heading">Réseaux sociaux</h3>
                    <div class="social-links">
                        <a href="https://www.facebook.com/irsa" aria-label="IRSA sur Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/irsa" aria-label="IRSA sur Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/company/irsa" aria-label="IRSA sur LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://www.youtube.com/@irsa" aria-label="IRSA sur YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </section>
            </div>

            <hr aria-hidden="true">

            <p>
                <small>&copy; <?= date('Y') ?> IRSA – Institut Royal pour Sourds et Aveugles – Tous droits réservés</small><br>
                <small>Site réalisé par <a href="https://zkiss.example">Z.Kiss</a></small>
            </p>
        </div>

    </footer>

</body>

</html>