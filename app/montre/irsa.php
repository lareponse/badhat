<?php

$events = qp('SELECT * FROM `timeline` ORDER BY `event_year` ASC')->fetchAll(PDO::FETCH_ASSOC);
$intro = qp('SELECT * FROM `page` WHERE `slug` = ?', ['irsa-intro'])->fetch(PDO::FETCH_ASSOC);
$oa = qp('SELECT * FROM `page` WHERE `slug` = ?', ['irsa-oa'])->fetch(PDO::FETCH_ASSOC);

?>
<header>
    <h1><span class="tight">L'IRSA : une histoire qui dure !</span></h1>
    <div class="tight"><?= $intro['content']; ?></div>
</header>
<section class="tight" aria-labelledby="timeline-heading">
    <h2 id="timeline-heading" class="visually-hidden">
        Frise chronologique de l'IRSA
    </h2>

    <div class="timeline">
        <?php foreach ($args['events'] as $event) : ?>
            <article class="timeline-item <?= $event['position_hint'] ?>"
                tabindex="0"
                aria-expanded="false"
                data-flip>

                <div class="flip-inner">

                    <div class="flip-front">
                        <h3>
                            <time datetime="<?= $event['event_year'] ?>">
                                <?= $event['event_year'] ?>
                            </time>
                        </h3>

                        <figure>
                            <img src="/ui/pages/irsa/timeline/<?= $event['photo_filename'] ?>" alt="">
                            <figcaption><?= $event['label'] ?></figcaption>
                        </figure>
                    </div>

                    <div class="flip-back">
                        <?= $event['content'] ?? '<p><strong>Sed sed ex metus</strong>. Ut sollicitudin ipsum leo, ultricies eleifend ligula commodo et.</p><p> Cras vel velit sed lectus ultricies lobortis vel eget diam.</p>' ?>
                    </div>

                </div>
            </article>

        <?php endforeach; ?>
    </div>
</section>
<script>
    document.addEventListener('click', e => {
        const card = e.target.closest('[data-flip]');
        if (!card) return;

        toggle(card);
    });

    document.addEventListener('keydown', e => {
        if (!['Enter', ' '].includes(e.key)) return;
        const card = e.target.closest('[data-flip]');
        if (!card) return;

        e.preventDefault();
        toggle(card);
    });

    function toggle(card) {
        const flipped = card.classList.toggle('is-flipped');
        card.setAttribute('aria-expanded', flipped);
    }
</script>



<!-- Organisme d'Administration -->
<section class="oa" aria-labelledby="oa-heading">
    <div class="tight">
        <h2 id="oa-heading"><?= $oa['label']; ?></h2>
        <?= $oa['content']; ?>
        <p class="cta">
            <a href="#" class="btn">Voir la composition complète de l'OA</a>
        </p>
    </div>

</section>

<section id="partners" aria-labelledby="partners-title">
    <h2 id="partners-title" class="visually-hidden">Nos partenaires</h2>
    <ul class="partners-list">
        <li><a href="https://www.aviq.be" target="_blank" rel="noopener"><img src="/ui/partners/aviq.png" alt="AVIQ - Agence pour une Vie de Qualité"></a></li>
        <li><a href="https://c-h-s.be/" target="_blank" rel="noopener"><img src="/ui/partners/chs.be.webp" alt="CHS - Centre Hospitalier Spécialisé"></a></li>
        <li><a href="https://www.ceth.be" target="_blank" rel="noopener"><img src="/ui/partners/c_eth.png" alt="CETH - Centre d'Éducation Thérapeutique"></a></li>
        <li><a href="https://www.kbs-frb.be/fr" target="_blank" rel="noopener"><img src="/ui/partners/fondation_roi_baudouin.png" alt="Fondation Roi Baudouin"></a></li>
        <li><a href="https://www.federation-wallonie-bruxelles.be" target="_blank" rel="noopener"><img src="/ui/partners/federation_wallonie_bruxelles.png" alt="Fédération Wallonie-Bruxelles"></a></li>
        <li><a href="https://www.irsa.be" target="_blank" rel="noopener"><img src="/ui/partners/fondation_irsa.png" alt="Fondation IRSA"></a></li>
        <li><a href="https://fondationisee.be" target="_blank" rel="noopener"><img src="/ui/partners/fondation_isee.svg" alt="Fondation ISEE"></a></li>
        <li><a href="https://www.enseignement.be/index.php?page=28001" target="_blank" rel="noopener"><img src="/ui/partners/centres_pms.jpg" alt="Centres PMS"></a></li>
        <li><a href="https://www.one.be" target="_blank" rel="noopener"><img src="/ui/partners/one.png" alt="ONE - Office de la Naissance et de l’Enfance"></a></li>
        <li><a href="https://shc.health.belgium.be" target="_blank" rel="noopener"><img src="/ui/partners/shc.png" alt="SHC - Service d’Hygiène Communale"></a></li>
        <li><a href="https://ccf.brussels/" target="_blank" rel="noopener"><img src="/ui/partners/francophones_bruxelles.png" alt="Francophones Bruxelles - COCOF"></a></li>
        <li><a href="https://www.reseaudefrance.be" target="_blank" rel="noopener"><img src="/ui/partners/reseau_francophone.png" alt="Réseau Francophone"></a></li>
        <li><a href="https://www.uccle.be" target="_blank" rel="noopener"><img src="/ui/partners/uccle.png" alt="Commune d’Uccle"></a></li>
        <li><a href="https://uclouvain.be" target="_blank" rel="noopener"><img src="/ui/partners/uc_louvain.png" alt="UC Louvain - Université catholique de Louvain"></a></li>
    </ul>
</section>


<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-irsa']], IO_EXTRACT);
    return $page;
};
