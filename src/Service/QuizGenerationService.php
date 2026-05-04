<?php

namespace App\Service;

use App\Entity\OffreEmploi;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\ReponseQcm;
use Doctrine\ORM\EntityManagerInterface;

class QuizGenerationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function generateForOffer(OffreEmploi $offre, bool $replaceExisting = false): Quiz
    {
        $existingQuiz = null;
        foreach ($offre->getQuizs() as $quiz) {
            $existingQuiz = $quiz;
            break;
        }

        if ($existingQuiz instanceof Quiz) {
            if (!$replaceExisting) {
                return $existingQuiz;
            }

            if ($existingQuiz->getResultatQuizs()->count() > 0) {
                throw new \RuntimeException('Impossible de regenerer ce quiz car des candidats l\'ont deja passe.');
            }

            foreach ($existingQuiz->getQuestions()->toArray() as $question) {
                foreach ($question->getReponseQcms()->toArray() as $reponse) {
                    $this->entityManager->remove($reponse);
                }
                $this->entityManager->remove($question);
            }

            $this->entityManager->remove($existingQuiz);
            $this->entityManager->flush();
        }

        $quiz = new Quiz();
        $quiz->setOffreEmploi($offre);
        $quiz->setTitre(sprintf('Quiz - %s', $offre->getTitre() ?? 'Offre'));
        $quiz->setDureeMinutes(12);
        $quiz->setSeuilPourcentage(70);

        $this->entityManager->persist($quiz);

        foreach ($this->buildQuestionBlueprints($offre) as $blueprint) {
            $question = new Question();
            $question->setQuiz($quiz);
            $question->setQuestionText($blueprint['question']);
            $question->setType('QCM');
            $question->setPoints($blueprint['points']);
            $this->entityManager->persist($question);

            foreach ($blueprint['answers'] as $index => $answerText) {
                $answer = new ReponseQcm();
                $answer->setQuestion($question);
                $answer->setTexte($answerText);
                $answer->setCorrecte($index === $blueprint['correct_index']);
                $this->entityManager->persist($answer);
            }
        }

        $this->entityManager->flush();

        return $quiz;
    }

    /**
     * @return array<int, array{question:string,answers:string[],correct_index:int,points:int}>
     */
    private function buildQuestionBlueprints(OffreEmploi $offre): array
    {
        $title = trim((string) ($offre->getTitre() ?? 'ce poste'));
        $department = trim((string) ($offre->getDepartement() ?? 'Ressources humaines'));
        $contract = trim((string) ($offre->getTypeContrat() ?? 'CDI'));
        $description = trim((string) ($offre->getDescription() ?? ''));

        $skills = $this->extractKeywords($description);
        $primarySkill = $skills[0] ?? 'communication';
        $secondarySkill = $skills[1] ?? 'organisation';
        $thirdSkill = $skills[2] ?? 'analyse';

        $summary = $this->extractSummary($description);

        return [
            [
                'question' => sprintf('Dans quel departement s\'inscrit principalement l\'offre "%s" ?', $title),
                ...$this->shuffleAnswers([$department, 'Finance', 'Marketing', 'Support client']),
                'points' => 1,
            ],
            [
                'question' => sprintf('Quel type de contrat est propose pour le poste "%s" ?', $title),
                ...$this->shuffleAnswers([$contract, 'Freelance', 'Stage', 'Alternance']),
                'points' => 1,
            ],
            [
                'question' => sprintf('Quelle competence semble la plus importante pour reussir sur le poste "%s" ?', $title),
                ...$this->shuffleAnswers([$primarySkill, 'Peinture decorative', 'Navigation maritime', 'Archivage papier']),
                'points' => 2,
            ],
            [
                'question' => 'Quel enonce resume le mieux la mission de ce poste ?',
                ...$this->shuffleAnswers([
                    $summary,
                    'Gerer uniquement la maintenance physique des locaux sans lien avec l\'equipe.',
                    'Piloter un restaurant interne a temps plein.',
                    'Se concentrer uniquement sur des taches sans rapport avec l\'offre.',
                ]),
                'points' => 3,
            ],
            [
                'question' => sprintf('Parmi ces choix, quelle combinaison est la plus coherente avec l\'offre "%s" ?', $title),
                ...$this->shuffleAnswers([
                    sprintf('%s, %s et %s', ucfirst($primarySkill), $secondarySkill, $thirdSkill),
                    'Livraison a domicile, couture et chant lyrique',
                    'Forage petrolier, menuiserie et natation',
                    'Pilotage de drone de loisir, jardinage et menage evenementiel',
                ]),
                'points' => 3,
            ],
        ];
    }

    /**
     * @param string[] $answers
     * @return array{answers:string[],correct_index:int}
     */
    private function shuffleAnswers(array $answers): array
    {
        $correct = $answers[0];
        $shuffled = $answers;
        shuffle($shuffled);

        return [
            'answers' => $shuffled,
            'correct_index' => array_search($correct, $shuffled, true),
        ];
    }

    /**
     * @return string[]
     */
    private function extractKeywords(string $description): array
    {
        $normalized = mb_strtolower($description);
        $normalized = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $normalized) ?? '';
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopWords = [
            'avec', 'dans', 'pour', 'vous', 'nous', 'une', 'des', 'les', 'sur', 'par', 'est', 'etre',
            'sera', 'plus', 'afin', 'tout', 'tous', 'cette', 'poste', 'offre', 'emploi', 'notre', 'votre',
            'leur', 'elle', 'il', 'aux', 'ses', 'son', 'qui', 'que', 'quoi', 'dont', 'faire', 'avoir',
            'comme', 'entre', 'ainsi', 'chez', 'mais', 'donc', 'or', 'ni', 'car', 'the', 'and',
        ];

        $counts = [];
        foreach ($words as $word) {
            if (mb_strlen($word) < 4 || in_array($word, $stopWords, true)) {
                continue;
            }

            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, 6);
    }

    private function extractSummary(string $description): string
    {
        $sentences = preg_split('/(?<=[\.\!\?])\s+/u', trim($description), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($sentences !== []) {
            $summary = trim($sentences[0]);

            if (mb_strlen($summary) > 140) {
                return mb_substr($summary, 0, 137).'...';
            }

            return $summary;
        }

        return 'Contribuer activement aux missions principales de l\'offre et collaborer avec l\'equipe concernee.';
    }
}
