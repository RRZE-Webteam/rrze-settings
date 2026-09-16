<?php

namespace RRZE\Settings\WebsiteFunctions;

defined('ABSPATH') || exit;

/**
 * XML-RPC class
 * @package RRZE\Settings\WebsiteFunctions
 */
class XMLRPC
{
    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions Site options
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Load XML-RPC settings
     *
     * @return void
     */
    public function loaded()
    {
        // Disables XML-RPC
        if ($this->siteOptions->general->disable_xmlrpc) {
            // Completely disable XML-RPC
            add_filter('xmlrpc_enabled', '__return_false');
            // Block direct access to xmlrpc.php
            add_action('init', [$this, 'blockXmlrpcRequests']);
        }
    }

    /**
     * Block XML-RPC requests
     *
     * @return void
     */
    public function blockXmlrpcRequests()
    {
        if (strpos($_SERVER['REQUEST_URI'], 'xmlrpc.php') !== false) {
            wp_die(
                esc_html__('XML-RPC services are disabled on this site.', 'rrze-settings'),
                esc_html__('XML-RPC Disabled', 'rrze-settings'),
                ['response' => 403]
            );
        }
    }
}
