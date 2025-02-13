# Bibliothèques Utilisées sur le Site IRSA

## Bibliothèques CSS

1. **Font Awesome**
   - Fichier : [font-awesome.min.css](http://www.irsa.be/media/gantry5/assets/css/font-awesome.min.css)
   - Objectif : Bibliothèque d'icônes pour projets web.

2. **Bootstrap**
   - Fichier : [bootstrap-gantry.css](http://www.irsa.be/media/gantry5/assets/css/bootstrap-gantry.css)
   - Objectif : Framework CSS pour un design réactif et une mise en page optimisée.

3. **UIkit**
   - Fichier : [uikit.min.css](http://www.irsa.be/templates/g5_hydrogen/custom/uikit/css/uikit.min.css)
   - Objectif : Framework modulaire léger pour des interfaces web modernes.

4. **IcoMoon**
   - Fichier : [icomoon.css](http://www.irsa.be/media/jui/css/icomoon.css)
   - Objectif : Bibliothèque d'icônes en police.

---

## Bibliothèques JavaScript

1. **Google Maps API**
   - Fichier : [Google Maps API](https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyB0qWVOvQvLp2Qe9-ZR9iZ8NrP5gZj7AU0&language=fr-FR&libraries=places)
   - Objectif : Fournir des fonctionnalités interactives de cartographie et des services de géolocalisation.

2. **jQuery**
   - Fichiers :
     - [jquery.min.js](http://www.irsa.be/media/jui/js/jquery.min.js)
     - [jquery-noconflict.js](http://www.irsa.be/media/jui/js/jquery-noconflict.js)
     - [jquery-migrate.min.js](http://www.irsa.be/media/jui/js/jquery-migrate.min.js)
   - Objectif : Simplifier la manipulation du DOM, la gestion des événements et les appels AJAX.

3. **MooTools**
   - Fichiers :
     - [mootools-core.js](http://www.irsa.be/media/system/js/mootools-core.js)
     - [mootools-more.js](http://www.irsa.be/media/system/js/mootools-more.js)
   - Objectif : Framework JavaScript pour la création d'interfaces utilisateur avancées.

4. **Bootstrap**
   - Fichier : [bootstrap.min.js](http://www.irsa.be/media/jui/js/bootstrap.min.js)
   - Objectif : Fournir des éléments et composants pour un design réactif.

5. **RokSprocket**
   - Fichiers :
     - [rokmediaqueries.js](http://www.irsa.be/components/com_roksprocket/assets/js/rokmediaqueries.js)
     - [roksprocket.js](http://www.irsa.be/components/com_roksprocket/assets/js/roksprocket.js)
     - [features.js](http://www.irsa.be/components/com_roksprocket/layouts/features/assets/js/features.js)
     - [slideshow2.js](http://www.irsa.be/components/com_roksprocket/layouts/features/themes/slideshow2/slideshow2.js)
     - [basic.js](http://www.irsa.be/components/com_roksprocket/layouts/grids/themes/basic/basic.js)
   - Objectif : Module de contenu Joomla pour des mises en page et animations avancées.

6. **UIkit**
   - Fichier : [uikit.min.js](http://www.irsa.be/templates/g5_hydrogen/custom/uikit/js/uikit.min.js)
   - Objectif : Framework JavaScript pour des interfaces web modernes.

7. **Plugin Google Maps**
   - Fichiers :
     - [googlemapsv3.js](http://www.irsa.be/media/plugin_googlemap3/site/googlemaps/googlemapsv3.js)
     - [modalbox1.3hackv3.js](http://www.irsa.be/media/plugin_googlemap3/site/moodalbox/js/modalbox1.3hackv3.js)
   - Objectif : Intégrer des fonctionnalités Google Maps aux sites basés sur Joomla.

---

## Polices

1. **Font Awesome**
   - Fichier : [fontawesome-webfont.woff2](http://www.irsa.be/media/gantry5/assets/fonts/fontawesome-webfont.woff2?v=4.7.0)
   - Objectif : Bibliothèque d'icônes pour les applications web modernes.

2. **Google Fonts**
   - Fichier : [Arimo](https://fonts.googleapis.com/css?family=Arimo)
   - Objectif : Typographie web sécurisée.

---

# Recommandations de Suppression

## 1. Bibliothèques Redondantes ou Obsolètes

### **MooTools**
- **Fichiers** :
  - [mootools-core.js](http://www.irsa.be/media/system/js/mootools-core.js)
  - [mootools-more.js](http://www.irsa.be/media/system/js/mootools-more.js)
- **Raison** :
  - MooTools est obsolète et n'est plus largement supporté. Des bibliothèques modernes comme jQuery ou du JavaScript natif offrent une alternative plus efficace.
  - Il ajoute un poids inutile à la page.
- **Action** :
  - Auditer et remplacer les fonctionnalités reposant sur MooTools par des alternatives modernes.

### **Redondance du Plugin Google Maps**
- **Fichiers** :
  - [googlemapsv3.js](http://www.irsa.be/media/plugin_googlemap3/site/googlemaps/googlemapsv3.js)
  - [modalbox1.3hackv3.js](http://www.irsa.be/media/plugin_googlemap3/site/moodalbox/js/modalbox1.3hackv3.js)
- **Raison** :
  - Des scripts liés à Google Maps sont chargés en double (ex. : [Google Maps API](https://maps.googleapis.com/maps/api/js)).
  - Cela provoque une redondance et ralentit le chargement de la page.
- **Action** :
  - Consolider toutes les fonctionnalités de cartographie en un seul script utilisant l'API officielle de Google Maps.

---

## 2. CSS Inutilisé ou Excessif

### **Font Awesome**
- **Fichier** : [font-awesome.min.css](http://www.irsa.be/media/gantry5/assets/css/font-awesome.min.css)
- **Raison** :
  - Si seule une poignée d'icônes est utilisée, l'ensemble de la bibliothèque représente une surcharge inutile.
  - L'utilisation de SVG en ligne ou d'un sous-ensemble de Font Awesome peut remplacer ce fichier volumineux.
- **Action** :
  - Extraire uniquement les icônes nécessaires ou les remplacer par des SVG en ligne.

### **Bootstrap**
- **Fichier** : [bootstrap-gantry.css](http://www.irsa.be/media/gantry5/assets/css/bootstrap-gantry.css)
- **Raison** :
  - Si le site utilise UIkit comme framework principal, Bootstrap peut être redondant.
- **Action** :
  - Auditer le CSS pour déterminer si Bootstrap peut être entièrement supprimé.

### **IcoMoon**
- **Fichier** : [icomoon.css](http://www.irsa.be/media/jui/css/icomoon.css)
- **Raison** :
  - Peut être redondant si Font Awesome ou des SVG en ligne sont déjà utilisés pour les icônes.
- **Action** :
  - Remplacer ou consolider l'utilisation des icônes.

---

## 3. Polices Duplicates et Inutiles

### **Google Fonts Arimo**
- **Fichiers** :
  - [Arimo (HTTP)](http://fonts.gstatic.com/s/arimo/v29/P5sfzZCDf9_T_3cV7NCUECyoxNk37cxcABrB.woff2)
  - [Arimo (HTTPS)](https://fonts.gstatic.com/s/arimo/v29/P5sfzZCDf9_T_3cV7NCUECyoxNk37cxcABrB.woff2)
- **Raison** :
  - Charger les polices via HTTP et HTTPS crée une redondance et peut poser des problèmes de sécurité.
- **Action** :
  - Utiliser une seule source sécurisée en HTTPS pour la police.

---

## 4. Consolidation ou Suppression de JavaScript Redondant

### **Doublons de jQuery**
- **Fichiers** :
  - [jquery.min.js](http://www.irsa.be/media/jui/js/jquery.min.js)
  - [jquery-noconflict.js](http://www.irsa.be/media/jui/js/jquery-noconflict.js)
  - [jquery-migrate.min.js](http://www.irsa.be/media/jui/js/jquery-migrate.min.js)
- **Raison** :
  - Plusieurs versions de scripts liés à jQuery augmentent le temps de chargement et peuvent provoquer des conflits.
- **Action** :
  - Utiliser une seule version récente de jQuery et supprimer `jquery-noconflict.js` et `jquery-migrate.min.js` sauf si leur présence est absolument nécessaire.

---

## 5. Images Lourdes ou Redondantes

### **Images en Cache**
- **Fichiers** :
  - [cache/mod_roksprocket](e.g., `3b0989e261bbe0ea3a4ad82e59b31282_630_1680.png`)
- **Raison** :
  - Ces images peuvent être obsolètes ou générées inutilement par des extensions comme Roksprocket.
- **Action** :
  - Supprimer les images non utilisées ou les remplacer par des versions optimisées.

### **Images de Galerie de Grande Taille**
- **Fichiers** :
  - [Image de Galerie 1](http://www.irsa.be/media/rokgallery/f/f2ed79a5-0880-41ad-9276-022f42973a4a/68ddd7cb-3fdb-4616-d382-ec220aeb066f.jpg)
  - [Image de Galerie 2](http://www.irsa.be/media/rokgallery/f/f15db25f-e729-4312-dda2-8d58fa347d92/96e9323d-256e-4b85-b5aa-0f2438ad75cf.jpg)
- **Raison** :
  - Des images volumineuses augmentent significativement le temps de chargement de la page.
- **Action** :
  - Compresser ces images et les servir dans des formats de nouvelle génération (ex. : WebP).

---

## 6. Fichiers RokSprocket Inutilisés
- **Fichiers** :
  - [rokmediaqueries.js](http://www.irsa.be/components/com_roksprocket/assets/js/rokmediaqueries.js)
  - [roksprocket.js](http://www.irsa.be/components/com_roksprocket/assets/js/roksprocket.js)
- **Raison** :
  - Si RokSprocket n'est pas activement utilisé pour le contenu, ces fichiers ajoutent un poids superflu.
- **Action** :
  - Supprimer ces fichiers s'ils ne sont pas essentiels au fonctionnement du site.

---

En supprimant ou en remplaçant ces ressources redondantes et obsolètes, le site peut améliorer ses temps de chargement, simplifier sa maintenance et réduire l'utilisation de la bande passante.
