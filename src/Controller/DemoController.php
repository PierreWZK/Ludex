<?php

namespace App\Controller;

use App\DataFixtures\AppFixtures;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** La page d'accueil : une démo qui se connecte avec le compte de démo et appelle l'API en direct */
final class DemoController extends AbstractController
{
    #[Route('/', name: 'demo', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('demo.html.twig', [
            'demo_email' => AppFixtures::DEMO_EMAIL,
            'demo_password' => AppFixtures::DEMO_PASSWORD,
        ]);
    }
}
