<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

/**
 * Roles class
 *
 * @package RRZE\Settings\Users
 */
class Roles
{
    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * @var string
     */
    protected $pagesAuthorRole = 'pages_author';

    /**
     * @var string
     */
    protected $superAuthorRole = 'super_author';

    /**
     * Constructor
     *
     * @param object $siteOptions
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
        // Add/Remove Pages Author Role
        if ($this->siteOptions->users->pages_author_role) {
            $this->addPagesAuthorRole();
        } elseif (get_role($this->pagesAuthorRole)) {
            remove_role($this->pagesAuthorRole);
        }

        // Add/Remove Super Author Role
        if ($this->siteOptions->users->super_author_role) {
            $this->addSuperAuthorRole();
        } elseif (get_role($this->superAuthorRole)) {
            remove_role($this->superAuthorRole);
        }
    }

    /**
     * Add Pages Author Role
     *
     * @return void
     */
    protected function addPagesAuthorRole()
    {
        if (get_role($this->pagesAuthorRole)) {
            return;
        }

        add_role(
            $this->pagesAuthorRole,
            __('Pages Author', 'rrze-settings'),
            [
                'read' => true,
                'edit_pages' => true,
                'delete_pages' => true,
                'publish_pages' => true,
                'edit_published_pages' => true,
                'delete_published_pages' => true,
                'upload_files' => true
            ]
        );
    }

    /**
     * Add Super Author Role
     *
     * @return void
     */
    protected function addSuperAuthorRole()
    {
        if (get_role($this->superAuthorRole)) {
            return;
        }

        add_role(
            $this->superAuthorRole,
            __('Super Author', 'rrze-settings'),
            [
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'edit_published_posts' => true,
                'delete_published_posts' => true,
                'edit_pages' => true,
                'delete_pages' => true,
                'publish_pages' => true,
                'edit_published_pages' => true,
                'delete_published_pages' => true,
                'upload_files' => true
            ]
        );
    }
}
