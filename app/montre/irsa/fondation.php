<?php
$president = [
    'name' => 'Vanessa ISSI',
    'role' => 'Présidente'
];

$board_members = [
    ['name' => 'Christiane BERGHMANS - WAUCQUEZ', 'role' => 'Présidente'],
    ['name' => 'Dominique COLLINET', 'role' => 'Président honoraire'],
    ['name' => 'Reginald BEYAERT'],
    ['name' => 'Jean-François van BOXMEER'],
    ['name' => 'Marcel CROCHET'],
    ['name' => 'Sœur Marie-Christiane GOSLAIN'],
    ['name' => 'Patrice le HODEY'],
    ['name' => 'Geoffroy de SCHREVEL'],
    ['name' => 'Xavier SINECHAL'],
    ['name' => 'Jean-Marie SOLVAY'],
    ['name' => 'Brigitte ULLENS de SCHOOTEN'],
    ['name' => 'Jos VANEYGEN'],
    ['name' => 'Magdeleine WILLAME - BOONEN']
];

$contact = [
    'email' => 'fondation@irsa.be',
    'phone' => '02/373 52 11',
    'iban' => 'BE94 7795-9133-9114'
];
?>
<header>
    <h1><span class="tight">La Fondation PRO-IRSA</span></h1>
    <p class="tight subtitle">Sous le Haut Patronage de Son Altesse Royale la Princesse Astrid</p>
</header>

<section class="tight">
    <h2>Soutenir l'IRSA c'est donner un coup de pouce à la vie</h2>
    <p>L'IRSA bénéficie depuis 2006 de l'aide de la Fondation PRO-IRSA, fondation d'utilité publique dont la mission est de soutenir les projets éducatifs et d'infrastructure en faveur des personnes déficientes sensorielles.</p>
    <p>La Fondation œuvre en priorité pour l'aménagement des infrastructures destinées à l'hébergement et à la formation des bénéficiaires, ainsi que pour l'installation d'équipements spécifiques et adaptés à l'évolution des handicaps.</p>
</section>

<section class="tight">
    <h2>Réalisations grâce aux donateurs</h2>
    <div class="tight">
        <p>Grâce à la générosité des donateurs et mécènes, l'IRSA a pu concrétiser plusieurs projets majeurs :</p>
        <ul>
            <li>Installation d'un ascenseur dans les écoles</li>
            <li>Cabine d'audimétrie au Centre de Services</li>
            <li>Construction des nouvelles infrastructures sportives adaptées</li>
            <li>Cuisine professionnelle de formation pour les élèves de la section hôtellerie</li>
            <li>Achat de bus et minibus avec élévateur</li>
            <li>Renouvellement d'équipements spécialisés (sanitaires adaptés, barrettes braille, station debout…)</li>
        </ul>
    </div>
</section>

<section class="tight">
    <h2>Votre soutien compte</h2>
    <div class="tight">
        <p>Pour permettre à l'IRSA d'assurer la qualité des services offerts, de poursuivre l'adaptation de ses infrastructures en fonction du degré des handicaps rencontrés et des nouvelles technologies, nous comptons sur <strong>VOUS</strong>.</p>
        <p class="highlight">UN IMMENSE MERCI À TOUS !</p>
    </div>
</section>

<section class="tight ">
    <h2>Contact récolte de fonds</h2>
    <dl class="grid">
        <dt>Email</dt>
        <dd><a href="mailto:<?= $contact['email'] ?>"><?= $contact['email'] ?></a></dd>
        <dt>Téléphone</dt>
        <dd><a href="tel:+3223735211"><?= $contact['phone'] ?></a></dd>
        <dt>Compte</dt>
        <dd><?= $contact['iban'] ?></dd>
    </dl>
</section>


<section aria-labelledby="oa" class="tight">
    <h2 id="oa">Conseil d'Administration :</h2>
    <div>
        <article>
            <figure>
                <img src="/ui/pages/oa/ISSI Vanessa Mobile.webp" alt="Présidente">
                <figcaption class="admin-caption">
                    <strong>Présidente</strong><br />
                    <?= $president['name'] ?>
                </figcaption>
            </figure>
        </article>
        <div>
            <h3 id="membres">Membres du Conseil</h3>

            <ul class="admin-members"><?php foreach ($board_members as $member): ?>
            <li>
                <?= $member['name'] ?>
                <?php if (!empty($member['role'])): ?>
                <span class="member-role">(<?= $member['role'] ?>)</span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<?php return ['page-administration'];