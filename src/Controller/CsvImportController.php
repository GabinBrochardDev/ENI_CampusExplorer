<?php

namespace App\Controller;

use App\Service\CsvImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CsvImportController extends AbstractController
{
    private $csvImportService;

    public function __construct(CsvImportService $csvImportService)
    {
        $this->csvImportService = $csvImportService;
    }

    #[Route('/import-participants', name: 'import_participants')]
    public function importParticipants(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('csv_file', FileType::class, [
                'label' => 'Fichier CSV (CSV uniquement)',
                'mapped' => false,
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csv_file')->getData();

            if ($csvFile) {
                try {
                    $result = $this->csvImportService->importParticipants($csvFile);
                    $participants = $result['participants'];
                    $errors = $result['errors'];

                    // Ajouter les messages de succès
                    if (count($participants) > 0) {
                        $this->addFlash('success', count($participants) . ' participants importés avec succès');
                    }

                    // Ajouter les messages d'erreur
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error);
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'import des participants : ' . $e->getMessage());
                }
            }
        }

        return $this->render('csv_import/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
