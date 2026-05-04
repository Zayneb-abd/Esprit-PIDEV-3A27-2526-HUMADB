<?php

namespace App\Service;

use App\Entity\JourFerie;
use App\Repository\JourFerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JourFerieService
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $em;
    private JourFerieRepository $repository;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $em,
        JourFerieRepository $repository
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->repository = $repository;
    }

    /**
     * Récupère les jours fériés depuis l'API Nager.Date
     */
    public function fetchJoursFeriesFromAPI(int $annee, string $countryCode = 'TN'): array
    {
        try {
            $url = "https://date.nager.at/api/v3/publicholidays/{$annee}/{$countryCode}";
            
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
            
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Synchronise les jours fériés en base de données
     */
    public function syncJoursFeries(int $annee, string $countryCode = 'TN'): array
    {
        $joursFeries = $this->fetchJoursFeriesFromAPI($annee, $countryCode);
        $result = [
            'added' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($joursFeries as $jour) {
            try {
                // Vérifie si le jour existe déjà
                $date = new \DateTime($jour['date']);
                $exists = $this->repository->findOneBy([
                    'date' => $date,
                    'pays' => $countryCode
                ]);

                if ($exists) {
                    $result['skipped']++;
                    continue;
                }

                // Créer le nouveau jour férié
                $jourFerie = new JourFerie();
                $jourFerie->setNom($this->translateHolidayName($jour['name']));
                $jourFerie->setDate($date);
                $jourFerie->setPays($countryCode);
                $jourFerie->setAnnee($annee);
                
                // Détermine le type (fixe ou variable)
                $type = $this->determineType($jour['name']);
                $jourFerie->setType($type);

                $this->em->persist($jourFerie);
                $result['added']++;
            } catch (\Exception $e) {
                $result['errors'][] = $jour['name'] . ': ' . $e->getMessage();
            }
        }

        $this->em->flush();
        return $result;
    }

    /**
     * Récupère les jours fériés pour une période
     */
    public function getJoursFeriesForPeriod(\DateTime $start, \DateTime $end, string $countryCode = 'TN'): array
    {
        return $this->repository->createQueryBuilder('j')
            ->where('j.date >= :start')
            ->andWhere('j.date <= :end')
            ->andWhere('j.pays = :pays')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('pays', $countryCode)
            ->orderBy('j.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Traduit les noms des fêtes en français
     */
    private function translateHolidayName(string $name): string
    {
        $translations = [
            'New Year' => 'Jour de l\'An',
            'Independence Day' => 'Fête de l\'Indépendance',
            "Youth Day" => 'Fête de la Jeunesse',
            'Martyrs\' Day' => 'Journée des Martyrs',
            'Eid al-Fitr' => 'Aïd El Fitr',
            'Eid al-Adha' => 'Aïd El Adha',
            'Republic Day' => 'Fête de la République',
            'Women\'s Day' => 'Journée de la Femme',
            'Labour Day' => 'Fête du Travail',
            'Evacuation Day' => 'Journée de l\'Évacuation',
            'Islamic New Year' => 'Nouvel An Hégire',
            'Prophet\'s Birthday' => 'Mawlid',
        ];

        return $translations[$name] ?? $name;
    }

    /**
     * Détermine si le jour férié est fixe ou variable
     */
    private function determineType(string $name): string
    {
        $variableHolidays = [
            'Eid al-Fitr', 'Eid al-Adha', 'Islamic New Year', 'Prophet\'s Birthday',
            'Aïd El Fitr', 'Aïd El Adha', 'Nouvel An Hégire', 'Mawlid'
        ];

        return in_array($name, $variableHolidays) ? 'variable' : 'fixe';
    }
}
