<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/irsa.css">
    <link rel="stylesheet" href="css/button.css">
    <link rel="stylesheet" href="css/card.css">
    <link rel="stylesheet" href="css/blob.css">
    <link rel="stylesheet" href="css/home.css">
</head>

<body>
    <img src="/ui/blob/blob_home_top_right.svg" alt="decorative blob shape" class="blob" id="blob_home_top_right" aria-hidden="true">
    <header>
        <nav>
            <h1><a href="/">IRSA</a></h1>
            <ol>
                <li>Accueil</li>
                <li>L'IRSA</li>
                <li>Nos services</li>
                <li>Ecoles</li>
                <li>Contact</li>
                <li>Faire un don</li>
            </ol>
        </nav>
    </header>

    <main>
        <section id="home-hero" aria-labelledby="hero-heading" lang="fr">
            <div>
                <h1 id="hero-heading">Un projet pour chacun&nbsp;!</h1>

                <p>
                    Depuis près de deux siècles, l'IRSA accompagne enfants, jeunes et adultes
                    atteints de déficiences sensorielles, avec ou sans handicaps associés.
                </p>

                <p>
                    <a href="/commencer" class="btn btn-primary">Commencer</a>
                    <a href="/services" class="btn btn-secondary">Découvrir nos services</a>
                </p>
            </div>

            <figure>
                <img src="/ui/home_hero.jpg"
                    alt="Un jeune garçon portant un implant auditif caresse un lapin avec tendresse">
            </figure>
        </section>


        <section id="home-questionnaire" aria-labelledby="questionnaire-heading" lang="fr">

            <h2 id="questionnaire-heading">Quels services correspondent à vos besoins ?</h2>

            <p>
                Répondez à 3 questions simples pour accéder aux services adaptés à votre
                situation ou celle de votre proche.
            </p>

            <p>
                <a href="/questionnaire" class="btn">Commencer le questionnaire</a>
            </p>
        </section>


        <section aria-labelledby="services-heading" lang="fr">
            <h2 id="services-heading">Des services adaptés à chaque étape de la vie</h2>
            <p>
                Un accompagnement global qui prend en compte les besoins éducatifs,
                thérapeutiques et sociaux de chaque personne.
            </p>

            <div class="card-grid">
                <!-- Éducation spécialisée -->
                <article>
                    <figure>
                        <img src="/ui/home/service_education.jpg" alt="Salle de classe avec des élèves attentifs et un enseignant">
                    </figure>

                    <h3>Éducation spécialisée</h3>
                    <a href="/ecoles">Vers Écoles</a>

                    <p>Un enseignement sur mesure, de la crèche au secondaire.</p>
                </article>

                <!-- Hébergements -->
                <article>
                    <figure>
                        <img src="/ui/home/service_hebergement.jpg" alt="Chambre aménagée dans un centre d'hébergement">
                    </figure>

                    <h3>Hébergements</h3>
                    <a href="/hebergements">Vers centres d'hébergement</a>

                    <p>Des lieux de vie pour accompagner l'autonomie et soulager les familles.</p>
                </article>

                <!-- Centres de jour -->
                <article>
                    <figure>
                        <img src="/ui/home/service_centres_jour.jpg" alt="Atelier artistique avec plusieurs personnes en activité">
                    </figure>
                    <h3>Centres de jour</h3>
                    <a href="/centres-jour">Vers centres de jour</a>
                    <p>Un cadre structuré et bienveillant où chaque personne bénéficie d'activités et de soins répondant à ses besoins.</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="stats-heading" lang="fr">
            <h2 id="stats-heading">L'IRSA en quelques chiffres</h2>

            <dl class="stats-list">
                <div>
                    <dt>600+</dt>
                    <dd>Personnes accompagnées chaque année</dd>
                </div>

                <div>
                    <dt>300+</dt>
                    <dd>Professionnels engagés</dd>
                </div>

                <div>
                    <dt>4</dt>
                    <dd>Établissements scolaires spécialisés</dd>
                </div>

                <div>
                    <dt>10</dt>
                    <dd>Services et asbl annexes</dd>
                </div>

                <div>
                    <dt>2</dt>
                    <dd>Lieux de vie pour enfants et jeunes</dd>
                </div>

                <div>
                    <dt>1835</dt>
                    <dd>Depuis</dd>
                </div>

                <div>
                    <dt>15</dt>
                    <dd>Moyenne d'enfants réintégrés dans l'enseignement traditionnel par an</dd>
                </div>

                <div>
                    <dt>5</dt>
                    <dd>Hectares de parc</dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="don-heading" lang="fr">
            <figure>
                <img src="/ui/home/home_don_acteur.jpg" alt="Main tenant une tablette avec une icône de don en surimpression">
            </figure>

            <div>
                <h2 id="don-heading">Et si vous deveniez acteur de notre mission ?</h2>

                <p>
                    Chaque don permet d'améliorer concrètement le quotidien des enfants, jeunes
                    et adultes accompagnés par l'IRSA.<br>
                    100 % des dons sont investis dans des projets utiles, visibles et concrets.
                </p>

                <p>
                    <a href="/don" class="btn">Faire un don</a>
                </p>
            </div>
        </section>

    </main>

    <footer role="contentinfo" lang="fr">
        <h2 class="visually-hidden">Pied de page du site IRSA</h2>

        <div class="footer-grid">
            <!-- Coordonnées -->
            <section aria-labelledby="coords-heading">
                <h3 id="coords-heading">Coordonnées</h3>
                <address>
                    <p><strong>IRSA – Institut Royal pour Sourds et Aveugles</strong></p>
                    <p>Chaussée de Waterloo 150<br>1180 Uccle – Belgique</p>
                </address>
            </section>

            <!-- Liens utiles -->
            <nav aria-labelledby="useful-links-heading">
                <h3 id="useful-links-heading">Liens utiles</h3>
                <ul>
                    <li><a href="/a-propos">À propos de l'IRSA</a></li>
                    <li><a href="/services">Nos services</a></li>
                    <li><a href="/don">Faire un don</a></li>
                    <li><a href="/rejoindre">Rejoindre nos équipes</a></li>
                    <li><a href="/contact">Contact</a></li>
                    <li><a href="/plan-du-site">Plan du site</a></li>
                </ul>
            </nav>

            <!-- Mentions et accessibilité -->
            <nav aria-labelledby="legal-access-heading">
                <h3 id="legal-access-heading">Mentions et accessibilité</h3>
                <ul>
                    <li><a href="/mentions-legales">Mentions légales</a></li>
                    <li><a href="/confidentialite">Politique de confidentialité</a></li>
                    <li><a href="/accessibilite">Accessibilité du site</a></li>
                    <li><a href="/cookies">Cookies</a></li>
                    <li><a href="/donnees-personnelles">Gestion des données personnelles</a></li>
                </ul>
            </nav>

            <!-- Réseaux sociaux -->
            <section aria-labelledby="social-heading">
                <h3 id="social-heading">Réseaux sociaux</h3>
                <div class="social-links">
                    <a href="https://www.facebook.com/irsa" aria-label="IRSA sur Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/irsa" aria-label="IRSA sur Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.linkedin.com/company/irsa" aria-label="IRSA sur LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://www.youtube.com/@irsa" aria-label="IRSA sur YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </section>
        </div>

        <hr aria-hidden="true">

        <p>
            <small>&copy; <?= date('Y') ?> IRSA – Institut Royal pour Sourds et Aveugles – Tous droits réservés</small><br>
            <small>Site réalisé par <a href="https://zkiss.example">Z.Kiss</a></small>
        </p>
    </footer>


</body>

</html>