<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

/**
 * Group existing network settings links without changing their access rules.
 */
class AdminNavigation
{
    /**
     * Register navigation enhancements only in network administration.
     *
     * @return void
     */
    public function loaded(): void
    {
        if (!is_network_admin()) {
            return;
        }

        add_action('network_admin_menu', [$this, 'groupSubmenu'], PHP_INT_MAX);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Define the shared order, CSS identifiers and translated group labels.
     *
     * @return array<string, array{label: string, pages: string[]}>
     */
    protected function getGroups(): array
    {
        return [
            'network' => [
                'label' => __('Network', 'rrze-settings'),
                'pages' => ['rrze-settings', 'rrze-settings-users', 'rrze-settings-governance', 'legal'],
            ],
            'editorial' => [
                'label' => __('Editorial', 'rrze-settings'),
                'pages' => ['rrze-settings-posts', 'rrze-settings-writing', 'rrze-settings-menus', 'rrze-settings-media'],
            ],
            'services' => [
                'label' => __('Services', 'rrze-settings'),
                'pages' => ['rrze-settings-website-functions', 'rrze-settings-plugins', 'rrze-settings-mail'],
            ],
            'technical' => [
                'label' => __('Technical', 'rrze-settings'),
                'pages' => ['rrze-settings-crawlers', 'rrze-settings-rest-api', 'rrze-settings-csp', 'rrze-settings-heartbeat', 'rrze-settings-advanced'],
            ],
            'other' => [
                'label' => __('Other Settings', 'rrze-settings'),
                'pages' => [],
            ],
        ];
    }

    /**
     * Sort registered links, retaining capabilities, URLs and unknown extensions.
     *
     * WordPress renders item index 4 as CSS classes and still performs its usual
     * capability checks. No placeholder pages or links are registered for headings.
     *
     * @return void
     */
    public function groupSubmenu(): void
    {
        global $submenu;

        if (empty($submenu['rrze-settings'])) {
            return;
        }

        $remaining = $submenu['rrze-settings'];
        $ordered = [];
        foreach ($this->getGroups() as $group => $definition) {
            foreach ($definition['pages'] as $page) {
                foreach ($remaining as $index => $item) {
                    if (($item[2] ?? '') !== $page) {
                        continue;
                    }
                    $ordered[] = $this->withGroup($item, $group);
                    unset($remaining[$index]);
                }
            }
        }

        foreach ($remaining as $item) {
            $ordered[] = $this->withGroup($item, 'other');
        }
        $submenu['rrze-settings'] = $ordered;
    }

    /**
     * Add one grouping class while retaining classes supplied by other plugins.
     *
     * @param array $item Original WordPress submenu item.
     * @param string $group Group identifier from getGroups().
     * @return array Decorated submenu item.
     */
    protected function withGroup(array $item, string $group): array
    {
        $classes = preg_split('/\s+/', trim((string) ($item[4] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        $retained = [];
        foreach ($classes as $class) {
            if (!str_starts_with($class, 'rrze-settings-menu-group-')) {
                $retained[] = $class;
            }
        }
        $retained[] = 'rrze-settings-menu-group-' . $group;
        $item[4] = implode(' ', $retained);
        return $item;
    }

    /**
     * Load the small enhancement on network screens where this menu is present.
     *
     * @return void
     */
    public function enqueueScripts(): void
    {
        global $submenu;

        if (empty($submenu['rrze-settings'])) {
            return;
        }

        $asset = include plugin()->getPath('build') . 'admin/admin.asset.php';
        wp_enqueue_script(
            'rrze-settings-navigation',
            plugins_url('build/admin/admin.js', plugin()->getBasename()),
            $asset['dependencies'],
            $asset['version'],
            true
        );
        $labels = [];
        foreach ($this->getGroups() as $group => $definition) {
            $labels[$group] = $definition['label'];
        }
        wp_localize_script('rrze-settings-navigation', 'rrzeSettingsNavigation', ['groups' => $labels]);
    }
}
