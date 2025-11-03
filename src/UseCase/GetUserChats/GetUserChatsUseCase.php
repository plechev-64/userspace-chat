<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\GetUserChats;

use UserSpace\Chat\Repository\ChatRepositoryInterface;

class GetUserChatsUseCase
{
    public function __construct(private readonly ChatRepositoryInterface $chatRepository)
    {
    }

    public function handle(int $userId): array
    {
        return $this->chatRepository->getUserPrivateChats($userId);
    }
}