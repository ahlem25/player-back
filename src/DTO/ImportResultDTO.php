<?php

namespace App\DTO;

class ImportResultDTO
{
    private array $importedPlayers;
    private array $notImportedPlayers;
    private int $importedCount;
    private int $notImportedCount;
    private int $totalRows;
    private bool $persistedInDatabase;

    public function __construct(
        array $importedPlayers,
        array $notImportedPlayers,
        int $totalRows,
        bool $persistedInDatabase
    ) {
        $this->importedPlayers = $importedPlayers;
        $this->notImportedPlayers = $notImportedPlayers;
        $this->importedCount = count($importedPlayers);
        $this->notImportedCount = count($notImportedPlayers);
        $this->totalRows = $totalRows;
        $this->persistedInDatabase = $persistedInDatabase;
    }

    /**
     * 
     */
    public function generateMessage(): string
    {
        if ($this->persistedInDatabase) {
            return "Import terminé: {$this->importedCount} joueurs importés, {$this->notImportedCount} joueurs rejetés sur {$this->totalRows} lignes traitées";
        } else {
            return "Validation terminée: {$this->importedCount} joueurs valides, {$this->notImportedCount} joueurs invalides sur {$this->totalRows} lignes analysées";
        }
    }

    /**
     * 
     */
    public function toArray(): array
    {
        return [
            'success' => true,
            'message' => $this->generateMessage(),
            'importedPlayers' => $this->importedPlayers,
            'notImportedPlayers' => $this->notImportedPlayers,
            'importedCount' => $this->importedCount,
            'notImportedCount' => $this->notImportedCount,
            'totalRows' => $this->totalRows,
            'persistedInDatabase' => $this->persistedInDatabase
        ];
    }

    public function getImportedPlayers(): array
    {
        return $this->importedPlayers;
    }

    public function getNotImportedPlayers(): array
    {
        return $this->notImportedPlayers;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getNotImportedCount(): int
    {
        return $this->notImportedCount;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function isPersistedInDatabase(): bool
    {
        return $this->persistedInDatabase;
    }
} 