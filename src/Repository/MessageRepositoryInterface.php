<?php

declare(strict_types=1);

namespace UserSpace\Chat\Repository;

interface MessageRepositoryInterface
{
    /**
     * Создает новое сообщение в чате.
     *
     * @param int $chatId
     * @param int $senderId
     * @param string $content
     * @return int
     */
    public function createMessage(int $chatId, int $senderId, string $content): int;

    /**
     * Получает сообщения для указанного чата.
     * @param int $chatId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessagesByChatId(int $chatId, int $limit = 50, int $offset = 0): array;

    /**
     * Получает новые сообщения для пользователя.
     * @param int $userId
     * @param int $lastId
     * @return array
     */
    public function getNewMessagesForUser(int $userId, int $lastId): array;

    /**
     * Получает последнее сообщение из всех чатов пользователя.
     * @param int $userId
     * @return object|null
     */
    public function getLastMessageForUser(int $userId): ?object;
}