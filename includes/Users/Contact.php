<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

/**
 * Contact
 *
 * Creates a virtual frontend page (/contact) that lists site admins —
 * but only if there is NO real page with the same slug.
 * The implementation is carefully scoped so it never interferes with
 * Customizer changesets or admin/ajax queries (prevents "missing_post").
 * 
 * @package RRZE\Settings\Users
 */
class Contact
{
    /** @var string Slug for the virtual page (empty string disables the feature) */
    protected $slug = '';

    /** @var array Arguments (title/content) for the virtual page */
    protected $args = [];

    /** @var int Current blog/site ID (for multisite) */
    protected $currentBlogId;

    /**
     * Use a negative ID to avoid collisions and make it obvious this is not a real DB post.
     */
    const VIRTUAL_CONTACT_ID = -999999;

    /**
     * Entry point: defer bootstrapping to `init` so translations and core are fully ready.
     * 
     * @return void
     */
    public function loaded(): void
    {
        // Skip on main site (as per original logic)
        if (function_exists('is_main_site') && is_main_site()) {
            return;
        }

        $this->currentBlogId = get_current_blog_id();

        // Build args and only then attach filters if we actually need the virtual page
        add_action('init', [$this, 'bootstrap']);
    }

    /**
     * Build page args and attach filters only when no real page with this slug exists.
     * 
     * @return void
     */
    public function bootstrap(): void
    {
        $this->setPostArgs();

        // If slug is empty, a real page exists or something went wrong → do not hook anything
        if (empty($this->slug)) {
            return;
        }

        // Only these two filters are needed to inject a virtual post.
        // Accept 2 args to inspect the WP_Query instance and bail out safely.
        add_filter('posts_results', [$this, 'postsResultsFilter'], 10, 2);
        add_filter('the_posts',     [$this, 'thePostsFilter'],     10, 2);

        // Prevent editing/deleting the virtual post
        add_filter('map_meta_cap',  [$this, 'mapMetaCapFilter'],   10, 4);
    }

    /**
     * Get the requested path from the WP_Query. Trim slashes.
     * 
     * @param \WP_Query $query
     * @return string
     */
    protected function getRequestedPath(\WP_Query $query): string
    {
        $requested = (string) $query->get('pagename');
        if ($requested === '') {
            // If pagename is missing, we can't reliably distinguish hierarchy.
            // Bail by returning empty to skip virtual injection.
            return '';
        }
        return trim($requested, '/');
    }

    /**
     * Initialize slug/title/content for the virtual page.
     * If a real page with the same slug already exists, disable the virtual page.
     * 
     * @return void
     */
    public function setPostArgs(): void
    {
        $slug = _x('contact', 'post_name (slug)', 'rrze-settings');

        // 1) Direct hierarchical path 'contact' (top-level only) – quick check
        $existing = get_page_by_path($slug, OBJECT, 'page');

        // 2) Fallback: find ANY page whose post_name equals the localized slug, no matter the parent
        if (!$existing) {
            $q = new \WP_Query([
                'post_type'      => 'page',
                'name'           => $slug, // matches post_name
                'post_status'    => ['publish', 'private', 'draft', 'pending', 'future'],
                'posts_per_page' => 1,
                'no_found_rows'  => true,
                'fields'         => 'ids',
            ]);
            if (!empty($q->posts)) {
                $existing = get_post((int) $q->posts[0]);
            }
        }

        // 3) Safety net: resolve absolute URL to a post (covers odd permalinks)
        if (!$existing) {
            $maybeId = url_to_postid(home_url('/' . $slug . '/'));
            if ($maybeId) {
                $existing = get_post($maybeId);
            }
        }

        // If any real page exists (at any depth), disable virtual
        if ($existing instanceof \WP_Post) {
            $this->slug = '';
            $this->args = [];
            return;
        }

        // Otherwise define virtual page args
        $this->args = [
            'post_name'    => $slug,
            'post_title'   => _x('Contact', 'post_title', 'rrze-settings'),
            'post_content' => $this->getPostContent(),
        ];
        $this->slug = $slug;
    }

