<header>
    <h1><span class="tight">Nous contacter</span></h1>
</header>

<!-- Formulaire de contact -->
<section class="tight" aria-labelledby="form-heading">
    <h2 id="form-heading">Formulaire de contact</h2>

    <form action="/contact" method="post" class="contact-form">
        <label for="name">Nom, prénom</label>
        <input type="text" id="name" name="name" placeholder="Nom, prénom" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="exemple@gmail.com" required>

        <label for="phone">Téléphone</label>
        <input type="tel" id="phone" name="phone" placeholder="04********">

        <label for="category">Objet</label>
        <select id="category" name="category" required>
            <option value="">— Catégorie principale —</option>
            <option value="scolarite">Scolarité</option>
            <option value="soins">Soins</option>
            <option value="administratif">Administratif</option>
            <option value="autre">Autre</option>
        </select>

        <select id="subcategory" name="subcategory">
            <option value="">— Sous-catégories —</option>
        </select>

        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" placeholder="Expliquez votre demande ici..." required></textarea>

        <button type="submit">Envoyer</button>
    </form>
</section>

<!-- Coordonnées -->
<section class="tight" aria-labelledby="contact-info-heading">
    <h2 id="contact-info-heading">Coordonnées et horaires</h2>

    <address>
        <p>
            IRSA – Institut Royal pour Sourds et Aveugles<br>
            Chaussée de Waterloo 1502-1508<br>
            1180 Uccle – Belgique
        </p>
    </address>

    <p><strong>Horaires :</strong><br>
        Du lundi au vendredi<br>
        8h30 – 12h / 13h – 16h30
    </p>

    <p><strong>Téléphone :</strong><br>
        <a href="tel:+3221234567">02 123 45 67</a>
    </p>
</section>

<!-- Réseaux sociaux -->
<section class="tight" aria-labelledby="social-heading">
    <h2 id="social-heading">Suivez-nous</h2>
    <ul class="social-links">
        <li><a href="#" aria-label="Facebook">Facebook</a></li>
        <li><a href="#" aria-label="Instagram">Instagram</a></li>
        <li><a href="#" aria-label="LinkedIn">LinkedIn</a></li>
        <li><a href="#" aria-label="YouTube">YouTube</a></li>
    </ul>
</section>

<?php
return function ($this_html, $args) {
    [$ret, $page] = ob_ret_get('app/montre/layout.php', ['main' => $this_html, 'css' => ['page-contact']], IO_EXTRACT);
    return $page;
};
