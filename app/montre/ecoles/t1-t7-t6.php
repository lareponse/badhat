<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1><span class="tight">École fondamentale spécialisée</span></h1>
</header>

<section class="tight" aria-labelledby="fondamentale-presentation-heading">
    <h2 id="fondamentale-presentation-heading" class="visually-hidden">
        Présentation de l'école
    </h2>

    <div class="triple-infos">
        <article>
            <h3>Handicap concerné</h3>
            <p>Déficience visuelle (type 6 et type 7) avec ou sans troubles associés</p>
        </article>

        <article>
            <h3>Types proposés</h3>
            <p>Type 6, Type 7, Type 8 (parcours et objectifs différenciés)</p>
        </article>

        <article>
            <h3>Type de structure</h3>
            <p>Enseignement fondamental spécialisé</p>
        </article>
    </div>

    <p>
        À l'IRSA, une école inclusive où chaque enfant trouve sa place : apprentissages adaptés, autonomie développée et ouverture au monde grâce à une équipe pédagogique spécialisée.
    </p>
</section>

<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <img src="" alt="Salle de classe avec élèves et enseignante">
        <img src="" alt="Élèves participant à une activité pédagogique">
        <img src="" alt="Groupe d'élèves dans le couloir de l'école">
    </figure>
</section>

<section class="tight" aria-labelledby="objectifs-heading">
    <h2 id="objectifs-heading">Objectifs pédagogiques</h2>
    <ul>
        <li>Développement de l'autonomie et des compétences sociales</li>
        <li>Apprentissages adaptés selon le type et les besoins individuels</li>
        <li>Acquisition des savoirs de base (lecture, écriture, mathématiques)</li>
        <li>Initiation aux outils numériques et technologies adaptées</li>
        <li>Projets de vie personnalisés en lien avec chaque élève et sa famille</li>
    </ul>
</section>

<section class="tight" aria-labelledby="approches-heading">
    <h2 id="approches-heading">Approches & dispositifs</h2>
    <ul>
        <li>Pédagogies adaptées : supports tactiles, braille, gros caractères, audio</li>
        <li>Classes à effectifs réduits pour un accompagnement individualisé</li>
        <li>Outils numériques et méthodes digitales adaptés aux besoins visuels</li>
        <li>Soutien paramédical intégré (logo, kiné, psychomotricité, etc.)</li>
        <li>Collaboration étroite avec les familles et les services IRSA</li>
    </ul>
</section>

<section class="tight" aria-labelledby="types-heading">
    <h2 id="types-heading">Types d'enseignement</h2>

    <div class="types-grid">
        <article>
            <h3>Type 6</h3>
            <p>Pour élèves avec déficience visuelle nécessitant un enseignement adapté avec objectifs du tronc commun</p>
        </article>

        <article>
            <h3>Type 7</h3>
            <p>Pour élèves avec déficience visuelle nécessitant un enseignement adapté avec objectifs différenciés</p>
        </article>

        <article>
            <h3>Type 8</h3>
            <p>Pour élèves avec troubles des apprentissages associés nécessitant des aménagements spécifiques</p>
        </article>
    </div>
</section>

<section class="tight" aria-labelledby="services-heading">
    <h2 id="services-heading">Services associés IRSA</h2>
    <ul>
        <li>Accès au centre de services (soins spécialisés, accompagnement social)</li>
        <li>Possibilités d'internat / semi-internat (Centre pur) / externe selon la situation familiale</li>
        <li>Transport scolaire : possible via la COCOF (service externe, information/liaison fournie par l'école)</li>
    </ul>
</section>

<section class="tight" aria-labelledby="admission-heading">
    <h2 id="admission-heading">Modalités d'admission</h2>
    <ul>
        <li>Orientation : attestation vers type 6/7/8 (médecin spécialiste ou PMS, selon le type) + dossier scolaire</li>
        <li>Demande d'inscription : via formulaire en ligne (renseignements famille, âge/classe, type, besoins) → commission interne d'admission → réponse et visite</li>
    </ul>
</section>

<section>
    <a href="#formulaire-inscription">Vers formulaire d'inscription</a>
</section>

<section class="tight" aria-labelledby="contact-heading">
    <h2 id="contact-heading">Contact</h2>
    <div class="triple-infos">
        <article>
            <h3>Numéro de téléphone</h3>
            <span>02 374 03 68</span>
        </article>

        <article>
            <h3>Adresse mail</h3>
            <a href="mailto:dirsec@irsa.be">dirsec@irsa.be</a>
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
        <a href="brochure-fondamentale.pdf" download>Télécharger Brochure de présentation</a>
    </div>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
