<?php

declare(strict_types=1);

namespace UserSpace\Chat\Repository;

interface ChatRepositoryInterface
{
    /**
     * Находит ID приватного чата между двумя пользователями.
     *
     * @param int $userOneId
     * @param int $userTwoId
     * @return int|null
     */
    public function findPrivateChatIdBetweenUsers(int $userOneId, int $userTwoId): ?int;

    /**
     * Создает новый приватный чат.
     *
     * @param int $creatorId
     * @return int ID созданного чата.
     */
    public function createPrivateChat(int $creatorId): int;

    /**
     * Получает список чатов пользователя.
     * @param int $userId
     * @return array
     */
    public function getUserChats(int $userId): array;
}