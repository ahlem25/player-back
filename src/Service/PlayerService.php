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
     * @param UploadedFile $file Le fichier XLSX à importer
     * @param bool $persistInDatabase Si true, les joueurs valides sont insérés en base de données
     * @return array Tableau contenant les joueurs importés et non importés
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
        
        foreach ($rows as $rowIndex => $row) {
            if (count($row) < 5) {
                $notImportedPlayers[] = [
                    'row' => $rowIndex + 2,
                    'data' => $row,
                    'error' => 'Nombre de colonnes insuffisant'
                ];
                continue;
            }

            try {

                $player = new Player();

                $player->setFirstName(!empty(trim((string)$row[0])) ? $row[0] : "");
                $player->setLastName(!empty(trim((string)$row[1])) ? $row[1] : "");
                $player->setPosition(!empty(trim((string)$row[2])) ? $row[2] : "");
                $player->setTeam(!empty(trim((string)$row[3])) ? $row[3] : "");
                $player->setAge(!empty($row[4]) ? (int)$row[4] : 0);
                

                $errors = $this->validator->validate($player);

                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                    }
                    
                    $notImportedPlayers[] = [
                        'row' => $rowIndex + 2,
                        'data' => $row,
                        'error' => implode(', ', $errorMessages)
                    ];
                } else {
                    $importedPlayers[] = [
                        'row' => $rowIndex + 2,
                        'data' => $row,
                        'player' => $player
                    ];

                    if ($persistInDatabase) {
                        $this->entityManager->persist($player);
                    }
                }
            } catch (\Exception $e) {
                $notImportedPlayers[] = [
                    'row' => $rowIndex + 2,
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
            'notImportedPlayers' => $notImportedPlayers
        ];
    }
}
 