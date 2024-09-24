<?php
// src/Controller/ParticipantController.php

namespace App\Controller;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParticipantController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/participants', name: 'app_participants')]
    public function index(): Response
    {
        $participants = $this->entityManager->getRepository(Participant::class)->findAll();
        return $this->render('participants/index.html.twig', [
            'participants' => $participants,
        ]);
    }

    #[Route('/participants/{id}', name: 'app_participant_detail')]
    public function show(int $id): Response
    {
        $participant = $this->entityManager->getRepository(Participant::class)->find($id);
        if (!$participant) {
            throw $this->createNotFoundException('Participant not found');
        }
        return $this->render('participants/show.html.twig', [
            'participant' => $participant,
        ]);
    }
}