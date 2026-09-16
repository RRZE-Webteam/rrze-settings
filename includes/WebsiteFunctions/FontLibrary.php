<?php

namespace RRZE\Settings\WebsiteFunctions;

defined('ABSPATH') || exit;

/**
 * Font library class.
 *
 * @package RRZE\Settings\WebsiteFunctions
 */
class FontLibrary
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
        if (!empty($this->siteOptions->advanced->disable_font_library_admin)) {
            add_action('admin_menu', [$this, 'hideFontLibraryPage'], PHP_INT_MAX);
            add_action('load-appearance_page_font-library', [$this, 'blockFontLibraryPageAccess']);
        }
    }

    /**
     * Hide the font library admin page.
     *
     * @return void
     */
    public function hideFontLibraryPage(): void
    {
        if (is_network_admin()) {
            return;
        }

        remove_submenu_page('themes.php', 'font-library.php');
    }

    /**
     * Block direct access to the font library page.
     *
     * @return void
     */
    public function blockFontLibraryPageAccess(): void
    {
        wp_die(
            esc_html__('This feature has been disabled.', 'rrze-settings'),
            esc_html__('Disabled', 'rrze-settings'),
            ['response' => 403, 'back_link' => true]
        );
    }
}
