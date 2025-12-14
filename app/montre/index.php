<?php
$statistics = qp('SELECT label, value FROM `statistics`')->fetchAll(PDO::FETCH_KEY_PAIR);
?>

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
    <h2 id="questionnaire-heading" class="visually-hidden">
        Questionnaire d’orientation vers les services IRSA
    </h2>

    <form id="questionnaire-form">

        <fieldset class="questionnaire-step active">
            <legend>Quels services correspondent à vos besoins ?</legend>
            <p>Répondez à quelques questions simples pour accéder aux services adaptés.</p>
            <nav>
                <button type="button" class="btn btn-primary" data-action="start">Commencer</button>
            </nav>
        </fieldset>

        <!-- STEP AGE -->
        <fieldset class="questionnaire-step">
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

        <!-- STEP SENSORIAL -->
        <fieldset class="questionnaire-step">
            <legend>Quelle situation de handicap concerne la personne ?</legend>

            <div class="multi-choice">
                <label>
                    <input type="checkbox" name="situation[]" value="surdite">
                    Surdité ou malentendance sévère
                </label>
                <label>
                    <input type="checkbox" name="situation[]" value="cecite">
                    Cécité ou malvoyance sévère
                </label>
                <label>
                    <input type="checkbox" name="situation[]" value="ASD">
                    Trouble du spectre de l’autisme (TSA)
                </label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>

        <!-- STEP POLY -->
        <fieldset class="questionnaire-step">
            <legend>La personne présente-t-elle un polyhandicap ou des troubles associés ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="poly" value="oui" required> Oui</label>
                <label><input type="radio" name="poly" value="non"> Non</label>
                <label><input type="radio" name="poly" value=""> Je ne sais pas</label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>

        <!-- STEP SCHOOL -->
        <fieldset class="questionnaire-step">
            <legend>A-t-elle besoin d'une scolarité ou d'un cadre scolaire ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="school" value="oui" required> Oui</label>
                <label><input type="radio" name="school" value="non"> Non</label>
                <label><input type="radio" name="school" value=""> Pas concerné</label>
            </div>

            <nav>
                <button type="button" class="btn" data-action="prev">Retour</button>
                <button type="button" class="btn btn-primary" data-action="next">Suivant</button>
            </nav>
        </fieldset>

        <!-- STEP HOSTING -->
        <fieldset class="questionnaire-step">
            <legend>A-t-elle besoin d'un hébergement ?</legend>

            <div class="multi-choice">
                <label><input type="radio" name="hosting" value="oui" required> Oui</label>
                <label><input type="radio" name="hosting" value="non"> Non</label>
                <label><input type="radio" name="hosting" value=""> Pas concerné</label>
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

        const ROUTING_RULES = [

            // --- SORTIES IMMÉDIATES ------------------------------------
            {
                when: {
                    age: "moins-3"
                },
                terminal: true,
                redirect: "/ecoles/creche",
            },

            {
                when: {
                    age: "21plus"
                },
                terminal: true,
                redirect: "/services/aubier",
            },

            // --- CHE ---------------------------------------------------

            {
                when: {
                    age: ["3-18", "18-21"],
                    situation: ["surdite"],
                    hosting: "oui",
                    school: "oui",
                },
                redirect: "/services/che-surdite",
            },

            {
                when: {
                    age: ["3-18", "18-21"],
                    situation: ["cecite"],
                    hosting: "oui",
                    school: "oui",
                },
                redirect: "/services/che-cecite",
            },

            // --- CJENS -------------------------------------------------

            {
                when: {
                    situation: ["surdite", "ASD"],
                    poly: "oui",
                    hosting: "non",
                    school: "non",
                },
                redirect: "/services/cjens-surdite",
            },

            {
                when: {
                    situation: ["cecite", "ASD"],
                    poly: "oui",
                    hosting: "non",
                    school: "non",
                },
                redirect: "/services/cjens-cecite",
            },

            // --- CJES --------------------------------------------------

            {
                when: {
                    situation: ["surdite", "ASD"],
                    school: "oui",
                    hosting: "non",

                },
                redirect: "/services/cjes-surdite",
            },

            {
                when: {
                    situation: ["cecite", "ASD"],
                    school: "oui",
                    hosting: "non",
                },
                redirect: "/services/cjes-cecite",
            },

        ];

        function ruleIsCompatible(rule, data) {
            return Object.entries(rule.when).every(([key, expected]) => {
                const actual = data[key];
                console.log(`Checking rule key="${key}": expected=`, expected, " actual=", actual);
                if (actual == null) return true;
                if (Array.isArray(expected) && Array.isArray(actual)) {
                    return actual.some(a => expected.includes(a));
                }
                return Array.isArray(expected) ?
                    expected.includes(actual) :
                    actual === expected;
            });
        }

        function resolveCandidates(data) {
            return ROUTING_RULES.filter(rule =>
                ruleIsCompatible(rule, data)
            );
        }

        function resolveService(data) {
            const candidates = ROUTING_RULES.filter(r =>
                ruleIsCompatible(r, data)
            );

            console.log("Candidates:", candidates);

            // 1. terminal rule wins immediately
            const terminal = candidates.find(r => r.terminal);
            if (terminal) return terminal.redirect;

            // 2. unique remaining candidate
            if (candidates.length === 1) {
                return candidates[0].redirect;
            }

            // 3. still ambiguous
            return null;
        }

        function showStep(index) {
            steps.forEach((step, i) => step.classList.toggle("active", i === index));
            currentStep = index;
        }

        const form = document.getElementById("questionnaire-form");
        const steps = Array.from(form.querySelectorAll('fieldset'));
        let currentStep = 0;

        // Navigation handling
        form.addEventListener("click", (e) => {

            const btn = e.target.closest("[data-action]");

            if (!btn) return;

            const fd = new FormData(form);
            const data = {};
            for (const pair of fd.entries()) {
                console.log(`FormData entries ${pair[0]}: ${pair[1]}`);
                if (pair[0].endsWith("[]")) {
                    const key = pair[0].slice(0, -2);
                    if (!data[key]) data[key] = [];
                    data[key].push(pair[1]);
                } else {
                    data[pair[0]] = pair[1] || null;
                }
            }

            console.log("Form data:", data);
            const action = btn.dataset.action;

            let url;

            if (action === "start") {
                showStep(1);
            } else if (action === "next") {
                const radios = steps[currentStep].querySelectorAll("input");
                const hasAnswer = Array.from(radios).some(r => r.checked);
                if (!hasAnswer) return;
                url = resolveService(data);
                console.log("Resolved URL:", url);
                if (url) {
                    console.log("Navigating to:", url);
                    // window.location.href = url;
                } else {
                    showStep(currentStep + 1);
                }
            } else if (action === "prev") {
                if (currentStep > 0) showStep(currentStep - 1);
            } else if (action === "finish") {
                url = resolveService(data);
                console.log("Finished, navigating to:", url);

                // window.location.href = url || "/services";
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
            <a href="/ecoles">
                <figure>
                    <img src="/ui/home/service_education.jpg" alt="Salle de classe avec des élèves attentifs et un enseignant">
                </figure>

                <h3>Éducation spécialisée</h3>
                <span class="cta">Vers Écoles</span>

                <p>Un enseignement sur mesure, de la crèche au secondaire.</p>
            </a>

        </article>

        <!-- Hébergements -->
        <article>
            <a href="/hebergements">
                <figure>
                    <img src="/ui/home/service_hebergement.jpg" alt="Chambre aménagée dans un centre d'hébergement">
                </figure>

                <h3>Hébergements</h3>
                <span class="cta">Vers centres d'hébergement</span>


                <p>Des lieux de vie pour accompagner l'autonomie et soulager les familles.</p>
            </a>
        </article>

        <!-- Centres de jour -->
        <article>
            <a href="/centres-jour">
                <figure>
                    <img src="/ui/home/service_centres_jour.jpg" alt="Atelier artistique avec plusieurs personnes en activité">
                </figure>
                <h3>Centres de jour</h3>
                <span class="cta">Vers centres de jour</span>
                <p>Un cadre structuré et bienveillant où chaque personne bénéficie d'activités et de soins répondant à ses besoins.</p>
            </a>
        </article>
    </div>
</section>

<section class="tight" aria-labelledby="stats-heading" lang="fr">
    <h2 id="stats-heading">L'IRSA en quelques chiffres</h2>
    <ul class="stats-list">

        <?php foreach ($statistics as $label => $value): ?>
            <li>
                <strong><?= $value ?></strong>
                <span><?= $label ?></span>
            </li>
        <?php endforeach; ?>
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
