<header>
    <h1><span class="tight">Rejoindre nos équipes</span></h1>
    <div class="tight">
        <p>Vous souhaitez contribuer à notre mission d'accompagnement des personnes sourdes et aveugles ? Découvrez les différentes façons de nous rejoindre.</p>
    </div>
</header>
<section class="tight">

    <div class="card-grid">
        <article>
            <h2>Emplois</h2>
            <p>Nos offres d'emploi sont disponibles via :</p>
            <ul>
                <li><a href="https://www.guidesocial.be" target="_blank" rel="noopener">Guide Social</a></li>
                <li><a href="https://www.enseignons.be" target="_blank" rel="noopener">enseignons.be</a></li>
            </ul>
        </article>

        <article>
            <h2>Stages</h2>
            <p>Pour effectuer un stage dans le cadre de vos études :</p>
            <ol>
                <li>Complétez le <a href="/documents/formulaire_demande_stage.pdf" download>formulaire de demande de stage</a></li>
                <li>Envoyez-le par mail à <a href="mailto:reception.cds@irsa.be">reception.cds@irsa.be</a></li>
            </ol>
        </article>

        <article>
            <h2>Bénévolat</h2>
            <p>Pour nous rejoindre en tant que bénévole, envoyez votre demande par mail à </p>
            <a href="mailto:info@irsa.be">info@irsa.be</a>
        </article>
    </div>
</section>


<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-ecole']], IO_EXTRACT);
    return $page;
};
