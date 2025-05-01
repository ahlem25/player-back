<?php
namespace App\Service;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlayerService
{
    private EntityManagerInterface $entityManager;
    private PlayerRepository $playerRepository;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PlayerRepository $playerRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->playerRepository = $playerRepository;
        $this->validator = $validator;
    }


    public function getAllPlayers(): array
    {
        return $this->playerRepository->findAll();
    }


    public function getPlayer(int $id): ?Player
    {
        return $this->playerRepository->find($id);
    }


    public function createPlayer(Player $player): Player
    {

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $player;
    }


    public function updatePlayer(Player $player): Player
    {

        $this->entityManager->flush();
        return $player;
    }


    public function deletePlayer(Player $player): void
    {
        $this->entityManager->remove($player);
        $this->entityManager->flush();
    }

    /**
     * @param UploadedFile 
     * @param bool 
     * @return array 
     */
    public function importPlayersFromXlsx(UploadedFile $file, bool $persistInDatabase = false): array
    {
 
        $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.xlsx';
        $file->move(dirname($tempFilePath), basename($tempFilePath));
        $spreadsheet = IOFactory::load($tempFilePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        unlink($tempFilePath);

        array_shift($rows);

        $importedPlayers = [];
        $notImportedPlayers = [];
        
        $validPositions = ['Attaquant', 'Défenseur', 'Gardien', 'Milieu'];
        
        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; 
            if (count($row) < 5) {
                $notImportedPlayers[] = [
                    'row' => $rowNumber,
                    'data' => $row,
                    'error' => 'Nombre de colonnes insuffisant, 5 colonnes requises'
                ];
                continue;
            }
            
            $errors = [];
            
            if (empty(trim((string)$row[0]))) {
                $errors[] = 'Le prénom ne peut pas être vide';
            }
            
            if (empty(trim((string)$row[1]))) {
                $errors[] = 'Le nom ne peut pas être vide';
            }
            
            if (empty(trim((string)$row[2]))) {
                $errors[] = 'La position ne peut pas être vide';
            } elseif (!in_array($row[2], $validPositions)) {
                $errors[] = 'Position invalide. Valeurs acceptées: ' . implode(', ', $validPositions);
            }
            
            if (empty(trim((string)$row[3]))) {
                $errors[] = 'L\'équipe ne peut pas être vide';
            }
            
            if (empty($row[4])) {
                $errors[] = 'L\'âge ne peut pas être vide';
            } elseif (!is_numeric($row[4]) || $row[4] <= 0) {
                $errors[] = 'L\'âge doit être un nombre positif';
            }
            
            if (!empty($errors)) {
                $notImportedPlayers[] = [
                    'row' => $rowNumber,
                    'data' => $row,
                    'error' => implode(', ', $errors)
                ];
                continue;
            }

            try {
                $player = new Player();
                $player->setFirstName($row[0]);
                $player->setLastName($row[1]);
                $player->setPosition($row[2]);
                $player->setTeam($row[3]);
                $player->setAge((int) $row[4]);
                
                $validationErrors = $this->validator->validate($player);
                
                if (count($validationErrors) > 0) {
                    $errorMessages = [];
                    foreach ($validationErrors as $error) {
                        $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                    }
                    
                    $notImportedPlayers[] = [
                        'row' => $rowNumber,
                        'data' => $row,
                        'error' => implode(', ', $errorMessages)
                    ];
                } else {
                    $importedPlayers[] = [
                        'row' => $rowNumber,
                        'data' => $row,
                        'player' => $player
                    ];
                    
                    if ($persistInDatabase) {
                        $this->entityManager->persist($player);
                    }
                }
            } catch (\Exception $e) {
                $notImportedPlayers[] = [
                    'row' => $rowNumber,
                    'data' => $row,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        if ($persistInDatabase && count($importedPlayers) > 0) {
            $this->entityManager->flush();
        }

        return [
            'importedPlayers' => $importedPlayers, 
            'notImportedPlayers' => $notImportedPlayers,
            'importedCount' => count($importedPlayers),
            'notImportedCount' => count($notImportedPlayers),
            'totalRows' => count($rows)
        ];
    }
}
 