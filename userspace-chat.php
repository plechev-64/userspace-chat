<?php
/**
 * Plugin Name: UserSpace Chat
 * Description: A chat addon for the UserSpace plugin.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: userspace-chat
 */

if ( ! defined('ABSPATH')) {
    exit;
}

use UserSpace\Chat\Chat;
use UserSpace\Chat\Service\PluginLifecycle;
use UserSpace\Core\Addon\AddonManagerInterface;

// Подключаем автозагрузчик Composer
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

add_action('userspace_loaded', function (AddonManagerInterface $addonManager) {
    // Проверяем, существует ли класс перед регистрацией
    $addonClass = Chat::class;

    if ( ! class_exists($addonClass)) {
        // Подключаем автозагрузчик, если он есть
        $autoloader = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }
    }

    // Регистрируем дополнение
    $addonManager->register($addonClass);
}, 10, 1);

register_activation_hook(__FILE__, function(){
    PluginLifecycle::onActivation();
});
register_deactivation_hook(__FILE__, function(){
    PluginLifecycle::onDeactivation();
});
