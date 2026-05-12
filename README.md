# HUMADB

HUMADB est une application web de gestion RH et de recrutement construite avec Symfony 6.4.
Le projet couvre plusieurs processus metier autour des ressources humaines:

- recrutement et gestion des candidatures
- gestion des offres d'emploi et des quiz de recrutement
- gestion des formations et des participations
- gestion des conges et des absences
- suivi des feedbacks, publications et commentaires
- tableaux de bord par role
- integrations IA, ML et outils externes

L'application est organisee pour plusieurs profils:

- administrateur
- manager
- employe
- candidat
- client public

## Sommaire

- [Apercu du projet](#apercu-du-projet)
- [Fonctionnalites principales](#fonctionnalites-principales)
- [Stack technique](#stack-technique)
- [Architecture et modules](#architecture-et-modules)
- [Installation locale](#installation-locale)
- [Variables denvironnement](#variables-denvironnement)
- [Commandes utiles](#commandes-utiles)
- [Qualite et tests](#qualite-et-tests)
- [Structure du projet](#structure-du-projet)
- [Integrations externes](#integrations-externes)

## Apercu du projet

Le projet HUMADB est un portail RH complet base sur Symfony et Doctrine.
Il permet de gerer:

- le cycle de recrutement, de l'offre au suivi des candidatures
- les entretiens avec lien Jitsi
- les formations et les inscriptions des employes
- les conges et les absences avec workflow d'approbation
- les publications internes avec reactions, commentaires et traduction
- des assistants IA pour generer ou analyser du contenu
- des outils de reporting et de tableau de bord

Le projet contient egalement:

- un module de publication connecte a des assistants IA
- des services de classement de CV avec Python
- des outils de prediction ML
- des notifications et exports de donnees
- des pages publiques pour les visiteurs

## Fonctionnalites principales

### Administration

- gestion des utilisateurs
- activation/desactivation des comptes
- export des utilisateurs
- consultation des logs
- gestion des offres d'emploi
- gestion des candidatures
- planification des entretiens
- generation automatique de quiz de recrutement
- publication d'offres sur Facebook
- gestion des formations
- gestion des conges
- gestion des absences
- gestion des publications et commentaires
- gestion des feedbacks
- acces aux rapports et tableaux de bord

### Manager

- tableau de bord manager
- consultation de l'equipe
- suivi des conges
- suivi des absences
- consultation des formations
- consultation des participations
- consultation des publications
- commentaire sur les publications
- consultation des profils employes

### Employe

- consultation des formations disponibles
- inscription aux formations
- suivi des participations
- affichage des QR codes de participation
- consultation des publications
- reactions et commentaires
- creation de feedbacks
- consultation des absences et documents

### Candidat

- consultation des offres
- postulation en plusieurs etapes
- televersement de CV PDF
- passage d'un quiz associe a l'offre
- consultation des rapports et documents
- tableau de bord candidat

### Client public

- page d'accueil
- pages institutionnelles
- page contact
- page services
- page offres / jobs

## Stack technique

### Backend

- PHP 8.1+
- Symfony 6.4
- Doctrine ORM
- Symfony Security
- Symfony Validator
- Symfony Mailer
- Symfony Messenger

### Frontend

- Twig
- Asset Mapper / Webpack Encore
- JavaScript
- SCSS

### Base de donnees

- MySQL / MariaDB

### Bibliotheques principales

- `doctrine/doctrine-bundle`
- `doctrine/doctrine-migrations-bundle`
- `knplabs/knp-paginator-bundle`
- `vich/uploader-bundle`
- `friendsofsymfony/ckeditor-bundle`
- `bacon/bacon-qr-code`
- `endroid/qr-code`
- `smalot/pdfparser`
- `fabpot/goutte`

## Architecture et modules

Le code est structure autour de plusieurs domaines metier.

### Recrutement

Le module recrutement est le plus complet du projet.
Il couvre:

- creation et gestion des offres
- gestion des candidatures
- upload et preview de CV PDF
- analyse automatique des CV
- generation de quiz manuels ou automatiques
- programmation d'entretiens
- integration Jitsi pour les meetings
- sourcing public de profils
- publication d'offres sur Facebook

Principaux fichiers:

- [src/Controller/AdminERecruitmentController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AdminERecruitmentController.php)
- [src/Controller/CondidatController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/CondidatController.php)
- [src/Controller/CandidatQuizController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/CandidatQuizController.php)
- [src/Service/CvMatchingService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/CvMatchingService.php)
- [src/Service/PythonCvRankingService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/PythonCvRankingService.php)
- [src/Service/QuizGenerationService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/QuizGenerationService.php)
- [src/Service/JitsiMeetService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/JitsiMeetService.php)

### Formations

Le module formation permet de:

- creer, modifier et supprimer des formations
- accepter ou refuser des demandes
- inscrire des employes aux formations
- suivre les participations
- generer ou telecharger des QR codes
- afficher les details de participation

Principaux fichiers:

- [src/Controller/AdminFormationController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AdminFormationController.php)
- [src/Controller/EmployeFormationController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/EmployeFormationController.php)
- [src/Controller/ParticipationController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/ParticipationController.php)
- [src/Service/FormationManager.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/FormationManager.php)
- [src/Service/ParticipationManager.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/ParticipationManager.php)
- [src/Service/QrCodeService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/QrCodeService.php)

### Conges et absences

Le projet integre un workflow RH pour les demandes:

- depot de conge
- validation par le manager
- rejection ou approbation
- suivi des absences
- historique du workflow
- notifications email

Principaux fichiers:

- [src/Controller/AdminController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AdminController.php)
- [src/Controller/ManagerController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/ManagerController.php)
- [src/WorkflowBundle/Controller/WorkflowController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/WorkflowBundle/Controller/WorkflowController.php)
- [src/Service/JourFerieService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/JourFerieService.php)
- [src/PlanningBundle/Service/ConflictDetector.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/PlanningBundle/Service/ConflictDetector.php)

### Publications et engagement

Le projet propose un espace de communication interne:

- creation et edition de publications
- reactions
- commentaires
- moderation
- traduction de contenus
- recommandation ou analyse de l'engagement

Principaux fichiers:

- [src/Controller/EmployePublicationController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/EmployePublicationController.php)
- [src/Controller/ManagerController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/ManagerController.php)
- [src/Controller/ReactionController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/ReactionController.php)
- [src/Controller/PublicationTranslationController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/PublicationTranslationController.php)
- [src/Controller/PublicationMLController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/PublicationMLController.php)
- [src/Controller/CommentMLController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/CommentMLController.php)

### Feedback

Le module feedback couvre:

- creation d'un feedback
- edition et suppression
- reponse automatique assistee par IA
- envoi de reponse
- suivi cote manager et administrateur

Principaux fichiers:

- [src/Controller/EmployeFeedbackController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/EmployeFeedbackController.php)
- [src/Controller/AdminFeedbackController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AdminFeedbackController.php)
- [src/Service/FeedbackAutoResponseGenerator.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/FeedbackAutoResponseGenerator.php)
- [src/Service/FeedbackPriorityAnalyzer.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/FeedbackPriorityAnalyzer.php)

### Notifications, rapports et dashboards

Le projet comprend:

- des notifications internes
- des dashboards par role
- des rapports RH
- des exports CSV
- des vues de synthese

Principaux fichiers:

- [src/Controller/NotificationController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/NotificationController.php)
- [src/ReportBundle/Controller/ReportController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/ReportBundle/Controller/ReportController.php)
- [src/Controller/MLPredictionController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/MLPredictionController.php)
- [src/Controller/ClientController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/ClientController.php)

### IA, ML et automatismes

Le projet contient plusieurs briques intelligentes:

- assistant IA pour les publications
- analyse de sentiment
- prediction d'engagement
- classement de CV
- sourcing de profils publics
- generation de contenu

Principaux fichiers:

- [src/Controller/AIAssistantController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AIAssistantController.php)
- [src/Service/AIAssistantService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/AIAssistantService.php)
- [src/Service/ChatbotService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/ChatbotService.php)
- [src/Service/AIService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/AIService.php)
- [src/Service/ExternalAiRecruitmentAnalyzer.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/ExternalAiRecruitmentAnalyzer.php)
- [src/Service/MeaningCloudSentimentService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/MeaningCloudSentimentService.php)

## Installation locale

### Preconditions

- PHP 8.1 ou plus
- Composer
- Node.js et npm
- MySQL ou MariaDB
- serveur web local de type MAMP, Symfony CLI ou Apache/Nginx

### Etapes

1. Cloner le depot.
2. Installer les dependances PHP.
3. Installer les dependances front.
4. Configurer les variables d'environnement locales.
5. Creer la base de donnees.
6. Executer les migrations.
7. Compiler les assets.
8. Demarrer l'application.

### Commandes de base

```bash
composer install
npm install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
npm run dev
```

Si vous utilisez Symfony CLI:

```bash
symfony server:start
```

Si vous utilisez MAMP, configurez simplement le document root sur le projet et assurez-vous que `DATABASE_URL` pointe vers votre instance MySQL locale.

## Variables denvironnement

Les variables principales sont definies dans `.env`, `.env.local` et `.env.test`.

Variables importantes:

- `APP_ENV`
- `APP_SECRET`
- `DATABASE_URL`
- `MESSENGER_TRANSPORT_DSN`
- `MAILER_DSN`
- `EXTERNAL_AI_API_URL`
- `EXTERNAL_AI_API_KEY`
- `EXTERNAL_AI_MODEL`
- `FACEBOOK_PAGE_ID`
- `FACEBOOK_PAGE_ACCESS_TOKEN`
- `FACEBOOK_GRAPH_API_VERSION`
- `APP_PUBLIC_BASE_URL`
- `OPENAI_API_KEY`
- `OPENAI_MODEL`
- `MEANINGCLOUD_API_KEY`
- `MEANINGCLOUD_API_URL`
- `GROQ_API_KEY`

Important:

- ne jamais committer de secrets reels dans un fichier partage
- garder les cles sensibles dans `.env.local`
- utiliser des valeurs de test pour l'environnement de developpement

## Commandes utiles

### Qualite du code

```bash
composer phpstan
composer test
```

### Cache et assets

```bash
php bin/console cache:clear
php bin/console assets:install
npm run build
```

### Doctrine

```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Outils projet

```bash
php bin/console debug:router
php bin/console debug:container
```

## Qualite et tests

Le projet inclut:

- PHPUnit pour les tests
- PHPStan pour l'analyse statique
- des scripts de verification Symfony
- des services metier couverts par tests

Scripts disponibles dans `composer.json`:

- `composer phpstan`
- `composer test`

Le dossier de tests est ici:

- [tests/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/tests/)

## Structure du projet

Arborescence principale:

- [src/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/)
- [templates/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/)
- [config/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/config/)
- [public/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/public/)
- [migrations/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/migrations/)
- [tests/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/tests/)
- [python/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/python/)
- [docs/](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/docs/)

Fichiers de configuration principaux:

- [composer.json](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/composer.json)
- [package.json](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/package.json)
- [.env](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/.env)
- [webpack.config.js](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/webpack.config.js)

## Integrations externes

### Groq

Utilise pour le chatbot IA de generation de publications.

### OpenAI-compatible external API

Utilisee dans le module de recrutement externe pour analyser ou enrichir les candidatures.

### MeaningCloud

Utilise pour l'analyse de sentiment.

### Facebook Graph API

Utilise pour publier des offres d'emploi sur une page Facebook.

### Jitsi

Utilise pour generer et ouvrir des liens de reunion d'entretien.

### Python

Utilise pour le classement des CV via le service de ranking Python.

## Notes de developpement

- Le projet contient plusieurs modules de recherche et d'optimisation deja documentes dans `RAPPORT_OPTIMISATIONS.md`.
- Le module recrutement dispose aussi d'une documentation dediee dans `docs/RECRUTEMENT.md`.
- Le chatbot IA dispose de son propre guide dans `CHATBOT_GUIDE.md`.

## Licence

Projet proprietaire.
