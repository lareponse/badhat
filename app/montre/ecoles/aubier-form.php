<form action="save_aubier.php" method="post" enctype="multipart/form-data">
    <img src="/ui/blob/blob_ecoles_top_right.svg" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
    <img src="/ui/blob/blob_ecoles_middle_left.svg" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

    <header>
        <label>Titre principal :</label>
        <input type="text" name="titre" value="L'Aubier" required>
    </header>

    <section class="tight">
        <div class="triple-infos">

            <article>
                <h3>Handicap concerné</h3>
                <textarea name="handicap" rows="2">Cécité ou déficience visuelle sévère</textarea>
            </article>

            <article>
                <h3>Tranche d'âge</h3>
                <textarea name="age" rows="2">Adultes à partir de 18 ans</textarea>
            </article>

            <article>
                <h3>Type de structure</h3>
                <textarea name="type_structure" rows="2">Centre d'hébergement spécialisé</textarea>
            </article>

        </div>

        <label>Description principale :</label>
        <textarea name="description" rows="5">
            L'Aubier est un centre d'hébergement de l'IRSA dédié aux adultes atteints de cécité ou de déficience visuelle sévère...
        </textarea>
    </section>

    <section class="tight">
        <h3>Galerie d'images</h3>
        <figure>
            <div>
                <label>Image 1 :</label>
                <input type="file" name="image1">
                <input type="text" name="image1_alt" value="Vue extérieure du bâtiment">
            </div>

            <div>
                <label>Image 2 :</label>
                <input type="file" name="image2">
                <input type="text" name="image2_alt" value="Activité créative dans un espace commun">
            </div>

            <div>
                <label>Image 3 :</label>
                <input type="file" name="image3">
                <input type="text" name="image3_alt" value="Groupe de résidents avec l'équipe éducative">
            </div>
        </figure>
    </section>

    <section class="tight">
        <h2>Détails de l'accompagnement</h2>
        <textarea name="accompagnement" rows="8">
            Chambres et espaces communs adaptés aux besoins sensoriels des résidents
            Soutien dans les activités quotidiennes (repas, hygiène, déplacements)
            Programme d'activités éducatives, culturelles et de loisirs
            Interventions de professionnels spécialisés (éducateurs, ergothérapeutes, psychologues...)
            Mise en place de projets de vie personnalisés en lien avec chaque résident
        </textarea>
    </section>

    <section class="tight">
        <h2>Services associés</h2>
        <textarea name="services_associes" rows="4">
            Les résidents de L'Aubier bénéficient également de l'expertise du centre de services IRSA, notamment en matière de soins paramédicaux, de rééducation et d'accompagnement social.
        </textarea>
    </section>

    <section class="tight">
        <h2>Modalités pratiques</h2>
        <textarea name="modalites" rows="4">
            Accueil en hébergement permanent ou temporaire
            Admission sur orientation AVIQ
            Visite sur rendez-vous.
        </textarea>
    </section>

    <section class="tight">
        <h2>Contact</h2>
        <div class="triple-infos">
            <article>
                <h3>Téléphone</h3>
                <input type="text" name="telephone" value="02 *** *** ***">
            </article>

            <article>
                <h3>Email</h3>
                <input type="email" name="email" value="aubier@irsa.be">
            </article>

            <article>
                <h3>Lien contact externe</h3>
                <input type="text" name="contact_lien" value="/contact">
            </article>
        </div>
    </section>

    <section>
        <label>Brochure (PDF) :</label>
        <input type="file" name="brochure_pdf">
    </section>

    <button type="submit" class="btn-save">Sauvegarder</button>
</form>