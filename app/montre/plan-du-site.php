<header>
    <h1><span class="tight">Plan du site</span></h1>
    <div class="tight">
        <p>Vous souhaitez contribuer à notre mission d'accompagnement des personnes sourdes et aveugles ? Découvrez les différentes façons de nous rejoindre.</p>
    </div>
</header>

<section class="tight">

    <nav aria-label="Plan du site">
        <section>
            <h2>Présentation</h2>
            <ul>
                <li><a href="/">Accueil</a></li>
                <li><a href="/a-propos">À propos de l'IRSA</a></li>
                <li><a href="/histoire">Notre histoire</a></li>
            </ul>
        </section>

        <section>
            <h2>Services et structures</h2>
            <ul>
                <li><a href="/services">Vue d'ensemble des services</a></li>
                <li>
                    <a href="/ecoles">Écoles</a>
                    <ul>
                        <li><a href="/ecoles/primaire-deficience-auditive">Primaire - Déficience auditive</a></li>
                        <li><a href="/ecoles/primaire-deficience-visuelle">Primaire - Déficience visuelle</a></li>
                        <li><a href="/ecoles/secondaire-deficience-auditive">Secondaire - Déficience auditive</a></li>
                        <li><a href="/ecoles/secondaire-deficience-visuelle">Secondaire - Déficience visuelle</a></li>
                    </ul>
                </li>
                <li>
                    <a href="/hebergements">Centres d'hébergement</a>
                    <ul>
                        <li><a href="/hebergements/enfants">Enfants</a></li>
                        <li><a href="/hebergements/aubier">Adultes - L'Aubier</a></li>
                    </ul>
                </li>
                <li>
                    <a href="/centres-jour">Centres de jour</a>
                    <ul>
                        <li><a href="/centres-jour/enfants">Enfants</a></li>
                        <li><a href="/centres-jour/adultes">Adultes</a></li>
                    </ul>
                </li>
                <li><a href="/creche">Crèche Le Petit Prince</a></li>
                <li><a href="/ludotheque">Ludothèque Oasis</a></li>
                <li><a href="/location-chateau">Location du Château d'Orangerie</a></li>
            </ul>
        </section>

        <section>
            <h2>Admission et inscriptions</h2>
            <ul>
                <li><a href="/admission">Procédure d'admission</a></li>
                <li><a href="/questionnaire">Questionnaire d'orientation</a></li>
            </ul>
        </section>

        <section>
            <h2>Soutenir l'IRSA</h2>
            <ul>
                <li><a href="/don">Faire un don</a></li>
                <li><a href="/rejoindre">Rejoindre nos équipes</a></li>
            </ul>
        </section>

        <section>
            <h2>Contact et informations</h2>
            <ul>
                <li><a href="/contact">Nous contacter</a></li>
                <li><a href="/acces">Accès et localisation</a></li>
            </ul>
        </section>

        <section>
            <h2>Mentions légales</h2>
            <ul>
                <li><a href="/mentions-legales">Mentions légales</a></li>
                <li><a href="/confidentialite">Politique de confidentialité</a></li>
                <li><a href="/accessibilite">Accessibilité du site</a></li>
                <li><a href="/cookies">Cookies</a></li>
                <li><a href="/donnees-personnelles">Gestion des données personnelles</a></li>
            </ul>
        </section>
    </nav>
</section>
<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecole']], IO_EXTRACT);
    return $page;
};
