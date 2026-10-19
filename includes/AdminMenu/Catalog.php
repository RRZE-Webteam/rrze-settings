<?php

namespace RRZE\Settings\AdminMenu;

defined('ABSPATH') || exit;

/**
 * Bundled menu metadata and bounded discoveries, scoped to the current network.
 */
class Catalog
{
    public const DISCOVERIES_OPTION = 'rrze_settings_admin_menu_discoveries';
    public const MAX_DISCOVERIES = 200;

    /**
     * Read the reviewed catalogue without scanning or loading installed plugins.
     *
     * @return array<string,array{label:string,slugs:string[]}> Bundled entries.
     */
    public static function defaults(): array
    {
        return require __DIR__ . '/defaults.php';
    }

    /**
     * Validate a persistent local menu identifier; exclude URLs and query tokens.
     *
     * @param mixed $slug Candidate registration slug.
     * @return bool Whether the identifier can be stored as a discovery.
     */
    public static function isDiscoverable($slug): bool
    {
        return is_string($slug) && preg_match('/\A[a-zA-Z0-9_.\/-]{1,200}\z/D', $slug) === 1;
    }

    /**
     * Read stored discoveries defensively, without granting menu access.
     *
     * @return array<string,array{label:string,slugs:string[]}> Valid discoveries.
     */
    public static function discoveries(): array
    {
        $stored = get_site_option(self::DISCOVERIES_OPTION, []);
        $entries = [];
        foreach (is_array($stored) ? array_slice($stored, 0, self::MAX_DISCOVERIES, true) : [] as $entry) {
            if (!is_array($entry) || !self::isDiscoverable($entry['slug'] ?? null) || !is_string($entry['label'] ?? null)) {
                continue;
            }
            $slug = $entry['slug'];
            $entries[self::discoveryId($slug)] = ['label' => self::cleanLabel($entry['label'], $slug), 'slugs' => [$slug]];
        }
        return $entries;
    }

    /**
     * Retain discovered IDs so saved positions can migrate to later bundled IDs.
     *
     * @return array<string,array{label:string,slugs:string[]}> Complete catalogue.
     */
    public static function entries(): array
    {
        return self::defaults() + self::discoveries();
    }

    /**
     * Remember new local registrations only during a network administrator's visit.
     *
     * No timestamps or site IDs are stored, and unchanged visits never write.
     *
     * @param array $menu Actual Settings submenu before sorting.
     * @return void
     */
    public static function discover(array $menu): void
    {
        if (!current_user_can('manage_network_options')) {
            return;
        }
        $known = array_fill_keys(Order::CORE, true);
        $stored = [];
        foreach (self::entries() as $id => $entry) {
            foreach ($entry['slugs'] as $slug) {
                $known[$slug] = true;
            }
            if (str_starts_with($id, 'discovered-')) {
                $stored[$id] = ['label' => $entry['label'], 'slug' => $entry['slugs'][0]];
            }
        }
        $changed = false;
        foreach ($menu as $item) {
            $slug = $item[2] ?? null;
            if (!self::isDiscoverable($slug) || isset($known[$slug]) || count($stored) >= self::MAX_DISCOVERIES) {
                continue;
            }
            $stored[self::discoveryId($slug)] = ['slug' => $slug, 'label' => self::cleanLabel((string) ($item[0] ?? ''), $slug)];
            $known[$slug] = true;
            $changed = true;
        }
        if ($changed) {
            update_site_option(self::DISCOVERIES_OPTION, $stored);
        }
    }

    /**
     * Derive a stable form-safe ID without changing the registered menu slug.
     *
     * @param string $slug Exact menu registration identifier.
     * @return string Stable discovery ID.
     */
    public static function discoveryId(string $slug): string
    {
        return 'discovered-' . hash('sha256', $slug);
    }

    /**
     * Remove markup and bound labels copied from third-party menu registrations.
     *
     * @param string $label Registered label.
     * @param string $fallback Slug used for an empty label.
     * @return string Plain text display label.
     */
    private static function cleanLabel(string $label, string $fallback): string
    {
        $label = trim(sanitize_text_field(wp_strip_all_tags($label)));
        return $label === '' ? $fallback : mb_substr($label, 0, 160);
    }
}
