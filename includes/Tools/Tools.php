<?php

namespace RRZE\Settings\Tools;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * Tools class
 *
 * @package RRZE\Settings\Tools
 */
class Tools extends Main
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

        // Prevents website deletion (Tools/Delete Site)
        if (!is_super_admin() && $this->siteOptions->tools->disable_delete_site) {
            $this->disableDeleteSite();
        }

        // Disables the privacy options (Tools/Export/Import Personal Data)
        if ($this->siteOptions->tools->disable_privacy_options) {
            $this->disablePrivacyOptions();
        }
    }

    /**
     * Prevents website deletion (Tools / Delete Site)
     * 
     * @return void
     */
    protected function disableDeleteSite()
    {
        add_action('admin_menu', [$this, 'removeSubmenuPage']);
        add_action('current_screen', [$this, 'currentScreen']);
        add_action('wp_uninitialize_site', [$this, 'deleteBlog']);
        add_action('pre_update_option_deleteblog_hash', [$this, 'preUpdateOptionDeleteBlogHash']);
    }

    /**
     * Disables the privacy options (Tools/Export/Import Personal Data)
     * 
     * @return void
     */
    protected function disablePrivacyOptions()
    {
        add_filter('map_meta_cap', [$this, 'mapMetaCap'], 10, 4);
    }

    /**
     * Removes the submenu page for deleting a site
     * 
     * @return void
     */
    public function removeSubmenuPage()
    {
        remove_submenu_page('tools.php', 'ms-delete-site.php');
    }

    /**
     * Checks the current screen and prevents access to the delete site page
     * 
     * @return void
     */
    public function currentScreen()
    {
        $screen = get_current_screen();
        if ($screen->id == 'ms-delete-site') {
            wp_die(__('You are not allowed to delete this website.', 'rrze-settings'));
        }
    }

    /**
     * Prevents the deletion of a site
     * 
     * @param  object \WP_Site $blog
     * @return void
     */
    public function deleteBlog($blog)
    {
        wp_die(__('You are not allowed to delete this website.', 'rrze-settings'));
    }

    /**
     * Prevents the deletion of a site by checking the deleteblog_hash option
     * 
     * @param  string $newVal
     * @return void
     */
    public function preUpdateOptionDeleteBlogHash($newVal)
    {
        wp_die(__('You are not allowed to delete this website.', 'rrze-settings'));
    }

    /**
     * Disables the privacy options added in WordPress 4.9.6
     * 
     * @param array $requiredCapabilities The primitive capabilities required to perform the desired meta-capability
     * @param string $requestedCapability The desired meta-capability
     * @param int $userId The User-ID
     * @param array $args Adds the context to the capability. Typically the object ID
     * @return array The primitive capabilities required to perform the desired meta-capability
     */
    public function mapMetaCap($requiredCapabilities, $requestedCapability, $userId, $args)
    {
        $privacyCapabilities = ['manage_privacy_options', 'erase_others_personal_data', 'export_others_personal_data'];

        if (in_array($requestedCapability, $privacyCapabilities)) {
            $requiredCapabilities[] = 'do_not_allow';
        }

        return $requiredCapabilities;
    }
}
