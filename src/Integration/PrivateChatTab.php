<?php

declare(strict_types=1);

namespace UserSpace\Chat\Integration;

use UserSpace\Common\Module\Locations\Src\Domain\AbstractTab;
use UserSpace\Common\Service\ViewedUserContext;
use UserSpace\Core\Asset\AssetRegistryInterface;
use UserSpace\Core\String\StringFilterInterface;
use UserSpace\Core\TemplateManagerInterface;

class PrivateChatTab extends AbstractTab
{
    public function __construct(
        private readonly ViewedUserContext $viewedUserContext,
        private readonly ChatShortcode $chatShortcode,
        StringFilterInterface $str,
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
        $viewedUser = $this->viewedUserContext->getViewedUser();
        // Выводим чат с конкретным пользователем
        return $this->chatShortcode->render(['user_id' => $viewedUser->getId()]);
    }
}