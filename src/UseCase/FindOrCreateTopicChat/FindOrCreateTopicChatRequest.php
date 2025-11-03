<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\FindOrCreateTopicChat;

class FindOrCreateTopicChatRequest
{
    public function __construct(
        private readonly string $topicId,
        private readonly int $userId,
        private readonly string $title
    ) {
    }

    public function getTopicId(): string
    {
        return $this->topicId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}