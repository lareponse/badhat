<img src="/ui/blob/blob_contact_top_left.svg" alt="decorative blob shape" class="blob" id="blob_contact_top_left" aria-hidden="true">
<img src="/ui/blob/blob_contact_bottom_right.svg" alt="decorative blob shape" class="blob" id="blob_contact_bottom_right" aria-hidden="true">
<header>
    <h1><span class="tight">Contact</span></h1>
</header>

<section class="tight contact-grid">
    <!-- Formulaire -->
    <form class="contact-form" action="contact.php" method="post" aria-labelledby="form-heading">
        <legend>
            <h2 id="form-heading">Envoyez-nous un message</h2>
        </legend>

        <label for="name">Nom et prénom</label>
        <input type="text" id="name" name="name" placeholder="Votre nom complet" required>

        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" placeholder="exemple@mail.com" required>
        <label for="category">Catégorie principale</label>
        <select id="category" name="category" required>
            <option value="">-- Sélectionnez une catégorie --</option>
            <option value="general">Informations générales</option>
            <option value="services">Nos services</option>
            <option value="ecoles">Écoles</option>
            <option value="don">Faire un don</option>
            <option value="autre">Autre</option>
        </select>

        <label for="subcategory">Sous-catégorie</label>
        <select id="subcategory" name="subcategory" disabled>
            <option value="">-- Choisissez d'abord une catégorie --</option>
        </select>

        <label for="message">Message</label>
        <textarea id="message" name="message" placeholder="Votre message..." required></textarea>

        <button class="btn btn-primary" type="submit">Envoyer</button>
    </form>

    <!-- Infos de contact -->
    <section>
        <h3 id="contact-info-heading">Contactez-nous&nbsp;!</h2>
            <dl>
                <dt>?</dt>
                <dd>
                    <address>
                        <strong>IRSA – Institut Royal pour Sourds et Aveugles</strong>
                        <p>Chaussée de Waterloo 1502-1508<br>1180 Uccle – Belgique</p>
                    </address>
                </dd>

                <dt>?</dt>
                <dd>Du lundi au vendredi<br>8h30 - 12h / 13h - 16h30</dd>

                <dt>?</dt>
                <dd>info@irsa.be</dd>

                <dt>?</dt>
                <dd>+32 (0)2 343 22 27</dd>

                <dt></dt>
                <dd>
                    <h3 id="social-heading">Suivez nous</h3>
                    <ul class="social-links">
                        <li><a href="#" aria-label="Facebook">📘</a></li>
                        <li><a href="#" aria-label="Twitter">🐦</a></li>
                        <li><a href="#" aria-label="LinkedIn">💼</a></li>
                        <li><a href="#" aria-label="Instagram">📸</a></li>
                    </ul>
                </dd>
    </section>

</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/layout.php', ['main' => $this_html, 'css' => ['page-contact']], IO_EXTRACT);
    return $page;
};
