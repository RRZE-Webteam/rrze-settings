<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

/**
 * Contact
 *
 * Creates a virtual frontend page (/contact) that lists site admins.
 * Carefully scoped so it never interferes with the Customizer changeset
 * or other admin/ajax queries (prevents "missing_post" errors).
 * 
 * @package RRZE\Settings\Users
 */
class Contact
{
    /** @var string Slug for the virtual page */
    protected $slug = '';

    /** @var array Arguments (title/content) for the virtual page */
    protected $args = [];

    /** @var int Current blog/site ID (for multisite) */
    protected $currentBlogId;

    /**
     * Use a negative ID to avoid collisions and to make it obvious this is not a real DB post.
     */
    const VIRTUAL_CONTACT_ID = -999999;

    /**
     * Bootstrap hooks
     */
    public function loaded()
    {
        // Do nothing on the main site
        if (function_exists('is_main_site') && is_main_site()) {
            return;
        }

        $this->currentBlogId = get_current_blog_id();

        // Prepare content early
        add_action('init', [$this, 'setPostArgs']);

        // Only these two filters are needed to inject a virtual post.
        // Accept 2 args to inspect the WP_Query instance and bail out safely.
        add_filter('posts_results', [$this, 'postsResultsFilter'], 10, 2);
        add_filter('the_posts',     [$this, 'thePostsFilter'],     10, 2);

        // Prevent editing/deleting the virtual post
        add_filter('map_meta_cap',  [$this, 'mapMetaCapFilter'],   10, 4);
    }

    /**
     * Initialize slug/title/content for the virtual page.
     */
    public function setPostArgs()
    {
        $args = [
            'post_name'    => _x('contact', 'post_name (slug)', 'rrze-settings'),
            'post_title'   => _x('Contact', 'post_title', 'rrze-settings'),
            'post_content' => $this->getPostContent(),
        ];

        $this->args = $args;
        $this->slug = $args['post_name'];
    }

    /**
     * Inject a virtual post at the SQL-results level when the main query
     * requests /{slug}. Never touch admin, AJAX, Customizer or changeset queries.
     *
     * @param \WP_Post[] $posts
     * @param \WP_Query  $query
     * @return \WP_Post[]
     */
    public function postsResultsFilter($posts, $query)
    {
        // Hard exits to avoid interfering with Customizer/admin flows
        if (is_admin() || wp_doing_ajax() || (function_exists('is_customize_preview') && is_customize_preview())) {
            return $posts;
        }

        // Never touch the Customizer changeset queries
        $pt = (array) $query->get('post_type');
        if (in_array('customize_changeset', $pt, true)) {
            return $posts;
        }

        // Only act on the main frontend query requesting the exact slug
        if ($query->is_main_query() && $query->get('pagename') === $this->slug) {
            return [$this->getVirtualWPPost()];
        }

        return $posts;
    }

    /**
     * Fallback hook at the loop level. If no posts were found but the request
     * matches the slug, provide the virtual post. Same safety bails as above.
     *
     * @param \WP_Post[] $posts
     * @param \WP_Query  $query
     * @return \WP_Post[]
     */
    public function thePostsFilter($posts, $query)
    {
        if (is_admin() || wp_doing_ajax() || (function_exists('is_customize_preview') && is_customize_preview())) {
            return $posts;
        }

        $pt = (array) $query->get('post_type');
        if (in_array('customize_changeset', $pt, true)) {
            return $posts;
        }

        if ($query->is_main_query() && empty($posts) && $query->get('pagename') === $this->slug) {
            return [$this->getVirtualWPPost()];
        }

        return $posts;
    }

    /**
     * Capability hardening: the virtual post must not be edited/deleted.
     *
     * @param array  $caps
     * @param string $cap
     * @param int    $user_id
     * @param array  $args
     * @return array
     */
    public function mapMetaCapFilter($caps, $cap, $user_id, $args)
    {
        // Only intercept for post-edit/delete caps
        if (! in_array($cap, ['edit_post', 'delete_post', 'edit_page', 'delete_page'], true)) {
            return $caps;
        }

        // If the target is our virtual ID, deny
        if (! empty($args) && (int) $args[0] === self::VIRTUAL_CONTACT_ID) {
            return ['do_not_allow'];
        }

        return $caps;
    }

    /**
     * Build the virtual WP_Post object.
     *
     * @return \WP_Post
     */
    protected function getVirtualWPPost()
    {
        $post_array = array_merge([
            'ID'                    => self::VIRTUAL_CONTACT_ID,
            'post_author'           => 1,
            'post_date'             => current_time('mysql'),
            'post_date_gmt'         => current_time('mysql', true),
            'post_content'          => $this->args['post_content'] ?? '',
            'post_title'            => $this->args['post_title'] ?? '',
            'post_excerpt'          => '',
            'post_status'           => 'publish',
            'comment_status'        => 'closed',
            'ping_status'           => 'closed',
            'post_password'         => '',
            'post_name'             => $this->slug,
            'to_ping'               => '',
            'pinged'                => '',
            'post_modified'         => current_time('mysql'),
            'post_modified_gmt'     => current_time('mysql', true),
            'post_content_filtered' => '',
            'post_parent'           => 0,
            'guid'                  => trailingslashit(get_bloginfo('wpurl')) . $this->slug,
            'menu_order'            => 0,
            'post_type'             => 'page',
            'post_mime_type'        => '',
            'comment_count'         => 0,
            'filter'                => 'raw',
        ], $this->args);

        return new \WP_Post((object) $post_array);
    }

    /**
     * Collect site admins for this blog.
     *
     * @return array{ID:int,display_name:string,user_email:string}[]
     */
    protected function getContactUsers()
    {
        return get_users([
            'blog_id'  => $this->currentBlogId,
            'role__in' => ['administrator'],
            'fields'   => ['ID', 'display_name', 'user_email'],
        ]);
    }

    /**
     * Render the page content (simple HTML list of admins).
     *
     * @return string
     */
    protected function getPostContent()
    {
        $users = $this->getContactUsers();
        if (empty($users)) {
            return '';
        }

        $out = '<h3>' . esc_html__('Contact persons', 'rrze-settings') . '</h3>';

        foreach ($users as $user) {
            $out .= sprintf(
                '<p>%1$s<br/>%2$s %3$s</p>' . PHP_EOL,
                esc_html($user->display_name),
                esc_html__('Email Address:', 'rrze-settings'),
                make_clickable(esc_html($user->user_email))
            );
        }

        return $out;
    }
}
