<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings;
use RRZE\Settings\General\General;
use RRZE\Settings\Posts\Posts;
use RRZE\Settings\RestAPI\RestAPI;
use RRZE\Settings\Users\Users;
use RRZE\Settings\Menus\Menus;
use RRZE\Settings\Tools\Tools;
use RRZE\Settings\Writing\Writing;
use RRZE\Settings\Media\Media;
use RRZE\Settings\Taxonomies\Taxonomies;
use RRZE\Settings\Plugins\Plugins;
use RRZE\Settings\CSP\CSP;
use RRZE\Settings\Mail\Mail;
use RRZE\Settings\Discussion\Discussion;
use RRZE\Settings\Advanced\Advanced;

/**
 * Main class
 * 
 * @package RRZE\Settings
 */
class Main
{
    /**
     * @var string
     */
    protected $optionName;

    /**
     * @var object
     */
    protected $options;

    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * @var object
     */
    protected $defaultOptions;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        $this->optionName = Options::getOptionName();
        $this->options = Options::getOptions();
        $this->siteOptions = Options::getSiteOptions();
        $this->defaultOptions = Options::getDefaultOptions();

        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    /**
     * Load all classes
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

        // General
        (new General())->loaded();

        // Users
        (new Users())->loaded();

        // Posts
        (new Posts())->loaded();

        // Menus
        (new Menus())->loaded();

        // Writing
        (new Writing())->loaded();

        // Discussion
        (new Discussion())->loaded();

        // Media
        (new Media())->loaded();

        // Taxonomies
        (new Taxonomies())->loaded();

        // Mail
        (new Mail())->loaded();

        // Tools
        (new Tools())->loaded();

        // Rest API
        (new RestAPI())->loaded();

        // Content Security Police
        (new CSP())->loaded();

        // Plugins
        (new Plugins())->loaded();

        // Advanced
        (new Advanced())->loaded();
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function adminEnqueueScripts($hook)
    {
        wp_enqueue_style(
            'rrze-settings',
            plugins_url('build/admin/admin.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );
    }
}
