<header>
    <h1><span class="tight">L'IRSA : une histoire qui dure !</span></h1>
    <div class="tight">
    <p>Depuis 1835, l'IRSA accompagne enfants, jeunes et adultes atteints de déficience auditive, visuelle ou multiple.</p>
    <p>Situé à Uccle, l'institut offre un accompagnement global : scolarité, soins, hébergement, activités éducatives, guidance familiale, etc.</p>
    <p>Chaque personne est accueillie avec une attention particulière à ses besoins, son rythme et son projet de vie.</p>
</div>
</header>

<section class="timeline tight">

    <article>
        <h2>1835</h2>
        <figure>
            <img src="/ui/timeline/1835.svg" alt="Fondation de l'Institut des Sourds-Muets">
        </figure>
        <p>Fondation de l'Institut des Sourds-Muets</p>
    </article>

    <article>
        <h2>1837</h2>
        <figure>
            <img src="/ui/timeline/1837.svg" alt="Arrivée des Sœurs de la Charité">
        </figure>
        <p>Arrivée des Sœurs de la Charité</p>
    </article>

    <article>
        <h2>1858</h2>
        <figure>
            <img src="/ui/timeline/1858.svg" alt="Installation à Uccle">
        </figure>
        <p>Installation à Uccle</p>
    </article>

    <article>
        <h2>1948</h2>
        <figure>
            <img src="/ui/timeline/1948.svg" alt="Reconnaissance officielle">
        </figure>
        <p>Reconnaissance officielle</p>
    </article>

    <article>
        <h2>1900'</h2>
        <figure>
            <img src="/ui/timeline/1900.svg" alt="Premiers ateliers professionnels">
        </figure>
        <p>Premiers ateliers professionnels</p>
    </article>

    <article>
        <h2>1870</h2>
        <figure>
            <img src="/ui/timeline/1870.svg" alt="Accueil des enfants aveugles">
        </figure>
        <p>Accueil des enfants aveugles</p>
    </article>

    <article>
        <h2>1970</h2>
        <figure>
            <img src="/ui/timeline/1970.svg" alt="Expansion">
        </figure>
        <p>Expansion</p>
    </article>

    <article>
        <h2>1987</h2>
        <figure>
            <img src="/ui/timeline/1987.svg" alt="Les centres de jour">
        </figure>
        <p>Les centres de jour</p>
    </article>

    <article>
        <h2>2000'</h2>
        <figure>
            <img src="/ui/timeline/2000.svg" alt="Scolarité, soins, vie quotidienne">
        </figure>
        <p>Scolarité, soins, vie quotidienne</p>
    </article>

    <article>
        <h2>2018</h2>
        <figure>
            <img src="/ui/timeline/2018.svg" alt="La ludothèque Oasis">
        </figure>
        <p>La ludothèque Oasis</p>
    </article>

    <article>
        <h2>2014</h2>
        <figure>
            <img src="/ui/timeline/2014.svg" alt="Appui administratif AVIQ">
        </figure>
        <p>Appui administratif AVIQ</p>
    </article>

    <article>
        <h2>2011</h2>
        <figure>
            <img src="/ui/timeline/2011.svg" alt="Le restaurant d'application">
        </figure>
        <p>Le restaurant d'application</p>
    </article>

    <article>
        <h2>2020'</h2>
        <figure>
            <img src="/ui/timeline/2020.svg" alt="Ouverture et expertise">
        </figure>
        <p>Ouverture et expertise</p>
    </article>

    <article>
        <h2>2025</h2>
        <figure>
            <img src="/ui/timeline/2025.svg" alt="Ouverture à l'Autisme">
        </figure>
        <p>Ouverture à l'Autisme</p>
    </article>

</section>

<!-- Organisme d'Administration -->
<section class="oa" aria-labelledby="oa-heading">
    <div class="tight">

        <h2 id="oa-heading">Notre Organisme d'Administration</h2>
        <p>
            L'IRSA est administré par un Organisme d'Administration (OA), composé de femmes et d'hommes issus du monde associatif,
            professionnel, social et éducatif.
        </p>
        <p>
            Ces membres bénévoles assurent la gestion stratégique, éthique et financière de l'institution.
            Ils veillent à la continuité des missions, à la qualité de l'accompagnement et au respect des valeurs fondamentales de l'IRSA.
        </p>

        <p class="cta">
            <a href="#" class="btn">Voir la composition complète de l'OA</a>
        </p>
    </div>

</section>

<img src="/ui/partners.jpg" alt="">

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-irsa']], IO_EXTRACT);
    return $page;
};
