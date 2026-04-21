# Module Recrutement

## Vue d'ensemble

Le module recrutement de `HUMADB` couvre:

- gestion des offres d'emploi
- postulation candidat en 2 etapes
- upload de CV PDF
- quiz de recrutement
- analyse automatique des CV
- planification d'entretiens
- integration Jitsi pour les meets

Le coeur fonctionnel est concentre dans:

- [src/Controller/AdminERecruitmentController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AdminERecruitmentController.php)
- [src/Controller/CondidatController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/CondidatController.php)
- [src/Controller/CandidatQuizController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/CandidatQuizController.php)

---

## Entites principales

### Offre d'emploi

Fichier:

- [src/Entity/OffreEmploi.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Entity/OffreEmploi.php)

Role:

- stocke les informations du poste
- rattache l'admin responsable
- rattache les candidatures
- rattache le quiz de l'offre

Champs importants:

- `titre`
- `description`
- `departement`
- `type_contrat`
- `nombre_postes`
- `date_publication`

### Candidature

Fichier:

- [src/Entity/Candidature.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Entity/Candidature.php)

Role:

- relie un candidat a une offre
- stocke le fichier CV de la candidature
- stocke le statut du traitement
- relie les entretiens

Champs importants:

- `user`
- `offreEmploi`
- `cv`
- `statut`
- `date_candidature`
- `date_statut`

### Quiz

Fichiers:

- [src/Entity/Quiz.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Entity/Quiz.php)
- [src/Entity/Question.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Entity/Question.php)
- [src/Entity/ReponseQcm.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Entity/ReponseQcm.php)
- [src/Entity/ResultatQuiz.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Entity/ResultatQuiz.php)

Role:

- un quiz appartient a une offre
- un quiz contient plusieurs questions
- une question QCM contient plusieurs reponses
- un resultat de quiz est enregistre par candidat

### Entretien

Fichier:

- [src/Entity/Entretien.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Entity/Entretien.php)

Role:

- relie une candidature a un entretien
- stocke admin, manager, date, duree, statut
- stocke le lien Jitsi

Champs importants:

- `candidature`
- `user` (admin RH)
- `manager`
- `date_entretien`
- `duree_minutes`
- `meet_link`
- `statut`

---

## Flux Admin

### 1. Gerer les offres

Ecran principal:

- [templates/admin/recrutment/recrutment.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/admin/recrutment/recrutment.html.twig)

Controleur:

- [src/Controller/AdminController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AdminController.php)

Actions:

- creer une offre
- modifier une offre
- supprimer une offre
- consulter les candidatures de l'offre

### 2. Consulter une offre

Ecran:

- [templates/admin/offre_emploi/show.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/admin/offre_emploi/show.html.twig)

Controleur:

- [src/Controller/AdminERecruitmentController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/AdminERecruitmentController.php)

Depuis cette page, l'admin peut:

- voir les candidatures rattachees
- voir l'analyse NLP des CV
- creer un quiz manuellement
- generer un quiz automatiquement
- regenerer un quiz si aucun candidat ne l'a deja passe

### 3. Creer un quiz manuellement

Ecran:

- [templates/admin/offre_emploi/quiz_new.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/admin/offre_emploi/quiz_new.html.twig)

Controleur:

- `AdminERecruitmentController::createManualQuiz()`

Fonctionnalites:

- titre du quiz
- duree en minutes
- seuil de reussite
- plusieurs questions QCM
- 4 reponses par question
- au moins une bonne reponse par question

### 4. Generer un quiz automatiquement

Service:

- [src/Service/QuizGenerationService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/QuizGenerationService.php)

Route:

- `admin_offre_generate_quiz`

Utilite:

- genere un quiz standard a partir du contenu de l'offre

### 5. Consulter une candidature

Ecran:

- [templates/admin/candidature/show.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/admin/candidature/show.html.twig)

Cette page affiche:

- informations du candidat
- CV PDF
- apercu texte du CV
- resultat du quiz
- historique des entretiens
- formulaire de planification d'entretien
- ouverture du meet Jitsi dans la meme page

### 6. Planifier un entretien

Formulaire:

- [src/Form/EntretienType.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Form/EntretienType.php)

Service:

- [src/Service/JitsiMeetService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/JitsiMeetService.php)

Regles:

- l'admin RH cree l'entretien
- l'admin choisit un manager
- un lien Jitsi unique est genere
- le meet peut s'ouvrir dans la meme page

---

## Flux Candidat

### 1. Consulter les offres

Ecrans:

- [templates/candidat/inventory/index.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/inventory/index.html.twig)
- [templates/candidat/offre/show.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/offre/show.html.twig)

### 2. Postuler en 2 etapes

Controleur:

- [src/Controller/CondidatController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/CondidatController.php)

