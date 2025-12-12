<img src="/ui/blob/blob_home_top_right.svg" alt="decorative blob shape" class="blob" id="blob_home_top_right" aria-hidden="true">

<section class="tight" id="home-hero" aria-labelledby="hero-heading" lang="fr">
    <div>
        <h1 id="hero-heading">Un projet pour chacun&nbsp;!</h1>

        <p>
            Depuis près de deux siècles, l'IRSA accompagne enfants, jeunes et adultes
            atteints de déficiences sensorielles, avec ou sans handicaps associés.
        </p>

        <p>
            <a href="#home-questionnaire" class="btn btn-primary">Commencer</a>
            <a href="/services" class="btn btn-secondary">Découvrir nos services</a>
        </p>
    </div>

    <figure>
        <img src="/ui/pages/home/garcon_lapin.png"
            alt="Un jeune garçon portant un implant auditif caresse un lapin avec tendresse">
    </figure>
</section>

<iframe width="100%" height="600px" src="https://www.youtube.com/embed/-Y0r8Sve0Sc" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>


<section class="tight" id="home-questionnaire" aria-labelledby="questionnaire-heading" lang="fr">

    <form id="questionnaire-form">

        <!-- STEP 0 — AGE -->
        <fieldset class="questionnaire-step active" data-step="0">
            <h2 id="questionnaire-heading">Quels services correspondent à vos besoins ?</h2>
            <p>Répondez à quelques questions simples pour accéder aux services adaptés.</p>

            <legend>Quel est l'âge de la personne concernée ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="age" value="moins-3" required> Moins de 3 ans</label>
                <label><input type="radio" name="age" value="3-18"> De 3 à 18 ans</label>
                <label><input type="radio" name="age" value="18-21"> De 18 à 21 ans</label>
                <label><input type="radio" name="age" value="21plus"> Plus de 21 ans</label>
            </div>

            <nav>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>

        <!-- STEP 1 — SENSORIAL -->
        <fieldset class="questionnaire-step" data-step="1">
            <legend>Quel est le type de déficience sensorielle concernée ?</legend>

            <div class="multi-choice">
                <label>
                    <input type="radio" name="sensory" value="surdite" required>
                    Déficience auditive (surdité)
                </label>
                <label>
                    <input type="radio" name="sensory" value="cecite">
                    Déficience visuelle (cécité ou malvoyance sévère)
                </label>
                <label>
                    <input type="radio" name="sensory" value="autre">
                    Autre situation / je ne sais pas
                </label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>


        <!-- STEP 1 — ASD -->
        <fieldset class="questionnaire-step" data-step="2">
            <legend>La personne est-elle concernée par un TSA (Autisme) ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="ASD" value="oui" required> Oui</label>
                <label><input type="radio" name="ASD" value="non"> Non</label>
                <label><input type="radio" name="ASD" value="inconnu"> Je ne sais pas</label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>


        <!-- STEP 2 — POLY -->
        <fieldset class="questionnaire-step" data-step="3">
            <legend>La personne présente-t-elle un polyhandicap ou des troubles associés ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="poly" value="oui" required> Oui</label>
                <label><input type="radio" name="poly" value="non"> Non</label>
                <label><input type="radio" name="poly" value="inconnu"> Je ne sais pas</label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>


        <!-- STEP 3 — SCHOOL -->
        <fieldset class="questionnaire-step" data-step="4">
            <legend>A-t-elle besoin d'une scolarité ou d'un cadre scolaire ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="school" value="oui" required> Oui</label>
                <label><input type="radio" name="school" value="non"> Non</label>
                <label><input type="radio" name="school" value="na"> Pas concerné</label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>


        <!-- STEP 4 — HOSTING -->
        <fieldset class="questionnaire-step" data-step="5">
            <legend>A-t-elle besoin d'un hébergement ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="hosting" value="oui" required> Oui</label>
                <label><input type="radio" name="hosting" value="non"> Non</label>
                <label><input type="radio" name="hosting" value="na"> Pas concerné</label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="finish">Afficher les résultats</button>
            </nav>
        </fieldset>

    </form>

</section>



