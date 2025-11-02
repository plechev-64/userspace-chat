<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\GetChatMessages;

use UserSpace\Chat\Repository\MessageRepositoryInterface;
use UserSpace\Chat\UseCase\CheckReadPermission\CheckReadPermissionUseCase;
use UserSpace\Core\Exception\UspException;

class GetChatMessagesUseCase
{
    public function __construct(
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly CheckReadPermissionUseCase $permissionUseCase
    ) {
    }

    /**
     * @param GetChatMessagesRequest $request
     * @return array{messages: array, has_more: bool}
     * @throws UspException
     */
    public function handle(GetChatMessagesRequest $request): array
    {
        if (!$this->permissionUseCase->handle($request->getUserId(), $request->getChatId())) {
            throw new UspException(
                'You do not have permission to read this chat.',
                '403'
            );
        }

        // Запрашиваем на один элемент больше, чтобы проверить, есть ли еще страницы
        $limitPlusOne = $request->getLimit() + 1;
        $messages = $this->messageRepository->getMessagesByChatId($request->getChatId(), $limitPlusOne, $request->getOffset());

        $hasMore = count($messages) === $limitPlusOne;
        if ($hasMore) {
            array_pop($messages); // Удаляем лишний элемент
        }

        return ['messages' => array_reverse($messages), 'has_more' => $hasMore];
    }
}