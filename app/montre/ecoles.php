<header>
    <h1><span class="tight">Les écoles de l'IRSA</span></h1>
    <p class="tight">
        L'IRSA propose un parcours scolaire complet pour les enfants et adolescents atteints de déficience auditive,
        visuelle ou avec troubles associés.<br>
        Chaque établissement est adapté à un type de public spécifique, avec un accompagnement pédagogique et
        thérapeutique individualisé.
    </p>
</header>

<section class="card-grid tight">
    <article>
        <img src="/images/ecole-visuelle.jpg" alt="Enseignante avec un élève">
        <h2>École fondamentale déficience visuelle et troubles associés</h2>
        <a href="#" class="btn">Découvrir l'école</a>
        <ul>
            <li>Déficience visuelle (T6)</li>
            <li>Troubles associés (T8)</li>
        </ul>
    </article>

    <article>
        <img src="/images/ecole-auditive.jpg" alt="Enseignante en langue des signes avec un élève">
        <h2>École fondamentale déficience auditive</h2>
        <a href="#" class="btn">Découvrir l'école</a>
        <p>Approche bilingue (LSFB / FR)</p>
    </article>

    <article>
        <img src="/images/ecole-secondaire.jpg" alt="Deux adolescentes en classe">
        <h2>École secondaire spécialisée</h2>
        <a href="#" class="btn">Découvrir le secondaire</a>
        <p>Adolescents avec :</p>
        <ul>
            <li>Déficience auditive (T7)</li>
            <li>Déficience visuelle (T6)</li>
            <li>Troubles d'apprentissage (T1)</li>
        </ul>
    </article>
</section>

<section class="card-grid tight">
    <article>
        <img src="/images/creche.jpg" alt="Jeune enfant jouant avec des blocs colorés">
        <h2>Crèche inclusive Le Petit Prince</h2>
        <a href="#" class="btn">Découvrir la crèche</a>
        <p>Accueille les tout-petits à partir de quelques mois, avec ou sans déficience sensorielle.</p>
    </article>

    <article>
        <img src="/images/pms.jpg" alt="Psychologue en discussion avec une adolescente et sa mère">
        <h2>Centre PMS spécialisé</h2>
        <a href="#" class="btn">Découvrir le PMS</a>
        <p>
            Écoute, soutien, orientation et collaboration avec l'équipe éducative pour favoriser le bien-être et la réussite.
        </p>
    </article>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-ecoles']], IO_EXTRACT);
    return $page;
};
