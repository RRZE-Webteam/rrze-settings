<?php

namespace RRZE\Settings\Mail;

defined('ABSPATH') || exit;

/**
 * Class AdminEmail
 *
 * This class handles the admin email functionality for the plugin.
 *
 * @package RRZE\Settings\Mail
 */
class AdminEmail
{
    /**
     * Websites DB Data Table
     * 
     * @var string
     */
    const DB_TABLE = 'cms_websites_data';

    /**
     * Data from the websites data table
     * 
     * @var mixed
     */
    protected $websiteData;

    /**
     * Site Options
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

        $this->websiteData = $this->getWebsiteData();
    }

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        // Network custom websites columns
        add_filter('wpmu_blogs_columns', [$this, 'blogsColumns']);
        add_action('manage_blogs_custom_column', [$this, 'adminEmailField'], 1, 3);
        add_action('manage_sites_custom_column', [$this, 'adminEmailField'], 1, 3);

        if (!$this->websiteData) {
            return;
        }

        if (!$this->hasException()) {
            // Links the script file to the admin page.
            add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);

            // Filters the allowed options list.
            add_filter('allowed_options', [$this, 'allowedOptions']);
            // Disable the WordPress admin email verification screen.
            add_filter('admin_email_check_interval', '__return_false');
            // Disable the site admin email change notification email.
            add_filter('send_site_admin_email_change_email', '__return_false');

            // Sets the admin email.
            add_action('admin_init', [$this, 'setAdminEmail']);
        }
    }

    /**
     * Links the script file to the admin page
     * 
     * @return void
     */
    public function adminEnqueueScripts()
    {
        $currentScreen = get_current_screen();
        if (
            $currentScreen != null
            && 'options-general' == get_current_screen()->id
            && is_multisite()
            && !current_user_can('manage_network_options')
        ) {
            wp_enqueue_script('rrze-settings-admin-email');
        }
    }

    /**
     * Adds a new column to the network websites table
     * 
     * @param array $columns The columns array
     * @return array The columns array
     */
    public function blogsColumns($columns)
    {
        $columns['admin_email'] = __('Admin Email', 'rrze-settings');
        return $columns;
    }

    /**
     * Adds a new column to the network websites table
     * 
     * @param string $column The column name
     * @param int $blogId The blog ID
     * @return void
     */
    public function adminEmailField($column, $blogId)
    {
        if ($column == 'admin_email') {
            switch_to_blog($blogId);
            $adminMail = get_option('admin_email');
            restore_current_blog();
            echo $adminMail;
        }
    }

    /**
     * Filters the allowed options list
     * 
     * @param  array $allowedOptions Allowed options
     * @return array Allowed options
     */
    public function allowedOptions(array $allowedOptions)
    {
        $filteredOptions = [
            'new_admin_email',
        ];
        if (
            is_multisite()
            && !current_user_can('manage_network_options')
        ) {
            $allowedOptions['general'] = array_diff($allowedOptions['general'], $filteredOptions);
        }
        return $allowedOptions;
    }

    /**
     * Sets the admin email
     * 
     * @return void
     */
    public function setAdminEmail()
    {
        $data = $this->websiteData;

        if (
            $data != null
            && !empty($data->site_email)
            && filter_var($data->site_email, FILTER_VALIDATE_EMAIL)
            && get_option('admin_email') !== $data->site_email
        ) {
            delete_option('adminhash');
            delete_option('new_admin_email');
            update_option('admin_email', $data->site_email);
        }
    }

    /**
     * Get the admin email from the first admin user
     * 
     * @return mixed
     */
    public function getAdminEmail()
    {
        $admins = get_users(['role' => 'administrator']);
        $adminEmails = array_map(fn($admin) => $admin->user_email, $admins);
        if (count($adminEmails) > 1) {
            return $adminEmails[0];
        }
        return false;
    }

    /**
     * Get the current website data from websites data table
     * 
     * @return mixed
     */
    public function getWebsiteData()
    {
        global $wpdb;

        $respond = null;

        if ($wpdb->get_var("SHOW TABLES LIKE '" . static::DB_TABLE . "'") == static::DB_TABLE) {
            $currentBlogId = get_current_blog_id();
            $query = sprintf('SELECT site_email FROM %s WHERE blog_id = %d AND active = 1', static::DB_TABLE, $currentBlogId);
            $respond = $wpdb->get_row($query);
        }

        return $respond;
    }

    /**
     * Has exception
     * 
     * @return bool
     */
    protected function hasException()
    {
        $exceptions = $this->siteOptions->mail->admin_email_exceptions;
        if (!empty($exceptions) && is_array($exceptions)) {
            foreach ($exceptions as $row) {
                $aryRow = explode(' - ', $row);
                $blogId = isset($aryRow[0]) ? trim($aryRow[0]) : '';
                if (absint($blogId) == get_current_blog_id()) {
                    return true;
                }
            }
        }

        $data = $this->websiteData;
        if ($data != null && empty($data->site_email)) {
            return true;
        }

        return false;
    }
}
