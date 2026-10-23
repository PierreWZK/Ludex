<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Les pages du site : de simples gabarits, toutes les données passent par l'API */
final class PagesController extends AbstractController
{
    #[Route('/connexion', name: 'connexion', methods: ['GET'])]
    public function connexion(): Response
    {
        return $this->render('account/connexion.html.twig', ['page' => 'connexion']);
    }

    #[Route('/compte', name: 'compte', methods: ['GET'])]
    public function compte(): Response
    {
        return $this->render('account/compte.html.twig', ['page' => 'compte']);
    }

    #[Route('/jeu', name: 'jeu', methods: ['GET'])]
    public function jeu(): Response
    {
        return $this->render('daily.html.twig', ['page' => 'jeu']);
    }
}
