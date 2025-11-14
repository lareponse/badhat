<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1>
        <label>École fondamentale – déficience visuelle et troubles associés</label>
        <input type="text" name="titre_principal" value="École fondamentale – déficience visuelle et troubles associés">
    </h1>
</header>

<form method="post" enctype="multipart/form-data">

<section class="tight" aria-labelledby="fondamentale-presentation-heading">
    <h2 id="fondamentale-presentation-heading" class="visually-hidden">Présentation de l'école</h2>

    <div class="triple-infos">
        <article>
            <h3>Handicap concerné</h3>
            <input type="text" name="handicap_concerne" value="Déficience visuelle (T6), troubles du spectre de l'autisme (T8), déficiences multiples (T2)">
        </article>

        <article>
            <h3>Tranche d'âge</h3>
            <input type="text" name="tranche_age" value="3 à 12 ans">
        </article>

        <article>
            <h3>Type de structure</h3>
            <input type="text" name="type_structure" value="École fondamentale spécialisée">
        </article>
    </div>

    <textarea name="presentation_texte">L'école fondamentale T2–T6–T8 de l'IRSA accueille des enfants présentant une déficience visuelle, un trouble du spectre de l'autisme ou une déficience multiple. Notre mission est de leur offrir un enseignement adapté, de développer leur autonomie et de favoriser leur inclusion, en collaboration étroite avec les familles.</textarea>
</section>

<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <label>Image 1</label><input type="file" name="img_env_1">
        <label>Image 2</label><input type="file" name="img_env_2">
        <label>Image 3</label><input type="file" name="img_env_3">
    </figure>
</section>

<section class="tight" aria-labelledby="accompagnement-heading">
    <h2 id="accompagnement-heading">Accompagnement et pédagogie</h2>
    <textarea name="accompagnement_liste">
        Classes à effectif réduit permettant un suivi individualisé
        Adaptations pédagogiques selon le type :
            - T6 : compensations liées à la basse vision ou à la cécité
            - T8 : stratégies spécifiques pour les enfants avec TSA
            - T2 : accompagnement global pour enfants avec déficiences multiples
        Travail pluridisciplinaire avec éducateurs et paramédicaux (logo, kiné, psychomotricité, etc.)
        Objectif central : soutenir les apprentissages fondamentaux et préparer l'entrée au secondaire
    </textarea>
</section>

<section class="tight" aria-labelledby="services-heading">
    <h2 id="services-heading">Services associés</h2>
    <textarea name="services_liste">
        Interventions paramédicales intégrées dans le temps scolaire
        Collaboration avec le centre de services IRSA pour renforcer les suivis spécialisés
        Activités éducatives et sorties favorisant l'autonomie et la socialisation
    </textarea>
</section>

<section class="tight" aria-labelledby="admission-heading">
    <h2 id="admission-heading">Modalités d'admission</h2>
    <textarea name="admission_public">
        <strong>Public :</strong> enfants de 3 à 12 ans avec une déficience visuelle, un TSA ou une déficience multiple (diagnostic requis)
    </textarea>
    <textarea name="procedure">
        Orientation par un PMS ou dossier médical adapté
        Premier contact avec la direction de l'école
        Rencontre et visite de l'établissement
        Décision d'admission validée par l'équipe pluridisciplinaire
    </textarea>
    <textarea name="admission_note">
        Certains profils médicaux trop lourds peuvent nécessiter une orientation vers une structure plus adaptée !
    </textarea>
</section>

<section class="tight" aria-labelledby="contact-heading">
    <h2 id="contact-heading">Contact</h2>
    <div class="triple-infos">
        <article>
            <h3>Numéro de téléphone</h3>
            <input type="text" name="telephone" value="02 *** *** ***">
        </article>

        <article>
            <h3>Adresse mail</h3>
            <input type="email" name="email" value="fondat288@irsa.be">
        </article>

        <article>
            <label>Vers le formulaire de contact</label>
            <input type="text" name="lien_contact" value="/contact">
        </article>
    </div>
</section>

<section class="tight" aria-labelledby="liens-heading">
    <h2 id="liens-heading">Liens utiles</h2>
    <div class="liens-grid">
        <label>Projet pédagogique : <input type="text" name="lien_projet" value="projet-pedagogique.pdf"></label>
        <label>Règlement des études : <input type="text" name="lien_reglement" value="reglement-etudes.pdf"></label>
        <label>Brochure métiers : <input type="text" name="lien_brochure" value="brochure-metiers.pdf"></label>
    </div>
</section>

<section>
    <label>Télécharger Brochure de présentation</label>
    <input type="text" name="brochure" value="brochure-presentation-fondamentale.pdf">
</section>

<button type="submit">Enregistrer</button>
</form>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};