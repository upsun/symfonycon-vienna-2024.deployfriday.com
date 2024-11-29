<?php

namespace App\Controller;

use App\Repository\SpeakerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(SpeakerRepository $speakerRepository)
    {
        $allSpeakers = $speakerRepository->findBy([], ['id' => 'ASC']);

        return $this->render('main/homepage.html.twig', [
            'speakers' =>  $allSpeakers,
        ]);
    }
}