<img src="/ui/blob/blob_services_3.svg" alt="decorative blob shape" class="blob" aria-hidden="true">

<header>
    <h1><span class="tight">Château de l'Orangeraie</span></h1>
</header>

<section class="tight">
    <div class="gallery-grid">
        <img src="/ui/pages/chateau/dsc_0066.jpg" alt="Vue extérieure du château">
        <img src="/ui/pages/chateau/cimg3160.jpg" alt="Salle de réception">
        <img src="/ui/pages/chateau/cimg3184.jpg" alt="Espace intérieur">
        <img src="/ui/pages/chateau/dsc_0036.jpg" alt="Jardin et terrasse">
    </div>

    <p>Organisez votre événement (mariage, baptême, anniversaire, fête du personnel, etc.) dans le Château de l'Orangeraie, situé dans le magnifique parc de l'IRSA. Le prix de location est intégralement versé à l'IRSA.</p>
</section>

<section>
    <h2>Contact</h2>
    <div class="tight">
        <ul>
            <li><strong>Téléphone :</strong> <a href="tel:+32479886335">0479/88.63.35</a></li>
            <li><strong>Email :</strong> <a href="mailto:locations@irsa.be">locations@irsa.be</a></li>
        </ul>
        <p>Pour toute utilisation de nos salles, nous demandons une participation financière dont une partie couvre les frais divers occasionnés et dont le solde contribue au développement de nos activités pour les personnes handicapées.</p>
    </div>
</section>

<section>
    <h2>Capacité</h2>
    <div class="tight">
        <ul>
            <li>100 personnes assises maximum (salles de 100, 60 et 40 personnes)</li>
            <li>200 personnes en réception</li>
        </ul>
    </div>
</section>

<section>
    <h2>Tarification</h2>
    <div class="tight">
        <dl>
            <dt>Participation financière</dt>
            <dd>1.650 € + 150 € de nettoyage</dd>

            <dt>Acompte</dt>
            <dd>500 € (option définitive au versement)</dd>

            <dt>Solde</dt>
            <dd>1.150 € (semaine précédant l'événement)</dd>

            <dt>Garantie</dt>
            <dd>500 € (semaine précédant l'événement)</dd>
        </dl>
        <p><strong>Annulation :</strong> En cas d'annulation, l'acompte ne sera pas remboursé.</p>
    </div>
</section>

<section>
    <h2>Prestations incluses</h2>
    <div class="tight">
        <ul>
            <li>Utilisation de la salle selon horaire défini</li>
            <li>Tables et chaises pour 100 personnes</li>
            <li>Chauffage, électricité, eau</li>
            <li>Équipements des salles et petite cuisine</li>
            <li>Vestiaire et sanitaires équipés</li>
            <li>Produits de vaisselle</li>
            <li>Personne de permanence</li>
            <li>Nettoyage</li>
            <li>Libre choix de traiteur et de sono</li>
            <li>Pas de droit de bouchon</li>
        </ul>
    </div>
</section>

<section>
    <h2>Informations pratiques</h2>
    <div class="tight">
        <ul>
            <li><strong>Location en semaine :</strong> Possibilité uniquement en soirée pour réunions, cours, etc.</li>
            <li><strong>Location week-end :</strong> Samedi ou dimanche</li>
            <li><strong>Terrasse :</strong> Disponible</li>
            <li><strong>Parking :</strong> 6 voitures maximum derrière le château (responsables, sono, traiteur)</li>
            <li><strong>Parking invités :</strong> Stationnement aisé sur l'avenue Van Bever</li>
        </ul>
    </div>
</section>

<section class="tight">
    <h2>Documents à télécharger</h2>
    <div class="downloads">
        <a href="/documents/chateau/formulaire-demande-visite.pdf" class="btn" download>Formulaire de demande de visite</a>
        <a href="/documents/chateau/convention-location.pdf" class="btn" download>Convention de location</a>
    </div>
</section>

<section class="tight">
    <div class="gallery-grid">
        <img src="/ui/pages/chateau/cimg3187.jpg" alt="Détail salle">
        <img src="/ui/pages/chateau/cimg3154.jpg" alt="Cuisine équipée">
        <img src="/ui/pages/chateau/dsc_0003.jpg" alt="Espace réception">
        <img src="/ui/pages/chateau/dsc_0043.jpg" alt="Vue parc">
    </div>
</section>

<?php
return function ($this_html) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-chateau']], IO_EXTRACT);
    return $page;
};
