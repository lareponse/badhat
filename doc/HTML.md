# 🌐 Guide HTML Pur : Sémantique & Accessibilité

Ce dépôt contient une **checklist HTML pur** et un **aide-mémoire ARIA** destinés aux développeurs travaillant sur ce projet.  
Objectif : produire un HTML **sémantique, accessible, performant et lisible** — sans classes ni IDs inutiles.

---

## ✅ Checklist HTML pur

### 1. Structure et sémantique
- Utiliser les balises sémantiques natives : `<header>`, `<main>`, `<nav>`, `<section>`, `<article>`, `<aside>`, `<footer>`.
- Respecter la hiérarchie des titres (`<h1>` → `<h2>` → `<h3>`…), sans sauter de niveau.
- Employer les balises adaptées : `<p>`, `<ul>/<ol>/<li>`, `<blockquote>`, `<figure>/<figcaption>`, `<time>`, `<em>`, `<strong>`, etc.
- N’utiliser `<div>` ou `<span>` qu’en dernier recours.

### 2. Accessibilité (native)
- Chaque image a un `alt` approprié (`alt=""` si décorative).
- Chaque champ de formulaire a un `<label>` associé.
- Groupes de champs : `<fieldset>` + `<legend>`.
- Boutons et liens : `<button>` pour les actions, `<a href>` pour la navigation.
- Navigation possible uniquement au clavier (tabulation, focus visible).
- Ordre logique des titres et du contenu pour les lecteurs d’écran.

### 3. ARIA (renforcé)
⚠️ Principe : **N’utiliser ARIA que si le HTML natif ne suffit pas**.

- `role` uniquement quand aucune balise native n’existe (`role="dialog"`, `role="alert"`, `role="tablist"`, etc.).
- Associer correctement les relations :
  - `aria-labelledby` pour désigner un titre existant.
  - `aria-describedby` pour rattacher une description.
- Gérer la visibilité :
  - `aria-hidden="true"` pour masquer un élément inutile aux lecteurs d’écran.
  - `aria-live="polite"` ou `aria-live="assertive"` pour signaler des changements dynamiques.
- Pour composants complexes :
  - Accordéon : `aria-expanded`, `aria-controls`.
  - Modale : `role="dialog"`, `aria-modal="true"`.
  - Onglets : `role="tablist"`, `role="tab"`, `role="tabpanel`.

👉 Toujours tester avec un lecteur d’écran (NVDA, VoiceOver).

### 4. Métadonnées et base du document
- `<!DOCTYPE html>` en début de fichier.
- Attribut `lang="fr"` sur `<html>`.
- `<meta charset="UTF-8">`.
- `<meta name="viewport" content="width=device-width, initial-scale=1.0">`.
- `<title>` unique et descriptif.
- `<meta name="description">` pertinente.

### 5. Bonnes pratiques de contenu
- Liens avec texte explicite (éviter “cliquez ici”).
- Utiliser des listes (`<ul>`, `<ol>`) pour les énumérations.
- Tableaux : `<caption>`, `<thead>`, `<tbody>`, `<th scope="col/row">`.
- `<time>` pour les dates, `<abbr>` pour les abréviations.

### 6. Performance & lisibilité
- Code indenté et lisible.
- Pas de balises vides inutiles.
- Pas de doublons (titres vides, images redondantes).
- Structure simple et peu profonde.

---

## 🗂️ Aide-mémoire ARIA – Composants fréquents

### 🔲 Accordéon
```html
<button aria-expanded="false" aria-controls="panel1" id="btn1">Titre section</button>
<div id="panel1" role="region" aria-labelledby="btn1" hidden>
  Contenu...
</div>
````

### 📑 Onglets

```html
<div role="tablist" aria-label="Exemple d’onglets">
  <button role="tab" id="tab1" aria-controls="panel1" aria-selected="true">Onglet 1</button>
  <button role="tab" id="tab2" aria-controls="panel2" aria-selected="false">Onglet 2</button>
</div>

<div id="panel1" role="tabpanel" aria-labelledby="tab1">Contenu 1</div>
<div id="panel2" role="tabpanel" aria-labelledby="tab2" hidden>Contenu 2</div>
```

### 🪟 Modale

```html
<div role="dialog" aria-modal="true" aria-labelledby="modaltitle" aria-describedby="modaldesc">
  <h2 id="modaltitle">Titre de la modale</h2>
  <p id="modaldesc">Description ou instructions</p>
  <button>Fermer</button>
</div>
```

### 📋 Menu déroulant

```html
<button aria-haspopup="true" aria-expanded="false" aria-controls="menu1">Menu</button>
<ul id="menu1" role="menu" hidden>
  <li role="menuitem"><a href="#">Lien 1</a></li>
  <li role="menuitem"><a href="#">Lien 2</a></li>
</ul>
```

### ⚠️ Alerte

```html
<div role="alert">
  Une erreur est survenue. Veuillez réessayer.
</div>

<div aria-live="polite">
  Résultats chargés…
</div>
```

---

## 🎯 Usage

* Avant chaque **pull request**, vérifier que le code respecte la checklist.
* Utiliser l’aide-mémoire ARIA pour les composants interactifs.
* Tester avec un lecteur d’écran avant livraison.

---

## 📚 Ressources utiles

* [MDN Web Docs – HTML](https://developer.mozilla.org/fr/docs/Web/HTML)
* [W3C – ARIA Authoring Practices Guide](https://www.w3.org/WAI/ARIA/apg/)
* [Référence rapide WCAG](https://www.w3.org/WAI/WCAG21/quickref/)
