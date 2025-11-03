<?php

namespace UserSpace\Chat\Settings;

use UserSpace\Common\Module\Form\Src\Infrastructure\Field\DTO\NumberFieldDto;
use UserSpace\Common\Module\Settings\Src\Domain\Configurator\SettingsConfig;
use UserSpace\Common\Module\Settings\Src\Domain\Configurator\SettingsConfiguratorInterface;
use UserSpace\Core\String\StringFilterInterface;

class ChatSettingsConfigurator implements SettingsConfiguratorInterface
{

    public function __construct(private readonly StringFilterInterface $str)
    {
    }

    public function configure(SettingsConfig $config): void
    {
        $config->addSection('chat', $this->str->translate('Chat Settings'))
            ->addBlock('display', $this->str->translate('Display Options'))
            ->addOption(new NumberFieldDto(
                'chat_messages_per_page',
                [
                    'label' => $this->str->translate('Messages Per Page'),
                    'description' => $this->str->translate('How many messages to display per page in the chat window.'),
                    'default' => 20,
                ]
            ));
    }
}