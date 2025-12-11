<form method="post" enctype="multipart/form-data">

<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1>
        <label>École fondamentale – déficience visuelle et troubles associés</label>
        <input type="text" name="titre_principal" value="École fondamentale – déficience visuelle et troubles associés">
    </h1>
</header>

<section class="tight" aria-labelledby="fondamentale-presentation-heading">
    <h2 id="fondamentale-presentation-heading" class="visually-hidden">Présentation de l'école</h2>

    <div class="triple-infos">
        <article>
            <h3>Handicap concerné</h3>
            <input type="text" name="handicap_concerne" value="Déficience visuelle et troubles associés">
        </article>

        <article>
            <h3>Tranche d'âge</h3>
            <input type="text" name="tranche_age" value="3 à 21 ans">
        </article>

        <article>
            <h3>Type de structure</h3>
            <input type="text" name="type_structure" value="École fondamentale spécialisée">
        </article>
    </div>

    <textarea name="presentation_texte">
        L'école fondamentale IRSA pour enfants atteints de déficience visuelle et/ou de troubles associés accueille des élèves de la maternelle à la 6e primaire. Elle propose un enseignement individualisé, des outils adaptés et un accompagnement pluridisciplinaire pour favoriser l'autonomie et la réussite scolaire.
    </textarea>
</section>

<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <label>Image 1</label><input type="file" name="img_env_1">
        <label>Image 2</label><input type="file" name="img_env_2">
        <label>Image 3</label><input type="file" name="img_env_3">
    </figure>
</section>

<section class="tight" aria-labelledby="details-heading">
    <h2 id="details-heading">Détails de l'accompagnement</h2>
    <textarea name="details_accompagnement">
        Classes à effectif réduit pour un suivi personnalisé
        Utilisation d'outils et supports adaptés (braille, gros caractères, supports tactiles)
        Interventions régulières de l'équipe paramédicale (logopédie, kinésithérapie, psychomotricité…)
        Collaboration étroite avec les familles pour assurer une continuité éducative</textarea>
</section>

<section class="tight" aria-labelledby="services-heading">
    <h2 id="services-heading">Services associés</h2>
    <textarea name="services_texte">
        En plus de la scolarité, chaque élève peut bénéficier des ressources du centre de services IRSA : soins spécialisés, accompagnement social, activités éducatives et culturelles.
    </textarea>
</section>

<section class="tight" aria-labelledby="modalites-heading">
    <h2 id="modalites-heading">Modalités pratiques</h2>
    <textarea name="modalites_texte">
        Accueil du lundi au vendredi
        Admission sur orientation du PMS et reconnaissance AVIQ
        Contact direct pour toute information ou visite de l'école
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
            <input type="email" name="email" value="fondamental178@irsa.be">
        </article>

        <article>
            <label>Vers le formulaire de contact</label>
            <input type="text" name="lien_contact" value="/contact">
        </article>
    </div>
</section>

<section>
    <label>Télécharger Brochure de présentationabel>
    <input type="text" name="brochure" value="brochure-presentation-fondamentale.pdf">
</section>

<button type="submit">Enregistrer</button>
</form>
<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
