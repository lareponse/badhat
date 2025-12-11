<img src="/ui/blob/blob_contact_top_left.svg" alt="decorative blob shape" class="blob" id="blob_contact_top_left" aria-hidden="true">
<img src="/ui/blob/blob_contact_bottom_right.svg" alt="decorative blob shape" class="blob" id="blob_contact_bottom_right" aria-hidden="true">

<header>
    <h1>
        <label>Contact</label>
        <input type="text" name="titre_contact" value="Contact">
    </h1>
</header>

<form method="post" enctype="multipart/form-data">

<section class="tight contact-grid">
    <!-- Formulaire -->
    <fieldset aria-labelledby="form-heading">
        <legend>
            <h2 id="form-heading">Envoyez-nous un message</h2>
        </legend>

        <label for="admin_name">Nom et prénom</label>
        <input type="text" id="admin_name" name="admin_name" value="">

        <label for="admin_email">Adresse e-mail</label>
        <input type="email" id="admin_email" name="admin_email" value="">
        <label for="admin_category">Catégorie principale</label>
        <select id="admin_category" name="admin_category">
            <option value="general">Informations générales</option>
            <option value="services">Nos services</option>
            <option value="ecoles">Écoles</option>
            <option value="don">Faire un don</option>
            <option value="autre">Autre</option>
        </select>

        <label for="admin_subcategory">Sous-catégorie</label>
        <select id="admin_subcategory" name="admin_subcategory">
            <option value="">-- Choisissez d'abord une catégorie --</option>
        </select>

        <label for="admin_message">Message</label>
        <textarea id="admin_message" name="admin_message"></textarea>

        <button class="btn btn-primary" type="submit">Enregistrer</button>
    </fieldset>

    <!-- Infos de contact -->
    <section>
        <h3 id="contact-info-heading">Contactez-nous&nbsp;!</h3>
        <dl>
            <dt>Adresse</dt>
            <dd>
                <address>
                    <input type="text" name="admin_organisation" value="IRSA – Institut Royal pour Sourds et Aveugles">
                    <input type="text" name="admin_adresse" value="Chaussée de Waterloo 1502-1508, 1180 Uccle – Belgique">
                </address>
            </dd>

            <dt>Horaires</dt>
            <dd><input type="text" name="admin_horaires" value="Du lundi au vendredi, 8h30 - 12h / 13h - 16h30"></dd>

            <dt>Email</dt>
            <dd><input type="email" name="admin_info_email" value="info@irsa.be"></dd>

            <dt>Téléphone</dt>
            <dd><input type="text" name="admin_phone" value="+32 (0)2 343 22 27"></dd>

            <dt>Réseaux sociaux</dt>
            <dd>
                <h3 id="social-heading">Suivez nous</h3>
                <ul class="social-links">
                    <li><input type="text" name="admin_facebook" value="#" placeholder="Facebook"></li>
                    <li><input type="text" name="admin_twitter" value="#" placeholder="Twitter"></li>
                    <li><input type="text" name="admin_linkedin" value="#" placeholder="LinkedIn"></li>
                    <li><input type="text" name="admin_instagram" value="#" placeholder="Instagram"></li>
                </ul>
            </dd>
        </dl>
    </section>
</section>
</form>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-contact']], IO_EXTRACT);
    return $page;
};