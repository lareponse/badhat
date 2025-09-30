<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1><span class="tight">École fondamentale – déficience visuelle et troubles associés</span></h1>
</header>

<section class="tight" aria-labelledby="fondamentale-presentation-heading">
    <h2 id="fondamentale-presentation-heading" class="visually-hidden">
        Présentation de l'école
    </h2>

    <div class="triple-infos">
        <article>
            <h3>Handicap concerné</h3>
            <p>Déficience visuelle et troubles associés</p>
        </article>

        <article>
            <h3>Tranche d'âge</h3>
            <p>3 à 21 ans</p>
        </article>

        <article>
            <h3>Type de structure</h3>
            <p>École fondamentale spécialisée</p>
        </article>
    </div>

    <p>
        L'école fondamentale IRSA pour enfants atteints de déficience visuelle et/ou de troubles associés accueille des élèves de la maternelle à la 6e primaire. Elle propose un enseignement individualisé, des outils adaptés et un accompagnement pluridisciplinaire pour favoriser l'autonomie et la réussite scolaire.
    </p>
</section>

<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <img src="" alt="Personne avec canne blanche accompagnée d'un chien guide traversant un passage piéton">
        <img src="" alt="Personne utilisant une canne blanche sur un passage piéton">
        <img src="" alt="Mains lisant un texte en braille">
    </figure>
</section>

<section class="tight" aria-labelledby="details-heading">
    <h2 id="details-heading">Détails de l'accompagnement</h2>
    <ul>
        <li>Classes à effectif réduit pour un suivi personnalisé</li>
        <li>Utilisation d'outils et supports adaptés (braille, gros caractères, supports tactiles)</li>
        <li>Interventions régulières de l'équipe paramédicale (logopédie, kinésithérapie, psychomotricité…)</li>
        <li>Collaboration étroite avec les familles pour assurer une continuité éducative</li>
    </ul>
</section>

<section class="tight" aria-labelledby="services-heading">
    <h2 id="services-heading">Services associés</h2>
    <p>En plus de la scolarité, chaque élève peut bénéficier des ressources du centre de services IRSA : soins spécialisés, accompagnement social, activités éducatives et culturelles.</p>
</section>

<section class="tight" aria-labelledby="modalites-heading">
    <h2 id="modalites-heading">Modalités pratiques</h2>
    <ul>
        <li>Accueil du lundi au vendredi</li>
        <li>Admission sur orientation du PMS et reconnaissance AVIQ</li>
        <li>Contact direct pour toute information ou visite de l'école</li>
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
            <a href="mailto:fondamental178@irsa.be">fondamental178@irsa.be</a>
        </article>

        <article>
            <a href="/contact">Vers le formulaire de contact</a>
        </article>
    </div>
</section>

<section>
    <a href="brochure-presentation-fondamentale.pdf" download>Télécharger Brochure de présentation</a>
</section>
<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
