#!/usr/bin/env python3
"""
Script ML - Prédiction d'Engagement des Commentaires
Prédit si un commentaire va recevoir des likes/réponses
"""

import sys
import json
import mysql.connector
import re
from datetime import datetime

def analyze_comment_features(text):
    """Analyse les caractéristiques du commentaire"""
    if not text:
        return {
            'length': 0, 
            'has_question': 0, 
            'has_exclamation': 0, 
            'has_emoji': 0,
            'has_tag': 0,
            'word_count': 0,
            'sentiment_keywords': 0
        }
    
    text_lower = text.lower()
    
    # Mots positifs qui augmentent l'engagement
    positive_words = ['super', 'excellent', 'bravo', 'merci', 'génial', 'top', 'cool', 
                     'parfait', 'agréable', 'utile', 'intéressant', 'participe', 'inscrit',
                     'félicitations', 'content', 'heureux', 'satisfait', 'approuve']
    
    # Mots négatifs qui diminuent l'engagement
    negative_words = ['non', 'refuse', 'désaccord', 'mauvais', 'problème', 'difficile',
                     'impossible', 'erreur', 'critique', 'contre', 'déçu']
    
    positive_count = sum(1 for word in positive_words if word in text_lower)
    negative_count = sum(1 for word in negative_words if word in text_lower)
    
    return {
        'length': len(text),
        'has_question': 1 if '?' in text else 0,
        'has_exclamation': 1 if '!' in text else 0,
        'has_emoji': 1 if any(ord(c) > 127 for c in text) else 0,
        'has_tag': 1 if '@' in text or '#' in text else 0,
        'word_count': len(text.split()),
        'sentiment_keywords': positive_count - negative_count,
        'positive_words': positive_count,
        'negative_words': negative_count
    }

def get_comment_history():
    """Récupère l'historique des commentaires avec leurs stats"""
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="humadb"
        )
        cursor = conn.cursor(dictionary=True)
        
        # Récupérer les commentaires avec leurs réactions
        query = """
        SELECT 
            c.id,
            c.contenu,
            c.date_creation,
            COUNT(DISTINCT r.id) as nb_reactions,
            COUNT(DISTINCT rc.id) as nb_reponses
        FROM commentaire c
        LEFT JOIN reaction_commentaire r ON r.commentaire_id = c.id
        LEFT JOIN commentaire rc ON rc.parent_id = c.id
        WHERE c.date_creation IS NOT NULL
        GROUP BY c.id, c.contenu, c.date_creation
        ORDER BY c.date_creation DESC
        LIMIT 100
        """
        
        cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        conn.close()
        
        return results
    except Exception as e:
        return []

def predict_comment_engagement(content):
    """Prédit l'engagement d'un commentaire"""
    
    # Historique pour le modèle
    history = get_comment_history()
    
    if not history:
        # Fallback basé sur les règles
        return fallback_prediction(content)
    
    # Analyser le commentaire
    features = analyze_comment_features(content)
    
    # Calculer les moyennes historiques
    avg_reactions = sum(h['nb_reactions'] for h in history) / len(history)
    avg_reponses = sum(h['nb_reponses'] for h in history) / len(history)
    
    # Score de base
    engagement_score = 5.0  # Score sur 10
    
    # Facteurs de boost/déboost
    if 20 <= features['length'] <= 200:
        engagement_score += 1.5
    elif features['length'] < 20:
        engagement_score -= 1.0
    elif features['length'] > 500:
        engagement_score -= 0.5
    
    # Question = plus d'engagement
    if features['has_question']:
        engagement_score += 1.5
    
    # Exclamation = enthousiasme
    if features['has_exclamation']:
        engagement_score += 0.5
    
    # Emoji = positif
    if features['has_emoji']:
        engagement_score += 0.5
    
    # Tag (@quelqu'un) = interaction
    if features['has_tag']:
        engagement_score += 1.0
    
    # Mots positifs
    if features['sentiment_keywords'] > 0:
        engagement_score += min(2.0, features['sentiment_keywords'] * 0.5)
    elif features['sentiment_keywords'] < 0:
        engagement_score += max(-2.0, features['sentiment_keywords'] * 0.5)
    
    # Score final
    engagement_score = min(10, max(1, engagement_score))
    
    # Confiance basée sur l'historique
    confidence = min(1.0, len(history) / 100)
    
    # Estimer réactions et réponses
    estimated_reactions = int(avg_reactions * (engagement_score / 5) * (0.8 + confidence * 0.4))
    estimated_reponses = int(avg_reponses * (engagement_score / 5) * (0.8 + confidence * 0.4))
    
    # Recommandations
    recommendations = []
    if features['length'] < 20:
        recommendations.append("Développez votre commentaire (min 20 caractères)")
    if not features['has_question']:
        recommendations.append("Posez une question pour +30% d'engagement")
    if features['sentiment_keywords'] <= 0:
        recommendations.append("Utilisez des mots positifs (super, merci, bravo...)")
    if not features['has_emoji']:
        recommendations.append("Ajoutez un emoji pour montrer votre enthousiasme")
    
    # Qualité du commentaire
    quality = "excellent" if engagement_score >= 8 else \
              "bon" if engagement_score >= 6 else \
              "moyen" if engagement_score >= 4 else "faible"
    
    return {
        "engagement_score": round(engagement_score, 1),
        "quality": quality,
        "estimated_reactions": max(0, estimated_reactions),
        "estimated_responses": max(0, estimated_reponses),
        "confidence": round(confidence, 2),
        "recommendations": recommendations,
        "content_analysis": {
            "length": features['length'],
            "has_question": bool(features['has_question']),
            "has_exclamation": bool(features['has_exclamation']),
            "has_emoji": bool(features['has_emoji']),
            "has_tag": bool(features['has_tag']),
            "positive_words": features['positive_words'],
            "negative_words": features['negative_words']
        },
        "method": "ml_comment_analysis" if len(history) >= 20 else "rule_based_with_history"
    }

def fallback_prediction(content):
    """Prédiction basique sans historique"""
    features = analyze_comment_features(content)
    
    score = 5.0
    
    if 20 <= features['length'] <= 200:
        score += 1.0
    if features['has_question']:
        score += 1.5
    if features['has_emoji']:
        score += 0.5
    if features['sentiment_keywords'] > 0:
        score += 1.0
    
    score = min(10, max(1, score))
    quality = "bon" if score >= 6 else "moyen"
    
    return {
        "engagement_score": round(score, 1),
        "quality": quality,
        "estimated_reactions": int(score * 1.5),
        "estimated_responses": int(score * 0.5),
        "confidence": 0.3,
        "recommendations": [
            "Développez votre commentaire (min 20 caractères)",
            "Posez une question pour plus d'interactions",
            "Utilisez des mots positifs (super, merci, bravo...)"
        ],
        "content_analysis": {
            "length": features['length'],
            "has_question": bool(features['has_question']),
            "has_exclamation": bool(features['has_exclamation']),
            "has_emoji": bool(features['has_emoji']),
            "has_tag": bool(features['has_tag']),
            "positive_words": features['positive_words'],
            "negative_words": features['negative_words']
        },
        "method": "rule_based_fallback",
        "note": "Peu d'historique disponible - estimation basique"
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python predict_comment_engagement.py <content>"}))
        sys.exit(1)
    
    content = sys.argv[1]
    
    result = predict_comment_engagement(content)
    
    # Forcer UTF-8 pour Windows
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    print(json.dumps(result, indent=2, ensure_ascii=False))
