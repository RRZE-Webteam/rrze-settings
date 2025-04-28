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
     * @var string
     */
    protected $slug = '';

    /**
     * @var array
     */
    protected $args = [];

    /**
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
     * Set the post args
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
     * Map meta capability filter
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
     * The posts filter
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

        $post = new \stdClass;
        $post->post_author = 1;
        $post->post_name = '';
        $post->post_title = '';
        $post->post_content = '';
        $post->guid = get_bloginfo('wpurl') . '/' . $this->slug;
        $post->post_type = 'page';
        $post->ID = -1;
        $post->post_status = 'publish';
        $post->comment_status = 'closed';
        $post->ping_status = 'closed';
        $post->comment_count = 0;
        $post->post_date = current_time('mysql');
        $post->post_date_gmt = current_time('mysql', 1);

        $post = (object) array_merge((array) $post, (array) $this->args);
        $posts = null;
        $posts[] = $post;

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
     * Get contact users
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
     * Get the post content
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
            $output .= sprintf('<p>%1$s<br/>%2$s %3$s</p>' . PHP_EOL, $user->display_name, __('Email Address:', 'rrze-settings'), make_clickable($user->user_email));
        }

        return $output;
    }
}
