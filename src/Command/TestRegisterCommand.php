<?php
namespace App\Command;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:test-register')]
class TestRegisterCommand extends Command
{
    private $em;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setPrenom("Jane");
        $user->setNom("Doe");
        $user->setEmail("jane.doe.test" . uniqid() . "@example.com");
        $user->setMdp($this->passwordHasher->hashPassword($user, "Password123"));
        $user->setRole("EMPLOYE");
        $user->setDate_naissance(new \DateTime('2000-01-01'));
        $user->setReputationScore(0);
        
        try {
            $this->em->persist($user);
            $this->em->flush();
            $output->writeln("Creation success! " . $user->getId());
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("DB ERROR: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
