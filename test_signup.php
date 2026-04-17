<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$passwordHasher = $kernel->getContainer()->get('security.user_password_hasher');

$user = new \App\Entity\User();
$user->setPrenom("Test");
$user->setNom("User");
$user->setEmail("testsignup@example.com");
$user->setMdp($passwordHasher->hashPassword($user, "Password123"));
$user->setRole("EMPLOYE");
$user->setDateNaissance(new \DateTime('2000-01-01'));
$user->setReputationScore(0);

try {
    $em->persist($user);
    $em->flush();
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
