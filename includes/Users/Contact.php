<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

/**
 * Contact class
 *
 * @package RRZE\Settings\Users
 */
class Contact
{
    /**
     * Slug for the contact page
     * 
     * This slug is used to create a virtual page that lists the contact persons.
     * It is not a real post, but a virtual one created on the fly.
     * 
     * The slug is set to 'contact' by default, but can be changed in the setPostArgs method.
     * @var string
     */
    protected $slug = '';

    /**
     * Post arguments
     * 
     * This array contains the arguments for the virtual post that will be created.
     * It includes the post name (slug), post title, and post content.
     * 
     * The content is generated dynamically based on the contact persons available in the current blog.
     * @var array
     */
    protected $args = [];

    /**
     * Current blog ID
     * 
     * This variable holds the ID of the current blog in a multisite installation.
     * It is used to fetch the contact persons for the current blog.
     * 
     * It is set in the loaded method and used in the getContactUsers method.
     * @var int
     */
    protected $currentBlogId;

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        if (is_main_site()) {
            return;
        }
        $this->currentBlogId = get_current_blog_id();

        add_action('init', [$this, 'setPostArgs']);
        add_filter('the_posts', [$this, 'thePostsFilter']);
        add_filter('map_meta_cap', [$this, 'mapMetaCapFilter'], 10, 4);
    }

    /**
     * Set the post arguments for the contact page
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
     * Filter the capabilities for meta capabilities
     * 
     * @param array $caps
     * @param string $cap
     * @param int $user_id
     * @param array $args
     * @return array
     */
    public function mapMetaCapFilter($caps, $cap, $user_id, $args)
    {
        $toFilter = ['edit_post', 'delete_post', 'edit_page', 'delete_page'];
        if (!in_array($cap, $toFilter, true)) {
            return $caps;
        }
        if (!$args || empty($args[0]) || $args[0] < 0) {
            return ['do_not_allow'];
        }
        return $caps;
    }


    /**
     * Filter the posts to add a virtual contact page
     * 
     * This function checks if the current request is for the contact page.
     * If it is, it creates a virtual post with the contact information.
     * If there are already posts, it returns them unchanged.
     * 
     * @param array $posts
     * @return array
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
            'ID' => -1,
            'post_author' => 1,
            'post_name' => $this->slug,
            'post_title' => $this->args['post_title'] ?? '',
            'post_content' => $this->args['post_content'] ?? '',
            'guid' => get_bloginfo('wpurl') . '/' . $this->slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'comment_count' => 0,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
            'post_excerpt' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'post_mime_type' => '',
            'filter' => 'raw',
        ], $this->args);

        $post = (object) $post_array;
        $posts = [$post];

        // Set WP_Query vars
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
     * Get the contact users for the current blog
     * 
     * @return array
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
     * Get the content for the contact page
     * 
     * @return string
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
