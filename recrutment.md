# Mise à jour du module recrutement

Ce document résume les fonctionnalités actuellement présentes dans le module recrutement du projet HUMA.

## Périmètre fonctionnel

- Gestion des offres d'emploi
- Gestion des candidatures
- Création et consultation des quizzes liés aux offres
- Suivi des entretiens
- Parcours candidat pour consulter et postuler aux offres
- Tableau de bord admin pour le suivi du recrutement

## Fichiers principaux

- [`src/Controller/AdminController.php`](./src/Controller/AdminController.php)
- [`src/Controller/CondidatController.php`](./src/Controller/CondidatController.php)
- [`src/Form/OffreEmploiType.php`](./src/Form/OffreEmploiType.php)
- [`src/Form/CandidatureType.php`](./src/Form/CandidatureType.php)
- [`src/Form/CandidatPostulationType.php`](./src/Form/CandidatPostulationType.php)
- [`src/Entity/OffreEmploi.php`](./src/Entity/OffreEmploi.php)
- [`src/Entity/Candidature.php`](./src/Entity/Candidature.php)
- [`src/Entity/Entretien.php`](./src/Entity/Entretien.php)
- [`src/Entity/Quiz.php`](./src/Entity/Quiz.php)
- [`src/Entity/Question.php`](./src/Entity/Question.php)
- [`src/Entity/ReponseQcm.php`](./src/Entity/ReponseQcm.php)
- [`src/Entity/ResultatQuiz.php`](./src/Entity/ResultatQuiz.php)
- [`src/Repository/OffreEmploiRepository.php`](./src/Repository/OffreEmploiRepository.php)
- [`src/Repository/CandidatureRepository.php`](./src/Repository/CandidatureRepository.php)
- [`src/Repository/EntretienRepository.php`](./src/Repository/EntretienRepository.php)
- [`src/Repository/QuizRepository.php`](./src/Repository/QuizRepository.php)
- [`src/Repository/ResultatQuizRepository.php`](./src/Repository/ResultatQuizRepository.php)

## Intégration et personnalisation de bundles externes

### 1. `KnpPaginatorBundle`

Le bundle est utilisé pour paginer les listes du module recrutement côté admin et côté candidat.

- Configuration: [`config/bundles.php`](./config/bundles.php)
- Utilisation dans le contrôleur admin: [`src/Controller/AdminController.php`](./src/Controller/AdminController.php)
- Utilisation côté candidat: [`src/Controller/CondidatController.php`](./src/Controller/CondidatController.php)

Usage principal:

- pagination des offres
- pagination des candidatures
- navigation plus fluide dans les tableaux de suivi

## Intégration des API

Dans l’état actuel du dépôt, le module recrutement ne contient pas encore d’API externe dédiée branchée directement dans les contrôleurs recrutement.

En revanche, le module est conçu pour être compatible avec des extensions futures, car il s’appuie déjà sur des services séparés et sur une architecture modulaire:

- génération des pages de pilotage dans [`src/Controller/AdminController.php`](./src/Controller/AdminController.php)
- parcours candidat dans [`src/Controller/CondidatController.php`](./src/Controller/CondidatController.php)
- modèles métiers bien isolés dans [`src/Entity`](./src/Entity)

## Intégration de l’IA

Aucune intégration IA spécifique au recrutement n’est actuellement branchée dans cette version du dépôt.

Le module reste toutefois prêt à accueillir:

- une analyse automatique des CV
- une génération assistée des quizzes
- un scoring intelligent des candidatures
- un classement des profils selon les critères de l’offre

## Développement de fonctionnalités métiers avancées

### Gestion des offres

- création d’offres avec validation des champs métier
- édition et suppression sécurisées
- interdiction de suppression si des candidatures ou quizzes sont liés à l’offre

Références:

- [`src/Controller/AdminController.php`](./src/Controller/AdminController.php)
- [`src/Form/OffreEmploiType.php`](./src/Form/OffreEmploiType.php)

### Gestion des candidatures

- dépôt de candidature par le candidat
- vérification du doublon de postulation
- suivi du statut de candidature

Références:

- [`src/Controller/CondidatController.php`](./src/Controller/CondidatController.php)
- [`src/Entity/Candidature.php`](./src/Entity/Candidature.php)

### Quizzes de recrutement

- création d’un quiz lié à une offre
- gestion des questions QCM
- évaluation automatique des réponses
- stockage des résultats de quiz

Références:

- [`src/Entity/Quiz.php`](./src/Entity/Quiz.php)
- [`src/Entity/Question.php`](./src/Entity/Question.php)
- [`src/Entity/ReponseQcm.php`](./src/Entity/ReponseQcm.php)
- [`src/Entity/ResultatQuiz.php`](./src/Entity/ResultatQuiz.php)
- [`src/Controller/AdminController.php`](./src/Controller/AdminController.php)
- [`src/Controller/CondidatController.php`](./src/Controller/CondidatController.php)

### Entretiens

- suivi des entretiens liés aux candidatures
- dépendance métier forte entre candidature et entretien
- suppression contrôlée pour éviter les données orphelines

Références:

- [`src/Entity/Entretien.php`](./src/Entity/Entretien.php)
- [`src/Repository/EntretienRepository.php`](./src/Repository/EntretienRepository.php)
- [`src/Controller/AdminController.php`](./src/Controller/AdminController.php)

## Conclusion

Le module recrutement est déjà structuré autour de briques métier solides:

- offres
- candidatures
- quizzes
- entretiens
- parcours candidat
- supervision admin

Les prochaines évolutions naturelles du module seraient l’ajout d’API externes, d’IA de tri des CV et d’une génération automatique avancée des quizzes.
