<?php

namespace RRZE\Settings\AdminMenu;

defined('ABSPATH') || exit;

/**
 * Pure ordering rules shared by the network form and website navigation.
 */
class Order
{
    /** @var string[] WordPress Settings pages whose relative order is retained. */
    public const CORE = [
        'options-general.php', 'options-connectors.php', 'options-writing.php',
        'options-reading.php', 'options-discussion.php', 'options-media.php',
        'options-permalink.php', 'options-privacy.php',
    ];

    /**
     * Keep saved priorities and append new defaults, excluding unaccepted discoveries.
     *
     * @param array $defaults Bundled entries keyed by stable identifier.
     * @param array $catalog All known entries, including discoveries.
     * @param array $saved Previously saved identifiers.
     * @return string[] Effective identifiers without duplicates.
     */
    public static function ids(array $defaults, array $catalog, array $saved): array
    {
        $ids = [];
        $canonical = [];
        foreach ($defaults as $id => $entry) {
            foreach ($entry['slugs'] as $slug) {
                $canonical[$slug] = $id;
            }
        }
        foreach (array_merge($saved, array_keys($defaults)) as $id) {
            if (is_string($id) && isset($catalog[$id])) {
                // Preserve a discovery's position when a later release bundles it.
                foreach ($catalog[$id]['slugs'] as $slug) {
                    if (isset($canonical[$slug])) {
                        $id = $canonical[$slug];
                        break;
                    }
                }
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    /**
     * Sort only existing items, retaining every field and duplicate registration.
     *
     * @param array $menu Original submenu in WordPress order.
     * @param array $catalog Known labels and slug aliases.
     * @param string[] $ids Effective entry order.
     * @return array Ordered submenu with consecutive numeric keys.
     */
    public static function apply(array $menu, array $catalog, array $ids): array
    {
        $ranks = [];
        foreach ($ids as $rank => $id) {
            foreach ($catalog[$id]['slugs'] ?? [] as $slug) {
                $ranks[$slug] ??= $rank;
            }
        }
        $general = $core = $ranked = $unknown = [];
        foreach ($menu as $item) {
            $slug = $item[2] ?? '';
            if ($slug === 'options-general.php') {
                $general[] = $item;
            } elseif (in_array($slug, self::CORE, true)) {
                $core[] = $item;
            } elseif (isset($ranks[$slug])) {
                $ranked[$ranks[$slug]][] = $item;
            } else {
                $unknown[] = $item;
            }
        }
        ksort($ranked, SORT_NUMERIC);
        $ordered = array_merge($general, $core);
        foreach ($ranked as $items) {
            $ordered = array_merge($ordered, $items);
        }
        return array_merge($ordered, $unknown);
    }

    /**
     * Convert submitted positions to a stable order, ignoring malformed values.
     *
     * @param string[] $ids Allowed identifiers in their previous order.
     * @param array $positions Submitted position per identifier.
     * @return string[] Sorted identifiers; equal positions preserve prior order.
     */
    public static function fromPositions(array $ids, array $positions): array
    {
        $ranked = [];
        foreach ($ids as $index => $id) {
            $value = $positions[$id] ?? null;
            $position = is_scalar($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;
            $ranked[] = [
                'id' => $id,
                'position' => $position !== false && $position >= 1 && $position <= 1000 ? $position : $index + 1,
                'index' => $index,
            ];
        }
        usort($ranked, [self::class, 'comparePositions']);
        return array_column($ranked, 'id');
    }

    /**
     * Break position ties using the original order.
     *
     * @param array $left First ranked entry.
     * @param array $right Second ranked entry.
     * @return int Comparison result.
     */
    private static function comparePositions(array $left, array $right): int
    {
        return ($left['position'] <=> $right['position']) ?: ($left['index'] <=> $right['index']);
    }
}
