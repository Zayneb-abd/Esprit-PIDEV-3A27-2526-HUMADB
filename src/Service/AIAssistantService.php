<?php

namespace App\Service;

use App\Entity\Conge;
use App\Entity\User;
use App\Repository\CongeRepository;
use App\Repository\UserRepository;
use App\Repository\JourFerieRepository;
use Doctrine\ORM\EntityManagerInterface;

class AIAssistantService
{
    private CongeRepository $congeRepository;
    private UserRepository $userRepository;
    private JourFerieService $jourFerieService;
    private EntityManagerInterface $em;

    public function __construct(
        CongeRepository $congeRepository,
        UserRepository $userRepository,
        JourFerieService $jourFerieService,
        EntityManagerInterface $em
    ) {
        $this->congeRepository = $congeRepository;
        $this->userRepository = $userRepository;
        $this->jourFerieService = $jourFerieService;
        $this->em = $em;
    }

    /**
     * Parse une demande en langage naturel et génère une proposition de congé
     */
    public function parseRequest(string $message, User $user): array
    {
        $message = strtolower(trim($message));
        
        // Détection du type de congé
        $typeConge = $this->detectTypeConge($message);
        
        // Extraction des dates
        $dates = $this->extractDates($message);
        
        // Extraction du nombre de jours
        $nbJours = $this->extractNbJours($message);
        
        // Si pas de dates spécifiques mais nombre de jours, calculer les dates
        if (empty($dates) && $nbJours > 0) {
            $dates = $this->suggestDates($nbJours, $user);
        }
        
        // Vérifier les conflits
        $conflicts = $this->checkConflicts($dates, $user);
        
        // Vérifier le solde
        $solde = $this->checkSolde($user, $nbJours);
        
        return [
            'success' => true,
            'type_conge' => $typeConge,
            'dates' => $dates,
            'nb_jours' => $nbJours,
            'conflicts' => $conflicts,
            'solde_ok' => $solde,
            'suggestion' => $this->generateSuggestion($dates, $conflicts, $solde, $typeConge),
            'can_create' => $solde && empty($conflicts['critical'])
        ];
    }

