#!/usr/bin/env python3
"""
Analyse de sentiment des feedbacks employés
Détecte le niveau de satisfaction et les problèmes récurrents
"""

import sys
import json
import re
from typing import Dict, List, Tuple

class SimpleSentimentAnalyzer:
    """Analyseur de sentiment basique pour feedbacks en français"""
    
    # Mots positifs/négatifs pour scoring
    POSITIVE_WORDS = [
        'bien', 'bon', 'excellent', 'super', 'parfait', 'satisfait', 'content',
        'heureux', 'merci', 'top', 'génial', 'formidable', 'agréable',
        'efficace', 'rapide', 'facile', 'recommande'
    ]
    
    NEGATIVE_WORDS = [
        'mal', 'mauvais', 'horrible', 'déçu', 'problème', 'difficile',
        'lent', 'compliqué', 'erreur', 'bug', 'impossible', 'impatient',
        'insatisfait', 'mécontent', 'refusé', 'refus', 'bloqué'
    ]
    
    THEMES = {
        'conge': ['congé', 'vacances', 'absence', 'repos'],
        'planning': ['planning', 'calendrier', 'date', 'horaire'],
        'validation': ['validation', 'approuvé', 'refusé', 'manager', 'délai'],
        'technique': ['bug', 'erreur', 'connexion', 'application', 'site'],
        'rh': ['rh', 'ressources humaines', 'administration']
    }
    
    def analyze(self, text: str) -> Dict:
        """Analyse un texte de feedback"""
        text_lower = text.lower()
        
        # Scoring
        pos_count = sum(1 for word in self.POSITIVE_WORDS if word in text_lower)
        neg_count = sum(1 for word in self.NEGATIVE_WORDS if word in text_lower)
        
        total = pos_count + neg_count
        if total == 0:
            sentiment_score = 0.5  # Neutre
        else:
            sentiment_score = pos_count / total
        
        # Classification
        if sentiment_score > 0.6:
            sentiment = 'positif'
        elif sentiment_score < 0.4:
            sentiment = 'négatif'
        else:
            sentiment = 'neutre'
        
        # Détection des thèmes
        detected_themes = []
        for theme, keywords in self.THEMES.items():
            if any(kw in text_lower for kw in keywords):
                detected_themes.append(theme)
        
        # Urgence (mots clés)
        urgency_keywords = ['urgent', 'bloquant', 'critique', 'impossible', 'erreur']
        urgency_score = sum(2 if kw in text_lower else 0 for kw in urgency_keywords)
        
        return {
            'sentiment': sentiment,
            'sentiment_score': round(sentiment_score, 2),
            'confidence': min(total / 5, 1.0),  # Plus de mots clés = plus confiant
            'themes': detected_themes,
            'urgency_score': urgency_score,
            'is_urgent': urgency_score >= 2,
            'positive_words_found': pos_count,
            'negative_words_found': neg_count,
            'recommendation': self._generate_recommendation(sentiment, detected_themes)
        }
    
    def _generate_recommendation(self, sentiment: str, themes: List[str]) -> str:
        """Génère une recommandation actionnable"""
        if sentiment == 'négatif' and 'validation' in themes:
            return 'Action RH: Vérifier les délais de validation des congés'
        elif sentiment == 'négatif' and 'technique' in themes:
            return 'Action IT: Problème technique signalé sur la plateforme'
        elif sentiment == 'positif':
            return 'Aucune action requise - Feedback positif'
        else:
            return 'À surveiller - Aucune action immédiate'
    
    def batch_analyze(self, feedbacks: List[Dict]) -> Dict:
        """Analyse un lot de feedbacks et donne des statistiques"""
        results = []
        
        for fb in feedbacks:
            analysis = self.analyze(fb['content'])
            analysis['feedback_id'] = fb.get('id')
            analysis['date'] = fb.get('date')
            results.append(analysis)
        
        # Statistiques globales
        total = len(results)
        if total == 0:
            return {'error': 'Aucun feedback à analyser'}
        
        sentiments = [r['sentiment'] for r in results]
        
        return {
            'total_feedbacks': total,
            'sentiment_distribution': {
                'positif': sentiments.count('positif'),
                'neutre': sentiments.count('neutre'),
                'négatif': sentiments.count('négatif')
            },
            'average_score': round(sum(r['sentiment_score'] for r in results) / total, 2),
            'urgent_feedbacks': [r for r in results if r['is_urgent']],
            'theme_distribution': self._count_themes(results),
            'detailed_results': results
        }
    
    def _count_themes(self, results: List[Dict]) -> Dict:
        """Compte les thèmes les plus fréquents"""
        theme_counts = {}
        for r in results:
            for theme in r['themes']:
                theme_counts[theme] = theme_counts.get(theme, 0) + 1
        return dict(sorted(theme_counts.items(), key=lambda x: x[1], reverse=True))


def main():
    """Point d'entrée pour CLI"""
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python sentiment_analysis.py '<text>'"}))
        sys.exit(1)
    
    text = sys.argv[1]
    analyzer = SimpleSentimentAnalyzer()
    result = analyzer.analyze(text)
    
    print(json.dumps(result, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
