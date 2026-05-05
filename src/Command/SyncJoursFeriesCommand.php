<?php

namespace App\Command;

use App\Service\JourFerieService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-jours-feries',
    description: 'Synchronise les jours fériés tunisiens depuis l\'API'
)]
class SyncJoursFeriesCommand extends Command
{
    private JourFerieService $jourFerieService;

    public function __construct(JourFerieService $jourFerieService)
    {
        parent::__construct();
        $this->jourFerieService = $jourFerieService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('annee', InputArgument::OPTIONAL, 'Année à synchroniser (par défaut: année courante)', date('Y'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $annee = (int) $input->getArgument('annee');

        $io->title("🗓️  Synchronisation des jours fériés tunisiens pour l'année {$annee}");

        $result = $this->jourFerieService->syncJoursFeries($annee, 'TN');

        $io->success("Synchronisation terminée !");
        $io->text("- Ajoutés : {$result['added']}");
        $io->text("- Ignorés (déjà existants) : {$result['skipped']}");
        
        if (!empty($result['errors'])) {
            $io->warning('Erreurs :');
            foreach ($result['errors'] as $error) {
                $io->text("  - {$error}");
            }
        }

        return Command::SUCCESS;
    }
}
