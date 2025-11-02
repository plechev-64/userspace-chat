<?php

declare(strict_types=1);

namespace UserSpace\Chat;

use UserSpace\Chat\Controller\ChatController;
use UserSpace\Chat\Grid\ContactListGrid;
use UserSpace\Chat\Integration\ChatShortcode;
use UserSpace\Chat\Integration\ContactsTab;
use UserSpace\Chat\Integration\PrivateChatTab;
use UserSpace\Chat\Service\PluginLifecycle;
use UserSpace\Chat\SSE\ChatEventSource;
use UserSpace\Core\Addon\AddonInterface;
use UserSpace\Common\Module\Locations\Src\Domain\ItemRegistryInterface;
use UserSpace\Core\Asset\AssetRegistryInterface;
use UserSpace\Core\Container\ContainerInterface;
use UserSpace\Common\Module\Grid\Src\Infrastructure\GridRegistryInterface;
use UserSpace\Core\Rest\Registry\ControllerRegistryInterface;
use UserSpace\Core\Hooks\HookManagerInterface;
use UserSpace\Common\Module\SSE\Src\Domain\Source\SseEventSourceRegistryInterface;


class Chat implements AddonInterface
{
    private string $path;

    public function __construct()
    {
        $this->path = dirname(__DIR__);
    }

    public function setup(ContainerInterface $container): void
    {
        $assetRegistry = $container->get(AssetRegistryInterface::class);
        $hookManager = $container->get(HookManagerInterface::class);
        $hookManager->addAction('init', function() use ($assetRegistry){
            $assetRegistry->registerScript('usp-chat', dirname(plugin_dir_url( __FILE__ )) . '/assets/js/chat.js', ['usp-core'], USERSPACE_VERSION);
            $assetRegistry->registerStyle('usp-chat',dirname(plugin_dir_url( __FILE__ )) . '/assets/css/chat.css', [], USERSPACE_VERSION);
        });

        $controllerRegistry = $container->get(ControllerRegistryInterface::class);
        $controllerRegistry->register(ChatController::class);

        // Регистрируем шорткод
        $chatShortcode = $container->get(ChatShortcode::class);
        add_shortcode(ChatShortcode::TAG, [$chatShortcode, 'render']);

        /** @var ItemRegistryInterface $itemRegistry */
        $itemRegistry = $container->get(ItemRegistryInterface::class);
        $itemRegistry->registerItem(ContactsTab::class);
        $itemRegistry->registerItem(PrivateChatTab::class);

        /** @var GridRegistryInterface $gridRegistry */
        $gridRegistry = $container->get(GridRegistryInterface::class);
        $gridRegistry->register('chat-contacts', ContactListGrid::class);

        /** @var SseEventSourceRegistryInterface $sseRegistry */
        $sseRegistry = $container->get(SseEventSourceRegistryInterface::class);
        $sseRegistry->register(ChatEventSource::class);

    }

    public function getName(): string
    {
        return __('Chat', 'userspace-chat');
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getContainerConfigPath(): ?string
    {
        $configPath = $this->getPath() . '/config/container.php';

        if (file_exists($configPath)) {
            return $configPath;
        }

        return null;
    }
}