Formulaire:

- [src/Form/CandidatPostulationType.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Form/CandidatPostulationType.php)

Etape 1:

- choisir l'offre
- uploader le CV PDF

Etape 2:

- si l'offre contient un quiz, le candidat est redirige vers le quiz

Ecrans:

- [templates/candidat/product/create.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/product/create.html.twig)
- [templates/candidat/offre/apply.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/offre/apply.html.twig)

### 3. Passer le quiz

Controleur:

- [src/Controller/CandidatQuizController.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Controller/CandidatQuizController.php)

Ecran:

- [templates/candidat/quiz/show.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/quiz/show.html.twig)

Fonctionnalites:

- minuterie du quiz
- duree basee sur `quiz.dureeMinutes`
- soumission automatique quand le temps est ecoule
- enregistrement du score et du statut

### 4. Voir les candidatures et l'entretien

Ecrans:

- [templates/candidat/reports/index.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/reports/index.html.twig)
- [templates/candidat/dashboard/index.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/dashboard/index.html.twig)
- [templates/candidat/offre/show.html.twig](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/templates/candidat/offre/show.html.twig)

Le candidat peut:

- voir le statut de sa candidature
- voir le score du quiz
- voir l'admin et le manager de l'entretien
- ouvrir le meet Jitsi dans la meme page

---

## Services utilises

### CandidateCvManager

Fichier:

- [src/Service/CandidateCvManager.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/CandidateCvManager.php)

Utilite:

- stocker le CV PDF d'une candidature
- retourner les chemins absolus et publics

### PdfCvPreviewService

Fichier:

- [src/Service/PdfCvPreviewService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/PdfCvPreviewService.php)

Lib utilisee:

- `smalot/pdfparser`

Utilite:

- lire le texte d'un PDF
- afficher un apercu du CV

### CvMatchingService

Fichier:

- [src/Service/CvMatchingService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/CvMatchingService.php)

Utilite:

- extraire des mots-cles de l'offre
- lire le texte du CV
- comparer offre et CV
- integrer le score du quiz
- classer les meilleurs CV

### QuizEvaluator

Fichier:

- [src/Service/QuizEvaluator.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/QuizEvaluator.php)

Utilite:

- corriger automatiquement un quiz QCM
- calculer le score
- determiner le statut `REUSSI` ou `ECHEC`

### JitsiMeetService

Fichier:

- [src/Service/JitsiMeetService.php](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/src/Service/JitsiMeetService.php)

Utilite:

- generer un lien Jitsi public
- nommer une salle unique
- utiliser `meet.jit.si` ou un serveur Jitsi personnalise

Config:

- [config/services.yaml](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/config/services.yaml)

---

## Bundles et librairies

### VichUploaderBundle

Utilite:

- upload de fichiers utilisateur

Config:

- [config/packages/vich_uploader.yaml](/Applications/MAMP/htdocs/Esprit-PIDEV-3A27-2526-HUMADB/config/packages/vich_uploader.yaml)

### Doctrine

Utilite:

- persistence de toutes les entites recrutement

### Twig

Utilite:

- rendu des pages admin et candidat

### smalot/pdfparser

Type:

- librairie PHP, pas bundle Symfony

Utilite:

- extraction du texte des CV PDF

### Jitsi

Type:

- service externe, pas bundle Symfony

Utilite:

- salle video publique sans login

---

## Points techniques importants

### Stockage du CV

Le champ `candidature.cv` contient uniquement:

- le nom du fichier PDF

Il ne contient pas:

- un objet `UploadedFile`
- un blob binaire

### Limite d'upload

La limite depend de PHP:

- `upload_max_filesize`
- `post_max_size`

### Meet dans la meme page

Le lien Jitsi est affiche dans un `iframe` sur:

- la fiche candidature admin
- la fiche offre candidat
- le dashboard candidat
- la liste des candidatures candidat

### Analyse CV

L'analyse actuelle est locale et simple:

- mots-cles de l'offre
- texte extrait du CV
- score de matching
- ponderation possible avec le quiz

Ce n'est pas encore:

- un modele IA externe
- un scoring LLM
- un moteur semantique avance

---

## Limites actuelles

- le sourcing LinkedIn prive n'est pas implemente
- l'analyse NLP reste heuristique
- l'edition manuelle d'un quiz existant n'est pas encore disponible
- le quiz manuel est base sur QCM
- l'envoi automatique d'emails entretien n'est pas encore branche

---

## Evolutions recommandees

1. Ajouter l'edition d'un quiz existant.
2. Ajouter un tableau admin dedie au classement des CV.
3. Ajouter notifications email pour quiz et entretien.
4. Ajouter analyse IA avancee via API externe.
5. Ajouter import legal de profils depuis CSV ou API autorisee.

