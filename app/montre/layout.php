<!DOCTYPE html>
<html lang="fr-BE">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Document metadata -->
    <title>IRSA – Institut Royal pour Sourds et Aveugles</title>
    <meta name="description" content="Depuis 1835, l’IRSA accompagne enfants, jeunes et adultes atteints de déficience auditive, visuelle ou multiple à Uccle.">

    <link rel="canonical" href="https://www.irsa.be/">
    <meta name="robots" content="index, follow">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Preload critical fonts -->
    <link rel="preload" href="/ui/font/josefin-sans-v34-latin-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/ui/font/josefin-sans-v34-latin-700.woff2" as="font" type="font/woff2" crossorigin>

    <link rel="preload" href="/ui/font/atkinson-hyperlegible-v12-latin-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/ui/font/atkinson-hyperlegible-v12-latin-italic.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/ui/font/atkinson-hyperlegible-v12-latin-700.woff2" as="font" type="font/woff2" crossorigin>


    <!-- Open Graph / Facebook -->
    <meta property="og:title" content="IRSA – Institut Royal pour Sourds et Aveugles">
    <meta property="og:description" content="Depuis 1835, l’IRSA accompagne enfants, jeunes et adultes atteints de déficience auditive, visuelle ou multiple à Uccle.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.irsa.be/">
    <meta property="og:image" content="https://www.irsa.be/assets/og-image.jpg">
    <meta property="og:locale" content="fr_BE">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="IRSA – Institut Royal pour Sourds et Aveugles">
    <meta name="twitter:description" content="Depuis 1835, l’IRSA accompagne enfants, jeunes et adultes atteints de déficience auditive, visuelle ou multiple à Uccle.">
    <meta name="twitter:image" content="https://www.irsa.be/assets/og-image.jpg">
    <meta name="twitter:site" content="@irsa_be">

    <!-- Stylesheets (ordered: variables/base → components → pages) -->
    <link rel="stylesheet" href="/css/irsa.css">
    <link rel="stylesheet" href="/css/header-footer.css">
    <link rel="stylesheet" href="/css/button.css">
    <link rel="stylesheet" href="/css/card.css">
    <link rel="stylesheet" href="/css/blob.css">
    <link rel="stylesheet" href="/css/triple-infos.css">
    <?php
    if (isset($css) && is_array($css)):
        foreach ($css as $file):
    ?>
            <link rel="stylesheet" href="/css/<?= $file ?>.css">
        <?php endforeach ?>
    <?php endif ?>

    <!-- Accessibility: use modern color scheme detection -->
    <meta name="color-scheme" content="light dark">

</head>

<body>
    <a href="#main" class="skip-link">Aller au contenu principal</a>

    <header role="banner">
        <nav role="navigation" class="tight">
            <!-- Logo -->
            <h1>
                <a href="/">
                    <img src="/ui/logo_irsa_text.jpg" alt="IRSA – Un projet pour chacun" height="60">
                </a>
            </h1>
            <!-- Liens de navigation -->
            <ol class="nav-links">
                <li><a href="/">Accueil</a></li>
                <li><a href="/irsa">L'IRSA</a></li>
                <li><a href="/services">Nos services</a></li>
                <li><a href="/ecoles">Écoles</a></li>
                <li><a href="/contact">Contact</a></li>
            </ol>

            <!-- Bouton Don -->
            <a href="/don" class="btn btn-primary">Faire un don</a>
            <button class="nav-toggle" aria-label="Ouvrir le menu">☰</button>
        </nav>
    </header>
    <?php
    $breadcrumb = explode('/', trim(http_in(), '/'));
    $page_id = implode('-', $breadcrumb);
    $page_class = implode(' ', $breadcrumb);
    ?>

    <main id="<?= $page_id ?>" class="<?= $page_class ?>"><?= $main ?? '' ?></main>

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
                <small>Experience concue par <a href="https://zkiss.example">Z.Kiss</a></small>
            </p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('nav .nav-toggle').addEventListener('click', (e) => {
                console.log(document.querySelector('nav .nav-links'));
                document.querySelector('nav .nav-links').classList.toggle('active');
            })

        });
    </script>
</body>

</html>