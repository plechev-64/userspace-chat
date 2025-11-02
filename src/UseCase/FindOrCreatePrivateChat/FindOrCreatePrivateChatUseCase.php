<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\FindOrCreatePrivateChat;

use UserSpace\Chat\Repository\ChatRepositoryInterface;
use UserSpace\Chat\Repository\ParticipantRepositoryInterface;

class FindOrCreatePrivateChatUseCase
{
    private ChatRepositoryInterface $chatRepository;
    private ParticipantRepositoryInterface $participantRepository;

    public function __construct(
        ChatRepositoryInterface $chatRepository,
        ParticipantRepositoryInterface $participantRepository
    ) {
        $this->chatRepository = $chatRepository;
        $this->participantRepository = $participantRepository;
    }

    public function handle(FindOrCreatePrivateChatRequest $request): int
    {
        $chatId = $this->chatRepository->findPrivateChatIdBetweenUsers($request->getUserOneId(), $request->getUserTwoId());

        if ($chatId) {
            return $chatId;
        }

        $newChatId = $this->chatRepository->createPrivateChat($request->getUserOneId());
        $this->participantRepository->createParticipant($newChatId, $request->getUserOneId());
        $this->participantRepository->createParticipant($newChatId, $request->getUserTwoId());

        return $newChatId;
    }
}