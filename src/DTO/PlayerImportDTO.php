<?php

namespace App\DTO;

class PlayerImportDTO
{
    private string $firstName;
    private string $lastName;
    private string $position;
    private string $team;
    private int $age;
    private int $row;
    private array $rawData;

    public function __construct(array $data, int $row)
    {
        $this->rawData = $data;
        $this->row = $row;
        $this->firstName = isset($data[0]) ? trim((string)$data[0]) : '';
        $this->lastName = isset($data[1]) ? trim((string)$data[1]) : '';
        $this->position = isset($data[2]) ? trim((string)$data[2]) : '';
        $this->team = isset($data[3]) ? trim((string)$data[3]) : '';
        $this->age = isset($data[4]) && is_numeric($data[4]) ? (int)$data[4] : 0;
    }

    /**
     * @param array 
     * @return array 
     */
    public function validate(array $validPositions): array
    {
        $errors = [];
        
        if (empty($this->firstName)) {
            $errors[] = 'Le prénom ne peut pas être vide';
        }
        
        if (empty($this->lastName)) {
            $errors[] = 'Le nom ne peut pas être vide';
        }
        
        if (empty($this->position)) {
            $errors[] = 'La position ne peut pas être vide';
        } elseif (!in_array($this->position, $validPositions)) {
            $errors[] = 'Position invalide. Valeurs acceptées: ' . implode(', ', $validPositions);
        }
        
        if (empty($this->team)) {
            $errors[] = 'L\'équipe ne peut pas être vide';
        }
        
        if ($this->age <= 0) {
            $errors[] = 'L\'âge doit être un nombre positif';
        }
        
        return $errors;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getTeam(): string
    {
        return $this->team;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }
} 