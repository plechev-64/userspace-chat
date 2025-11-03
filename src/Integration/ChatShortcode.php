<?php

declare(strict_types=1);

namespace UserSpace\Chat\Integration;

use UserSpace\Core\Asset\AssetRegistryInterface;
use UserSpace\Core\TemplateManagerInterface;

class ChatShortcode
{
    public const TAG = 'usp_chat';

    public function __construct(
        private readonly AssetRegistryInterface $assetRegistry,
        private readonly TemplateManagerInterface $templateManager
    ) {
    }

    /**
     * @param array $attributes
     * @return string
     */
    public function render(array $attributes): string
    {
        $defaultAttributes = [
            'user_id' => null,  // Для открытия приватного чата
            'topic-id' => null, // Для открытия тематического чата
            'title' => '',      // Заголовок для нового тематического чата
        ];

        $this->assetRegistry->enqueueStyle('usp-chat');
        $this->assetRegistry->enqueueScript('usp-chat');

        $attrs = shortcode_atts($defaultAttributes, $attributes);

        return $this->templateManager->render('chat-main', ['attributes' => $attrs]);
    }
}