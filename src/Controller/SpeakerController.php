<?php
namespace App\Controller;

use App\Repository\SpeakerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SpeakerController extends AbstractController
{
    #[Route('/api/get-speaker-list', methods: ['GET'])]
    public function getSpeakerList(SpeakerRepository $speakerRepository): Response
    {
        return $this->json($speakerRepository->findBy([], ['id' => 'ASC']));
    }

    #[Route('/api/get-podium', methods: ['GET'])]
    public function getPodium(SpeakerRepository $speakerRepository): Response
    {
        return $this->json($speakerRepository->getSpeakerPodium());
    }
}