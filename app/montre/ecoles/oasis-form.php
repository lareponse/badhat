<form action="save_oasis.php" method="post" enctype="multipart/form-data">
    <img src="/ui/blob/blob_ecoles_top_right.svg" class="blob" id="blob_ecoles_top_right" aria-hidden="true">
    <img src="/ui/blob/blob_ecoles_middle_left.svg" class="blob" id="blob_ecoles_middle_left" aria-hidden="true">

    <header>
        <label>Titre principal :</label>
        <input type="text" name="titre" value="Ludothèque Oasis" required>
    </header>

    <section class="tight">
        <div class="triple-infos">
            <article>
                <h3>Public accueilli</h3>
                <textarea name="public_accueilli" rows="2">
                    Enfants avec ou sans déficience sensorielle, familles, structures éducatives
                </textarea>
            </article>

            <article>
                <h3>Tranche d'âge</h3>
                <input type="text" name="tranche_age" value="De 0 à 99 ans">
            </article>

            <article>
                <h3>Type de structure</h3>
                <textarea name="type_structure" rows="2">
                    Ludothèque sensorielle, éducative et inclusive
                </textarea>
            </article>
        </div>

        <label>Description principale :</label>
        <textarea name="description" rows="4">
            Un espace de jeu, d'expérimentation et de partage, ouvert aux enfants avec ou sans déficience sensorielle, et à leurs familles.
        </textarea>
    </section>

    <section class="tight">
        <h3>Galerie d'images</h3>
        <figure class="banner-gallery">
            <img src="/ui/banners/accueil_1.webp" alt="">
            <img src="/ui/banners/accueil_2.webp" alt="Enfant accompagné dans une activité de communication : gestes, supports visuels ou langue des signes">
            <img src="/ui/banners/accueil_3.webp" alt="">
            <img src="/ui/banners/accueil_4.webp" alt="">
        </figure>
    </section>

    <section class="tight">
        <h2>Ce que vous trouverez à Oasis</h2>
        <textarea name="offre" rows="5">
            Jeux sensoriels, moteurs, symboliques et éducatifs
            Matériel adapté : braille, gros caractères, supports tactiles
            Ateliers thématiques encadrés
            Prêt de jeux et de matériel spécialisé
        </textarea>
    </section>

    <section class="tight">
        <h2>Un lieu ouvert à tous</h2>
        <textarea name="lieu" rows="3">
            La ludothèque est une ressource pour les familles, les écoles et les institutions extérieures. Chacun peut y trouver des idées, des outils et un espace bienveillant pour jouer et apprendre.
        </textarea>
    </section>

    <section class="tight">
        <h2>Infos pratiques</h2>
        <h3>Horaires d'ouverture</h3>
        <input type="text" name="horaires" value="Du lundi au vendredi : de 10h à 16h30">

        <h3>Tarifs</h3>
        <textarea name="tarifs" rows="3">
            Abonnement annuel famille : 80 €
            Prêt de jeu : 1 € / semaine
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
                <input type="email" name="email" value="ludo@irsa.be">
            </article>

            <article>
                <h3>Adresse</h3>
                <textarea name="adresse" rows="2">
                    Chaussée de Waterloo 1504, Uccle
                </textarea>
            </article>
        </div>
    </section>

    <section>
        <label>Brochure (PDF) :</label>
        <input type="file" name="brochure_pdf">
    </section>

    <button type="submit" class="btn-save">Sauvegarder</button>
</form>