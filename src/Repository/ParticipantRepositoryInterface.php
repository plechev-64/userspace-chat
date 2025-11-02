<?php

declare(strict_types=1);

namespace UserSpace\Chat\Repository; 

interface ParticipantRepositoryInterface
{
    /**
     * Создает запись об участнике чата.
     *
     * @param int $chatId
     * @param int $userId
     * @param string $role
     * @return int
     */
    public function createParticipant(int $chatId, int $userId, string $role = 'member'): int;

    /**
     * Находит участника чата по ID чата и ID пользователя.
     *
     * @param int $chatId
     * @param int $userId
     * @return array|null
     */
    public function findParticipantByChatAndUser(int $chatId, int $userId): ?array;
}