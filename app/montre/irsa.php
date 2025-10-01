<header>
    <h1><span class="tight">L'IRSA : une histoire qui dure !</span></h1>
    <div class="tight">
        <p>Depuis 1835, l'IRSA accompagne enfants, jeunes et adultes atteints de déficience auditive, visuelle ou multiple.</p>
        <p>Situé à Uccle, l'institut offre un accompagnement global : scolarité, soins, hébergement, activités éducatives, guidance familiale, etc.</p>
        <p>Chaque personne est accueillie avec une attention particulière à ses besoins, son rythme et son projet de vie.</p>
    </div>
</header>

<section class="timeline tight" aria-labelledby="timeline-heading">
    <h2 id="timeline-heading" class="visually-hidden">Frise chronologique de l’IRSA</h2>

    <article class="move-lr">
        <h3><time>1835</time></h3>
        <figure>
            <img src=" /ui/timeline/1835.svg" alt="">
            <figcaption>Fondation de l'Institut des Sourds-Muets</figcaption>
        </figure>
    </article>

    <article>
        <h3><time>1837</time></h3>
        <figure>
            <img src="/ui/timeline/1837.svg" alt="">
            <figcaption>Arrivée des Sœurs de la Charité</figcaption>
        </figure>
    </article>

    <article class="move-ud">
        <h3><time>1858</time></h3>
        <figure>
            <img src="/ui/timeline/1858.svg" alt="">
            <figcaption>Installation à Uccle</figcaption>
        </figure>
    </article>

    <article class="move-ud">
        <h3><time>1948</time></h3>
        <figure>
            <img src="/ui/timeline/1948.svg" alt="">
            <figcaption>Reconnaissance officielle</figcaption>
        </figure>
    </article>

    <article>
        <h3><time>1900'</time></h3>
        <figure>
            <img src="/ui/timeline/1900.svg" alt="">
            <figcaption>Premiers ateliers professionnels</figcaption>
        </figure>
    </article>

    <article class="move-rl">
        <h3><time>1870</time></h3>
        <figure>
            <img src="/ui/timeline/1870.svg" alt="">
            <figcaption>Accueil des enfants aveugles</figcaption>
        </figure>
    </article>

    <article class="move-lr">
        <h3><time>1970</time></h3>
        <figure>
            <img src="/ui/timeline/1970.svg" alt="">
            <figcaption>Expansion</figcaption>
        </figure>
    </article>

    <article>
        <h3><time>1987</time></h3>
        <figure>
            <img src="/ui/timeline/1987.svg" alt="">
            <figcaption>Les centres de jour</figcaption>
        </figure>
    </article>

    <article class="move-ud">
        <h3><time>2000'</time></h3>
        <figure>
            <img src="/ui/timeline/2000.svg" alt="">
            <figcaption>Scolarité, soins, vie quotidienne</figcaption>
        </figure>
    </article>

    <article class="move-ud">
        <h3><time>2018</time></h3>
        <figure>
            <img src="/ui/timeline/2018.svg" alt="">
            <figcaption>La ludothèque Oasis</figcaption>
        </figure>
    </article>

    <article>
        <h3><time>2014</time></h3>
        <figure>
            <img src="/ui/timeline/2014.svg" alt="">
            <figcaption>Appui administratif AVIQ</figcaption>
        </figure>
    </article>

    <article class="move-rl">
        <h3><time>2011</time></h3>
        <figure>
            <img src="/ui/timeline/2011.svg" alt="">
            <figcaption>Le restaurant d'application</figcaption>
        </figure>
    </article>

    <article class="move-lr">
        <h3><time>2020'</time></h3>
        <figure>
            <img src="/ui/timeline/2020.svg" alt="">
            <figcaption>Ouverture et expertise</figcaption>
        </figure>
    </article>

    <article>
        <h3><time>2025</time></h3>
        <figure>
            <img src="/ui/timeline/2025.svg" alt="">
            <figcaption>Ouverture à l'Autisme</figcaption>
        </figure>
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
