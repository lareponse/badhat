<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1><span class="tight">École fondamentale spécialisée</span></h1>
</header>

<form method="post" enctype="multipart/form-data">

    <section class="tight" aria-labelledby="fondamentale-presentation-heading">
        <h2 id="fondamentale-presentation-heading" class="visually-hidden">Présentation de l'école</h2>

        <div class="triple-infos">
            <article>
                <h3>Handicap concerné</h3>
                <textarea name="handicap_concerne">Déficience visuelle (type 6 et type 7) avec ou sans troubles associés</textarea>
            </article>

            <article>
                <h3>Types proposés</h3>
                <textarea name="types_proposes">Type 6, Type 7, Type 8 (parcours et objectifs différenciés)</textarea>
            </article>

            <article>
                <h3>Type de structure</h3>
                <textarea name="type_structure">Enseignement fondamental spécialisé</textarea>
            </article>
        </div>

        <textarea name="presentation_texte">À l'IRSA, une école inclusive où chaque enfant trouve sa place : apprentissages adaptés, autonomie développée et ouverture au monde grâce à une équipe pédagogique spécialisée.</textarea>
    </section>

    <section class="tight" aria-labelledby="environnement-heading">
        <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
        <figure class="banner-gallery">
            <img src="/ui/banners/accueil_1.webp" alt="">
            <img src="/ui/banners/accueil_2.webp" alt="Enfant accompagné dans une activité de communication : gestes, supports visuels ou langue des signes">
            <img src="/ui/banners/accueil_3.webp" alt="">
            <img src="/ui/banners/accueil_4.webp" alt="">
        </figure>
    </section>

    <section class="tight" aria-labelledby="objectifs-heading">
        <h2 id="objectifs-heading">Objectifs pédagogiques</h2>
        <textarea name="objectifs_pedagogiques" rows="8">
        Développement de l'autonomie et des compétences sociales
        Apprentissages adaptés selon le type et les besoins individuels
        Acquisition des savoirs de base (lecture, écriture, mathématiques)
        Initiation aux outils numériques et technologies adaptées
        Projets de vie personnalisés en lien avec chaque élève et sa famille
    </textarea>
    </section>

    <section class="tight" aria-labelledby="approches-heading">
        <h2 id="approches-heading">Approches & dispositifs</h2>
        <textarea name="approches_dispositifs" rows="8">
        Pédagogies adaptées : supports tactiles, braille, gros caractères, audio
        Classes à effectifs réduits pour un accompagnement individualisé
        Outils numériques et méthodes digitales adaptés aux besoins visuels
        Soutien paramédical intégré (logo, kiné, psychomotricité, etc.)
        Collaboration étroite avec les familles et les services IRSA
    </textarea>
    </section>

    <section class="tight" aria-labelledby="types-heading">
        <h2 id="types-heading">Types d'enseignement</h2>

        <div class="types-grid">
            <article>
                <h3>Type 6</h3>
                <textarea name="type6">
                Pour élèves avec déficience visuelle nécessitant un enseignement adapté avec objectifs du tronc commun
            </textarea>
            </article>

            <article>
                <h3>Type 7</h3>
                <textarea name="type7">
                Pour élèves avec déficience visuelle nécessitant un enseignement adapté avec objectifs différenciés
            </textarea>
            </article>

            <article>
                <h3>Type 8</h3>
                <textarea name="type8">
                Pour élèves avec troubles des apprentissages associés nécessitant des aménagements spécifiques
            </textarea>
            </article>
        </div>
    </section>

    <section class="tight" aria-labelledby="services-heading">
        <h2 id="services-heading">Services associés IRSA</h2>
        <textarea name="services_irsa" rows="6">
        Accès au centre de services (soins spécialisés, accompagnement social)
        Possibilités d'internat / semi-internat (Centre pur) / externe selon la situation familiale
        Transport scolaire : possible via la COCOF (service externe, information/liaison fournie par l'école)
    </textarea>
    </section>

    <section class="tight" aria-labelledby="admission-heading">
        <h2 id="admission-heading">Modalités d'admission</h2>
        <textarea name="modalites_admission" rows="6">
        Orientation : attestation vers type 6/7/8 (médecin spécialiste ou PMS, selon le type) + dossier scolaire
        Demande d'inscription : via formulaire en ligne (renseignements famille, âge/classe, type, besoins) → commission interne d'admission → réponse et visite
    </textarea>
    </section>

    <section class="tight" aria-labelledby="contact-heading">
        <h2 id="contact-heading">Contact</h2>
        <div class="triple-infos">
            <article>
                <h3>Numéro de téléphone</h3>
                <input type="text" name="telephone" value="02 374 03 68">
            </article>

            <article>
                <h3>Adresse mail</h3>
                <input type="email" name="email" value="dirsec@irsa.be">
            </article>

            <article>
                <h3>Texte du lien contact</h3>
                <input type="text" name="lien_contact" value="Vers le formulaire de contact">
            </article>
        </div>
    </section>

    <section class="tight" aria-labelledby="liens-heading">
        <h2 id="liens-heading">Liens utiles</h2>
        <div class="liens-grid">
            <label>Projet pédagogique : <input type="file" name="pdf_projet"></label>
            <label>Règlement des études : <input type="file" name="pdf_reglement"></label>
            <label>Télécharger Brochure de présentation : <input type="file" name="pdf_brochure"></label>
        </div>
    </section>

    <button type="submit">Enregistrer</button>
</form>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
