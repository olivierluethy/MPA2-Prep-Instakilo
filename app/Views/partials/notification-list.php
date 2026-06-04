<?php

use App\Core\View;

/**
 * Renders a sequence of notification <li> rows. Shared by the page (first
 * paint) and the poll/more endpoints (real-time + pagination) so the list
 * markup has exactly one definition.
 *
 * @var array<int, array> $items
 */
foreach ($items as $n) {
    echo View::partial('partials.notification', ['n' => $n]);
}
