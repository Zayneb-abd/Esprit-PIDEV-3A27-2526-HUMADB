# READMETest

Ce document resume les principaux ajouts realises sur les 4 derniers jours dans le module recrutement et dans les outils de qualite.

## 1. Entites metier mises a jour

### `OffreEmploi`
- Ajout de contraintes de validation Symfony sur les champs principaux.
- Amelioration de la coherence Doctrine entre l'offre, les candidatures et les quiz.
- Correction des methodes `add/remove` pour synchroniser les relations bidirectionnelles.

### `Candidature`
- Ajout de contraintes de validation sur la date, le statut, le CV, le candidat et l'offre.
- Correction des relations Doctrine avec `Entretien` et `OffreEmploi`.
- Meilleure coherence entre les objets PHP et la base de donnees.

### `ReactionPublication`
- Correction du mapping Doctrine avec `Publication`.
- Passage de la relation en `OneToOne` pour aligner les deux cotes de la relation.

## 2. Tests unitaires ajoutes

### Tests sur les entites
- `OffreEmploiTest`
- `CandidatureTest`

Ces tests verifient que les objets valides passent la validation et que les donnees invalides sont rejetes.

### Tests sur les services
- `OfferDeletionServiceTest`
- `PythonCvRankingServiceTest`
- `CvMatchingServiceTest`

Ces tests verifient :
- la suppression complete d'une offre avec ses relations
- le classement des CV avec le moteur Python
- l'integration du classement dans le service PHP principal

## 3. Machine learning Python pour classer les CV

### Nouveau script Python
- Fichier : `python/cv_ranker.py`
- Role : comparer le texte de l'offre avec les CV candidats.
- Methode : extraction de mots utiles, calcul de similarite et attribution d'un score.

### Service PHP de pont
- Fichier : `src/Service/PythonCvRankingService.php`
- Role : lancer le script Python, recuperer le JSON de sortie et normaliser les resultats.

### Integration dans le classement
- Fichier : `src/Service/CvMatchingService.php`
- Role : utiliser le score Python pour classer les candidatures de la meilleure a la moins pertinente.

### Affichage dans l'admin
- Fichier : `templates/admin/offre_emploi/show.html.twig`
- Ajout du badge `Meilleur CV`.
- Affichage du mode d'analyse `ML Python` ou `ML Python + IA externe`.

## 4. Qualite code et verification

### PHPStan
- Ajout du fichier `phpstan.neon.dist`.
- Ajout du script `composer phpstan`.
- Verification statique du perimetre recrutement.

### PHPUnit
- Ajout du script `composer test`.
- Execution des tests sur les entites et les services.

### Doctrine
- Validation du mapping et du schema.
- Correction d'un probleme de coherence Doctrine detecte par `doctrine:schema:validate`.

## 5. Rapport de performance

Un document de rapport de performance a ete complete avec :
- captures d'ecran
- resultat PHPStan
- resultats PHPUnit
- validation Doctrine
- mesure de performance et de memoire

## 6. Impact global

Ces ajouts rendent le module recrutement :
- plus robuste
- mieux teste
- plus facile a verifier
- capable de classer automatiquement les CV
- plus lisible dans l'interface admin

## 7. Commandes utiles

### Tests unitaires
```bash
vendor/bin/phpunit
```

### Analyse statique
```bash
vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress
```

### Validation Doctrine
```bash
php bin/console doctrine:schema:validate
```

### Mesure du temps de reponse
```bash
curl -o /dev/null -s -w 'Temps: %{time_starttransfer}\n' http://127.0.0.1:8000/
```

### Mesure de memoire
```bash
php <<'PHP'
<?php
echo 'Utilisation mémoire max: '.number_format(memory_get_peak_usage(true) / 1024 / 1024, 2, ',', '')." MB\n";
PHP
```

