<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\CheckReadPermission;

use UserSpace\Chat\Repository\ParticipantRepositoryInterface;

class CheckReadPermissionUseCase
{
    private ParticipantRepositoryInterface $participantRepository;

    public function __construct(ParticipantRepositoryInterface $participantRepository)
    {
        $this->participantRepository = $participantRepository;
    }

    public function handle(int $userId, int $chatId): bool
    {
        $participant = $this->participantRepository->findParticipantByChatAndUser($chatId, $userId);

        return $participant !== null;
    }
}