<?php

declare(strict_types=1);

namespace UserSpace\Chat\SSE;

use UserSpace\Chat\Repository\MessageRepositoryInterface;
use UserSpace\Common\Module\SSE\Src\Domain\DTO\SseEventDto;
use UserSpace\Common\Module\SSE\Src\Domain\Source\SseEventSourceInterface;

class ChatEventSource implements SseEventSourceInterface
{
    public const CHANNEL_NAME = 'chat_messages';

    public function __construct(private readonly MessageRepositoryInterface $messageRepository)
    {
    }

    public function getChannelName(): string
    {
        return self::CHANNEL_NAME;
    }

    /**
     * Возвращает новые события из своего источника данных.
     *
     * @param int      $lastEventId ID последнего события, полученного клиентом.
     * @param int|null $userId      ID текущего пользователя (или null для гостя).
     *
     * @return SseEventDto[]
     */
    public function getEvents( int $lastEventId, ?int $userId ): array
    {
        if ($lastEventId === 0) {
            // Сценарий для первого подключения: получаем только последнее сообщение
            $messages = [];
            $message = $this->messageRepository->getLastMessageForUser($userId);
            if ($message) {
                $messages[] = $message;
            }
            $eventType = 'last_message';
        } else {
            // Стандартный сценарий: получаем все новые сообщения
            $messages = $this->messageRepository->getNewMessagesForUser($userId, $lastEventId);
            $eventType = 'private_message';
        }

        if (empty($messages)) {
            return [];
        }

        return array_map(function (object $message) use ($eventType) {
            return new SseEventDto(
                (int)$message->id,
                $eventType,
                [
                    'id' => (int)$message->id,
                    'chat_id' => (int)$message->chat_id,
                    'sender_id' => (int)$message->sender_id,
                    'sender_name' => $message->sender_name,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                ]
            );
        }, $messages);
    }
}