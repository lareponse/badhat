<?php
$director = [
    'name' => 'Geneviève Gors',
    'role' => 'Directrice',
    'phone' => '02/375 92 69',
    'mobile' => '0478/37 58 22',
    'email' => 'g.gors@irsa.be'
];

$assistant_director = [
    'name' => 'Fina Moral',
    'role' => 'Directrice adjointe',
    'mobile' => '0477/24 99 14',
    'email' => 'f.moral@irsa.be'
];

$fosses = [
    'address_primary' => '4 Place du Chapitre, 5070 Fosses-la-Ville',
    'address_maternal' => '22 Rue de Zolos, 5070 Fosses-la-Ville'
];

$montjoie = [
    'address' => '30-97 Avenue Montjoie, 1180 Uccle'
];
?>
<img src="/ui/blob/blob_ecoles_detail.svg" alt="" class="blob" id="blob_detail" aria-hidden="true">

<header>
    <h1><span class="tight">École fondamentale déficience auditive</span></h1>

    <nav aria-label="Fil d'Ariane" class="breadcrumb tight">
        <ol class="tight">
            <li><a href="/">Accueil</a></li>
            <li><a href="/ecoles">Les écoles</a></li>
            <li aria-current="page">École Fondamentale Type 7</li>
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
            <h3><?= $assistant_director['role'] ?></h3>
            <p><strong><?= $assistant_director['name'] ?></strong></p>
            <p>Tél. <?= $assistant_director['mobile'] ?></p>
            <p><a href="mailto:<?= $assistant_director['email'] ?>"><?= $assistant_director['email'] ?></a></p>
        </article>
    </div>
</section>

<section aria-labelledby="intro-heading">
    <h2 id="intro-heading"><span class="tight">À propos</span></h2>
    <div class="section-intro">
        <div class="tight">
            <p>Enseignement maternel et primaire (dès 2 ans si dérogation) pour enfants sourds, malentendants, enfants à trouble développemental du langage et enfants à trouble du spectre autistique sans déficience intellectuelle.</p>
            <p>Projets pédagogiques adaptés à chacun.</p>
            <p>Travail en partenariat avec le Centre de Services et le CHS pour les suivis pluridisciplinaires et l'encadrement éducatif.</p>
        </div>
    </div>
</section>

<section aria-labelledby="uccle-heading">
    <h2 id="uccle-heading"><span class="tight">Sur le site de l'IRSA à Uccle</span></h2>

    <div class="tight">
        <h3>Surdité</h3>

        <h4>Classes bilingues</h4>
        <p>Projets pédagogiques en langue des signes et langue française à visée CEB selon les acquis des enfants :</p>
        <ul>
            <li>Enseignement en langue des signes, cours de L.S., accès à la culture sourde</li>
            <li>Enseignement en langue française avec appuis diversifiés (AKA, grammaire visuelle)</li>
        </ul>

        <h4>Classes Langue française</h4>
        <p>Projets pédagogiques en langue française à visée CEB selon les acquis des enfants :</p>
        <ul>
            <li>Enseignement en langue française avec appuis diversifiés (signes, AKA, grammaire visuelle)</li>
            <li>Sensibilisation à la langue des signes et à la culture sourde</li>
        </ul>

        <h4>Classes d'enfants sourds avec trouble(s) associé(s) « ESoLaM »</h4>
        <ul>
            <li>Multimodalité communicationnelle</li>
            <li>Travail en partenariat avec les différents services de l'IRSA</li>
            <li>Thérapies</li>
        </ul>

        <h3>Trouble développemental du langage (TDL)</h3>

        <h4>Classes de langage « CoAALaM »</h4>
        <p>Projets pédagogiques en multimodalité communicationnelle à visée CEB selon les acquis des enfants :</p>
        <ul>
            <li>Lecture avec la méthode Borel-Maisonny</li>
            <li>Mathématique : méthode spécifique avec codage</li>
        </ul>

        <h3>Trouble du spectre autistique (TSA) sans déficience intellectuelle</h3>

        <h4>Classes CALIPSO</h4>
        <p><strong>Objectifs :</strong></p>
        <ul>
            <li><strong>C</strong>ommunication</li>
            <li><strong>A</strong>utonomie</li>
            <li><strong>L</strong>angage</li>
            <li><strong>I</strong>nteraction</li>
            <li><strong>P</strong>artage</li>
            <li><strong>SO</strong>cialisation</li>
        </ul>
        <p>Apprentissage scolaire : Méthodologie TEACCH</p>

        <p><strong>Dans toutes les classes :</strong> pédagogies adaptées selon les besoins des enfants pour l'autisme et aphasie/Trouble Développemental du Langage (Dysphasie).</p>
    </div>
</section>

<section aria-labelledby="external-heading">
    <h2 id="external-heading"><span class="tight">En dehors de l'IRSA</span></h2>

    <div class="tight">
        <h3>IRSA à Fosses-la-Ville</h3>
        <p>Classes maternelles et primaires d'enseignement spécialisé en inclusion.</p>
        <p>Implantation fondamentale spécialisée type 7 au sein de l'École Saint-Feuillen de Fosses-la-Ville.</p>
        <ul>
            <li>Programme pédagogique de l'enseignement ordinaire avec méthodologie adaptée</li>
            <li>Préparation à l'intégration dans l'école partenaire selon le projet individuel de l'enfant</li>
            <li>Sensibilisation à la surdité</li>
            <li>Suivi paramédical assuré par l'équipe du CHS</li>
        </ul>
        <p><strong>Adresse primaire :</strong> <?= $fosses['address_primary'] ?></p>
        <p><strong>Implantation maternelle :</strong> <?= $fosses['address_maternal'] ?></p>
        <p><strong>Directrice adjointe :</strong> <?= $assistant_director['name'] ?> – Tél. <?= $assistant_director['mobile'] ?><br>
            <a href="mailto:<?= $assistant_director['email'] ?>"><?= $assistant_director['email'] ?></a>
        </p>

        <h3>IRSA-en-Montjoie</h3>
        <p>Classe maternelle d'enseignement spécialisé en inclusion.</p>
        <p>Implantation fondamentale spécialisée type 7 au sein de l'Institut Montjoie à Uccle.</p>
        <ul>
            <li>Programme pédagogique de l'enseignement ordinaire avec méthodologie adaptée</li>
            <li>Préparation à l'intégration dans l'école partenaire selon le projet individuel de l'enfant</li>
            <li>Sensibilisation à la surdité</li>
            <li>Suivi assuré par les équipes de l'IRSA</li>
            <li>Suivi en intégration partielle dans les classes maternelles et primaires</li>
        </ul>
        <p><strong>Adresse :</strong> <?= $montjoie['address'] ?></p>
        <p><strong>Directrice adjointe :</strong> <?= $assistant_director['name'] ?> – Tél. <?= $assistant_director['mobile'] ?><br>
            <a href="mailto:<?= $assistant_director['email'] ?>"><?= $assistant_director['email'] ?></a>
        </p>
    </div>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-school-detail']], IO_EXTRACT);
    return $page;
};
