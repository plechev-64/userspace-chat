<?php

declare(strict_types=1);

namespace UserSpace\Chat\Integration;

use UserSpace\Common\Module\Locations\Src\Domain\AbstractTab;
use UserSpace\Common\Service\ViewedUserContext;
use UserSpace\Common\Module\User\Src\Domain\UserApiInterface;
use UserSpace\Chat\UseCase\GetUserChats\GetUserChatsUseCase;
use UserSpace\Core\String\StringFilterInterface;
use UserSpace\Core\TemplateManagerInterface;

class PrivateChatTab extends AbstractTab
{
    public function __construct(
        private readonly ViewedUserContext $viewedUserContext,
        private readonly UserApiInterface $userApi,
        private readonly GetUserChatsUseCase $getUserChatsUseCase,
        private readonly ChatShortcode $chatShortcode,
        private readonly StringFilterInterface $str,
        TemplateManagerInterface $templateManager
    ) {
        parent::__construct($templateManager);
        $this->id = 'usp-private-chat';
        $this->title = $str->translate('Private Chat', 'userspace-chat');
        $this->location = 'sidebar';
        $this->order = 5;
        $this->icon = 'dashicons-format-chat';
    }

    public function getContent(): string
    {
        $currentUser = $this->userApi->getCurrentUser();
        $viewedUser = $this->viewedUserContext->getViewedUser();

        // Если мы в своем профиле, проверяем наличие чатов
        if ($currentUser && $viewedUser && $currentUser->getId() === $viewedUser->getId()) {
            $chats = $this->getUserChatsUseCase->handle($currentUser->getId());
            if (empty($chats)) {
                return sprintf(
                    '<p>%s</p>',
                    $this->str->translate('You have no contacts yet. Start a conversation from another user\'s profile.', 'userspace-chat')
                );
            }
        }

        // Выводим чат с конкретным пользователем
        return $this->chatShortcode->render(['user_id' => $viewedUser->getId()]);
    }
}