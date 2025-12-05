<form method="post" enctype="multipart/form-data">
<!-- Images décoratives -->
<label>Blob top right<br><input type="file" name="blob_ecoles_top_right"></label>
<label>Blob middle left<br><input type="file" name="blob_ecoles_middle_left"></label>

<header>
    <h1><input type="text" name="header_title" value="Les écoles de l'IRSA"></h1>
    <div>
        <textarea name="header_p1">
            L'IRSA propose un parcours scolaire complet pour les enfants et adolescents atteints de déficience auditive, visuelle ou avec troubles associés.
        </textarea>
        <textarea name="header_p2">
            Chaque établissement est adapté à un type de public spécifique, avec un accompagnement pédagogique et thérapeutique individualisé.
        </textarea>
    </div>
</header>

<section class="card-grid">
    <article>
        <label>Image 1<input type="file" name="ecole1_img"></label>
        <input type="text" name="ecole1_title" value="École fondamentale déficience visuelle et troubles associés">
        <input type="text" name="ecole1_link" value="/ecoles/t6-t8">
        <textarea name="ecole1_dt">Pour les enfants atteints de :</textarea>
        <textarea name="ecole1_dd1">Déficience visuelle (T6)</textarea>
        <textarea name="ecole1_dd2">Troubles associés (T8)</textarea>
    </article>

    <article>
        <label>Image 2<input type="file" name="ecole2_img"></label>
        <input type="text" name="ecole2_title" value="École fondamentale déficience auditive">
        <input type="text" name="ecole2_link" value="/ecoles/t7">
        <textarea name="ecole2_dd1">Approche bilingue (LSFB / FR)</textarea>
    </article>

    <article>
        <label>Image 3<input type="file" name="ecole3_img"></label>
        <input type="text" name="ecole3_title" value="École secondaire spécialisée">
        <input type="text" name="ecole3_link" value="/ecoles/t1-t7-t6">
        <textarea name="ecole3_dt">Adolescents avec :</textarea>
        <textarea name="ecole3_dd1">Déficience auditive (T7)</textarea>
        <textarea name="ecole3_dd2">Déficience visuelle (T6)</textarea>
        <textarea name="ecole3_dd3">Troubles d'apprentissage (T1)</textarea>
    </article>
</section>

<section class="card-grid">
    <article>
        <label>Image 4<input type="file" name="ecole4_img"></label>
        <input type="text" name="ecole4_title" value="Crèche inclusive Le Petit Prince">
        <input type="text" name="ecole4_link" value="/ecoles/creche">
        <textarea name="ecole4_p">
            Accueille les tout-petits à partir de quelques mois, avec ou sans déficience sensorielle.
        </textarea>
    </article>

    <article>
        <label>Image 5<input type="file" name="ecole5_img"></label>
        <input type="text" name="ecole5_title" value="Centre PMS spécialisé">
        <input type="text" name="ecole5_link" value="#">
        <textarea name="ecole5_p">
            Écoute, soutien, orientation et collaboration avec l'équipe éducative pour favoriser le bien-être et la réussite.
        </textarea>
    </article>
</section>
<button type="submit">Enregistrer</button>
</form>