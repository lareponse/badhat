<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1><span class="tight">L'Aubier</span></h1>
</header>

<section class="tight" aria-labelledby="aubier-presentation-heading">
    <h2 id="aubier-presentation-heading" class="visually-hidden">
        Présentation du centre
    </h2>

    <div class="triple-infos">
        <article>
            <h3>Handicap concerné</h3>
            <p>Cécité ou déficience visuelle sévère</p>
        </article>

        <article>
            <h3>Tranche d'âge</h3>
            <p>Adultes à partir de 18 ans</p>
        </article>

        <article>
            <h3>Type de structure</h3>
            <p>Centre d'hébergement spécialisé</p>
        </article>
    </div>

    <p>
        L'Aubier est un centre d'hébergement de l'IRSA dédié aux adultes atteints
        de cécité ou de déficience visuelle sévère. Il offre un lieu de vie adapté,
        sécurisé et chaleureux, permettant à chaque résident de développer son autonomie
        tout en bénéficiant d'un accompagnement individualisé.
    </p>
</section>


<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <img src="" alt="Vue extérieure du bâtiment de L'Aubier, architecture moderne avec espaces verts">
        <img src="" alt="Deux résidents participant à une activité créative dans un espace commun">
        <img src="" alt="Groupe de résidents et membres de l'équipe posant ensemble lors d'un événement">
    </figure>
</section>

<section class="tight" aria-labelledby="accompagnement-heading">
    <h2 id="accompagnement-heading">Détails de l'accompagnement</h2>
    <ul>
        <li>Chambres et espaces communs adaptés aux besoins sensoriels des résidents</li>
        <li>Soutien dans les activités quotidiennes (repas, hygiène, déplacements)</li>
        <li>Programme d'activités éducatives, culturelles et de loisirs</li>
        <li>Interventions de professionnels spécialisés (éducateurs, ergothérapeutes, psychologues...)</li>
        <li>Mise en place de projets de vie personnalisés en lien avec chaque résident</li>
    </ul>
</section>

<section class="tight" aria-labelledby="services-heading">
    <h2 id="services-heading">Services associés</h2>
    <p>Les résidents de L'Aubier bénéficient également de l'expertise du centre de services IRSA, notamment en matière de soins paramédicaux, de rééducation et d'accompagnement social.</p>
</section>

<section class="tight" aria-labelledby="modalites-heading">
    <h2 id="modalites-heading">Modalités pratiques</h2>
    <ul>
        <li>Accueil en hébergement permanent ou temporaire</li>
        <li>Admission sur orientation AVIQ</li>
        <li>Visite sur rendez-vous</li>
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
            <a href="mailto:aubier@irsa.be">aubier@irsa.be</a>
        </article>

        <article>
            <a href="#formulaire">Vers le formulaire de contact</a>
        </article>

    </div>
</section>

<section>
    <a href="brochure-aubier.pdf" download>Télécharger brochure de présentation</a>
</section>
<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);

    return $page;
};
