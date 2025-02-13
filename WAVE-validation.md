# Rapport d'Accessibilité

## Erreurs (Problèmes d'accessibilité critiques)
- **Liens vides (3 occurrences) :**  
  Des liens sans texte ou contenu significatif sont présents. Ils doivent comporter un texte descriptif ou une étiquette ARIA appropriée pour fournir un contexte.

## Erreurs de Contraste (45 occurrences)
- **Contraste très faible :**  
  Plusieurs éléments textuels présentent un contraste insuffisant avec l'arrière-plan, rendant la lecture difficile pour les personnes ayant une déficience visuelle. Il est recommandé d'ajuster les couleurs du texte ou de l'arrière-plan pour respecter les directives de contraste.

## Alertes (47 occurrences)
- **Niveau de titre sauté (1 occurrence) :**  
  Un niveau de titre a été sauté, ce qui peut perturber l'ordre logique de lecture pour les technologies d'assistance. Réorganisez les titres pour garantir une structure cohérente (par exemple, passer de h2 à h3 sans sauter de niveaux).
- **Lien redondant (1 occurrence) :**  
  Un texte de lien est répété à côté d'un autre lien ayant la même destination ou le même texte. Fusionnez ou clarifiez ces liens pour éviter toute confusion.
- **Lien vers un document PDF (7 occurrences) :**  
  Des liens pointent directement vers des fichiers PDF. Envisagez d'ajouter une indication (par exemple, « (PDF) ») ou de vérifier que le PDF est accessible.
- **Élément `<noscript>` (1 occurrence) :**  
  La présence de `<noscript>` peut indiquer que le contenu n'est pas entièrement accessible sans JavaScript. Assurez-vous que le contenu de secours est utilisable.
- **Texte de titre redondant (32 occurrences) :**  
  Certains éléments possèdent des attributs title identiques à leur contenu textuel. Ces attributs ne sont pas nécessaires et peuvent créer une redondance pour les utilisateurs de lecteurs d'écran.
- **Tableaux de mise en page (4 occurrences) :**  
  Les tableaux utilisés pour la mise en page peuvent prêter à confusion. Utilisez CSS pour la mise en page et réservez les tableaux aux données.
- **Vidéo ou audio HTML5 (1 occurrence) :**  
  Assurez-vous que les éléments multimédias offrent des sous-titres, des transcriptions ou des descriptions audio selon les besoins.

## Fonctionnalités (20 occurrences)
- **Texte alternatif nul ou vide (7 occurrences) :**  
  Plusieurs images ont des attributs alt vides ou manquants. Fournissez un texte alternatif significatif ou marquez les images décoratives avec `alt=""`.
- **Image liée avec texte alternatif (3 occurrences) :**  
  Les images liées qui possèdent un texte alternatif doivent s'assurer que celui-ci décrit bien l'objectif du lien.
- **Étiquette de formulaire (2 occurrences) :**  
  Certains champs de formulaire peuvent manquer d'étiquettes claires. Ajoutez des étiquettes explicites et descriptives pour tous les contrôles de formulaire.
- **Langue (8 occurrences) :**  
  Assurez-vous que la langue de la page est correctement définie et que tout changement de langue au sein de la page est identifié.

## Éléments Structurels (43 occurrences)
Différents niveaux de titres sont utilisés (6 h1, 1 h2, 3 h3, 12 h4), plusieurs listes non ordonnées (16) et des éléments de repère (header, navigation, main, footer). Veillez à ce que les titres suivent une hiérarchie logique et que les repères soient utilisés correctement pour améliorer la navigation.

## ARIA (10 occurrences)
- **Utilisation d'ARIA (1 occurrence), étiquette ARIA (2 occurrences), ARIA hidden (6 occurrences), ARIA expanded (1 occurrence) :**  
  Vérifiez que les attributs ARIA sont utilisés correctement et de manière cohérente pour améliorer l'accessibilité sans la compliquer. Ils doivent correspondre au contenu et au contexte.

## Recommandations Clés
1. Fournir un texte descriptif pour les liens vides et les images avec attribut alt vide.
2. Améliorer le contraste des couleurs entre le texte et l'arrière-plan.
3. Utiliser des titres dans un ordre logique sans sauter de niveaux.
4. Consolider les liens redondants et étiqueter correctement les éléments de formulaire.
5. Veiller à ce que les documents PDF et les fichiers multimédias disposent de fonctionnalités d'accessibilité appropriées.
6. Supprimer les attributs title redondants et utiliser les attributs ARIA de manière judicieuse.
7. Vérifier que la langue, les rôles ARIA et les éléments de repère sont correctement implémentés pour une meilleure navigation et compréhension.

## Contraste

- **WCAG AA : Échoué**
- **WCAG AAA : Échoué**
