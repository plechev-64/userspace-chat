<?php

declare(strict_types=1);

namespace UserSpace\Chat;

use UserSpace\Chat\Controller\ChatController;
use UserSpace\Chat\Grid\ContactListGrid;
use UserSpace\Chat\Integration\ChatShortcode;
use UserSpace\Chat\Integration\ContactsTab;
use UserSpace\Chat\Integration\PrivateChatTab;
use UserSpace\Chat\SSE\ChatEventSource;
use UserSpace\Core\Addon\AddonInterface;
use UserSpace\Common\Module\Locations\Src\Domain\ItemRegistryInterface;
use UserSpace\Core\Asset\AssetRegistryInterface;
use UserSpace\Core\Container\ContainerInterface;
use UserSpace\Common\Module\Grid\Src\Infrastructure\GridRegistryInterface;
use UserSpace\Core\Localization\LocalizationApiInterface;
use UserSpace\Core\Rest\Registry\ControllerRegistryInterface;
use UserSpace\Core\Hooks\HookManagerInterface;
use UserSpace\Common\Module\SSE\Src\Domain\Source\SseEventSourceRegistryInterface;
use UserSpace\Core\String\StringFilterInterface;


class Chat implements AddonInterface
{
    private string $path;

    public function __construct()
    {
        $this->path = dirname(__DIR__);
    }

    public function setup(ContainerInterface $container): void
    {
        $this->registerAssets($container);
        $this->registerControllers($container);
        $this->registerUiComponents($container);
        $this->registerSse($container);
    }

    public function getName(): string
    {
        return __('Chat', 'usp-chat');
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

    /**
     * Регистрирует скрипты и стили плагина.
     */
    private function registerAssets(ContainerInterface $container): void
    {
        $assetRegistry = $container->get(AssetRegistryInterface::class);
        $localizationApi = $container->get(LocalizationApiInterface::class);
        $str = $container->get(StringFilterInterface::class);
        $hookManager = $container->get(HookManagerInterface::class);
        $hookManager->addAction('init', function () use (
            $assetRegistry,
            $str,
            $localizationApi
        ) {
            $pluginUrl = plugin_dir_url(dirname(__FILE__));
            $localizationApi->loadPluginTextdomain('usp-chat', dirname(plugin_basename(USERSPACE_CHAT_PLUGIN_FILE)) . '/languages');
            $assetRegistry->registerScript('usp-chat', $pluginUrl . 'assets/js/chat.js', ['usp-core'], USERSPACE_VERSION);
            $assetRegistry->localizeScript('usp-chat', 'uspChatL10n', $this->getScriptTranslations($str));
            $assetRegistry->registerStyle('usp-chat', $pluginUrl . 'assets/css/chat.css', [], USERSPACE_VERSION);
        });
    }

    /**
     * Возвращает массив строк для локализации в JavaScript.
     *
     * @return array
     */
    private function getScriptTranslations(StringFilterInterface $str): array
    {
        return [
            'loadingMessages' => $str->translate('Loading messages...', 'usp-chat'),
            'errorLoadingMessages' => $str->translate('Error loading messages.', 'usp-chat'),
            'noMessages' => $str->translate('No messages in this chat yet.', 'usp-chat'),
            'errorLoadingChats' => $str->translate('Error loading chats.', 'usp-chat'),
            'noChats' => $str->translate('No chats yet.', 'usp-chat'),
            'defaultChatTitle' => $str->translate('Chat', 'usp-chat'),
            'defaultTopicTitle' => $str->translate('Topic Chat', 'usp-chat'),
        ];
    }

    /**
     * Регистрирует REST-контроллеры.
     */
    private function registerControllers(ContainerInterface $container): void
    {
        $controllerRegistry = $container->get(ControllerRegistryInterface::class);
        $controllerRegistry->register(ChatController::class);
    }

    /**
     * Регистрирует компоненты пользовательского интерфейса (шорткоды, вкладки, гриды).
     */
    private function registerUiComponents(ContainerInterface $container): void
    {
        // Регистрируем шорткод
        $chatShortcode = $container->get(ChatShortcode::class);
        add_shortcode(ChatShortcode::TAG, [$chatShortcode, 'render']);

        $itemRegistry = $container->get(ItemRegistryInterface::class);
        $itemRegistry->registerItem(ContactsTab::class);
        $itemRegistry->registerItem(PrivateChatTab::class);

        $gridRegistry = $container->get(GridRegistryInterface::class);
        $gridRegistry->register('chat-contacts', ContactListGrid::class);
    }

    /**
     * Регистрирует источник событий для Server-Sent Events.
     */
    private function registerSse(ContainerInterface $container): void
    {
        $sseRegistry = $container->get(SseEventSourceRegistryInterface::class);
        $sseRegistry->register(ChatEventSource::class);
    }
}