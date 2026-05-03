<?php

require __DIR__ . '/vendor/autoload.php';
use App\Entity\Feedback;
use App\Service\FeedbackPriorityAnalyzer;
use Symfony\Component\HttpClient\HttpClient;

$feedback = new Feedback();
$feedback->setCategory('Plainte');
$feedback->setContenu('Je suis en danger, c\'est grave.');

$analyzer = new FeedbackPriorityAnalyzer(HttpClient::create());
$priority = $analyzer->analyze($feedback);

echo "Priority is: " . $priority . PHP_EOL;

