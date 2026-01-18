<?php
$events = bad\db\qp('SELECT * FROM `timeline` ORDER BY `event_year` ASC')->fetchAll(PDO::FETCH_ASSOC);
$intro = bad\db\qp('SELECT * FROM `page` WHERE `slug` = ?', ['irsa-intro'])->fetch(PDO::FETCH_ASSOC);
$oa = bad\db\qp('SELECT * FROM `page` WHERE `slug` = ?', ['irsa-oa'])->fetch(PDO::FETCH_ASSOC);
$fondation_pro_irsa = bad\db\qp('SELECT * FROM `page` WHERE `slug` = ?', ['irsa-fondation-pro'])->fetch(PDO::FETCH_ASSOC);

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
        <?php foreach ($events as $event) : ?>
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
                        <?= $event['content'] ?? '<p><strong>Contenu en cours de rédaction.</strong></p><p>Cette étape de l’histoire sera bientôt complétée.</p>' ?>
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
    <div class="tight gouvernance">
        <div>
            <h2 id="oa-heading"><?= $oa['label']; ?></h2>
            <?= $oa['content']; ?>
            <p class="cta"><a href="/oa" class="btn">Voir la composition complète</a></p>
        </div>
        <div>
            <h2 id="oa-heading"><?= $fondation_pro_irsa['label']; ?></h2>
            <?= $fondation_pro_irsa['content']; ?>
            <p class="cta"><a href="/irsa/fondation" class="btn">Voir la composition complète</a></p>
        </div>
    </div>
</section>

<section id="partners" aria-labelledby="partners-title">
    <div class="partners-carousel" aria-roledescription="carousel">
        <ul class="partners-track">
            <li><a href="https://www.aviq.be" target="_blank" rel="noopener"><img src="/ui/partners/aviq.png" alt="AVIQ - Agence pour une Vie de Qualité"></a></li>
            <li><a href="https://c-h-s.be/" target="_blank" rel="noopener"><img src="/ui/partners/chs.be.webp" alt="CHS - Centre Hospitalier Spécialisé"></a></li>
            <li><a href="https://www.ceth.be" target="_blank" rel="noopener"><img src="/ui/partners/c_eth.png" alt="CETH - Centre d'Éducation Thérapeutique"></a></li>
            <li><a href="https://www.kbs-frb.be/fr" target="_blank" rel="noopener"><img src="/ui/partners/fondation_roi_baudouin.png" alt="Fondation Roi Baudouin"></a></li>
            <li><a href="https://www.federation-wallonie-bruxelles.be" target="_blank" rel="noopener"><img src="/ui/partners/federation_wallonie_bruxelles.png" alt="Fédération Wallonie-Bruxelles"></a></li>
            <li><a href="https://fondationisee.be" target="_blank" rel="noopener"><img src="/ui/partners/fondation_isee.svg" alt="Fondation ISEE"></a></li>
            <li><a href="https://www.enseignement.be/index.php?page=28001" target="_blank" rel="noopener"><img src="/ui/partners/centres_pms.jpg" alt="Centres PMS"></a></li>
            <li><a href="https://www.one.be" target="_blank" rel="noopener"><img src="/ui/partners/one.png" alt="ONE - Office de la Naissance et de l’Enfance"></a></li>
            <li><a href="https://shc.health.belgium.be" target="_blank" rel="noopener"><img src="/ui/partners/shc.png" alt="SHC - Service d’Hygiène Communale"></a></li>
            <li><a href="https://ccf.brussels/" target="_blank" rel="noopener"><img src="/ui/partners/francophones_bruxelles.png" alt="Francophones Bruxelles - COCOF"></a></li>
            <li><a href="https://www.reseaudefrance.be" target="_blank" rel="noopener"><img src="/ui/partners/reseau_francophone.png" alt="Réseau Francophone"></a></li>
            <li><a href="https://www.uccle.be" target="_blank" rel="noopener"><img src="/ui/partners/uccle.png" alt="Commune d’Uccle"></a></li>
            <li><a href="https://uclouvain.be" target="_blank" rel="noopener"><img src="/ui/partners/uc_louvain.png" alt="UC Louvain - Université catholique de Louvain"></a></li>
        </ul>
    </div>
</section>

<script>
(function () {
  const track = document.querySelector('.partners-track');
  if (!track) return;

  // duplicate content for infinite loop
  track.innerHTML += track.innerHTML;

  let x = 0;
  let speed = 0.3; // px per frame (adjust)

  function animate() {
    x -= speed;

    // reset when half passed
    if (Math.abs(x) >= track.scrollWidth / 2) {
      x = 0;
    }

    track.style.transform = `translateX(${x}px)`;
    requestAnimationFrame(animate);
  }

  animate();

  /* pause on hover (accessibility + UX) */
  track.addEventListener('mouseenter', () => speed = 0);
  track.addEventListener('mouseleave', () => speed = 0.3);
})();
</script>

<?php
return ['page-irsa'];
