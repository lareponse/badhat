# Plan Structurel pour la Refonte du Site IRSA

## 1. **Objectifs et Buts**

- **Performance** :
  - Atteindre un FCP < 2 secondes et un LCP < 2,5 secondes.
  - Maintenir la taille totale de la page à moins de 2 Mo.
  - Réduire le nombre de requêtes HTTP à moins de 30.
- **Expérience Utilisateur** :
  - Créer un design responsive orienté mobile.
  - Assurer une navigation fluide avec un CLS < 0,1.
- **Accessibilité** :
  - Respecter les critères de conformité WCAG 2.1 Niveau AA.
- **SEO** :
  - Optimiser pour les Core Web Vitals.
  - Améliorer l'exploration et l'indexation.
- **Maintenabilité** :
  - Utiliser des composants modulaires et réutilisables.

---

## 2. **Pages Principales**

### 2.1 Page d'Accueil
- **Objectif** : Fournir un aperçu concis de la mission d'IRSA, de ses services et des informations clés.
- **Caractéristiques Clés** :
  - Section héro légère avec des animations minimales.
  - Boutons d'appel à l'action bien en évidence.
  - Liens rapides vers les services et les informations de contact.

### 2.2 Page des Services
- **Objectif** : Présenter en détail les services offerts par IRSA.
- **Caractéristiques Clés** :
  - Section pour chaque service avec un texte descriptif et des images.
  - Possibilité d'ajouter des PDF téléchargeables ou des liens vers des contenus connexes.

### 2.3 Page de Contact
- **Objectif** : Permettre aux utilisateurs de contacter facilement IRSA.
- **Caractéristiques Clés** :
  - Formulaire de contact avec validation.
  - Intégration de Google Maps pour la localisation (chargé à la demande).
  - Section FAQ pour les questions fréquentes.

### 2.4 Page À Propos
- **Objectif** : Présenter un aperçu de la mission d'IRSA et de son équipe.
- **Caractéristiques Clés** :
  - Section chronologique ou des jalons.
  - Profils des membres de l'équipe avec photos et biographies.

### 2.5 Page Blog / Actualités
- **Objectif** : Partager des mises à jour, des articles et des actualités.
- **Caractéristiques Clés** :
  - Options de recherche et de filtrage.
  - Liste paginée des articles avec des résumés.

---

## 3. **Design et Mise en Page**

### 3.1 Framework et Style
- Utiliser **Tailwind CSS** ou du CSS personnalisé pour un style léger et facile à maintenir.
- Opter pour un design épuré et minimaliste.
- Mettre en place une mise en page responsive orientée mobile.

### 3.2 Navigation
- En-tête fixe avec des liens vers les pages principales.
- Menu mobile avec sections déroulantes.
- Fil d'Ariane pour les pages secondaires.

### 3.3 Pied de Page
- Liens clés (par exemple : Conditions, Confidentialité, Contact).
- Icônes de réseaux sociaux.
- Brève description de la mission d'IRSA.

---

## 4. **Pile Technologique**

### 4.1 Frontend
- **Langages** : HTML5, CSS3, JavaScript (Vanilla ou Alpine.js).
- **Style** : Tailwind CSS ou CSS personnalisé léger.
- **Ressources** : Polices auto-hébergées et images optimisées (format WebP).

### 4.2 Backend
- **Langage** : PHP (pour sa simplicité) ou Node.js.
- **Framework** : Aucun ou un framework léger afin de minimiser la complexité.
- **Base de données** : MySQL ou PostgreSQL pour les données structurées.

### 4.3 Hébergement et Déploiement
- Utiliser un CDN moderne (par exemple : Cloudflare) pour la distribution des ressources.
- Activer HTTP/2 ou HTTP/3 pour un chargement plus rapide des ressources.
- Mettre en place des pipelines de déploiement automatisés.

---

## 5. **Optimisation des Performances**

### 5.1 Optimisation des Ressources
- Compresser et servir les images au format WebP.
- Minifier et combiner les fichiers CSS/JS.
- Supprimer le CSS/JS inutilisé (par exemple avec des outils comme PurgeCSS).

### 5.2 Chargement Paresseux (Lazy Loading)
- Charger en différé les images, vidéos et cartes.

### 5.3 Mise en Cache
- Définir les en-têtes de mise en cache appropriés pour les ressources statiques.
- Utiliser la mise en cache côté serveur (par exemple : Redis).

---

## 6. **Conformité en Matière d'Accessibilité**
- Utiliser du HTML sémantique pour tous les composants.
- Assurer des ratios de contraste de couleur suffisants.
- Ajouter des attributs `alt` à toutes les images.
- Rendre la navigation accessible au clavier.

---

## 7. **Tests et Déploiement**

### 7.1 Tests
- Utiliser Lighthouse pour les audits de performance, d'accessibilité et de SEO.
- Tester sur les navigateurs et appareils modernes (mobile, tablette, desktop).
- Réaliser des tests manuels d'ergonomie.

### 7.2 Déploiement
- Utiliser un environnement de pré-production pour tester les modifications.
- Surveiller les performances après le lancement et effectuer des améliorations itératives.

---

## 8. **Calendrier**

### Phase 1 : Planification et Audit
- **Durée** : 2 semaines.
- **Tâches** :
  - Auditer le contenu existant.
  - Définir les objectifs en termes de performance et d'expérience utilisateur.

### Phase 2 : Développement
- **Durée** : 6 semaines.
- **Tâches** :
  - Construire les pages principales et implémenter le design.
  - Optimiser la performance et tester de manière itérative.

### Phase 3 : Tests et Lancement
- **Durée** : 2 semaines.
- **Tâches** :
  - Finaliser les tests en pré-production.
  - Déployer en production et surveiller les performances après le lancement.
