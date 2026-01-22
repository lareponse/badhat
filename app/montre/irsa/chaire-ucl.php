<?php
$contact = [
    'name'  => 'Anne Bragard',
    'role'  => 'Responsable scientifique de la Chaire UCLouvain-IRSA',
    'email' => 'anne.bragard@uclouvain.be',
];

$links = [
    [
        'label' => 'Recherche (IPSY) — Chaire UCLouvain-IRSA',
        'url'   => 'https://www.uclouvain.be/fr/instituts-recherche/ipsy/chaire-uclouvain-irsa-recherche',
    ],
    [
        'label' => 'Formations — UCLouvain-IRSA',
        'url'   => 'https://www.uclouvain.be/fr/instituts-recherche/ipsy/uclouvain-irsa-formations',
    ],
    [
        'label' => 'Collection “Sensorialité” (PUL)',
        'url'   => 'https://pul.uclouvain.be/collections/sensorialites/',
    ],
    [
        'label' => 'En savoir plus — Chaire UCLouvain-IRSA',
        'url'   => 'https://www.uclouvain.be/fr/instituts-recherche/ipsy/chaire-ucl-irsa',
    ],
    [
        'label' => "S’inscrire à la newsletter (3 fois/an)",
        'url'   => 'https://ucl.odoo.com/newsletter-irsa',
    ],
];
?>
<header>
    <h1><span class="tight">Chaire UCLouvain-IRSA</span></h1>
    <p class="tight subtitle">Déficiences sensorielles et troubles d’apprentissage</p>
</header>

<section class="tight">
    <h2>Introduction</h2>
    <p>
        Un partenariat entre les facultés de Sciences de l’Education, de Psychologie, logopédie, sexologie et des sciences de la famille,
        l’Institut de recherche en Sciences Psychologiques (IPSY) de l’UCLouvain et l’IRSA (Institut Royal pour Sourds et Aveugles)
        a vu le jour en janvier 2013 par la création d’une Chaire en déficiences sensorielles et troubles d’apprentissage.
    </p>
</section>

<section class="tight">
    <h2>Objectif</h2>
    <p>
        Ce projet poursuit l’objectif de développer un centre de recherches appliquées, un centre de formation ainsi qu’un centre de ressources
        spécialisé pour répondre aux particularités d’apprentissage requises par les personnes atteintes de déficiences sensorielles
        (troubles de la vue et de l’ouïe), de troubles du langage, de troubles instrumentaux, voire de polyhandicaps.
    </p>
</section>

<section id="recherche" class="tight">
    <h2>Recherche</h2>
    <div class="tight">
        <p>
            Plusieurs groupes de travail ont été mis en place au sein de l’IRSA afin de favoriser les échanges entre professionnels et alimenter la réflexion,
            dans le but de dégager des problématiques de terrain sur lesquelles des recherches devraient être menées via, par exemple,
            des mémoires ou travaux de recherches à l’UCLouvain.
        </p>

        <p>Les recherches menées au sein de cette Chaire s’articulent sur trois grands axes :</p>
        <ul>
            <li>l’évaluation de l’efficacité des méthodes d’apprentissage et de prise en charge</li>
            <li>l’évaluation des troubles (validation et création d’outils d’évaluation)</li>
            <li>
                le développement de l’enfant, l’adolescent et l’adulte déficient visuel, déficient auditif et/ou présentant un trouble du langage
                ou des apprentissages
            </li>
        </ul>

        <p class="highlight">
            Pour plus d’informations :
            <a href="<?= htmlspecialchars($links[0]['url'], ENT_QUOTES) ?>"><?= htmlspecialchars($links[0]['label']) ?></a>
        </p>
    </div>
</section>

<section class="tight">
    <h2>Formation initiale et continue</h2>
    <div class="tight">
        <p>Au sein de l’IRSA, des temps de formation sont proposés aux professionnels de terrain sous plusieurs modes :</p>
        <ul>
            <li>des moments d’initiation encadrés par des professionnels de l’institution via le « catalogue de formation interne »</li>
            <li>des conférences organisées en fin de journée par un expert extérieur sur des thématiques initiées par les praticiens</li>
        </ul>

        <p>
            Par ailleurs, tout professionnel désireux d’approfondir ses connaissances a la possibilité de participer à divers modules de formations
            proposées dans le cadre de la formation continue de l’UCLouvain.
            À ce jour, la Chaire organise cinq modules de formation (de 2 à 3,5 jours chacun) :
            déficience visuelle, déficience auditive, polyhandicap, surdicécité et troubles neurovisuels.
        </p>

        <p class="highlight">
            Pour plus d’informations :
            <a href="<?= htmlspecialchars($links[1]['url'], ENT_QUOTES) ?>"><?= htmlspecialchars($links[1]['label']) ?></a>
        </p>
    </div>
</section>

<section class="tight">
    <h2>Ressources</h2>
    <div class="tight">
        <p>
            La collection <strong>Sensorialité</strong> a pour but de rendre accessibles les résultats de la recherche et l’expertise du terrain sur les déficiences sensorielles,
            auditives ou visuelles. Cette collection s’adresse tant aux théoriciens qu’aux praticiens, auxquels elle propose une synthèse des outils et des réflexions
            en la matière, à l’échelle de la Francophonie. À ce jour, 13 ouvrages ont été édités.
        </p>

        <p class="highlight">
            Pour plus d’informations :
            <a href="<?= htmlspecialchars($links[2]['url'], ENT_QUOTES) ?>"><?= htmlspecialchars($links[2]['label']) ?></a>
        </p>
    </div>
</section>

<section class="tight">
    <h2>Contact</h2>
    <dl class="grid">
        <dt>Personne de contact</dt>
        <dd><?= htmlspecialchars($contact['name']) ?> — <?= htmlspecialchars($contact['role']) ?></dd>
        <dt>Email</dt>
        <dd><a href="mailto:<?= htmlspecialchars($contact['email'], ENT_QUOTES) ?>"><?= htmlspecialchars($contact['email']) ?></a></dd>
    </dl>
</section>

<section class="tight">
    <h2>Liens utiles</h2>
    <div class="tight">
        <ul>
            <li><a href="<?= htmlspecialchars($links[3]['url'], ENT_QUOTES) ?>"><?= htmlspecialchars($links[3]['label']) ?></a></li>
            <li><a href="<?= htmlspecialchars($links[4]['url'], ENT_QUOTES) ?>"><?= htmlspecialchars($links[4]['label']) ?></a></li>
        </ul>
    </div>
</section>

<?php return ['page-administration'];
