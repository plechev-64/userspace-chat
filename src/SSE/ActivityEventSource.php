<?php

declare(strict_types=1);

namespace UserSpace\Chat\SSE;

use UserSpace\Chat\Repository\ChatRepositoryInterface;
use UserSpace\Common\Module\Activity\Src\Domain\ActivityServiceInterface;
use UserSpace\Common\Module\SSE\Src\Domain\DTO\SseEventDto;
use UserSpace\Common\Module\SSE\Src\Domain\Source\SseEventSourceInterface;

class ActivityEventSource implements SseEventSourceInterface
{
    public const CHANNEL_NAME = 'chat_activity';

    public function __construct(
        private readonly ChatRepositoryInterface $chatRepository,
        private readonly ActivityServiceInterface $activityService
    ) {
    }

    public function getChannelName(): string
    {
        return self::CHANNEL_NAME;
    }

    public function getConnectionLifeTime(): int
    {
        return 0;
    }

    public function getMaxTimeRetry(): int
    {
        return 60000;
    }

    /**
     * @inheritDoc
     */
    public function getEvents(int $lastEventId, ?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        // Получаем всех собеседников пользователя
        $chats = $this->chatRepository->getUserPrivateChats($userId);

        if (empty($chats)) {
            return [];
        }

        // Получаем их статусы активности
        $contactIds = array_map(fn($chat) => (int)$chat->contact_user_id, $chats);
        $activityData = $this->activityService->getMultipleUserStatuses($contactIds);

        // Отправляем одно событие с данными по всем контактам.
        // ID здесь не так важен, так как мы не отслеживаем "новые" статусы, а просто периодически их обновляем.
        return [
            new SseEventDto(
                (int)floor(microtime(true) * 1000),
                'activity_update',
                $activityData
            )
        ];
    }
}