<?php

declare(strict_types=1);

namespace UserSpace\Chat\Integration;

use UserSpace\Common\Module\Grid\Src\Infrastructure\GridProvider;
use UserSpace\Common\Module\Locations\Src\Domain\AbstractTab;
use UserSpace\Core\Exception\UspException;
use UserSpace\Core\String\StringFilterInterface;
use UserSpace\Core\TemplateManagerInterface;

class ContactsTab extends AbstractTab
{
    public function __construct(
        private readonly GridProvider $gridProvider,
        StringFilterInterface $str,
        TemplateManagerInterface $templateManager
    ) {
        parent::__construct($templateManager);
        $this->id = 'usp-chat-contacts';
        $this->title = $str->translate('Contacts', 'userspace-chat');
        $this->location = 'sidebar'; // или другая локация
        $this->order = 50;
        $this->icon = 'dashicons-format-chat';
        $this->isPrivate = true;
    }

    /**
     * @throws UspException
     */
    public function getContent(): string
    {
        $grid = $this->gridProvider->getGrid('chat-contacts');
        return $grid->render();
    }
}