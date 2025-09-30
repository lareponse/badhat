<img src="/ui/blob/blob_ecoles_top_right.svg" alt="decorative blob shape" class="blob" id="blob_ecoles_top_right" aria-hidden="true">

<header>
    <h1><span class="tight">Crèche Le Petit Prince</span></h1>
    <div class="section-intro tight">
        <p><strong>Directrice :</strong> Claire Dirick</p>
        <p><strong>Email :</strong> <a href="mailto:creche_le_petit_prince@irsa.be">creche_le_petit_prince@irsa.be</a></p>
        <p><strong>Téléphone :</strong> <a href="tel:+3228826912">02/882 69 12</a></p>
    </div>
</header>

<section>
    <h2>Présentation</h2>
    <div class="tight">
        <p>Notre crèche a été créée en 1989 et est agréée par l'O.N.E. pour accueillir 18 enfants âgés de 3 mois à 3 ans. Un tiers de ces enfants peut être atteint d'une déficience sensorielle.</p>
        <p><a href="/documents/brochure-creche.pdf" class="btn">Télécharger la brochure</a></p>
    </div>
</section>

<section>
    <h2>Objectifs</h2>
    <div class="tight">
        <p>Développement psychomoteur, acquisition de la communication et du langage, développement de l'autonomie en vue de l'entrée en maternelle.</p>
        <p>Pour les enfants déficients sensoriels : élaboration d'un projet éducatif personnalisé.</p>
    </div>
</section>

<section>
    <h2>L'équipe</h2>
    <ul class="tight">
        <li>Deux infirmières sociales</li>
        <li>Plusieurs puéricultrices</li>
        <li>Un médecin</li>
        <li>Du personnel spécialisé en matière de surdité (logopède, audiologue, kinésithérapeute)</li>
        <li>Du personnel formé en langue des signes et en moyens de communication visualisés</li>
    </ul>
</section>

<?php
return function ($this_html) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-school-detail']], IO_EXTRACT);
    return $page;
};
