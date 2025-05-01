<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\PlayerService;

#[Route('/api')]
class PlayerController extends AbstractController
{
    private $entityManager;
    private $serializer;
    private $playerService;
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        PlayerService $playerService,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->playerService = $playerService;
        $this->validator = $validator;
    }

    #[Route('/players', name: 'get_all_players', methods: ['GET'])]
    public function getAllPlayers(): JsonResponse
    {
        $players = $this->playerService->getAllPlayers();
        $jsonPlayers = $this->serializer->serialize($players, 'json');
        
        return new JsonResponse($jsonPlayers, Response::HTTP_OK, [], true);
    }

    #[Route('/players/{id}', name: 'get_player', methods: ['GET'])]
    public function getPlayer(int $id): JsonResponse
    {
        $player = $this->playerService->getPlayer($id);
        
        if (!$player) {
            return new JsonResponse(['message' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }
        
        $jsonPlayer = $this->serializer->serialize($player, 'json');
        return new JsonResponse($jsonPlayer, Response::HTTP_OK, [], true);
    }

    #[Route('/players', name: 'create_player', methods: ['POST'])]
    public function createPlayer(Request $request): JsonResponse
    {
        try {
            $contentType = $request->headers->get('Content-Type');
            $format = str_contains($contentType, 'application/ld+json') ? 'jsonld' : 'json';
            
            $player = $this->serializer->deserialize($request->getContent(), Player::class, $format);
            
            $errors = $this->validator->validate($player);
            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }
            
            $player = $this->playerService->createPlayer($player);
            $jsonPlayer = $this->serializer->serialize($player, $format);
            
            return new JsonResponse($jsonPlayer, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/players/{id}', name: 'update_player', methods: ['PUT'])]
    public function updatePlayer(int $id, Request $request): JsonResponse
    {
        $player = $this->playerService->getPlayer($id);
    
        if (!$player) {
            return new JsonResponse(['message' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }
    
        $contentType = $request->headers->get('Content-Type');
        $format = str_contains($contentType, 'application/ld+json') ? 'jsonld' : 'json';
        
        $updatedPlayer = $this->serializer->deserialize($request->getContent(), Player::class, $format);

        $player->setFirstName($updatedPlayer->getFirstName());
        $player->setLastName($updatedPlayer->getLastName());
        $player->setPosition($updatedPlayer->getPosition());
        $player->setTeam($updatedPlayer->getTeam());
        $player->setAge($updatedPlayer->getAge());
    
        $errors = $this->validator->validate($player);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }
    
        $player = $this->playerService->updatePlayer($player);
        $jsonPlayer = $this->serializer->serialize($player, $format);
        
        return new JsonResponse($jsonPlayer, Response::HTTP_OK, [], true);
    }
    
    #[Route('/players/{id}', name: 'delete_player', methods: ['DELETE'])]
    public function deletePlayer(int $id): JsonResponse
    {
        $player = $this->playerService->getPlayer($id);
    
        if (!$player) {
            return new JsonResponse(['message' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }
    
        $this->playerService->deletePlayer($player);
    
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
    #[Route('/players/import', name: 'player_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
    
        if (!$file) {
            return $this->createErrorResponse('Aucun fichier n\'a été téléchargé', Response::HTTP_BAD_REQUEST);
        }
    
        if ($file->getClientMimeType() !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            return $this->createErrorResponse('Type de fichier invalide. Veuillez télécharger un fichier XLSX', Response::HTTP_BAD_REQUEST);
        }
    
        try {
            $persistInDatabase = filter_var($request->query->get('persistInDatabase', 'false'), FILTER_VALIDATE_BOOLEAN);
            $result = $this->playerService->importPlayersFromXlsx($file, $persistInDatabase);
            
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                'Une erreur est survenue lors de l\'importation : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    
    /**
     *
     * @param string 
     * @param int 
     * @return JsonResponse 
     */
    private function createErrorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}  