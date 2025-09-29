<header class="page-header">
    <h1><span class="tight">Faire un don à l’IRSA</span></h1>

    <p class="tight">
        Soutenir l’IRSA, c’est permettre à des enfants, des jeunes et des adultes en situation de handicap sensoriel de grandir,
        d’apprendre, de s’épanouir et de vivre pleinement. Grâce à votre aide, nous contribuons à financer des accompagnements
        spécialisés, du matériel adapté, des projets innovants et une société plus inclusive.
    </p>
</header>

<!-- Dons ponctuels -->
<section class="tight" aria-labelledby="ponctuels-heading">
    <h2 id="ponctuels-heading">Dons ponctuels</h2>
    <p>
        Pour un don ponctuel à partir de 40 €, vous bénéficiez d’une déduction fiscale de 45 %.
        Concrètement, un don de 50 € ne vous coûte réellement que 27,5 €.
    </p>

    <div class="card-grid">
        <article>
            <img src="/img/don_50.jpg" alt="Enfant avec un appareil auditif">
            <h3>En donnant 50 €</h3>
            <p>Vous financez une séance d’orthophonie pour un enfant sourd.</p>
            <a href="#" class="btn btn-secondary">Faites un don</a>
        </article>

        <article>
            <img src="/img/don_75.jpg" alt="Bébé accompagné par un adulte">
            <h3>En donnant 75 €</h3>
            <p>Vous permettez un accompagnement précoce pour un enfant avec TSA.</p>
            <a href="#" class="btn btn-secondary">Faites un don</a>
        </article>

        <article>
            <img src="/img/don_100.jpg" alt="Enfant avec son éducateur">
            <h3>En donnant 100 €</h3>
            <p>Vous soutenez des activités éducatives et pédagogiques pour un jeune déficient visuel.</p>
            <a href="#" class="btn btn-secondary">Faites un don</a>
        </article>
    </div>

    <p class="don-choice"><a href="#">Choisissez le montant de votre don</a></p>
</section>

<!-- Dons mensuels -->
<section class="tight" aria-labelledby="mensuels-heading">
    <h2 id="mensuels-heading">Dons mensuels</h2>
    <div class="card-grid">
        <article>
            <img src="/img/don_7.jpg" alt="Famille marchant ensemble">
            <h3>En donnant 7 €/mois</h3>
            <p>Vous financez l’accompagnement d’un parent pour soutenir son enfant en difficulté.</p>
            <a href="#" class="btn btn-secondary">Faites un don</a>
        </article>

        <article>
            <img src="/img/don_15.jpg" alt="Adolescente avec casque audio">
            <h3>En donnant 15 €/mois</h3>
            <p>Vous permettez un suivi personnalisé pour un jeune présentant des troubles du spectre de l’autisme.</p>
            <a href="#" class="btn btn-secondary">Faites un don</a>
        </article>

        <article>
            <img src="/img/don_30.jpg" alt="Enfant dans une salle de classe spécialisée">
            <h3>En donnant 30 €/mois</h3>
            <p>Vous contribuez à du matériel adapté pour des enfants en situation de handicap visuel ou auditif.</p>
            <a href="#" class="btn btn-secondary">Faites un don</a>
        </article>
    </div>

    <p class="don-choice"><a href="#">Choisissez le montant de votre don mensuel</a></p>
</section>

<!-- Autres moyens -->
<section class="tight" aria-labelledby="autres-heading">
    <h2 id="autres-heading">Autres moyens de soutenir l’IRSA</h2>
    <div class="card-grid">
        <article>
            <h3>Donner par virement</h3>
            <p>
                IBAN : BE74 9799 3933 4811<br>
                BIC : CREGEBBE
            </p>
            <a href="#">Plus d’infos sur l’attestation fiscale</a>
        </article>

        <article>
            <h3>Faire un legs</h3>
            <p>Incluez l’IRSA dans votre testament pour soutenir durablement nos missions.</p>
            <a href="#">En savoir plus sur les legs</a>
        </article>

        <article>
            <h3>Don en nature ou mécénat de matériel</h3>
            <p>Ordinateurs, mobilier, etc. Merci de nous contacter pour convenir d’un don.</p>
            <a href="#">Contactez-nous</a>
        </article>

        <article>
            <h3>Louer le Château de l’Orangerie</h3>
            <p>Organisez vos événements et soutenez nos projets éducatifs.</p>
            <a href="#">Réservez le château</a>
        </article>
    </div>
</section>

<!-- FAQ -->
<section class="tight" aria-labelledby="faq-heading">
    <h2 id="faq-heading">FAQ</h2>
    <details>
        <summary>Mon don est-il déductible fiscalement ?</summary>
        <p>Oui, à partir de 40 € par an, vous bénéficiez d’une déduction fiscale de 45 %.</p>
    </details>

    <details>
        <summary>Comment vais-je recevoir mon attestation fiscale ?</summary>
        <p>Nous vous l’envoyons automatiquement chaque année au printemps.</p>
    </details>

    <details>
        <summary>Puis-je modifier ou arrêter un don mensuel ?</summary>
        <p>Oui, à tout moment sur simple demande par email ou téléphone.</p>
    </details>

    <details>
        <summary>Où va mon argent ?</summary>
        <p>Votre don finance directement nos services éducatifs, pédagogiques et matériels spécialisés.</p>
    </details>

    <details>
        <summary>Que fait l’IRSA de mes données personnelles ?</summary>
        <p>Nous respectons strictement le RGPD. Vos données sont sécurisées et jamais revendues.</p>
    </details>

    <details>
        <summary>Puis-je faire confiance à l’IRSA pour l’utilisation de mon don ?</summary>
        <p>Oui, nos comptes sont audités chaque année et rendus publics.</p>
    </details>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $buffer] = ob_ret_get('app/montre/layout.php');
    $page = str_replace('</main>', $this_html . '</main>', $buffer);
    $page = str_replace('</head>', '<link rel="stylesheet" href="css/page-don.css"></head>', $page);

    return $page;
};