    /**
     * Détecte le type de congé demandé
     */
    private function detectTypeConge(string $message): string
    {
        $patterns = [
            'conge_annuel' => ['/congé annuel/', '/conge payé/', '/vacances/'],
            'conge_maladie' => ['/maladie/', '/sick/', '/docteur/', '/medecin/'],
            'conge_familial' => ['/familial/', '/naissance/', '/mariage/', '/décès/', '/deces/'],
            'conge_exceptionnel' => ['/exceptionnel/', '/urgence/', '/imprévu/', '/imprevu/'],
            'conge_sans_solde' => ['/sans solde/', '/unpaid/', '/non payé/'],
        ];

        foreach ($patterns as $type => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $message)) {
                    return $type;
                }
            }
        }

        return 'conge_annuel'; // Par défaut
    }

    /**
     * Extrait les dates du message
     */
    private function extractDates(string $message): array
    {
        $dates = [];
        
        // Pattern: "du 15/04 au 20/04" ou "du 15-04 au 20-04"
        if (preg_match('/du\s+(\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{2,4})?)\s+au?\s+(\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{2,4})?)/i', $message, $matches)) {
            $dates = [
                'debut' => $this->parseDate($matches[1]),
                'fin' => $this->parseDate($matches[2])
            ];
        }
        // Pattern: "à partir du 15/04 pour 3 jours"
        elseif (preg_match('/(?:à partir du|apartir du|a partir du)\s+(\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{2,4})?)/i', $message, $matches)) {
            $debut = $this->parseDate($matches[1]);
            $nbJours = $this->extractNbJours($message);
            if ($debut && $nbJours > 0) {
                $fin = clone $debut;
                $fin->modify('+' . ($nbJours - 1) . ' days');
                $dates = [
                    'debut' => $debut,
                    'fin' => $fin
                ];
            }
        }
        // Pattern: "la semaine prochaine"
        elseif (preg_match('/semaine prochaine/', $message)) {
            $dates = $this->getNextWeekDates();
        }
        // Pattern: "demain" ou "après-demain"
        elseif (preg_match('/demain/', $message)) {
            $debut = new \DateTime('tomorrow');
            $nbJours = $this->extractNbJours($message) ?: 1;
            $fin = clone $debut;
            if ($nbJours > 1) {
                $fin->modify('+' . ($nbJours - 1) . ' days');
            }
            $dates = ['debut' => $debut, 'fin' => $fin];
        }
        
        return $dates;
    }

    /**
     * Parse une date string en DateTime
     */
    private function parseDate(string $dateStr): ?\DateTime
    {
        $dateStr = str_replace('-', '/', $dateStr);
        
        // Essayer différents formats
        $formats = ['d/m/Y', 'd/m/y', 'j/n/Y', 'j/n/y'];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date !== false) {
                // Si année sur 2 chiffres, ajuster
                $year = (int)$date->format('Y');
                if ($year < 100) {
                    $year += ($year < 50) ? 2000 : 1900;
                    $date->setDate($year, (int)$date->format('m'), (int)$date->format('d'));
                }
                return $date;
            }
        }
        
        return null;
    }

    /**
     * Extrait le nombre de jours du message
     */
    private function extractNbJours(string $message): int
    {
        // Pattern: "3 jours", "trois jours"
        if (preg_match('/(\d+)\s*jours?/', $message, $matches)) {
            return (int)$matches[1];
        }
        
        // Mots en français
        $words = [
            'un' => 1, 'deux' => 2, 'trois' => 3, 'quatre' => 4, 'cinq' => 5,
            'six' => 6, 'sept' => 7, 'huit' => 8, 'neuf' => 9, 'dix' => 10
        ];
        
        foreach ($words as $word => $number) {
            if (preg_match('/\b' . $word . '\b.*jours?/', $message)) {
                return $number;
            }
        }
        
        return 0;
    }

    /**
     * Suggère des dates en fonction du nombre de jours
     */
    private function suggestDates(int $nbJours, User $user): array
    {
        $debut = new \DateTime('next monday');
        $fin = clone $debut;
        $fin->modify('+' . ($nbJours - 1) . ' days');
        
        return [
            'debut' => $debut,
            'fin' => $fin,
            'suggestion' => true
        ];
    }

    /**
     * Retourne les dates de la semaine prochaine
     */
    private function getNextWeekDates(): array
    {
        $debut = new \DateTime('next monday');
        $fin = clone $debut;
        $fin->modify('+4 days'); // Vendredi
        
        return [
            'debut' => $debut,
            'fin' => $fin,
            'suggestion' => true
        ];
    }

    /**
     * Vérifie les conflits d'équipe
     */
    private function checkConflicts(array $dates, User $user): array
    {
        if (empty($dates['debut']) || empty($dates['fin'])) {
            return [];
        }

        $conflicts = [
            'warning' => [],
            'critical' => []
        ];

        // Vérifier si manager
        if ($user->getRole() === 'MANAGER') {
            $teamMembers = $this->userRepository->findBy(['user' => $user, 'role' => 'EMPLOYE']);
            $teamSize = count($teamMembers) + 1; // +1 pour le manager

            // Compter les absents sur la période
            $currentDate = clone $dates['debut'];
            while ($currentDate <= $dates['fin']) {
                $absents = $this->countAbsentMembers($teamMembers, $currentDate, $user);
                $percentage = ($absents / $teamSize) * 100;

                if ($percentage >= 50) {
                    $conflicts['critical'][] = [
                        'date' => $currentDate->format('d/m/Y'),
                        'absents' => $absents,
                        'total' => $teamSize,
                        'percentage' => round($percentage, 1)
                    ];
                } elseif ($percentage >= 30) {
                    $conflicts['warning'][] = [
                        'date' => $currentDate->format('d/m/Y'),
                        'absents' => $absents,
                        'total' => $teamSize,
                        'percentage' => round($percentage, 1)
                    ];
                }

                $currentDate->modify('+1 day');
            }
        }

        return $conflicts;
    }

    /**
     * Compte les membres absents à une date donnée
     */
    private function countAbsentMembers(array $teamMembers, \DateTime $date, User $manager): int
    {
        $count = 0;
        
        foreach ($teamMembers as $member) {
            $conges = $this->congeRepository->findBy(['user' => $member]);
            foreach ($conges as $conge) {
                $absence = $conge->getAbsence();
                if ($absence && $absence->getStatut() === 'approuvé') {
                    if ($date >= $absence->getDateDebut() && $date <= $absence->getDateFin()) {
                        $count++;
                        break;
                    }
                }
            }
        }
        
        // Vérifier aussi si le manager a déjà un congé
        $managerConges = $this->congeRepository->findBy(['user' => $manager]);
        foreach ($managerConges as $conge) {
            $absence = $conge->getAbsence();
            if ($absence && $absence->getStatut() === 'approuvé') {
                if ($date >= $absence->getDateDebut() && $date <= $absence->getDateFin()) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    /**
     * Vérifie si le solde de congés est suffisant
     */
    private function checkSolde(User $user, int $nbJours): bool
    {
        // Pour l'instant, on suppose que l'utilisateur a toujours assez de jours
        // Tu peux ajouter une logique de solde ici
        return $nbJours <= 30; // Max 30 jours
    }

    /**
     * Génère une suggestion textuelle
     */
    private function generateSuggestion(array $dates, array $conflicts, bool $soldeOk, string $typeConge): string
    {
        $typeLabels = [
            'conge_annuel' => 'congé annuel',
            'conge_maladie' => 'congé maladie',
            'conge_familial' => 'congé familial',
            'conge_exceptionnel' => 'congé exceptionnel',
            'conge_sans_solde' => 'congé sans solde'
        ];

        $label = $typeLabels[$typeConge] ?? 'congé';
        
        if (empty($dates)) {
            return "Je n'ai pas compris les dates. Pouvez-vous préciser ? (ex: 'du 15/04 au 20/04')";
        }

        $debut = $dates['debut']->format('d/m/Y');
        $fin = $dates['fin']->format('d/m/Y');
        $isSuggestion = isset($dates['suggestion']) ? ' (suggéré)' : '';

        $suggestion = "🤖 J'ai compris : **{$label}** du **{$debut}** au **{$fin}**{$isSuggestion}.\n\n";

        // Vérifier solde
        if (!$soldeOk) {
            $suggestion .= "⚠️ **Attention** : Votre solde de congés semble insuffisant.\n\n";
        } else {
            $suggestion .= "✅ **Solde** : Vous avez suffisamment de jours de congé.\n\n";
        }

        // Vérifier conflits
        if (!empty($conflicts['critical'])) {
            $suggestion .= "🚨 **CONFLITS CRITIQUES** :\n";
            foreach ($conflicts['critical'] as $conflict) {
                $suggestion .= "  • Le {$conflict['date']} : {$conflict['absents']}/{$conflict['total']} absents ({$conflict['percentage']}%)\n";
            }
            $suggestion .= "\n💡 **Je vous suggère de choisir d'autres dates ou de demander l'accord de votre hiérarchie.**\n";
        } elseif (!empty($conflicts['warning'])) {
            $suggestion .= "⚠️ **Attention** : Certains jours ont beaucoup d'absents :\n";
            foreach ($conflicts['warning'] as $conflict) {
                $suggestion .= "  • Le {$conflict['date']} : {$conflict['absents']}/{$conflict['total']} absents ({$conflict['percentage']}%)\n";
            }
            $suggestion .= "\n";
        } else {
            $suggestion .= "✅ **Aucun conflit** détecté avec votre équipe.\n\n";
        }

        if ($soldeOk && empty($conflicts['critical'])) {
            $suggestion .= "📝 **Je peux créer cette demande pour vous.** Confirmez-vous ?";
        } else {
            $suggestion .= "❌ **Je ne peux pas créer cette demande automatiquement.** Veuillez vérifier les points ci-dessus.";
        }

        return $suggestion;
    }

    /**
     * Crée automatiquement une demande de congé
     */
    public function createConge(array $parsedRequest, User $user): ?Conge
    {
        if (!$parsedRequest['can_create']) {
            return null;
        }

        $dateDebut = $this->normalizeDateValue($parsedRequest['dates']['debut'] ?? null);
        $dateFin = $this->normalizeDateValue($parsedRequest['dates']['fin'] ?? null);
        if ($dateDebut === null || $dateFin === null) {
            return null;
        }

        $conge = new Conge();
        $conge->setUser($user);
        $conge->setCommentaireValidation('Demande créée par l\'assistant IA');
        $conge->setDateDemande(new \DateTime());
        
        // Créer l'absence associée
        $absence = new \App\Entity\Absence();
        $absence->setUser($user);
        $absence->setDateDebut($dateDebut);
        $absence->setDateFin($dateFin);
        $absence->setStatut('en_attente');
        $absence->setTypeAbsence($this->mapAssistantTypeToAbsenceType((string) ($parsedRequest['type_conge'] ?? 'conge_annuel')));
        
        $conge->setAbsence($absence);

        $this->em->persist($absence);
        $this->em->persist($conge);
        $this->em->flush();

        return $conge;
    }

    private function mapAssistantTypeToAbsenceType(string $typeConge): string
    {
        return match ($typeConge) {
            'conge_maladie' => 'MALADIE',
            'conge_sans_solde' => 'CONGE_SANS_SOLDE',
            'conge_familial', 'conge_exceptionnel' => 'AUTRE',
            default => 'CONGE_PAYE',
        };
    }

    /**
     * @param mixed $value
     */
    private function normalizeDateValue(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
