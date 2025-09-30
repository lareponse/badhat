<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
<img src="/ui/blob/blob_ecoles_middle_left.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">
<header>
    <h1><span class="tight">Les écoles de l'IRSA</span></h1>
    <div class="tight">
        <p>L'IRSA propose un parcours scolaire complet pour les enfants et adolescents atteints de déficience auditive, visuelle ou avec troubles associés.</p>
        <p>Chaque établissement est adapté à un type de public spécifique, avec un accompagnement pédagogique et thérapeutique individualisé.</p>
    </div>
</header>

<section class="card-grid">
    <article>
        <img src="/ui/pages/ecoles/enseignante-assise-au-bureau-eleve.jpg" alt="Enseignante avec un élève">
        <h2>École fondamentale déficience visuelle et troubles associés</h2>
        <a href="/ecoles/t6-t8">Découvrir l'école</a>
        <dl>
            <dt>Pour les enfants atteints de :</dt>
            <dd>Déficience visuelle <span>(T6)</span></dd>
            <dd>Troubles associés <span>(T8)</span></dd>
        </dl>

    </article>

    <article>
        <img src="/ui/pages/ecoles/ecole-auditive.jpg" alt="Enseignante en langue des signes avec un élève">
        <h2>École fondamentale déficience auditive</h2>
        <a href="/ecoles/t7">Découvrir l'école</a>
        <dl>
            <dd>Approche bilingue <span>(LSFB / FR)</span></dd>
        </dl>
    </article>

    <article>
        <img src="/ui/pages/ecoles/ecole-secondaire.jpg" alt="Deux adolescentes en classe">
        <h2>École secondaire spécialisée</h2>
        <a href="/ecoles/t1-t7-t6">Découvrir le secondaire</a>
        <dl>
            <dt>Adolescents avec :</dt>
            <dd>Déficience auditive <span>(T7)</span></dd>
            <dd>Déficience visuelle <span>(T6)</span></dd>
            <dd>Troubles d'apprentissage <span>(T1)</span></dd>
        </dl>
    </article>
</section>

<section class="card-grid">
    <article>
        <img src="/ui/pages/ecoles/creche.jpg" alt="Jeune enfant jouant avec des blocs colorés">
        <h2>Crèche inclusive Le Petit Prince</h2>
        <a href="#">Découvrir la crèche</a>
        <p>Accueille les tout-petits à partir de quelques mois, avec ou sans déficience sensorielle.</p>
    </article>

    <article>
        <img src="/ui/pages/ecoles/pms.jpg" alt="Psychologue en discussion avec une adolescente et sa mère">
        <h2>Centre PMS spécialisé</h2>
        <a href="#">Découvrir le PMS</a>
        <p>Écoute, soutien, orientation et collaboration avec l'équipe éducative pour favoriser le bien-être et la réussite.</p>
    </article>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
