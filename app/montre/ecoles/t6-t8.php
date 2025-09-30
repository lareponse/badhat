<?php
$director = [
    'name' => 'Caroline Tyteca',
    'role' => 'Directrice',
    'phone' => '02/375 47 85',
    'mobile' => '0476/90 34 30',
    'email' => 'c.tyteca@irsa.be'
];

$coordinator = [
    'name' => 'Charlotte Verhamme',
    'role' => 'Coordination pôle siège T8',
    'mobile' => '0476/61 32 45',
    'email' => 'c.verhamme@irsa.be'
];

$t6_projects = [
    [
        'name' => 'Projet A',
        'target' => 'Pour les enfants susceptibles de suivre une scolarité comme dans l\'enseignement ordinaire',
        'desc' => 'Programme identique à celui de l\'enseignement ordinaire (possibilité de l\'obtention du CEB). Relations de partenariat avec des écoles d\'enseignement ordinaire.'
    ],
    [
        'name' => 'Projet B',
        'target' => 'Pour les enfants avec problèmes associés',
        'desc' => 'Handicap moteur, handicap mental léger, retard de langage, troubles instrumentaux : classes à rythme lent, apprentissage de l\'autonomie et programme scolaire adapté.'
    ],
    [
        'name' => 'Projet C',
        'target' => 'Pour les enfants porteurs de déficiences multiples',
        'desc' => 'Déficiences visuelle, neurologique, motrice, mentale, syndrome, problème médical et/ou déficience auditive (surdi-cécité). Programme de pédagogie conductive avec 4 objectifs principaux : autonomie, communication multimodale, découverte multi-sensorielle par le corps vécu, apprentissage de base.'
    ]
];

$t8_workshops = [
    'Atelier mathématique',
    'Atelier dyspraxie',
    'Atelier CSC phono',
    'Atelier son',
    'Atelier éducation'
];
?>
<img src="/ui/blob/blob_ecoles_detail.svg" alt="" class="blob" id="blob_detail" aria-hidden="true">

<header>
    <h1><span class="tight">École fondamentale déficience visuelle et troubles associés</span></h1>
    <nav aria-label="Fil d'Ariane" class="breadcrumb tight">
        <ol class="tight">
            <li><a href="/">Accueil</a></li>
            <li><a href="/ecoles">Les écoles</a></li>
            <li aria-current="page">École Fondamentale Type 6 et Type 8</li>
        </ol>
    </nav>
</header>

<section aria-labelledby="contact-heading">
    <h2 id="contact-heading"><span class="tight">Contacts</span></h2>
    <div class="contact-grid tight">
        <article>
            <h3><?= $director['role'] ?></h3>
            <p><strong><?= $director['name'] ?></strong></p>
            <p>Tél. <?= $director['phone'] ?> – <?= $director['mobile'] ?></p>
            <p><a href="mailto:<?= $director['email'] ?>"><?= $director['email'] ?></a></p>
        </article>
        <article>
            <h3><?= $coordinator['role'] ?></h3>
            <p><strong><?= $coordinator['name'] ?></strong></p>
            <p>Tél. <?= $coordinator['mobile'] ?></p>
            <p><a href="mailto:<?= $coordinator['email'] ?>"><?= $coordinator['email'] ?></a></p>
        </article>
    </div>
</section>

<section aria-labelledby="t6-heading">
    <h2 id="t6-heading"><span class="tight">Type 6 : Déficience visuelle</span></h2>

    <div class="section-intro">
        <div class="tight">
            <p>Enseignement maternel et primaire (dès 2 ans et demi) pour enfants aveugles et malvoyants.</p>
        </div>
    </div>

    <div class="tight">
        <h3>Moyens adaptés</h3>
        <ul>
            <li>Choix des agrandissements, supports, couleurs, éclairage</li>
            <li>Techniques braille, TV loupe, thermoforme</li>
            <li>PC, PC braille</li>
            <li>Découverte des matières grâce au braille et représentations graphiques en relief</li>
            <li>Suivi thérapeutique et services spécialisés en basse vision</li>
            <li>Orientation et mobilité organisés avec le Centre de Services et le CHS</li>
        </ul>

        <h3>Projets proposés</h3>
        <?php foreach ($t6_projects as $project): ?>
            <article class="project-card">
                <h4><?= $project['name'] ?></h4>
                <p class="project-target"><strong><?= $project['target'] ?></strong></p>
                <p><?= $project['desc'] ?></p>
            </article>
        <?php endforeach; ?>
    </div>

</section>

<section aria-labelledby="t8-heading">
    <h2 id="t8-heading"><span class="tight">Type 8 : Troubles instrumentaux</span></h2>

    <div class="section-intro">
        <div class="tight">

            <p>Enseignement primaire (dès 6 ans) pour enfants présentant des troubles instrumentaux (dyslexie, dyscalculie, dyspraxie), troubles de l'attention, troubles mnésiques, troubles du langage (dysphasie).</p>
            <p><strong>Objectif :</strong> Le bien-être, la confiance et l'estime de l'enfant sont nos priorités.</p>
        </div>
    </div>

    <div class="tight">


        <h3>Organisation</h3>
        <ul>
            <li><strong>5 classes :</strong> 2 classes T8 + 3 classes de langage</li>
            <li>Travail en plateaux pour mathématiques et français (niveaux homogènes)</li>
            <li>Classes à effectifs réduits pour aide individualisée</li>
            <li>4 niveaux de maturité (non 6 années scolaires)</li>
            <li>Prises en charge logopédiques</li>
        </ul>

        <h3>Ateliers collaboratifs</h3>
        <p>Travail en ateliers en collaboration avec les logopèdes :</p>
        <ul>
            <?php foreach ($t8_workshops as $workshop): ?>
                <li><?= $workshop ?></li>
            <?php endforeach; ?>
        </ul>

        <h3>Public concerné</h3>
        <p>Ces troubles engendrent des difficultés d'apprentissage dans le domaine du développement du langage, de la parole, de l'écriture ou du calcul, dont la gravité est telle qu'une intervention particulière dans le cadre de l'enseignement ordinaire ne peut suffire.</p>
        <p>On peut y entrer à tout moment d'une scolarité primaire justifiant une telle orientation.</p>
    </div>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-school-detail']], IO_EXTRACT);
    return $page;
};
