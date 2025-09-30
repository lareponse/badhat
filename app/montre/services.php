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
                <li><a href="#">Avec déficience auditive</a></li>
                <li><a href="#">Avec déficience visuelle</a></li>
                <li><a href="#">Avec troubles autistiques</a></li>
            </ul>
        </article>

        <article>
            <h3>Centre d'hébergement pour adulte :</h3>
            <ul>
                <li><a href="#">Avec déficience visuelle – Aubier</a></li>
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
                    <li><a href="#">Avec déficience auditive</a></li>
                    <li><a href="#">Avec déficience visuelle</a></li>
                    <li><a href="#">Avec troubles autistiques</a></li>
                </ul>
            </div>
            <div>
                <h4>Non-scolarisé :</h4>
                <ul>
                    <li><a href="#">Avec déficience auditive</a></li>
                    <li><a href="#">Avec déficience visuelle</a></li>
                    <li><a href="#">Avec troubles autistiques</a></li>
                </ul>
            </div>
        </div>
    </article>

    <article class="tight">
        <h3>Centre de jour pour adulte :</h3>
        <div class="link-grid">

            <ul>
                <li><a href="#">Avec déficience visuelle</a></li>
            </ul>
        </div>
    </article>
</section>

<!-- Services -->
<section aria-labelledby="services-heading">
    <h2 id="services-heading">Nos services</h2>
    <div class="card-grid tight">
        <article>
            <img src="images/ludotheque.jpg" alt="Ludothèque Oasis">
            <h3>Ludothèque Oasis</h3>
            <a href="#">Horaires et Infos</a>
            <p>Un espace de jeux sensoriels à tous, internes ou externes.</p>
        </article>

        <article>
            <img src="images/chateau.jpg" alt="Location du Château d'Orangerie">
            <h3>Location du Château d'Orangerie</h3>
            <a href="#">Découvrir</a>
            <p>Un lieu chaleureux et adapté disponible à la location pour vos formations, événements.</p>
        </article>

        <article>
            <img src="images/documentation.jpg" alt="Centre de documentation">
            <h3>Centre de documentation</h3>
            <a href="#">Horaires et Infos</a>
            <p>Un enseignement sur mesure, du déjà acquis au secondaire.</p>
        </article>

        <article>
            <img src="images/restaurant.jpg" alt="Restaurant d'application">
            <h3>Restaurant d'application</h3>
            <a href="#">Horaires et Infos</a>
            <p>Un lieu de formation et d'évolution où les jeunes en insertion vous cuisinent pour vous.</p>
        </article>

        <article>
            <img src="images/conference.jpg" alt="Conférences">
            <h3>Conférences</h3>
            <a href="#">Découvrir</a>
            <p>Événements pour sensibiliser et partager son expertise.</p>
        </article>

        <article>
            <img src="images/formations.jpg" alt="Formations">
            <h3>Formations</h3>
            <a href="#">Découvrir</a>
            <p>Sessions animées par nos professionnels de terrain, adaptées aux besoins actuels.</p>
        </article>
    </div>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-services']], IO_EXTRACT);
    return $page;
};
