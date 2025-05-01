<?php
namespace App\Service;

use App\DTO\ImportResultDTO;
use App\DTO\PlayerImportDTO;
use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlayerService
{
    private const VALID_POSITIONS = ['Attaquant', 'Défenseur', 'Gardien', 'Milieu'];

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

    /**
     * 
     * @return Player[] 
     */
    public function getAllPlayers(): array
    {
        return $this->playerRepository->findAll();
    }

    /**
     * 
     * @param int 
     * @return Player|null 
     */
    public function getPlayer(int $id): ?Player
    {
        return $this->playerRepository->find($id);
    }

    /**
     * 
     * @param Player 
     * @return Player 
     */
    public function createPlayer(Player $player): Player
    {
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $player;
    }

    /**
     * 
     * @param Player 
     * @return Player 
     */
    public function updatePlayer(Player $player): Player
    {
        $this->entityManager->flush();
        return $player;
    }

    /**
     * 
     * @param Player 
     */
    public function deletePlayer(Player $player): void
    {
        $this->entityManager->remove($player);
        $this->entityManager->flush();
    }

    /**

     * 
     * @param UploadedFile
     * @param bool 
     * @return array 
     */
    public function importPlayersFromXlsx(UploadedFile $file, bool $persistInDatabase = false): array
    {
        $rows = $this->loadExcelFile($file);
        
        array_shift($rows);

        $importedPlayers = [];
        $notImportedPlayers = [];
        
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
            
            $playerDTO = new PlayerImportDTO($row, $rowNumber);
            $validationErrors = $playerDTO->validate(self::VALID_POSITIONS);
            if (!empty($validationErrors)) {
                $notImportedPlayers[] = [
                    'row' => $rowNumber,
                    'data' => $row,
                    'error' => implode(', ', $validationErrors)
                ];
                continue;
            }

            try {
                $player = $this->createPlayerFromDTO($playerDTO);
                $entityErrors = $this->validator->validate($player);
                
                if (count($entityErrors) > 0) {
                    $errorMessages = $this->formatValidationErrors($entityErrors);
                    
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

        $resultDTO = new ImportResultDTO(
            $importedPlayers, 
            $notImportedPlayers, 
            count($rows),
            $persistInDatabase
        );
        
        return $resultDTO->toArray();
    }
    
    /**
     * 
     * @param UploadedFile 
     * @return array 
     */
    private function loadExcelFile(UploadedFile $file): array
    {
        $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.xlsx';
        $file->move(dirname($tempFilePath), basename($tempFilePath));
        
        $spreadsheet = IOFactory::load($tempFilePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        unlink($tempFilePath);
        
        return $rows;
    }
    
    /**
     * 
     * @param PlayerImportDTO 
     * @return Player 
     */
    private function createPlayerFromDTO(PlayerImportDTO $dto): Player
    {
        $player = new Player();
        $player->setFirstName($dto->getFirstName());
        $player->setLastName($dto->getLastName());
        $player->setPosition($dto->getPosition());
        $player->setTeam($dto->getTeam());
        $player->setAge($dto->getAge());
        
        return $player;
    }
    
    /**
     * 
     * @param \Symfony\Component\Validator\ConstraintViolationListInterface 
     * @return array 
     */
    private function formatValidationErrors($errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
        }
        
        return $errorMessages;
    }
}
 