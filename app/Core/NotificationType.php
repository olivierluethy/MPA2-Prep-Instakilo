<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Notification type registry — the single source of truth for the whole
 * notification framework.
 *
 * Adding a new notification kind (repost, save, mention, message, share, …) is
 * done HERE and only here: append a descriptor to {@see self::TYPES}, and emit
 * it with Notification::create(...). The model derives its grouping SQL from
 * this registry, and the view renders the message/icon/target through these
 * presenter methods — so no other file needs to learn about the new type.
 *
 * Descriptor keys:
 *   group  bool   Collapse repeat events about the same `reference_id` into one
 *                 line ("A, B and N others …"). false = one line per event.
 *   action ?string Inline action the recipient can take ('follow' = follow-back).
 */
final class NotificationType
{
    /** @var array<string, array{group: bool, action: ?string}> */
    public const TYPES = [
        'like'    => ['group' => true,  'action' => null],
        'comment' => ['group' => true,  'action' => null],
        'follow'  => ['group' => false, 'action' => 'follow'],
        // --- future types plug in here, e.g.: ---
        // 'repost'  => ['group' => true,  'action' => null],
        // 'save'    => ['group' => true,  'action' => null],
        // 'mention' => ['group' => false, 'action' => null],
        // 'message' => ['group' => false, 'action' => null],
        // 'share'   => ['group' => false, 'action' => null],
    ];

    public static function isKnown(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function isGrouping(string $type): bool
    {
        return self::TYPES[$type]['group'] ?? false;
    }

    /** @return array<int, string> types whose events collapse by reference_id */
    public static function groupingTypes(): array
    {
        return array_keys(array_filter(self::TYPES, static fn ($d) => $d['group']));
    }

    public static function action(string $type): ?string
    {
        return self::TYPES[$type]['action'] ?? null;
    }

    /**
     * Where clicking the notification should navigate.
     *
     * @param array{type:string, key:int, actor_id:int, secondary:?int} $item
     */
    public static function url(array $item): string
    {
        return match ($item['type']) {
            'like'    => url('post?id=' . (int) $item['key']),
            'comment' => url('post?id=' . (int) $item['key']
                         . ($item['secondary'] ? '#comment-' . (int) $item['secondary'] : '')),
            'follow'  => url('profile/visit?id=' . (int) $item['actor_id']),
            default   => url('home'),
        };
    }

    /**
     * The human-readable message as safe HTML (actor names escaped + bolded).
     *
     * @param array{type:string, total:int, actors:array<int, array{id:int, username:string}>} $item
     */
    public static function message(array $item): string
    {
        $names = self::actorLabel($item['actors'], (int) $item['total']);
        $plural = (int) $item['total'] > 1;

        return match ($item['type']) {
            'like'    => $names . ' gefällt dein Beitrag.',
            'comment' => $names . ' ' . ($plural ? 'haben' : 'hat') . ' deinen Beitrag kommentiert.',
            'follow'  => $names . ' folgt dir jetzt.',
            default   => $names . ' hat mit dir interagiert.',
        };
    }

    /** A small inline SVG badge icon for the type. */
    public static function icon(string $type): string
    {
        $paths = [
            'like'    => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
            'comment' => 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z',
            'follow'  => 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z',
        ];
        $d = $paths[$type] ?? 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z';

        return '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">'
            . '<path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
    }

    /** Tailwind colour for the type's icon badge. */
    public static function accent(string $type): string
    {
        return match ($type) {
            'like'    => 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300',
            'comment' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-300',
            'follow'  => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300',
            default   => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    /**
     * "<b>A</b>", "<b>A</b> und <b>B</b>", "<b>A</b>, <b>B</b> und N weitere".
     *
     * @param array<int, array{id:int, username:string}> $actors newest first
     */
    private static function actorLabel(array $actors, int $total): string
    {
        $bold = static fn (string $name): string =>
            '<span class="font-semibold">' . e($name) . '</span>';

        $names = array_map(static fn ($a) => $bold((string) $a['username']), $actors);

        if ($total <= 1) {
            return $names[0] ?? $bold('Jemand');
        }
        if ($total === 2 && count($names) >= 2) {
            return $names[0] . ' und ' . $names[1];
        }
        // 3+: show the two most recent, then "und N weitere".
        $shown = array_slice($names, 0, 2);
        $others = $total - count($shown);
        return implode(', ', $shown) . ' und ' . $others . ' weitere';
    }
}
