<?php

declare(strict_types=1);

namespace UserSpace\Chat\UseCase\SendMessage;

use UserSpace\Chat\Repository\MessageRepositoryInterface;
use UserSpace\Chat\UseCase\CheckReadPermission\CheckReadPermissionUseCase;

class SendMessageUseCase
{
    private MessageRepositoryInterface $messageRepository;
    private CheckReadPermissionUseCase $permissionUseCase;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        CheckReadPermissionUseCase $permissionUseCase
    ) {
        $this->messageRepository = $messageRepository;
        $this->permissionUseCase = $permissionUseCase;
    }

    public function handle(SendMessageRequest $request): int
    {
        // TODO: Добавить выброс исключения, если нет прав
        $this->permissionUseCase->handle($request->getSenderId(), $request->getChatId());
        return $this->messageRepository->createMessage($request->getChatId(), $request->getSenderId(), $request->getContent());
    }
}