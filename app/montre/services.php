<header>
    <h1><span class="tight">Nos structures et services</span></h1>
</header>

<!-- Centres d'hébergement -->
<section aria-labelledby="hebergement-heading">
    <h2 id="hebergement-heading">Centres d'hébergement</h2>

    <div class="link-grid tight">
        <article>
            <h3>Centre d'hébergement pour enfant :</h3>
            <ul>
                <li><a href="/services/che-surdite">Avec déficience auditive</a></li>
                <li><a href="/services/che-cecite">Avec déficience visuelle</a></li>
                <li><a href="#">Avec troubles autistiques</a></li>
            </ul>
        </article>

        <article>
            <h3>Centre d'hébergement pour adulte :</h3>
            <ul>
                <li><a href="/services/aubier">Avec déficience visuelle – Aubier</a></li>
            </ul>
        </article>
    </div>
</section>

<!-- Centres de jour -->
<section aria-labelledby="jour-heading">
    <h2 id="jour-heading">Centres de jour</h2>

    <article class="tight">
        <h3>Centre de jour pour enfant :</h3>
        <div class="link-grid">
            <div>
                <h4>Scolarisé :</h4>
                <ul>
                    <li><a href="/services/cjes-surdite">Avec déficience auditive</a></li>
                    <li><a href="/services/cjes-cecite">Avec déficience visuelle</a></li>
                    <li><a href="#">Avec troubles autistiques</a></li>
                </ul>
            </div>
            <div>
                <h4>Non-scolarisé :</h4>
                <ul>
                    <li><a href="/services/cjens-surdite">Avec déficience auditive</a></li>
                    <li><a href="/services/cjens-cecite">Avec déficience visuelle</a></li>
                    <li><a href="#">Avec troubles autistiques</a></li>
                </ul>
            </div>
        </div>
    </article>

    <article class="tight">
        <h3>Centre de jour pour adulte :</h3>
        <div class="link-grid">

            <ul>
                <li><a href="/services/aubier">Avec déficience visuelle</a></li>
            </ul>
        </div>
    </article>
</section>

<!-- Services -->
<section aria-labelledby="services-heading">
    <h2 id="services-heading">Nos services</h2>
    <div class="card-grid tight">
        <article>
            <a href="/services/ludotheque">
                <img src="/ui/pages/service/ludotheque.jpg" alt="Ludothèque Oasis">
                <h3>Ludothèque Oasis</h3>
                <span class="cta">Horaires et Infos</span>
                <p>Un espace de jeux sensoriels à tous, internes ou externes.</p>
            </a>
        </article>

        <article>
            <a href="/services/chateau-orangerie">
                <img src="/ui/pages/service/chateau.jpg" alt="Location du Château d'Orangerie">
                <h3>Location du Château d'Orangerie</h3>
                <span class="cta">Découvrir</span>
                <p>Un lieu chaleureux et adapté disponible à la location pour vos formations, événements.</p>
            </a>
        </article>

        <article>
            <a href="/services/documentation">
                <img src="/ui/pages/service/documentation.jpg" alt="Centre de documentation">
                <h3>Centre de documentation</h3>
                <span class="cta">Horaires et Infos</span>
                <p>Un enseignement sur mesure, du déjà acquis au secondaire.</p>
            </a>
        </article>

        <article>
            <a href="/services/restaurant-application">
                <img src="/ui/pages/service/restaurant.jpg" alt="Restaurant d'application">
                <h3>Restaurant d'application</h3>
                <span class="cta">Horaires et Infos</span>
                <p>Un lieu de formation et d'évolution où les jeunes en insertion vous cuisinent pour vous.</p>
            </a>
        </article>

        <article>
            <a href="/services/conference">
                <img src="/ui/pages/service/conference.jpg" alt="Conférences">
                <h3>Conférences</h3>
                <span class="cta">Découvrir</span>
                <p>Événements pour sensibiliser et partager son expertise.</p>
            </a>
        </article>

        <article>
            <a href="/services/formations">
                <img src="/ui/pages/service/formations.jpg" alt="Formations">
                <h3>Formations</h3>
                <span class="cta">Découvrir</span>
                <p>Sessions animées par nos professionnels de terrain, adaptées aux besoins actuels.</p>
            </a>
        </article>
    </div>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-services-dash']], IO_EXTRACT);
    return $page;
};
