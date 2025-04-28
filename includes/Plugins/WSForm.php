<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Options;
use RRZE\Settings\Helper;
use RRZE\Settings\Library\Encryption\Encryption;

/**
 * WS Form class
 * 
 * @link https://wsform.com
 * @package RRZE\Settings\Plugins
 */
class WSForm
{
    /**
     * Plugin slug
     * 
     * @var string
     */
    const PLUGIN = 'ws-form-pro/ws-form.php';

    /**
     * Site options
     * 
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
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        if (!$this->pluginExists(self::PLUGIN) || !$this->isPluginActive(self::PLUGIN)) {
            return;
        }

        // Set WS Form add-on PDF license key if available
        if (!defined('WSF_ACTION_PDF_LICENSE_KEY') && $this->siteOptions->plugins->ws_form_action_pdf_license_key) {
            $addOnLicenseKey = $this->siteOptions->plugins->ws_form_action_pdf_license_key ? Encryption::decrypt($this->siteOptions->plugins->ws_form_action_pdf_license_key) : '';
            define('WSF_ACTION_PDF_LICENSE_KEY', $addOnLicenseKey);
        }

        if (
            !$this->hasException()
        ) {
            // Add filter to modify the field types
            add_filter('wsf_config_field_types', [$this, 'configFieldTypes']);
        }
    }

    /**
     * Set WS Form license key
     * 
     * @return void
     */
    public static function setWSFormLicenseKey()
    {
        $siteOptions = Options::getSiteOptions();
        if (!defined('WSF_LICENSE_KEY') && $siteOptions->plugins->ws_form_license_key) {
            $licenseKey = $siteOptions->plugins->ws_form_license_key ? Encryption::decrypt($siteOptions->plugins->ws_form_license_key) : '';
            define('WSF_LICENSE_KEY', $licenseKey);
        }
    }

    public function configFieldTypes($fieldTypes)
    {
        $allowedFieldTypes = $this->siteOptions->plugins->ws_form_not_allowed_field_types;
        $allowedFieldTypes = !empty($allowedFieldTypes) && is_array($allowedFieldTypes) ? $allowedFieldTypes : [];
        if (!empty($allowedFieldTypes)) {
            foreach ($fieldTypes as $key => $fieldType) {
                $types = $fieldType['types'] ?? null;
                foreach ($allowedFieldTypes as $allowedFieldType) {
                    if (isset($types[$allowedFieldType])) {
                        unset($fieldTypes[$key]['types'][$allowedFieldType]);
                    }
                }
            }
        }
        return $fieldTypes;
    }

    /**
     * Check if network-wide the plugin has exceptions
     * 
     * @return bool
     */
    protected function hasException()
    {
        $exceptions = $this->siteOptions->plugins->ws_form_exceptions;
        if (!empty($exceptions) && is_array($exceptions)) {
            foreach ($exceptions as $row) {
                $aryRow = explode(' - ', $row);
                $blogId = isset($aryRow[0]) ? trim($aryRow[0]) : '';
                if (absint($blogId) == get_current_blog_id()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if plugin is available
     * 
     * @param  string  $plugin Plugin
     * @return boolean True if plugin is available
     */
    protected function pluginExists($plugin)
    {
        return Helper::pluginExists($plugin);
    }

    /**
     * Check if plugin is active
     * 
     * @param  string  $plugin Plugin
     * @return boolean True if plugin is active
     */
    protected function isPluginActive($plugin)
    {
        return Helper::isPluginActive($plugin);
    }
}
