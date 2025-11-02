<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\FindOrCreatePrivateChat;

interface FindOrCreatePrivateChatUseCaseInterface
{
    /**
     * @param FindOrCreatePrivateChatRequest $request
     * @return int
     */
    public function handle(FindOrCreatePrivateChatRequest $request): int;
}