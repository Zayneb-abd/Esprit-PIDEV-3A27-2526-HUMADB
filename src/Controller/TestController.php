<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CommentValidatorService;

class TestController extends AbstractController
{
    #[Route('/test/bad-words', name: 'test_bad_words')]
    public function testBadWords(CommentValidatorService $commentValidator): Response
    {
        $testComments = [
            "Ceci est un commentaire normal et approprié.",
            "Ce travail est vraiment merdique !", // Contient un mot inapproprié
            "Je suis content de ce projet.", // Commentaire normal
            "C'est de la connerie pure !", // Contient un mot inapproprié
            "Excellent travail, continuez comme ça !", // Commentaire positif
        ];

        $results = [];
        
        foreach ($testComments as $comment) {
            $validation = $commentValidator->validateComment($comment);
            $results[] = [
                'original' => $comment,
                'is_valid' => $validation['is_valid'],
                'is_censored' => $validation['is_censored'],
                'bad_words_found' => $validation['bad_words_found'],
                'censored_content' => $validation['censored_content'],
                'message' => $validation['message']
            ];
        }

        return $this->render('test/bad_words.html.twig', [
            'results' => $results
        ]);
    }
}
