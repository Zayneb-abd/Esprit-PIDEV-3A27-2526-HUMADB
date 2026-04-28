<?php

namespace App\Service;

use App\Entity\Quiz;

class QuizEvaluator
{
    /**
     * @param array<string, mixed> $submittedAnswers
     * @return array{score:float,correct:int,total:int,statut:string,maxPoints:int,earnedPoints:int}
     */
    public function evaluate(Quiz $quiz, array $submittedAnswers): array
    {
        $maxPoints = 0;
        $earnedPoints = 0;
        $correctAnswers = 0;
        $gradableQuestions = 0;

        foreach ($quiz->getQuestions() as $question) {
            if ($question->getType() !== 'QCM') {
                continue;
            }

            $gradableQuestions++;
            $questionPoints = max(1, (int) ($question->getPoints() ?? 1));
            $maxPoints += $questionPoints;

            $submittedId = isset($submittedAnswers[(string) $question->getId()]) ? (int) $submittedAnswers[(string) $question->getId()] : null;
            $correctResponse = null;

            foreach ($question->getReponseQcms() as $reponseQcm) {
                if ($reponseQcm->isCorrecte()) {
                    $correctResponse = $reponseQcm;
                    break;
                }
            }

            if ($correctResponse !== null && $submittedId === $correctResponse->getId()) {
                $correctAnswers++;
                $earnedPoints += $questionPoints;
            }
        }

        $score = $maxPoints > 0 ? round(($earnedPoints / $maxPoints) * 100, 2) : 0.0;
        $threshold = (int) ($quiz->getSeuilPourcentage() ?? 0);

        return [
            'score' => $score,
            'correct' => $correctAnswers,
            'total' => $gradableQuestions,
            'statut' => $score >= $threshold ? 'REUSSI' : 'ECHEC',
            'maxPoints' => $maxPoints,
            'earnedPoints' => $earnedPoints,
        ];
    }
}
