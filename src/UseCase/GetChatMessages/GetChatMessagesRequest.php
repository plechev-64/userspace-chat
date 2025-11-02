<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\GetChatMessages;

class GetChatMessagesRequest
{
    public function __construct(
        private readonly int $userId,
        private readonly int $chatId,
        private readonly int $limit,
        private readonly int $offset
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}