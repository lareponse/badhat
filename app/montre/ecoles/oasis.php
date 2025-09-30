<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1><span class="tight">Ludothèque Oasis</span></h1>
</header>

<section class="tight" aria-labelledby="ludotheque-presentation-heading">
    <h2 id="ludotheque-presentation-heading" class="visually-hidden">
        Présentation de la ludothèque
    </h2>

    <div class="triple-infos">
        <article>
            <h3>Public accueilli</h3>
            <p>Enfants avec ou sans déficience sensorielle, familles, structures éducatives</p>
        </article>

        <article>
            <h3>Tranche d'âge</h3>
            <p>De 0 à 99 ans</p>
        </article>

        <article>
            <h3>Type de structure</h3>
            <p>Ludothèque sensorielle, éducative et inclusive</p>
        </article>
    </div>

    <p>
        Un espace de jeu, d'expérimentation et de partage, ouvert aux enfants avec ou sans déficience sensorielle, et à leurs familles.
    </p>
</section>

<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <img src="" alt="Deux membres de l'équipe de la ludothèque dans l'espace d'accueil">
        <img src="" alt="Enfants jouant dans un espace sensoriel avec éclairage bleu">
        <img src="" alt="Espace extérieur décoré avec des créations artistiques colorées">
    </figure>
</section>

<section class="tight" aria-labelledby="offre-heading">
    <h2 id="offre-heading">Ce que vous trouverez à Oasis</h2>
    <ul>
        <li>Jeux sensoriels, moteurs, symboliques et éducatifs</li>
        <li>Matériel adapté : braille, gros caractères, supports tactiles</li>
        <li>Ateliers thématiques encadrés</li>
        <li>Prêt de jeux et de matériel spécialisé</li>
    </ul>
</section>

<section class="tight" aria-labelledby="lieu-heading">
    <h2 id="lieu-heading">Un lieu ouvert à tous</h2>
    <p>La ludothèque est une ressource pour les familles, les écoles et les institutions extérieures. Chacun peut y trouver des idées, des outils et un espace bienveillant pour jouer et apprendre.</p>
</section>

<section class="tight" aria-labelledby="infos-heading">
    <h2 id="infos-heading">Infos pratiques</h2>
    <h3>Horaires d'ouverture</h3>
    <p>Du lundi au vendredi : de 10h à 16h30</p>

    <h3>Tarifs</h3>
    <ul>
        <li>Abonnement annuel famille : 80 €</li>
        <li>Prêt de jeu : 1 € / semaine</li>
    </ul>
</section>

<section class="tight" aria-labelledby="contact-heading">
    <h2 id="contact-heading">Contact</h2>
    <div class="triple-infos">
        <article>
            <h3>Numéro de téléphone</h3>
            <span>02 *** *** ***</span>
        </article>

        <article>
            <h3>Adresse mail</h3>
            <a href="mailto:ludo@irsa.be">ludo@irsa.be</a>
        </article>

        <article>
            <h3>Adresse</h3>
            <address>Chée de Waterloo 1504, Uccle</address>
        </article>
    </div>
</section>

<section>
    <a class="btn btn-primary" href="/documents/brochure-ludotheque-oasis.pdf" download>Télécharger Brochure de présentation</a>
</section>

<?php
return function ($this_html) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
