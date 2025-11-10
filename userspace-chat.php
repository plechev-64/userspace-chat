<?php
/**
 * Plugin Name: UserSpace Chat
 * Description: A chat addon for the UserSpace plugin.
 * Version: 1.0
 * Author: Андрей Плечёв
 * Text Domain: usp-chat
 */

if ( ! defined('ABSPATH')) {
    exit;
}

use UserSpace\Chat\Chat;
use UserSpace\Chat\Service\PluginLifecycle;
use UserSpace\Core\Addon\AddonManagerInterface;

if (!defined('USERSPACE_CHAT_VERSION')) define('USERSPACE_CHAT_VERSION', '1.0.0');
if (!defined('USERSPACE_CHAT_PLUGIN_FILE')) define('USERSPACE_CHAT_PLUGIN_FILE', __FILE__);

// Подключаем автозагрузчик Composer
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

add_action('userspace_loaded', function (AddonManagerInterface $addonManager) {
    // Проверяем, существует ли класс перед регистрацией
    $addonClass = Chat::class;
    // Регистрируем дополнение
    $addonManager->register($addonClass);
}, 10, 1);

register_activation_hook(__FILE__, function(){
    PluginLifecycle::onActivation();
});
register_deactivation_hook(__FILE__, function(){
    PluginLifecycle::onDeactivation();
});
