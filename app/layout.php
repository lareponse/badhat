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
    <link rel="stylesheet" href="/css/irsa.css?v=20251214-1334">
    <link rel="stylesheet" href="/css/header-footer.css?v=20251214-1334">
    <link rel="stylesheet" href="/css/button.css?v=20251214-1334">
    <link rel="stylesheet" href="/css/card.css?v=20251214-1334">
    <link rel="stylesheet" href="/css/blob.css?v=20251214-1334">
    <link rel="stylesheet" href="/css/triple-infos.css?v=20251214-1334">
    <?php
    if (isset($css) && is_array($css)):
        foreach ($css as $file):
    ?>
            <link rel="stylesheet" href="/css/<?= $file ?>.css?v=20251214-1334">
        <?php endforeach ?>
    <?php endif ?>

    <!-- Accessibility: use modern color scheme detection -->
    <meta name="color-scheme" content="light dark">

</head>

<body>
    <a href="#main" class="skip-link visually-hidden">Aller au contenu principal</a>
    <?php include('app/a11y.html'); ?>

    <header role="banner">
        <nav role="navigation" class="tight">
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
        <section class="tight">
            <h2 class="visually-hidden">Pied de page du site IRSA</h2>
            <div class="footer-grid">
                <!-- Coordonnées -->
                <article aria-labelledby="coords-heading">
                    <h3 id="coords-heading">Coordonnées</h3>
                    <address>
                        <p><strong>IRSA – Institut Royal pour Sourds et Aveugles</strong></p>
                        <p>Chaussée de Waterloo 150<br>1180 Uccle – Belgique</p>
                    </address>
                </article>

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
                <nav aria-labelledby="social-heading">
                    <h3 id="social-heading">Réseaux sociaux</h3>
                    <div class="social-links">
                        <a href="https://www.facebook.com/InstitutRoyalpourSourdsetAveugles/" aria-label="IRSA sur Facebook">
                            <svg width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10.1576 8.47815L9.91378 10.4329C9.87254 10.7595 9.59562 11.0051 9.26715 11.0051H6.09659V19.1781C5.76223 19.2082 5.42345 19.2237 5.08099 19.2237C4.31505 19.2237 3.56752 19.1472 2.84503 19.0016V11.0051H0.406538C0.182647 11.0051 0 10.822 0 10.5977V8.15161C0 7.9273 0.182647 7.74418 0.406538 7.74418H2.84503V4.07581C2.84503 1.82463 4.66487 0 6.91041 0H9.75544C9.97933 0 10.162 0.183124 10.162 0.407433V2.8535C10.162 3.07781 9.97933 3.26094 9.75544 3.26094H7.72275C6.82498 3.26094 6.09733 3.99049 6.09733 4.89141V7.74491H9.51166C9.90347 7.74491 10.2062 8.0891 10.1583 8.47888L10.1576 8.47815Z" fill="white" />
                            </svg>
                        </a>
                        <a href="https://www.instagram.com/irsa.bruxelles/" aria-label="IRSA sur Instagram">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11.6452 0H3.70524C1.6593 0 0 1.65695 0 3.7V11.6288C0 13.6718 1.6593 15.3288 3.70524 15.3288H11.6452C13.6912 15.3288 15.3505 13.6718 15.3505 11.6288V3.7C15.3505 1.65695 13.6912 0 11.6452 0ZM14.027 11.3647C14.027 12.8239 12.8413 14.0079 11.3801 14.0079H3.96964C2.50846 14.0079 1.32273 12.8239 1.32273 11.3647V3.96475C1.32273 2.50564 2.50846 1.32158 3.96964 1.32158H11.3801C12.8413 1.32158 14.027 2.50564 14.027 3.96475V11.3647Z" fill="white" />
                                <path d="M7.68253 3.69983C5.4915 3.69983 3.71289 5.47592 3.71289 7.66385C3.71289 9.85178 5.4915 11.6279 7.68253 11.6279C9.87356 11.6279 11.6522 9.85178 11.6522 7.66385C11.6522 5.47592 9.87356 3.69983 7.68253 3.69983ZM7.68253 10.307C6.22429 10.307 5.03561 9.12002 5.03561 7.66385C5.03561 6.20768 6.22429 5.02068 7.68253 5.02068C9.14076 5.02068 10.3294 6.20768 10.3294 7.66385C10.3294 9.12002 9.14076 10.307 7.68253 10.307Z" fill="white" />
                                <path d="M11.9175 4.2288C11.48 4.2288 11.1235 3.87284 11.1235 3.43599C11.1235 2.99914 11.48 2.64319 11.9175 2.64319C12.3549 2.64319 12.7114 2.99914 12.7114 3.43599C12.7114 3.87284 12.3549 4.2288 11.9175 4.2288Z" fill="white" />
                            </svg>
                        </a>
                        <a href="https://be.linkedin.com/company/irsa-institut-royal-pour-sourds-et-aveugles" aria-label="IRSA sur LinkedIn">
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.88466 3.76398C2.92553 3.76398 3.76932 2.92138 3.76932 1.88199C3.76932 0.842593 2.92553 0 1.88466 0C0.843796 0 0 0.842593 0 1.88199C0 2.92138 0.843796 3.76398 1.88466 3.76398Z" fill="white" />
                                <path d="M3.31488 5.01794H0.455124C0.377435 5.01794 0.314453 5.08083 0.314453 5.15841V14.2867C0.314453 14.3643 0.377435 14.4272 0.455124 14.4272H3.31488C3.39257 14.4272 3.45555 14.3643 3.45555 14.2867V5.15841C3.45555 5.08083 3.39257 5.01794 3.31488 5.01794Z" fill="white" />
                                <path d="M14.1345 8.46869V13.8006C14.1345 14.1455 13.8516 14.428 13.5062 14.428H11.6216C11.2762 14.428 10.9934 14.1455 10.9934 13.8006V9.40932C10.9934 8.54371 10.29 7.84136 9.42318 7.84136C8.55635 7.84136 7.853 8.54371 7.853 9.40932V13.8006C7.853 14.1455 7.5702 14.428 7.22479 14.428H5.34013C4.99472 14.428 4.71191 14.1455 4.71191 13.8006V5.64534C4.71191 5.30042 4.99472 5.01801 5.34013 5.01801H7.22479C7.5702 5.01801 7.853 5.30042 7.853 5.64534V6.04689C8.48122 5.23423 9.52113 4.70471 10.6796 4.70471C12.4133 4.70471 14.1345 5.95937 14.1345 8.46869Z" fill="white" />
                            </svg>
                        </a>
                        <a href="https://www.youtube.com/@IRSA-Belgium" aria-label="IRSA sur YouTube">
                            <svg width="18" height="13" viewBox="0 0 18 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.8681 0H2.25658C1.01045 0 0 1.00902 0 2.25265V10.1292C0 11.3736 1.01045 12.3819 2.25658 12.3819H14.8681C16.1135 12.3819 17.1239 11.3736 17.1239 10.1292V2.25265C17.1239 1.00902 16.1135 0 14.8681 0ZM10.5877 6.73516L7.08054 8.4855C6.67547 8.68775 6.1997 8.39431 6.1997 7.94275V4.44205C6.1997 3.99049 6.67621 3.69705 7.08054 3.89857L10.5877 5.64891C11.0354 5.87248 11.0354 6.51158 10.5877 6.73516Z" fill="white" />
                            </svg>
                        </a>
                    </div>
                </nav>
            </div>
        </section>

        <hr aria-hidden="true">

        <section class="tight">
            <p>&copy; <?= date('Y') ?> IRSA – Institut Royal pour Sourds et Aveugles – Tous droits réservés</small></p>
            <small>Experience concue par <a href="https://evohe.be">Z.Kiss</a> et <a href="https://lareponse.be">lareponse</a></small>
        </section>
    </footer>

    <script src="/js/onload.js?v=20251214-1334"></script>
</body>

</html>