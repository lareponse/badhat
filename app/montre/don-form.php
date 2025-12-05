<form method="post" enctype="multipart/form-data">
<header class="page-header">
    <h1>
        <input type="text" name="header_title" value="Faire un don à l'IRSA" />
    </h1>

    <p class="tight">
        <textarea name="header_description">
            Soutenir l'IRSA, c'est permettre à des enfants, des jeunes et des adultes en situation de handicap sensoriel de grandir,
            d'apprendre, de s'épanouir et de vivre pleinement. Grâce à votre aide, nous contribuons à financer des accompagnements
            spécialisés, du matériel adapté, des projets innovants et une société plus inclusive.
        </textarea>
    </p>
</header>

<!-- Dons ponctuels -->
<section class="tight" aria-labelledby="ponctuels-heading">
    <h2>
        <input type="text" name="ponctuels_title" value="Dons ponctuels" />
    </h2>

    <p>
        <textarea name="ponctuels_description">
            Pour tout don annuel à partir de 40 €, vous bénéficiez d'une déduction fiscale de 45 %.
            Concrètement, un don de 100 € ne vous coûte en réalité que 55 €.
            À vous de choisir le montant qui correspond à votre engagement.
        </textarea>
    </p>

    <div class="card-grid">
        <!-- Don 50 € -->
        <article>
            <input type="file" name="don50_img" />
            <h3>
                <span>En donnant</span>
                <input type="text" name="don50_amount" value="50 €" />
            </h3>
            <textarea name="don50_text">Vous contribuez à l'achat d'un appareil auditif reconditionné</textarea>
            <input type="text" name="don50_link" value="#" />
            <textarea name="don50_note">
                Cela ne vous coûte que 27 € en réalité après déduction fiscale.
            </textarea>
        </article>

        <!-- Don 75 € -->
        <article>
            <input type="file" name="don75_img" />
            <h3>
                <span>En donnant</span>
                <input type="text" name="don75_amount" value="75 €" />
            </h3>
            <textarea name="don75_text">Vous financez une séance d'éveil sensoriel pour un bébé avec TSA</textarea>
            <input type="text" name="don75_link" value="#" />
            <textarea name="don75_note">
                Cela ne vous coûte que 34 € en réalité après déduction fiscale.
            </textarea>
        </article>

        <!-- Don 100 € -->
        <article>
            <input type="file" name="don100_img" />
            <h3>
                <span>En donnant</span>
                <input type="text" name="don100_amount" value="100 €" />
            </h3>
            <textarea name="don100_text">
                Vous soutenez la formation d'un enseignant spécialisé en langue des signes
            </textarea>
            <input type="text" name="don100_link" value="#" />
            <textarea name="don100_note">
                Cela ne vous coûte que 55 € en réalité après déduction fiscale.
            </textarea>
        </article>
    </div>
</section>

<!-- Dons mensuels -->
<section class="tight">
    <h2>
        <input type="text" name="mensuels_title" value="Dons mensuels">
    </h2>

    <div class="card-grid">
        <!-- Don 7 €/mois -->
        <article>
            <input type="file" name="don7_img">
            <input type="text" name="don7_title" value="En donnant 7 €/mois">
            <textarea name="don7_text">
                Vous financez 1 canne blanche tous les 2 mois pour un enfant
                ou un adulte aveugle en cours d'apprentissage.
            </textarea>
            <input type="text" name="don7_link" value="#">
            <textarea name="don7_note">
                Coût réel après déduction : 3,8 €/mois.
            </textarea>
        </article>

        <!-- Don 15 €/mois -->
        <article>
            <input type="file" name="don15_img">
            <input type="text" name="don15_title" value="En donnant 15 €/mois">
            <textarea name="don15_text">
                Vous permettez l'achat de 2 casques anti-bruit par trimestre
                pour des enfants présentant des troubles sensoriels ou TSA.
            </textarea>
            <input type="text" name="don15_link" value="#">
            <textarea name="don15_note">
                Coût réel après déduction : 8 €/mois.
            </textarea>
        </article>

        <!-- Don 30 €/mois -->
        <article>
            <input type="file" name="don30_img">
            <input type="text" name="don30_title" value="En donnant 30 €/mois">
            <textarea name="don30_text">
                Vous contribuez à l'équipement de nos classes spécialisées
                avec du matériel de communication pictographique.
            </textarea>
            <input type="text" name="don30_link" value="#">
            <textarea name="don30_note">
                Coût réel après déduction : 14 €/mois.
            </textarea>
        </article>
    </div>
</section>

