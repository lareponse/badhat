<img src="/ui/blob/blob_home_top_right.svg" alt="decorative blob shape" class="blob" id="blob_home_top_right" aria-hidden="true">

<section class="tight" id="home-hero" aria-labelledby="hero-heading" lang="fr">
    <div>
        <h1 id="hero-heading">Un projet pour chacun&nbsp;!</h1>

        <p>
            Depuis près de deux siècles, l'IRSA accompagne enfants, jeunes et adultes
            atteints de déficiences sensorielles, avec ou sans handicaps associés.
        </p>

        <p>
            <a href="/commencer" class="btn btn-primary">Commencer</a>
            <a href="/services" class="btn btn-secondary">Découvrir nos services</a>
        </p>
    </div>

    <figure>
        <img src="/ui/home_hero.jpg"
            alt="Un jeune garçon portant un implant auditif caresse un lapin avec tendresse">
    </figure>
</section>
<iframe width="100%" height="600px" src="https://www.youtube.com/embed/-Y0r8Sve0Sc" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>

<section class="tight" id="home-questionnaire" aria-labelledby="questionnaire-heading" lang="fr">

    <h2 id="questionnaire-heading">Quels services correspondent à vos besoins ?</h2>

    <p>
        Répondez à 3 questions simples pour accéder aux services adaptés à votre
        situation ou celle de votre proche.
    </p>

    <p>
        <a href="/questionnaire" class="btn">Commencer le questionnaire</a>
    </p>
</section>


<section class="tight" aria-labelledby="services-heading" lang="fr">
    <h2 id="services-heading">Des services adaptés à chaque étape de la vie</h2>
    <img src="/ui/blob/blob_home_top_right.svg" alt="decorative blob shape" class="blob" id="blob_home_top_right" aria-hidden="true">
    <p>
        Un accompagnement global qui prend en compte les besoins éducatifs,
        thérapeutiques et sociaux de chaque personne.
    </p>

    <div class="card-grid">
        <!-- Éducation spécialisée -->
        <article>
            <figure>
                <img src="/ui/home/service_education.jpg" alt="Salle de classe avec des élèves attentifs et un enseignant">
            </figure>

            <h3>Éducation spécialisée</h3>
            <a href="/ecoles">Vers Écoles</a>

            <p>Un enseignement sur mesure, de la crèche au secondaire.</p>
        </article>

        <!-- Hébergements -->
        <article>
            <figure>
                <img src="/ui/home/service_hebergement.jpg" alt="Chambre aménagée dans un centre d'hébergement">
            </figure>

            <h3>Hébergements</h3>
            <a href="/hebergements">Vers centres d'hébergement</a>

            <p>Des lieux de vie pour accompagner l'autonomie et soulager les familles.</p>
        </article>

        <!-- Centres de jour -->
        <article>
            <figure>
                <img src="/ui/home/service_centres_jour.jpg" alt="Atelier artistique avec plusieurs personnes en activité">
            </figure>
            <h3>Centres de jour</h3>
            <a href="/centres-jour">Vers centres de jour</a>
            <p>Un cadre structuré et bienveillant où chaque personne bénéficie d'activités et de soins répondant à ses besoins.</p>
        </article>
    </div>
</section>

<section class="tight" aria-labelledby="stats-heading" lang="fr">
    <h2 id="stats-heading">L'IRSA en quelques chiffres</h2>

    <dl class="stats-list">
        <div>
            <dt>600+</dt>
            <dd>Personnes accompagnées chaque année</dd>
        </div>

        <div>
            <dt>300+</dt>
            <dd>Professionnels engagés</dd>
        </div>

        <div>
            <dt>4</dt>
            <dd>Établissements scolaires spécialisés</dd>
        </div>

        <div>
            <dt>10</dt>
            <dd>Services et asbl annexes</dd>
        </div>

        <div>
            <dt>2</dt>
            <dd>Lieux de vie pour enfants et jeunes</dd>
        </div>

        <div>
            <dt>1835</dt>
            <dd>Depuis</dd>
        </div>

        <div>
            <dt>15</dt>
            <dd>Moyenne d'enfants réintégrés dans l'enseignement traditionnel par an</dd>
        </div>

        <div>
            <dt>5</dt>
            <dd>Hectares de parc</dd>
        </div>
    </dl>
</section>

<section class="tight" aria-labelledby="don-heading" lang="fr">
    <figure>
        <img src="/ui/home/home_don_acteur.jpg" alt="Main tenant une tablette avec une icône de don en surimpression">
    </figure>

    <div>
        <h2 id="don-heading">Et si vous deveniez acteur de notre mission ?</h2>

        <p>
            Chaque don permet d'améliorer concrètement le quotidien des enfants, jeunes
            et adultes accompagnés par l'IRSA.<br>
            100 % des dons sont investis dans des projets utiles, visibles et concrets.
        </p>

        <p>
            <a href="/don" class="btn">Faire un don</a>
        </p>
    </div>
</section>
<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-home']], IO_EXTRACT);
    return $page;
};