<script>
    (function() {

        const SERVICES = {
            che: {
                surdite: "/services/che-surdite",
                cecite: "/services/che-cecite",
            },
            cjes: {
                surdite: "/services/cjes-surdite",
                cecite: "/services/cjes-cecite",
            },
            cjens: {
                surdite: "/services/cjens-surdite",
                cecite: "/services/cjens-cecite",
            },
            creche: "/ecoles/creche",
            aubier: "/services/aubier",
        };

        const form = document.getElementById("questionnaire-form");
        const steps = Array.from(form.querySelectorAll(".questionnaire-step"));
        let currentStep = 0;

        // Show step
        function showStep(index) {
            steps.forEach((step, i) => step.classList.toggle("active", i === index));
            currentStep = index;
        }

        // Evaluate strict IRSA routing
        function resolveService() {
            const fd = new FormData(form);

            const age = fd.get("age");
            const sensory = fd.get("sensory");
            const ASD = fd.get("ASD");
            const poly = fd.get("poly");
            const school = fd.get("school");
            const hosting = fd.get("hosting");

            console.log("Form data:", {
                age, sensory, ASD, poly, school, hosting
            });
            // --- SORTIES IMMÉDIATES ------------------------------------

            if (age === "moins-3") {
                return SERVICES.creche;
            }

            if (age === "21plus") {
                return SERVICES.aubier;
            }

            // Sécurité : si on ne connaît pas la déficience sensorielle
            if (!SERVICES.che[sensory]) {
                return null;
            }

            // --- SERVICES ENFANTS / JEUNES -----------------------------

            // CHE : pas de TSA + hébergement + cadre scolaire
            if (ASD === "non" && hosting === "oui" && school === "oui") {
                return SERVICES.che[sensory];
            }

            // CJENS : TSA + polyhandicap + pas d’hébergement + pas de scolarité
            if (
                ASD === "oui" &&
                poly === "oui" &&
                hosting === "non" &&
                school === "non"
            ) {
                return SERVICES.cjens[sensory];
            }

            // CJES : TSA (autres situations)
            if (ASD === "oui") {
                return SERVICES.cjes[sensory];
            }

            // --- AUCUN PARCOURS STRICT ---------------------------------
            return null;
        }


        // Navigation handling
        form.addEventListener("click", (e) => {
            const btn = e.target.closest("[data-action]");
            let url;

            if (!btn) return;

            const action = btn.dataset.action;

            if (action === "next") {
                const radios = steps[currentStep].querySelectorAll("input");
                const hasAnswer = Array.from(radios).some(r => r.checked);
                if (!hasAnswer) return;
                url = resolveService();
                console.log("Resolved URL:", url);
                if (url) {
                    console.log("Navigating to:", url);
                    window.location.href = url;
                } else {
                    showStep(currentStep + 1);
                }
            } else if (action === "prev") {
                if (currentStep > 0) showStep(currentStep - 1);
            } else if (action === "finish") {
                const url = resolveService();
                window.location.href = url || "/services";
            }

        });

    })();
</script>


<section class="tight" aria-labelledby="services-heading">
    <h2 id="services-heading">Des services adaptés à chaque étape de la vie</h2>
    <img src="/ui/blob/blob_home_top_right.svg" alt="decorative blob shape" class="blob" id="blob_home_top_right" aria-hidden="true">
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

<section class="tight" aria-labelledby="stats-heading" lang="fr">
    <h2 id="stats-heading">L'IRSA en quelques chiffres</h2>

    <ul class="stats-list">
        <li>
            <strong>600+</strong>
            <span>Personnes accompagnées chaque année</span>
        </li>

        <li>
            <strong>300+</strong>
            <span>Professionnels engagés</span>
        </li>

        <li>
            <strong>4</strong>
            <span>Établissements scolaires spécialisés</span>
        </li>

        <li>
            <strong>10</strong>
            <span>Services et asbl annexes</span>
        </li>

        <li>
            <strong>2</strong>
            <span>Lieux de vie pour enfants et jeunes</span>
        </li>

        <li>
            <strong>1835</strong>
            <span>Depuis</span>
        </li>

        <li>
            <strong>15</strong>
            <span>Moyenne d'enfants réintégrés dans l'enseignement traditionnel par an</span>
        </li>

        <li>
            <strong>5</strong>
            <span>Hectares de parc</span>
        </li>
    </ul>
</section>

<section class="tight" aria-labelledby="don-heading" lang="fr">
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
            <a href="/don" class="btn btn-primary">Faire un don</a>
        </p>
    </div>
</section>


<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-home']], IO_EXTRACT);
    return $page;
};