<!-- Autres moyens -->
<section class="tight">
    <h2><input type="text" name="autres_title" value="Autres moyens de soutenir l'IRSA"></h2>

    <div class="card-grid">
        <!-- Virement -->
        <article>
            <input type="text" name="virement_title" value="Donner par virement">
            <textarea name="virement_text">
                Vous pouvez effectuer un don ponctuel ou régulier (ordre permanent)&nbsp;:<br><br>
                <strong>IBAN&nbsp;: BE94 7795 9133 9114</strong><br>
                <strong>BIC&nbsp;: GKCCBEBB</strong>
            </textarea>
            <textarea name="virement_ul">
                En communication : Don IRSA + votre nom
                Déductibilité fiscale dès 40 €/an, atout connu du fisc belge (réduction de 45%)
            </textarea>
            <p>
                Téléchargez le bulletin de virement
            </p>
            <input type="text" name="virement_link" value="/documents/irsa-don-virement.pdf">
        </article>

        <!-- Legs -->
        <article>
            <input type="text" name="legs_title" value="Faire un legs">
            <textarea name="legs_text1">
                Les legs garantissent la durabilité de nos projets et la pérennité de l'IRSA.</textarea>
            <textarea name="legs_text2">
                En choisissant la formule du duo legs (ou legs en duo), vous permettez à vos héritiers de payer moins de droits de succession tout en aidant l'IRSA à poursuivre sa mission d'aide aux personnes déficientes sensorielles.
            </textarea>
            <textarea name="legs_text3">
                Pour plus d'infos sur les legs, nous vous invitons à consulter votre notaire.
            </textarea>
            <p>
                En savoir plus sur les legs à l'IRSA
            </p>
            <input type="text" name="legs_link" value="https://notaire.be">
        </article>

        <!-- Don en nature -->
        <article>
            <input type="text" name="nature_title" value="Don en nature ou mécénat de matériel">
            <textarea name="nature_text">
                Vous déménagez ou renouvelez vos équipements
                Nous récupérons des jeux sensoriels, ordinateurs, mobilier, etc. en bon état.
                Merci de nous contacter avant don concret.
            </textarea>
            <p>Contactez-nous</p>
            <input type="text" name="nature_link" value="/contact">
        </article>

        <!-- Château -->
        <article>
            <input type="text" name="chateau_title" value="Louer le Château de l'Orangeraie">
            <textarea name="chateau_text1">
                Organisez mariages, événements, garden-parties dans notre magnifique château.
            </textarea>
            <textarea name="chateau_text2">
                Les revenus générés sont intégralement reversés à l'IRSA pour soutenir ses projets éducatifs et sociaux.
            </textarea>
            <p>Réservez le Château</p>
            <input type="text" name="chateau_link" value="contribuer/chateau">
        </article>
    </div>
</section>

<!-- FAQ -->
<section class="tight" aria-labelledby="faq-heading">
    <h2><input type="text" name="faq_title" value="FAQ"></h2>

    <div class="faq-group">
        <article>
            <input type="text" name="faq1_q" value="Mon don est-il déductible fiscalement ?">
            <textarea name="faq1_a">
                Oui, dès 40 € par an, vous bénéficiez d'une déduction fiscale de 45 %.
            </textarea>
        </article>

        <article>
            <input type="text" name="faq2_q" value="Comment vais-je recevoir mon attestation fiscale ?">
            <textarea name="faq2_a">
                Elle vous est envoyée automatiquement chaque année, au printemps.
            </textarea>
        </article>

        <article>
            <input type="text" name="faq3_q" value="Puis-je modifier ou arrêter un don mensuel ?">
            <textarea name="faq3_a">
                Oui, à tout moment, sur simple demande par email ou téléphone.
            </textarea>
        </article>

        <article>
            <input type="text" name="faq4_q" value="Où va mon argent ?">
            <textarea name="faq4_a">
                Votre don soutient directement nos services éducatifs, nos projets pédagogiques et l'achat de matériel spécialisé.
            </textarea>
        </article>

        <article>
            <input type="text" name="faq5_q" value="Que fait l'IRSA de mes données personnelles ?">
            <textarea name="faq5_a">
                Nous respectons strictement le RGPD : vos données sont sécurisées et jamais revendues.
            </textarea>
        </article>

        <article>
            <input type="text" name="faq6_q" value="Puis-je faire confiance à l'IRSA ?">
            <textarea name="faq6_a">
                Oui, nos comptes sont audités chaque année et publiés en toute transparence.
            </textarea>
        </article>
    </div>
</section>
<button type="submit">Enregistrer</button>
</form>
