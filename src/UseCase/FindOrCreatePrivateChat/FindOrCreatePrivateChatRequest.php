<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\FindOrCreatePrivateChat;

class FindOrCreatePrivateChatRequest
{
    private int $userOneId;
    private int $userTwoId;

    public function __construct(int $userOneId, int $userTwoId)
    {
        $this->userOneId = $userOneId;
        $this->userTwoId = $userTwoId;
    }

    public function getUserOneId(): int
    {
        return $this->userOneId;
    }

    public function getUserTwoId(): int
    {
        return $this->userTwoId;
    }
}