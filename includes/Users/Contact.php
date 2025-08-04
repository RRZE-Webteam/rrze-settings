<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

/**
 * Contact class
 * 
 * This class handles the contact page functionality for the RRZE Settings plugin.
 * It creates a virtual post that displays contact information for administrators of the current blog.
 *
 * @package RRZE\Settings\Users
 */
class Contact
{
    /**
     * @var string Slug for the contact page
     * 
     * This property holds the slug for the contact page, which is used to identify the page in WordPress.
     * It is set to 'contact' by default and can be used in queries or URLs to access the contact page.
     */
    protected $slug = '';

    /**
     * @var array Arguments for the contact page post
     * 
     * This array holds the post arguments for the contact page, such as post name, title, and content.
     * It is used to create a virtual post that represents the contact page in WordPress.
     */
    protected $args = [];

    /**
     * @var int Current blog ID
     * 
     * This property holds the ID of the current blog in a multisite WordPress installation.
     * It is used to retrieve users and other blog-specific data.
     */
    protected $currentBlogId;

    /**
     * @var int Virtual contact ID
     * This constant defines a virtual post ID for the contact page.
     * 
     * This ID is used to create a virtual post that represents the contact page in WordPress.
     * It is set to a high number to avoid conflicts with existing posts.
     */
    const VIRTUAL_CONTACT_ID = 999999999999;

    public function loaded()
    {
        if (is_main_site()) {
            return;
        }
        $this->currentBlogId = get_current_blog_id();

        add_action('init', [$this, 'setPostArgs']);
        add_filter('the_posts', [$this, 'thePostsFilter']);
        add_filter('map_meta_cap', [$this, 'mapMetaCapFilter'], 10, 4);
        add_filter('get_post', [$this, 'getVirtualPost'], 10, 2);
        add_filter('posts_results', [$this, 'postsResultsFilter'], 10, 2);
    }

    /**
     * Set the post arguments for the contact page.
     * 
     * This method initializes the post arguments for the contact page, including the post name, title, and content.
     * It sets the slug to 'contact' and prepares the content with contact information of administrators.
     * 
     * @return void
     */
    public function setPostArgs()
    {
        $args = [
            'post_name' => _x('contact', 'post_name (slug)', 'rrze-settings'),
            'post_title' => _x('Contact', 'post_title', 'rrze-settings'),
            'post_content' => $this->getPostContent(),
        ];

        $this->args = $args;
        $this->slug = $args['post_name'];
    }

    /**
     * Get a virtual post for the contact page.
     * 
     * This method retrieves a virtual post object for the contact page based on the slug.
     * It creates a new WP_Post object with the predefined arguments and returns it.
     * 
     * @param \WP_Post|null $post The post object to filter.
     * @param int $post_id The ID of the post.
     * @return \WP_Post|null The virtual post object or null if not applicable.
     */
    public function postsResultsFilter($posts, $query)
    {
        // Solo si es la query principal y la slug coincide, fuerza el post virtual
        if ($query->is_main_query() && isset($query->query['pagename']) && $query->query['pagename'] === $this->slug) {
            $posts = [$this->getVirtualWPPost()];
        }
        return $posts;
    }

    /**
     * Get a virtual WP_Post object for the contact page.
     * 
     * This method constructs a virtual WP_Post object with the predefined arguments for the contact page.
     * It sets various properties such as post ID, author, date, content, title, status, and more.
     * 
     * @return \WP_Post The virtual WP_Post object representing the contact page.
     */
    protected function getVirtualWPPost()
    {
        $post_array = array_merge([
            'ID' => self::VIRTUAL_CONTACT_ID,
            'post_author' => 1,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
            'post_content' => $this->args['post_content'] ?? '',
            'post_title' => $this->args['post_title'] ?? '',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => $this->slug,
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1),
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => get_bloginfo('wpurl') . '/' . $this->slug,
            'menu_order' => 0,
            'post_type' => 'page',
            'post_mime_type' => '',
            'comment_count' => 0,
            'filter' => 'raw',
        ], $this->args);

        return new \WP_Post((object)$post_array);
    }

    /**
     * Filter to map meta capabilities for the contact page.
     * 
     * This method filters the capabilities for editing or deleting posts and pages.
     * If the post ID is the virtual contact ID, it denies the capability by returning 'do_not_allow'.
     * 
     * @param array $caps The capabilities to filter.
     * @param string $cap The capability being checked.
     * @param int $user_id The user ID.
     * @param array $args Additional arguments.
     * @return array Filtered capabilities.
     */
    public function mapMetaCapFilter($caps, $cap, $user_id, $args)
    {
        $toFilter = ['edit_post', 'delete_post', 'edit_page', 'delete_page'];
        if (!in_array($cap, $toFilter, true)) {
            return $caps;
        }
        if (empty($args) || (int)$args[0] === self::VIRTUAL_CONTACT_ID) {
            return ['do_not_allow'];
        }
        return $caps;
    }

    /**
     * Get a virtual post for the contact page.
     * This method checks if the current request is for the contact page and returns a virtual post object.
     * If the request does not match the contact page slug, it returns the original posts array.
     * 
     * @param array $posts The array of posts.
     * @return array The modified posts array containing the virtual contact post if applicable.
     */
    public function thePostsFilter($posts)
    {
        global $wp, $wp_query;
        if (
            count($posts) > 0
            || !(strtolower($wp->request) == $this->slug || (isset($wp->query_vars['page_id']) && $wp->query_vars['page_id'] == $this->slug))
        ) {
            return $posts;
        }

        $post_array = array_merge([
            'ID' => self::VIRTUAL_CONTACT_ID,
            'post_author' => 1,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
            'post_content' => $this->args['post_content'] ?? '',
            'post_title' => $this->args['post_title'] ?? '',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => $this->slug,
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1),
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => get_bloginfo('wpurl') . '/' . $this->slug,
            'menu_order' => 0,
            'post_type' => 'page',
            'post_mime_type' => '',
            'comment_count' => 0,
            'filter' => 'raw',
        ], $this->args);

        $wp_post = new \WP_Post((object) $post_array);
        $posts = [$wp_post];

        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;
        unset($wp_query->query['error']);
        $wp_query->query_vars['error'] = '';
        $wp_query->is_404 = false;

        return $posts;
    }

    /**
     * Get contact users.
     * 
     * This method retrieves users with the 'administrator' role from the current blog.
     * It returns an array of user objects containing their ID, display name, and email address.
     * 
     * @return array An array of user objects with contact information.
     */
    protected function getContactUsers()
    {
        $users = get_users([
            'blog_id' => $this->currentBlogId,
            'role__in' => ['administrator'],
            'fields' => ['ID', 'display_name', 'user_email']
        ]);

        return $users;
    }

    /**
     * Get the content for the contact page.
     * 
     * This method generates the content for the contact page by retrieving contact users and formatting their information.
     * It returns a string containing the contact information of all administrators in the current blog.
     * 
     * @return string The formatted contact information for the page.
     */
    protected function getPostContent()
    {
        $contactUsers = $this->getContactUsers();
        $output = '';
        if (empty($contactUsers)) {
            return $output;
        }
        $output = '<h3>' . __('Contact persons', 'rrze-settings') . '</h3>';

        foreach ($contactUsers as $user) {
            $output .= sprintf(
                '<p>%1$s<br/>%2$s %3$s</p>' . PHP_EOL,
                $user->display_name,
                __('Email Address:', 'rrze-settings'),
                make_clickable($user->user_email)
            );
        }

        return $output;
    }
}
