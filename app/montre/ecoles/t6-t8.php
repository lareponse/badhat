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
            <p>Déficience visuelle (T6), troubles du spectre de l'autisme (T8), déficiences multiples (T2)</p>
        </article>

        <article>
            <h3>Tranche d'âge</h3>
            <p>3 à 12 ans</p>
        </article>

        <article>
            <h3>Type de structure</h3>
            <p>École fondamentale spécialisée</p>
        </article>
    </div>

    <p>
        L'école fondamentale T2–T6–T8 de l'IRSA accueille des enfants présentant une déficience visuelle, un trouble du spectre de l'autisme ou une déficience multiple. Notre mission est de leur offrir un enseignement adapté, de développer leur autonomie et de favoriser leur inclusion, en collaboration étroite avec les familles.
    </p>
</section>

<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <img src="" alt="Élèves travaillant sur ordinateurs en classe">
        <img src="" alt="Activité en classe avec enseignante et élèves">
        <img src="" alt="Bus scolaire jaune devant l'établissement">
    </figure>
</section>

<section class="tight" aria-labelledby="accompagnement-heading">
    <h2 id="accompagnement-heading">Accompagnement et pédagogie</h2>
    <ul>
        <li>Classes à effectif réduit permettant un suivi individualisé</li>
        <li>Adaptations pédagogiques selon le type :
            <ul>
                <li>T6 : compensations liées à la basse vision ou à la cécité</li>
                <li>T8 : stratégies spécifiques pour les enfants avec TSA</li>
                <li>T2 : accompagnement global pour enfants avec déficiences multiples</li>
            </ul>
        </li>
        <li>Travail pluridisciplinaire avec éducateurs et paramédicaux (logo, kiné, psychomotricité, etc.)</li>
        <li>Objectif central : soutenir les apprentissages fondamentaux et préparer l'entrée au secondaire</li>
    </ul>
</section>

<section class="tight" aria-labelledby="services-heading">
    <h2 id="services-heading">Services associés</h2>
    <ul>
        <li>Interventions paramédicales intégrées dans le temps scolaire</li>
        <li>Collaboration avec le centre de services IRSA pour renforcer les suivis spécialisés</li>
        <li>Activités éducatives et sorties favorisant l'autonomie et la socialisation</li>
    </ul>
</section>

<section class="tight" aria-labelledby="admission-heading">
    <h2 id="admission-heading">Modalités d'admission</h2>
    <p><strong>Public :</strong> enfants de 3 à 12 ans avec une déficience visuelle, un TSA ou une déficience multiple (diagnostic requis)</p>
    <p><strong>Procédure :</strong></p>
    <ol>
        <li>Orientation par un PMS ou dossier médical adapté</li>
        <li>Premier contact avec la direction de l'école</li>
        <li>Rencontre et visite de l'établissement</li>
        <li>Décision d'admission validée par l'équipe pluridisciplinaire</li>
    </ol>
    <p>Certains profils médicaux trop lourds peuvent nécessiter une orientation vers une structure plus adaptée !</p>
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
            <a href="mailto:fondat288@irsa.be">fondat288@irsa.be</a>
        </article>

        <article>
            <a href="/contact">Vers le formulaire de contact</a>
        </article>
    </div>
</section>

<section class="tight" aria-labelledby="liens-heading">
    <h2 id="liens-heading">Liens utiles</h2>
    <div class="liens-grid">
        <a href="projet-pedagogique.pdf">Projet pédagogique</a>
        <a href="reglement-etudes.pdf">Règlement des études</a>
        <a href="brochure-metiers.pdf">Brochure métiers</a>
    </div>
</section>

<section>
    <a href="brochure-presentation-fondamentale.pdf" download>Télécharger Brochure de présentation</a>
</section>
<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
