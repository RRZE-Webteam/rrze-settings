<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

/**
 * Search class
 *
 * @package RRZE\Settings\Users
 */
class Search
{
    /**
     * Plugin loaded action
     */
    public function loaded()
    {
        // Users search enhanced
        // Fires after the WP_User_Query has been parsed, and before the query is executed.
        // The passed WP_User_Query object contains SQL parts formed from parsing the given query.
        // The current WP_User_Query instance, passed by reference.
        // https://developer.wordpress.org/reference/hooks/pre_user_query/
        add_action('pre_user_query', [$this, 'preUserQuery']);
    }

    /**
     * Pre user query
     *
     * @param object \WP_User_Query $wpUserQuery
     * @return void
     */
    public function preUserQuery($wpUserQuery)
    {
        if (
            strpos($wpUserQuery->query_where, '@') !== false
            || stripos($_SERVER['REQUEST_URI'], 'users.php') === false
            || empty($_GET['s'])
        ) {
            return;
        }

        global $wpdb;

        $terms = $this->getUserSearchTerms();
        $metaKeys = $this->getUserMetaKeys();

        // AND (default) or an OR?
        $searchWithOr = array_search('OR', $terms);

        if ($searchWithOr !== false) {
            // Remove the OR keyword from the terms
            unset($terms[$searchWithOr]);

            // Reset the array keys
            $terms = array_values($terms);
        }

        // Set @meta_keys MySQL user variable
        $wpdb->query($sql = $wpdb->prepare("SET @meta_keys := %s;", implode(',', $metaKeys)));

        // Build the data for $wpdb->prepare()
        $values = [];

        foreach ($terms as $term) {
            $values[] = "%{$term}%";
        }

        // The last value is for HAVING COUNT(*), so it is added.
        // Note that the minimum count is 1 if OR is found in the terms.
        $values[] = ($searchWithOr !== false ? 1 : count($values));

        // Query for matching users
        $userIds = $wpdb->get_col($sql = $wpdb->prepare("
                SELECT user_id
                FROM (" . implode('UNION ALL', array_fill(0, count($terms), "
                    SELECT DISTINCT u.ID AS user_id
                    FROM {$wpdb->users} u
                    INNER JOIN {$wpdb->usermeta} um
                    ON um.user_id = u.ID
                    WHERE (
                        (@term := '%s') IS NOT NULL
                        AND FIND_IN_SET(um.meta_key, @meta_keys)
                        AND LOWER(um.meta_value) LIKE @term
                    )
                    OR LOWER(u.user_login) LIKE @term
                    OR LOWER(u.user_nicename) LIKE @term
                    OR LOWER(u.user_email) LIKE @term
                    OR LOWER(u.user_url) LIKE @term
                    OR LOWER(u.display_name) LIKE @term
                ")) . ") AS user_search_union
                GROUP BY user_id
                HAVING COUNT(*) >= %d;
            ", $values));

        // Change query to include our new user IDs
        if (is_array($userIds) && count($userIds)) {
            $idString = implode(',', $userIds);

            $extraSql = " OR ID IN ({$idString})";

            if (substr($wpUserQuery->query_where, -1) == ')') {
                // Additional query before the closing )
                $wpUserQuery->query_where = substr($wpUserQuery->query_where, 0, -1) . $extraSql . substr($wpUserQuery->query_where, -1);
            } else {
                // Additional query at the end
                $wpUserQuery->query_where .= $extraSql;
            }
        }
    }

    /**
     * Get user search terms
     *
     * @return array
     */
    protected function getUserSearchTerms()
    {
        $terms = trim(strtolower(stripslashes($_GET['s'])));

        $terms = explode(' ', $terms);

        foreach ($terms as $key => $term) {
            if (empty($term)) {
                unset($terms[$key]);
            }
        }

        $terms = array_values($terms);

        return $terms;
    }

    /**
     * Get user meta keys
     *
     * @return array
     */
    protected function getUserMetaKeys()
    {
        $metaKeys = ['first_name', 'last_name'];
        return $metaKeys;
    }
}