    /**
     * Inject a virtual post at the SQL-results level when the main query
     * requests /{slug}. Never touch admin, AJAX, Customizer or changeset queries.
     *
     * @param \WP_Post[] $posts
     * @param \WP_Query  $query
     * @return \WP_Post[]
     */
    public function postsResultsFilter($posts, $query): array
    {
        if (empty($this->slug)) return $posts;
        if (is_admin() || wp_doing_ajax() || (function_exists('is_customize_preview') && is_customize_preview())) return $posts;

        $pt = (array) $query->get('post_type');
        if (in_array('customize_changeset', $pt, true)) return $posts;
        if (!$query->is_main_query()) return $posts;

        $requested = $this->getRequestedPath($query);
        if ($requested === '') return $posts;          // can't trust hierarchy ⇒ skip
        if ($requested !== $this->slug) return $posts; // strict: no 'parent/child'

        // One more safety: if this URL resolves to a real post, do nothing
        $absUrl   = home_url('/' . $requested . '/');
        $realPost = url_to_postid($absUrl);
        if ($realPost) return $posts;

        return [$this->getVirtualWPPost()];
    }

    /**
     * Fallback at the loop level. If no posts were found but the request
     * matches the slug, provide the virtual post. Same safety bails as above.
     *
     * @param \WP_Post[] $posts
     * @param \WP_Query  $query
     * @return \WP_Post[]
     */
    public function thePostsFilter($posts, $query): array
    {
        if (empty($this->slug)) return $posts;
        if (is_admin() || wp_doing_ajax() || (function_exists('is_customize_preview') && is_customize_preview())) return $posts;

        $pt = (array) $query->get('post_type');
        if (in_array('customize_changeset', $pt, true)) return $posts;

        if (!$query->is_main_query() || !empty($posts)) return $posts;

        $requested = $this->getRequestedPath($query);
        if ($requested === '') return $posts;          // ambiguous ⇒ skip
        if ($requested !== $this->slug) return $posts; // must be exact /{slug}

        $absUrl   = home_url('/' . $requested . '/');
        $realPost = url_to_postid($absUrl);
        if ($realPost) return $posts;

        return [$this->getVirtualWPPost()];
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
    public function mapMetaCapFilter($caps, $cap, $user_id, $args): array
    {
        // Only intercept for post-edit/delete caps
        if (!in_array($cap, ['edit_post', 'delete_post', 'edit_page', 'delete_page'], true)) {
            return $caps;
        }

        // If the target is our virtual ID, deny
        if (!empty($args) && (int) $args[0] === self::VIRTUAL_CONTACT_ID) {
            return ['do_not_allow'];
        }

        return $caps;
    }

    /**
     * Build the virtual WP_Post object.
     *
     * @return \WP_Post
     */
    protected function getVirtualWPPost(): \WP_Post
    {
        $postArray = array_merge([
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
            'guid'                  => trailingslashit(home_url()) . $this->slug,
            'menu_order'            => 0,
            'post_type'             => 'page',
            'post_mime_type'        => '',
            'comment_count'         => 0,
            'filter'                => 'raw',
        ], $this->args);

        return new \WP_Post((object) $postArray);
    }

    /**
     * Collect site admins for this blog.
     *
     * @return array<int, array{ID:int,display_name:string,user_email:string}>
     */
    protected function getContactUsers(): array
    {
        $raw = get_users([
            'blog_id'  => $this->currentBlogId,
            'role__in' => ['administrator'],
            'fields'   => ['ID', 'display_name', 'user_email'],
        ]);

        $out = [];
        if (!is_array($raw)) {
            return $out;
        }

        foreach ($raw as $u) {
            // $u is stdClass with the requested props
            $out[] = [
                'ID'           => (int) ($u->ID ?? 0),
                'display_name' => (string) ($u->display_name ?? ''),
                'user_email'   => (string) ($u->user_email ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Render the page content (simple HTML list of admins).
     *
     * @return string
     */
    protected function getPostContent(): string
    {
        $users = $this->getContactUsers();
        if (empty($users)) {
            return '';
        }

        $out = '<h3>' . esc_html__('Contact persons', 'rrze-settings') . '</h3>';

        foreach ($users as $user) {
            $email_raw   = sanitize_email($user['user_email']);
            $email_disp  = antispambot($email_raw);
            $mailto_href = sprintf('mailto:%s', rawurlencode($email_raw));

            $out .= sprintf(
                '<p>%1$s<br/>%2$s <a href="%3$s">%4$s</a></p>' . PHP_EOL,
                esc_html($user['display_name']),
                esc_html__('Email Address:', 'rrze-settings'),
                esc_attr($mailto_href),
                esc_html($email_disp)
            );
        }

        return $out;
    }
}
