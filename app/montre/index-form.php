<link rel="stylesheet" href="/ui/css/page-home.css">

<form id="admin-home" enctype="multipart/form-data">

    <!-- BLOB -->
    <img src="/ui/blob/blob_home_top_right.svg" 
         alt="decorative blob shape" 
         class="blob" 
         id="blob_home_top_right" 
         aria-hidden="true">

    <!-- HERO -->
    <section class="tight" id="home-hero" aria-labelledby="hero-heading" lang="fr">
        <div>
            <h1 id="hero-heading">
                <input type="text" name="hero_heading" value="Un projet pour chacun !">
            </h1>

            <p>
                <textarea name="hero_p1">
                    Depuis près de deux siècles, l'IRSA accompagne enfants, jeunes et adultes atteints de déficiences sensorielles, avec ou sans handicaps associés.
                </textarea>
            </p>

            <p>
                <a class="btn btn-primary">
                    <input type="text" name="hero_btn1_text" value="Commencer">
                </a>

                <input type="text" name="hero_btn1_link" value="/commencer">

                <a class="btn btn-secondary">
                    <input type="text" name="hero_btn2_text" value="Découvrir nos services">
                </a>

                <input type="text" name="hero_btn2_link" value="/services">
            </p>
        </div>

        <figure>
            <img src="/ui/home_hero.jpg"
                 alt="Un jeune garçon portant un implant auditif caresse un lapin avec tendresse"
                 style="max-width:200px; display:block;">

            <label>Changer l'image :  
                <input type="file" name="hero_image">
            </label>

            <label>Alt :
                <input type="text" name="hero_image_alt"
                       value="Un jeune garçon portant un implant auditif caresse un lapin avec tendresse">
            </label>
        </figure>
    </section>

    <!-- VIDEO -->
    <input type="text" name="video_src" 
           value="https://www.youtube.com/embed/-Y0r8Sve0Sc"
           style="width:100%; margin-bottom:10px;">

    <iframe width="100%" height="600px"
            src="https://www.youtube.com/embed/-Y0r8Sve0Sc"
            frameborder="0"
            allowfullscreen></iframe>


    <!-- QUESTIONNAIRE -->
    <section class="tight" id="home-questionnaire" aria-labelledby="questionnaire-heading" lang="fr">

        <h2 id="questionnaire-heading">
            <input type="text" name="questionnaire_heading"
                   value="Quels services correspondent à vos besoins ?">
        </h2>

        <p>
            <textarea name="questionnaire_p">
                Répondez à 3 questions simples pour accéder aux services adaptés à votre situation ou celle de votre proche.
            </textarea>
        </p>

        <p>
            <a class="btn btn-primary">
                <input type="text" name="questionnaire_btn_text"
                       value="Commencer le questionnaire">
            </a>

            <input type="text" name="questionnaire_btn_link" value="/questionnaire">
        </p>
    </section>

    <!-- SERVICES -->
    <section class="tight" aria-labelledby="services-heading" lang="fr">
        <h2 id="services-heading">
            <input type="text" name="services_heading"
                   value="Des services adaptés à chaque étape de la vie">
        </h2>

        <img src="/ui/blob/blob_home_top_right.svg" alt="decorative blob shape"
             class="blob" id="blob_home_top_right" aria-hidden="true">

        <p>
            <textarea name="services_intro">
                Un accompagnement global qui prend en compte les besoins éducatifs, thérapeutiques et sociaux de chaque personne.
            </textarea>
        </p>

        <div class="card-grid">

            <!-- Éducation spécialisée -->
            <article>
                <figure>
                    <img src="/ui/home/service_education.jpg"
                         alt="Salle de classe avec des élèves attentifs et un enseignant"
                         style="max-width:150px;">
                    <label>Changer image :
                        <input type="file" name="service_education_img">
                    </label>
                </figure>

                <h3>
                    <input type="text" name="service_education_title" value="Éducation spécialisée">
                </h3>

                <a>
                    <input type="text" name="service_education_link" value="/ecoles">
                </a>

                <p>
                    <textarea name="service_education_desc">
                        Un enseignement sur mesure, de la crèche au secondaire.
                    </textarea>
                </p>
            </article>

            <!-- Hébergements -->
            <article>
                <figure>
                    <img src="/ui/home/service_hebergement.jpg"
                         alt="Chambre aménagée dans un centre d'hébergement"
                         style="max-width:150px;">
                    <label>Changer image :
                        <input type="file" name="service_hebergement_img">
                    </label>
                </figure>

                <h3>
                    <input type="text" name="service_hebergement_title" value="Hébergements">
                </h3>

                <a>
                    <input type="text" name="service_hebergement_link" value="/hebergements">
                </a>

                <p>
                    <textarea name="service_hebergement_desc">
                        Des lieux de vie pour accompagner l'autonomie et soulager les familles.
                    </textarea>
                </p>
            </article>

            <!-- Centres de jour -->
            <article>
                <figure>
                    <img src="/ui/home/service_centres_jour.jpg"
                         alt="Atelier artistique avec plusieurs personnes en activité"
                         style="max-width:150px;">
                    <label>Changer image :
                        <input type="file" name="service_centres_jour_img">
                    </label>
                </figure>

                <h3>
                    <input type="text" name="service_centres_jour_title" value="Centres de jour">
                </h3>

                <a>
                    <input type="text" name="service_centres_jour_link" value="/centres-jour">
                </a>

                <p>
                    <textarea name="service_centres_jour_desc">
                        Un cadre structuré et bienveillant où chaque personne bénéficie d'activités et de soins répondant à ses besoins.
                    </textarea>
                </p>
            </article>
        </div>
    </section>
    <!-- STATISTIQUES -->
    <section class="tight" aria-labelledby="stats-heading" lang="fr">
        <h2 id="stats-heading">
            <input type="text" name="stats_heading" value="L'IRSA en quelques chiffres">
        </h2>

        <ul class="stats-list">

            <li>
                <strong>
                    <input type="text" name="stat_1_number" value="600+">
                </strong>
                <span>
                    <input type="text" name="stat_1_text" value="Personnes accompagnées chaque année">
                </span>
            </li>

            <li>
                <strong>
                    <input type="text" name="stat_2_number" value="300+">
                </strong>
                <span>
                    <input type="text" name="stat_2_text" value="Professionnels engagés">
                </span>
            </li>

            <li>
                <strong>
                    <input type="text" name="stat_3_number" value="4">
                </strong>
                <span>
                    <input type="text" name="stat_3_text" value="Établissements scolaires spécialisés">
                </span>
            </li>

            <li>
                <strong>
                    <input type="text" name="stat_4_number" value="10">
                </strong>
                <span>
                    <input type="text" name="stat_4_text" value="Services et asbl annexes">
                </span>
            </li>

            <li>
                <strong>
                    <input type="text" name="stat_5_number" value="2">
                </strong>
                <span>
                    <input type="text" name="stat_5_text" value="Lieux de vie pour enfants et jeunes">
                </span>
            </li>

            <li>
                <strong>
                    <input type="text" name="stat_6_number" value="1835">
                </strong>
                <span>
                    <input type="text" name="stat_6_text" value="Depuis">
                </span>
            </li>

            <li>
                <strong>
                    <input type="text" name="stat_7_number" value="15">
                </strong>
                <span>
                    <input type="text" name="stat_7_text" value="Moyenne d'enfants réintégrés dans l'enseignement traditionnel par an">
                </span>
            </li>

            <li>
                <strong>
                    <input type="text" name="stat_8_number" value="5">
                </strong>
                <span>
                    <input type="text" name="stat_8_text" value="Hectares de parc">
                </span>
            </li>

        </ul>
    </section>

    <!-- DON -->
    <section class="tight" aria-labelledby="don-heading" lang="fr">

        <figure>
            <img src="/ui/home/home_don_acteur.jpg" 
                 alt="Main tenant une tablette avec une icône de don en surimpression"
                 style="max-width:200px;">

            <label>Changer l'image :
                <input type="file" name="don_image">
            </label>

            <label>Alt :
                <input type="text" name="don_image_alt"
                       value="Main tenant une tablette avec une icône de don en surimpression">
            </label>
        </figure>

        <div>
            <h2 id="don-heading">
                <input type="text" name="don_heading"
                       value="Et si vous deveniez acteur de notre mission ?">
            </h2>

            <p>
                <textarea name="don_p">
                    Chaque don permet d'améliorer concrètement le quotidien des enfants, jeunes et adultes accompagnés par l'IRSA.
                    100 % des dons sont investis dans des projets utiles, visibles et concrets.
                </textarea>
            </p>

            <p>
                <a class="btn btn-primary">
                    <input type="text" name="don_btn_text"
                           value="Faire un don">
                </a>

                <input type="text" name="don_btn_link" value="/don">
            </p>
        </div>

    </section>

    <p>
        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
    </p>

</form>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-home']], IO_EXTRACT);
    return $page;
};

