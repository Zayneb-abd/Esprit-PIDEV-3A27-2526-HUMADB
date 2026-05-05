# 🤖 Machine Learning - Gestion Congés/Absences

## Installation

### 1. Installer Python (si pas déjà installé)
```bash
# Vérifier l'installation
python --version
```

### 2. Installer les dépendances
```bash
cd ml/
pip install -r requirements.txt
```

### 3. Tester le script ML
```bash
# Test prédiction congé (remplacer 1 par un user_id existant)
python predict_conge.py 1

# Test analyse sentiment
python sentiment_analysis.py "Le système de congés fonctionne bien, merci!"
```

## Utilisation dans Symfony

### 1. Accéder au Dashboard ML
```
http://127.0.0.1:8000/ml/dashboard
```

### 2. API JSON
```bash
curl http://127.0.0.1:8000/ml/api/predict/1
```

## Fonctionnalités ML Implémentées

### ✅ 1. Prédiction des Congés
- **Algorithme**: Random Forest Classifier
- **Données**: Historique des congés (mois, jour, durée)
- **Sortie**: Probabilité de congé pour le mois prochain

### ✅ 2. Détection d'Anomalies
- **Pattern**: Absences fréquentes en début/fin de semaine
- **Seuil**: 60% des absences = anomalie
- **Action**: Alerte manager

### ✅ 3. Suggestion de Périodes
- **Contraintes**: Charge de l'équipe, jours fériés
- **Optimisation**: Éviter Juillet-Août et Décembre

### ✅ 4. Analyse de Sentiment (Bonus)
- **NLP**: Mots clés positifs/négatifs
- **Thèmes**: Congé, Planning, Validation, Technique
- **Action**: Recommandation automatique

## Pour le Rapport Pidev

### Captures à faire:
1. Dashboard ML avec prédictions
2. Résultat Python en terminal
3. Graphiques de distribution des congés
4. Détection d'anomalie avec score

### Explications techniques:
- **Scikit-learn**: Bibliothèque ML choisie (simple, efficace)
- **Random Forest**: Algorithme robuste pour classification
- **Fallback**: Si Python indisponible → prédiction par saisonnalité
- **Intégration**: Appel shell_exec() depuis PHP

## Architecture

```
src/Service/MLPredictionService.php  →  Service Symfony
ml/predict_conge.py                  →  Script Python ML
templates/ml/dashboard.html.twig     →  Interface utilisateur
```

## Limitations & Améliorations

### Actuel:
- ✅ Prédiction basique avec historique
- ✅ Détection d'anomalies simple
- ✅ Fallback si ML indisponible

### Possible:
- 🔄 Deep Learning avec TensorFlow
- 🔄 API externe (OpenAI GPT)
- 🔄 Prédiction météo + congés
- 🔄 Classification automatique des absences
