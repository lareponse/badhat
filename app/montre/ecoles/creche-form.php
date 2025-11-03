<form action="save_creche.php" method="post" enctype="multipart/form-data">
    <img src="/ui/blob/blob_ecoles_top_right.svg" class="blob" id="blob_ecoles_top_right" aria-hidden="true">

    <header>
        <label>Titre principal :</label>
        <input type="text" name="titre" value="Crèche Le Petit Prince" required>

        <div class="section-intro tight">

            <p><strong>Directrice :</strong>
                <input type="text" name="directrice" value="Claire Dirick">
            </p>

            <p><strong>Email :</strong>
                <input type="email" name="email" value="creche_le_petit_prince@irsa.be">
            </p>

            <p><strong>Téléphone :</strong>
                <input type="text" name="telephone" value="02/882 69 12">
            </p>

        </div>
    </header>

    <section>
        <h2>Présentation</h2>
        <div class="tight">
            <textarea name="presentation" rows="4">
                Notre crèche a été créée en 1989 et est agréée par l'O.N.E. pour accueillir 18 enfants âgés de 3 mois à 3 ans. Un tiers de ces enfants peut être atteint d'une déficience sensorielle.
            </textarea>

            <label>Brochure (PDF) :</label>
            <input type="file" name="brochure_pdf">
        </div>
    </section>

    <section>
        <h2>Objectifs</h2>
        <div class="tight">
            <textarea name="objectifs" rows="4">
                Développement psychomoteur, acquisition de la communication et du langage, développement de l'autonomie en vue de l'entrée en maternelle.
                Pour les enfants déficients sensoriels : élaboration d'un projet éducatif personnalisé.
            </textarea>
        </div>
    </section>

    <section>
        <h2>L'équipe</h2>
        <textarea name="equipe" rows="6" class="tight">
            Deux infirmières sociales
            Plusieurs puéricultrices
            Un médecin
            Du personnel spécialisé en matière de surdité (logopède, audiologue, kinésithérapeute)
            Du personnel formé en langue des signes et en moyens de communication visualisés
        </textarea>
    </section>

    <button type="submit" class="btn-save">Sauvegarder</button>
</form>