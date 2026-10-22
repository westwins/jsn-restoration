<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

class StaticController extends LegacyController
{
    #[Route(path: '/about/', name: 'about')]
    public function aboutAction()
    {
        return $this->render('static/about.html.twig', []);
    }

    #[Route(path: '/workshop/2015/', name: 'workshop')]
    public function workshopAction()
    {
        return $this->render('static/workshop.html.twig', []);
    }
}
