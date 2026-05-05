#!/usr/bin/env python3
import json
import math
import re
import sys
from collections import Counter

STOP_WORDS = {
    "avec", "pour", "dans", "vous", "nous", "leur", "elles", "ils", "une", "des", "les", "sur", "par",
    "est", "sont", "etre", "avoir", "plus", "moins", "this", "that", "from", "your", "will", "need",
    "and", "the", "aux", "ses", "nos", "vos", "notre", "votre", "entre", "dont", "afin", "tout", "tous",
    "toute", "toutes", "comme", "chez", "sans", "mais", "donc", "car", "offre", "emploi", "poste",
    "profil", "candidat", "candidature", "experience", "ans", "dun", "du", "de", "la",
    "le", "un", "en", "au", "ou", "et", "a", "to", "of", "in", "on", "for",
}


def tokenize(text: str) -> list[str]:
    normalized = text.lower()
    normalized = re.sub(r"[^a-z0-9\-\+\# ]+", " ", normalized)
    parts = re.split(r"\s+", normalized.strip()) if normalized.strip() else []
    tokens: list[str] = []

    for part in parts:
        if len(part) < 3:
            continue
        if part in STOP_WORDS:
            continue
        tokens.append(part)

    return tokens


def weighted_offer_text(payload: dict) -> str:
    title = str(payload.get("title", "")).strip()
    department = str(payload.get("department", "")).strip()
    contract = str(payload.get("contract", "")).strip()
    description = str(payload.get("description", "")).strip()

    segments = []
    if title:
        segments.extend([title, title, title])
    if department:
        segments.extend([department, department])
    if contract:
        segments.extend([contract, contract])
    if description:
        segments.append(description)

    return " ".join(segments)


def tfidf_vectors(documents: list[str]) -> tuple[list[dict[str, float]], list[str], dict[str, float]]:
    tokenized = [tokenize(doc) for doc in documents]
    vocab = sorted({token for doc in tokenized for token in doc})
    if not vocab:
        return ([{} for _ in documents], [], {})

    document_frequency: Counter[str] = Counter()
    for doc in tokenized:
        for token in set(doc):
            document_frequency[token] += 1

    total_docs = len(documents)
    idf = {
        token: math.log((1 + total_docs) / (1 + document_frequency[token])) + 1.0
        for token in vocab
    }

    vectors: list[dict[str, float]] = []
    for doc in tokenized:
        counts = Counter(doc)
        length = max(1, len(doc))
        vector = {
            token: (counts[token] / length) * idf[token]
            for token in counts
            if token in idf
        }
        norm = math.sqrt(sum(weight * weight for weight in vector.values()))
        if norm > 0:
            vector = {token: weight / norm for token, weight in vector.items()}
        vectors.append(vector)

    return vectors, vocab, idf


def cosine_similarity(left: dict[str, float], right: dict[str, float]) -> float:
    if not left or not right:
        return 0.0

    shared = set(left).intersection(right)
    return sum(left[token] * right[token] for token in shared)


def score_documents(payload: dict) -> dict:
    offer_text = weighted_offer_text(payload)
    candidate_texts = [str(item.get("text", "")) if isinstance(item, dict) else str(item) for item in payload.get("candidates", [])]
    documents = [offer_text, *candidate_texts]
    vectors, _, _ = tfidf_vectors(documents)

    offer_tokens = tokenize(offer_text)
    offer_unique = list(dict.fromkeys(offer_tokens))
    offer_set = set(offer_unique)

    rankings = []
    offer_vector = vectors[0] if vectors else {}

    for index, candidate_text in enumerate(candidate_texts):
        candidate_tokens = tokenize(candidate_text)
        candidate_vector = vectors[index + 1] if index + 1 < len(vectors) else {}

        similarity = cosine_similarity(offer_vector, candidate_vector)
        overlap = offer_set.intersection(candidate_tokens)
        coverage = (len(overlap) / len(offer_set)) if offer_set else 0.0
        score = int(round(max(0.0, min(1.0, (similarity * 0.75) + (coverage * 0.25))) * 100))

        rankings.append({
            "index": index,
            "score": score,
            "matched_terms": list(overlap)[:8],
            "missing_terms": [term for term in offer_unique if term not in overlap][:8],
            "similarity": round(similarity, 6),
        })

    rankings.sort(key=lambda item: (item["score"], item["similarity"]), reverse=True)
    return {"rankings": rankings}


def main() -> int:
    try:
        raw_input_data = sys.stdin.read()
        payload = json.loads(raw_input_data or "{}")
        result = score_documents(payload)
        json.dump(result, sys.stdout, ensure_ascii=False)
        return 0
    except Exception as exc:  # noqa: BLE001
        json.dump({"error": str(exc), "rankings": []}, sys.stdout, ensure_ascii=False)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
