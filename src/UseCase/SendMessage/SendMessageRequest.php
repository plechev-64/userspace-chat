<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\SendMessage;

class SendMessageRequest
{
    private int $chatId;
    private int $senderId;
    private string $content;

    public function __construct(int $chatId, int $senderId, string $content)
    {
        $this->chatId = $chatId;
        $this->senderId = $senderId;
        $this->content = $content;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getSenderId(): int
    {
        return $this->senderId;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}