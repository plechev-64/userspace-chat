<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\FindOrCreateTopicChat;

use UserSpace\Chat\Repository\ChatRepositoryInterface;
use UserSpace\Chat\Repository\ParticipantRepositoryInterface;

class FindOrCreateTopicChatUseCase
{
    public function __construct(
        private readonly ChatRepositoryInterface $chatRepository,
        private readonly ParticipantRepositoryInterface $participantRepository
    ) {
    }

    public function handle(FindOrCreateTopicChatRequest $request): int
    {
        $chatId = $this->chatRepository->findChatByTopicId($request->getTopicId());

        if ($chatId) {
            // Убедимся, что текущий пользователь является участником
            $participant = $this->participantRepository->findParticipantByChatAndUser($chatId, $request->getUserId());
            if (!$participant) {
                $this->participantRepository->createParticipant($chatId, $request->getUserId());
            }
            return $chatId;
        }

        // Создаем новый чат и добавляем пользователя как первого участника
        $newChatId = $this->chatRepository->createTopicChat($request->getTopicId(), $request->getUserId(), $request->getTitle());
        $this->participantRepository->createParticipant($newChatId, $request->getUserId());

        return $newChatId;
    }
}