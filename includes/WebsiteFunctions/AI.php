<?php

namespace RRZE\Settings\WebsiteFunctions;

defined('ABSPATH') || exit;

/**
 * AI functionality class.
 *
 * @package RRZE\Settings\WebsiteFunctions
 */
class AI
{
    /**
     * Site options object.
     *
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor.
     *
     * @param object $siteOptions Site options object.
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Plugin loaded action.
     *
     * @return void
     */
    public function loaded(): void
    {
        if (!empty($this->siteOptions->advanced->disable_ai_functionality)) {
            add_filter('wp_supports_ai', '__return_false', PHP_INT_MAX);
            add_filter('wp_ai_client_prevent_prompt', '__return_true', PHP_INT_MAX);
        }

        if (!empty($this->siteOptions->advanced->hide_ai_connector_page)) {
            add_action('admin_menu', [$this, 'hideAIConnectorPage'], PHP_INT_MAX);
            add_action('admin_init', [$this, 'blockAIConnectorPageAccess'], 0);
        }
    }

    /**
     * Hide the AI connector settings page.
     *
     * @return void
     */
    public function hideAIConnectorPage(): void
    {
        if (is_network_admin()) {
            return;
        }

        remove_submenu_page('options-general.php', 'options-connectors.php');
    }

    /**
     * Block direct access to the AI connector settings page.
     *
     * @return void
     */
    public function blockAIConnectorPageAccess(): void
    {
        global $pagenow;

        if (is_network_admin()) {
            return;
        }

        $isConnectorsPage = 'options-connectors.php' === $pagenow;
        $isLegacyConnectorsPage = 'options-general.php' === $pagenow
            && isset($_GET['page'])
            && 'options-connectors' === sanitize_key(wp_unslash($_GET['page']));

        if ($isConnectorsPage || $isLegacyConnectorsPage) {
            wp_safe_redirect(admin_url());
            exit;
        }
    }
}
