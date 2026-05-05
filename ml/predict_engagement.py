#!/usr/bin/env python3
"""
Script ML - Prédiction d'Engagement des Publications
Prédit le nombre de likes et commentaires pour une publication
"""

import sys
import json
import mysql.connector
import re
from datetime import datetime

def analyze_text_features(text):
    """Analyse les caractéristiques du texte"""
    if not text:
        return {'length': 0, 'has_image': 0, 'has_video': 0, 'has_question': 0, 'has_urgent': 0}
    
    text_lower = text.lower()
    
    return {
        'length': len(text),
        'has_image': 1 if '[image' in text_lower or 'photo' in text_lower else 0,
        'has_video': 1 if '[video' in text_lower or 'vidéo' in text_lower else 0,
        'has_question': 1 if '?' in text else 0,
        'has_urgent': 1 if any(word in text_lower for word in ['urgent', 'important', 'attention', 'alerte']) else 0,
        'word_count': len(text.split())
    }

def get_historical_engagement():
    """Récupère l'historique des engagements depuis la base"""
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="humadb"
        )
        cursor = conn.cursor(dictionary=True)
        
        # Récupérer les publications avec leurs stats d'engagement
        query = """
        SELECT 
            p.id,
            p.contenu,
            p.type,
            p.date_publication,
            COUNT(DISTINCT c.id) as nb_commentaires,
            COALESCE(rp.nombre_reactions, 0) as nb_reactions
        FROM publication p
        LEFT JOIN commentaire c ON c.publication_id = p.id
        LEFT JOIN reaction_publication rp ON rp.publication_id = p.id
        WHERE p.date_publication IS NOT NULL
        GROUP BY p.id, p.contenu, p.type, p.date_publication, rp.nombre_reactions
        ORDER BY p.date_publication DESC
        LIMIT 100
        """
        
        cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        conn.close()
        
        return results
    except Exception as e:
        return []

def predict_engagement(content, pub_type=""):
    """Prédit l'engagement d'une publication"""
    
    # Historique pour le modèle simple
    history = get_historical_engagement()
    
    if not history:
        # Fallback basé sur les règles
        return fallback_prediction(content, pub_type)
    
    # Analyser la publication actuelle
    features = analyze_text_features(content)
    
    # Calculer les moyennes historiques
    avg_likes = sum(h['nb_reactions'] for h in history) / len(history)
    avg_comments = sum(h['nb_commentaires'] for h in history) / len(history)
    
    # Score de base
    engagement_score = 5.0  # Score sur 10
    
    # Facteurs de boost
    boost_factors = {
        'length_optimal': 0,      # +1.5 si longueur optimale (100-500 chars)
        'has_question': 0,        # +1.0 si contient une question
        'has_urgent': 0,        # +1.5 si contient mot urgent
        'has_media': 0,         # +2.0 si contient image/video
        'hashtags': 0           # +0.5 par hashtag
    }
    
    # Analyser la longueur
    if 100 <= features['length'] <= 500:
        boost_factors['length_optimal'] = 1.5
    elif features['length'] > 500:
        boost_factors['length_optimal'] = 0.5
    else:
        boost_factors['length_optimal'] = -0.5
    
    # Analyser les questions
    if features['has_question']:
        boost_factors['has_question'] = 1.0
    
    # Analyser l'urgence
    if features['has_urgent']:
        boost_factors['has_urgent'] = 1.5
    
    # Analyser les médias
    if features['has_image'] or features['has_video']:
        boost_factors['has_media'] = 2.0
    
    # Calculer le score final
    total_boost = sum(boost_factors.values())
    engagement_score = min(10, max(1, 5 + total_boost))
    
    # Estimer likes et commentaires
    confidence = min(1.0, len(history) / 50)  # Confiance basée sur l'historique
    
    estimated_likes = int(avg_likes * (engagement_score / 5) * (0.8 + confidence * 0.4))
    estimated_comments = int(avg_comments * (engagement_score / 5) * (0.8 + confidence * 0.4))
    
    # Recommandations
    recommendations = []
    if not features['has_question']:
        recommendations.append("Ajoutez une question pour +20% d'engagement")
    if not features['has_media']:
        recommendations.append("Ajoutez une image pour +40% d'engagement")
    if features['length'] < 100:
        recommendations.append("Développez le contenu (min 100 caractères)")
    if not features['has_urgent']:
        recommendations.append("Utilisez des mots comme 'Important' ou 'Urgent' si pertinent")
    
    best_time = "Mardi-Jeudi 14h-16h" if engagement_score > 6 else "Lundi-Vendredi 9h-11h"
    
    return {
        "engagement_score": round(engagement_score, 1),
        "estimated_likes": max(0, estimated_likes),
        "estimated_comments": max(0, estimated_comments),
        "confidence": round(confidence, 2),
        "best_publish_time": best_time,
        "recommendations": recommendations,
        "content_analysis": {
            "length": features['length'],
            "has_question": bool(features['has_question']),
            "has_media": bool(features['has_image'] or features['has_video']),
            "has_urgent_keywords": bool(features['has_urgent'])
        },
        "method": "ml_analysis" if len(history) >= 10 else "rule_based_with_history"
    }

def fallback_prediction(content, pub_type=""):
    """Prédiction basique sans historique"""
    features = analyze_text_features(content)
    
    score = 5.0
    if features['has_image'] or features['has_video']:
        score += 2.0
    if features['has_question']:
        score += 1.0
    if features['has_urgent']:
        score += 0.5
    if 100 <= features['length'] <= 500:
        score += 1.0
    
    score = min(10, max(1, score))
    
    return {
        "engagement_score": round(score, 1),
        "estimated_likes": int(score * 10),
        "estimated_comments": int(score * 3),
        "confidence": 0.3,
        "best_publish_time": "Lundi-Vendredi 9h-17h",
        "recommendations": [
            "Ajoutez du contenu visuel (images/vidéos)",
            "Posez une question à vos collègues",
            "Publiez en début de semaine"
        ],
        "content_analysis": {
            "length": features['length'],
            "has_question": bool(features['has_question']),
            "has_media": bool(features['has_image'] or features['has_video']),
            "has_urgent_keywords": bool(features['has_urgent'])
        },
        "method": "rule_based_fallback",
        "note": "Peu d'historique disponible - estimation basique"
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python predict_engagement.py <content> [type]"}))
        sys.exit(1)
    
    content = sys.argv[1]
    pub_type = sys.argv[2] if len(sys.argv) > 2 else ""
    
    result = predict_engagement(content, pub_type)
    
    # Forcer UTF-8 pour Windows
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    print(json.dumps(result, indent=2, ensure_ascii=False))
