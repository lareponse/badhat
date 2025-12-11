<form action="save_chateau.php" method="post" enctype="multipart/form-data">
    <img src="/ui/blob/blob_services_3.svg" alt="decorative blob shape" class="blob" aria-hidden="true">

    <header>
        <label>Titre principal :</label>
        <input type="text" name="titre_page" value="Château de l'Orangeraie" required>
    </header>

    <section class="tight">
        <h3>Images galerie haut</h3>
        <div class="gallery-grid">
            <div>
                <label>Image 1 :</label>
                <input type="file" name="galerie1">
                <input type="text" name="galerie1_alt" value="Vue extérieure du château">
            </div>
            <div>
                <label>Image 2 :</label>
                <input type="file" name="galerie2">
                <input type="text" name="galerie2_alt" value="Salle de réception">
            </div>
            <div>
                <label>Image 3 :</label>
                <input type="file" name="galerie3">
                <input type="text" name="galerie3_alt" value="Espace intérieur">
            </div>
            <div>
                <label>Image 4 :</label>
                <input type="file" name="galerie4">
                <input type="text" name="galerie4_alt" value="Jardin et terrasse">
            </div>
        </div>

        <label>Description principale :</label>
        <textarea name="description" rows="4">Organisez votre événement (mariage, baptême, anniversaire, fête du personnel, etc.) dans le Château de l'Orangeraie, situé dans le magnifique parc de l'IRSA. Le prix de location est intégralement versé à l'IRSA.</textarea>
    </section>

    <section class="card-grid tight">
        <article>
            <h2>Contact</h2>

            <label>Téléphone :</label>
            <input type="text" name="tel" value="0479/88.63.35">

            <label>Email :</label>
            <input type="email" name="email" value="locations@irsa.be">

            <label>Texte sous contact :</label>
            <textarea name="contact_texte" rows="4">Pour toute utilisation de nos salles, nous demandons une participation financière dont une partie couvre les frais divers occasionnés et dont le solde contribue au développement de nos activités pour les personnes handicapées.</textarea>
        </article>

        <article>
            <h2>Capacité</h2>
            <textarea name="capacite" rows="4">
                100 personnes assises maximum (salles de 100, 60 et 40 personnes)
                200 personnes en réception
            </textarea>
        </article>

        <article>
            <h2>Tarification</h2>
            <label>Participation financière :</label>
            <input type="text" name="participation" value="1.650 € + 150 € de nettoyage">

            <label>Acompte :</label>
            <input type="text" name="acompte" value="500 € (option définitive au versement)">

            <label>Solde :</label>
            <input type="text" name="solde" value="1.150 € (semaine précédant l'événement)">

            <label>Garantie :</label>
            <input type="text" name="garantie" value="500 € (semaine précédant l'événement)">

            <label>Texte annulation :</label>
            <textarea name="annulation" rows="3">
                En cas d'annulation, l'acompte ne sera pas remboursé.
            </textarea>
        </article>

        <article>
            <h2>Prestations incluses</h2>
            <textarea name="prestations" rows="8">
                Utilisation de la salle selon horaire défini
                Tables et chaises pour 100 personnes
                Chauffage, électricité, eau
                Équipements des salles et petite cuisine
                Vestiaire et sanitaires équipés
                Produits de vaisselle
                Personne de permanence
                Nettoyage
                Libre choix de traiteur et de sono
                Pas de droit de bouchon
            </textarea>
        </article>

        <article>
            <h2>Informations pratiques</h2>
            <textarea name="infos_pratiques" rows="8">
                Location en semaine : Possibilité uniquement en soirée pour réunions, cours, etc.
                Location week-end : Samedi ou dimanche
                Terrasse : Disponible
                Parking : 6 voitures maximum derrière le château (responsables, sono, traiteur)
                Parking invités : Stationnement aisé sur l'avenue Van Bever
            </textarea>
        </article>

        <article>
            <h2>Documents à télécharger</h2>
            <label>Formulaire de demande de visite (PDF) :</label>
            <input type="file" name="doc_formulaire">

            <label>Convention de location (PDF) :</label>
            <input type="file" name="doc_convention">
        </article>
    </section>

    <section class="tight">
        <h3>Images galerie bas</h3>
        <div class="gallery-grid">
            <div>
                <label>Image 5 :</label>
                <input type="file" name="galerie5">
                <input type="text" name="galerie5_alt" value="Détail salle">
            </div>
            <div>
                <label>Image 6 :</label>
                <input type="file" name="galerie6">
                <input type="text" name="galerie6_alt" value="Cuisine équipée">
            </div>
            <div>
                <label>Image 7 :</label>
                <input type="file" name="galerie7">
                <input type="text" name="galerie7_alt" value="Espace réception">
            </div>
            <div>
                <label>Image 8 :</label>
                <input type="file" name="galerie8">
                <input type="text" name="galerie8_alt" value="Vue parc">
            </div>
        </div>
    </section>

    <button type="submit" class="btn-save">Sauvegarder</button>
</form>
