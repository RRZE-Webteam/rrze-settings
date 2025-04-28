<?php

namespace RRZE\Settings\Mail;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use function RRZE\Settings\plugin;

/**
 * Class Mail
 *
 * This class handles the mail functionality for the plugin.
 *
 * @package RRZE\Settings\Mail
 */
class Mail extends Main
{
    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        (new PHPMailer($this->siteOptions))->loaded();

        (new AdminEmail($this->siteOptions))->loaded();

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    /**
     * Enqueue admin scripts
     * 
     * @param string $hook The current admin page hook
     * @return void
     */
    public function adminEnqueueScripts($hook)
    {
        $assetFile = include(plugin()->getPath('build') . 'mail/admin-email.asset.php');
        wp_register_script(
            'rrze-settings-admin-email',
            plugins_url('build/mail/admin-email.js', plugin()->getBasename()),
            $assetFile['dependencies'] ?? [],
            plugin()->getVersion(),
            true
        );
    }
}
