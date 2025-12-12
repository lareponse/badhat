<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

<header>
    <h1><span class="tight">CJES – Surdité (enfants et ados)</span></h1>
</header>

<section class="tight" aria-labelledby="cjes-presentation-heading">
    <h2 id="cjes-presentation-heading" class="visually-hidden">Présentation du centre</h2>

    <div class="triple-infos">
        <article>
            <h3>Handicap concerné</h3>
            <p>Surdité / malentendance</p>
        </article>

        <article>
            <h3>Tranche d'âge</h3>
            <p>3 à 21 ans</p>
        </article>

        <article>
            <h3>Accompagnements</h3>
            <p>Scolaire, éducatif et thérapeutique, en synergie avec l’école</p>
        </article>
    </div>

    <p>
        Le CJES Surdité accompagne des élèves sourds ou malentendants pendant les temps hors classe et en co-intervention à l’école.
    </p>

    <p>
        Les éducateurs et les professionnels paramédicaux travaillent avec les enseignants pour proposer des aménagements pédagogiques
        et éducatifs adaptés, dans un cadre structuré, chaleureux et accessible à la communication visuelle.
    </p>

    <article aria-labelledby="cjes-public-heading">
        <h3 id="cjes-public-heading">Public accueilli</h3>
        <ul>
            <li>Enfants et adolescents sourds ou malentendants</li>
            <li>Surdité sévère à profonde, avec ou sans appareillage ou implant cochléaire</li>
            <li>Troubles associés possibles, pris en compte au cas par cas</li>
            <li>Porte d’entrée par la déficience auditive : accompagnement individualisé selon les besoins de communication</li>
        </ul>
    </article>
</section>

<section class="tight" aria-labelledby="environnement-heading">
    <h2 id="environnement-heading" class="visually-hidden">Notre environnement</h2>
    <figure>
        <img src="" alt="Espace d’accueil CJES avec supports visuels et repères du quotidien">
        <img src="" alt="Moment d’accompagnement et de co-intervention en contexte scolaire, communication visuelle">
        <img src="" alt="Atelier en petit groupe : jeux, échanges et supports adaptés (pictogrammes, outils visuels)">
    </figure>
</section>

<section class="tight" aria-labelledby="equipe-heading">
    <h2 id="equipe-heading">Équipe pluridisciplinaire</h2>
    <ul>
        <li>Éducateurs</li>
        <li>Logopèdes spécialisés en surdité et communication visuelle</li>
        <li>Ergothérapeutes</li>
        <li>Psychomotriciens</li>
        <li>Psychologues et assistante sociale</li>
        <li>Interprètes ou maîtres en LSFB lorsque nécessaire</li>
        <li>Coordination par les chefs de projet en collaboration avec l’école</li>
    </ul>
</section>

<section class="tight" aria-labelledby="journee-heading">
    <h2 id="journee-heading">Une journée type</h2>
    <dl>
        <dt>08:00 – 08:30</dt>
        <dd>Accueil CJES et accompagnement vers l’école</dd>

        <dt>08:30 – 12:00</dt>
        <dd>Cours et prises en charge individuelles</dd>

        <dt>12:00 / 12:30 – 13:30</dt>
        <dd>Repas au CJES, communication visuelle, autonomie</dd>

        <dt>13:30 – 15:00</dt>
        <dd>Retour en classe ou suivis logopédiques</dd>

        <dt>15:00 – 16:15</dt>
        <dd>CJES (goûter, ateliers, jeux, retour avec support visuel), transport COCOF</dd>
    </dl>

    <p>
        Les horaires varient selon l’âge et les sections du fondamental ou du secondaire.
    </p>
</section>

<section class="tight" aria-labelledby="activites-heading">
    <h2 id="activites-heading">Activités régulières et ressources</h2>
    <ul>
        <li>Ateliers de communication en LSFB ou oralité, selon le profil</li>
        <li>Ateliers sensoriels et perceptifs (vibration, rythme, exploration corporelle)</li>
        <li>Activités sportives, sorties culturelles, médiation animale, psychomotricité</li>
        <li>EVRAS adaptée : travail de la bulle, affectivité et relations</li>
        <li>Outils adaptés : pictogrammes, supports visuels, outils vibrants, applications de communication</li>
        <li>Inclusion : activités sur site et à l’extérieur, projets communs avec l’école</li>
    </ul>
</section>

<section class="tight" aria-labelledby="modalites-heading">
    <h2 id="modalites-heading">Modalités d’admission</h2>
    <ol>
        <li>Premier contact par mail ou téléphone</li>
        <li>Analyse de la demande par la direction de l’école et le chef de projet</li>
        <li>Rencontre famille et visite : explications, adaptations possibles selon le mode de communication de l’enfant</li>
        <li>Inscription via le dossier école</li>
    </ol>

    <p>
        Les enfants déjà scolarisés à l’IRSA peuvent intégrer le CJES via une procédure simplifiée.
    </p>
</section>

<section class="tight" aria-labelledby="distinction-heading">
    <h2 id="distinction-heading">Ce qui nous distingue</h2>
    <ul>
        <li>Un cadre structuré et bienveillant centré sur la communication</li>
        <li>Une vision globale école et CJES</li>
        <li>Des supports visuels permanents et adaptés</li>
        <li>Un accompagnement individualisé selon le mode de communication</li>
        <li>Une alliance forte avec les familles grâce à des échanges réguliers</li>
        <li>De petites unités qui permettent un suivi précis et rassurant</li>
    </ul>
</section>

<section class="tight" aria-labelledby="contact-heading">
    <h2 id="contact-heading" class="visually-hidden">Contact</h2>
    <p>
        <a href="/contact">Vers le formulaire de contact</a>
    </p>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);

    return $page;
};
