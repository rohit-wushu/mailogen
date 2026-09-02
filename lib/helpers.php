<?php
/**
 * Global helper functions.
 */

declare(strict_types=1);

/** HTML-escape for output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute URL within the app. */
function url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Same as url(), but appends the file's own mtime as a ?v= cache-buster.
 * Forces browsers and any intermediary CDN to fetch a fresh copy under a new
 * cache key whenever the file changes on disk — no manual version bumping,
 * and no risk of a stale cached asset (e.g. from an earlier broken deploy)
 * surviving indefinitely.
 */
function asset_url(string $path): string
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : time();
    return url($path) . '?v=' . $v;
}

/** Issue a redirect and stop. */
function redirect(string $path): never
{
    $location = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $location);
    exit;
}

/** Read a request value (GET/POST). */
function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/** Trimmed string request value. */
function str_input(string $key, string $default = ''): string
{
    $v = input($key, $default);
    return is_string($v) ? trim($v) : $default;
}

/** Integer request value. */
function int_input(string $key, int $default = 0): int
{
    return (int) (input($key, $default));
}

// -------------------------------------------------------------------
//  CSRF
// -------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && !empty($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $sent);
}

/**
 * Guard POST requests against CSRF. A mismatch is almost always an expired
 * session / stale tab rather than an attack, so instead of dead-ending the user
 * on a raw white page we recover gracefully: AJAX callers get a clean JSON 419,
 * normal form posts are bounced back to where they came from with a friendly
 * flash so they can simply retry on a styled page.
 */
function csrf_guard(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || csrf_verify()) {
        return;
    }

    $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($wantsJson) {
        http_response_code(419);
        header('Content-Type: application/json');
        exit(json_encode(['ok' => false, 'error' => 'Your session expired. Please reload the page and try again.']));
    }

    flash('error', 'Your session expired — please try again.');
    // Return to the originating page when it's same-origin, else a safe default.
    $ref  = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url(APP_URL, PHP_URL_HOST);
    if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === $host) {
        redirect($ref);
    }
    redirect(Auth::check() ? 'dashboard' : 'login');
}

// -------------------------------------------------------------------
//  Flash messages
// -------------------------------------------------------------------
function flash(string $type, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$type] = $message;
        return null;
    }
    $msg = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return $msg;
}

/** Personalise a template body/subject with lead placeholders. */
function render_placeholders(string $text, array $lead): string
{
    $map = [
        '{{first_name}}' => $lead['first_name'] ?? '',
        '{{last_name}}'  => $lead['last_name'] ?? '',
        '{{name}}'       => trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? '')),
        '{{email}}'      => $lead['email'] ?? '',
        '{{company}}'    => $lead['company'] ?? '',
        '{{phone}}'      => $lead['phone'] ?? '',
    ];

    // Any extra scalar key on the record becomes a merge tag too — this is what
    // lets arbitrary Google Sheet / CSV columns work as {{Column Header}} (and
    // a lower-case {{column header}} variant). Stored custom_fields JSON is
    // expanded the same way. Built-in tags above always win.
    $extra = $lead;
    if (isset($lead['custom_fields'])) {
        $cf = is_array($lead['custom_fields'])
            ? $lead['custom_fields']
            : json_decode((string) $lead['custom_fields'], true);
        if (is_array($cf)) {
            $extra = array_merge($extra, $cf);
        }
    }
    foreach ($extra as $key => $val) {
        if (!is_string($key) || $key === '' || is_array($val) || is_object($val)) {
            continue;
        }
        $val = (string) $val;
        foreach (['{{' . $key . '}}', '{{' . strtolower($key) . '}}'] as $tag) {
            if (!isset($map[$tag])) {
                $map[$tag] = $val;
            }
        }
    }

    return strtr($text, $map);
}

/** Best-effort client IP (used for rate limiting). */
function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/** Random URL-safe tracking id. */
function tracking_id(): string
{
    return bin2hex(random_bytes(16));
}

/**
 * Render a view file with the layout.
 *
 * The path/data/title params are underscore-prefixed so a data key of the same
 * name (e.g. 'template') extracts correctly instead of being skipped by
 * EXTR_SKIP and leaving the view to read the function's parameter by mistake.
 */
function view(string $__view, array $__data = [], string $__title = ''): void
{
    extract($__data, EXTR_SKIP);
    $pageTitle = $__title;
    require BASE_PATH . '/includes/layout/header.php';
    require BASE_PATH . '/views/' . $__view . '.php';
    require BASE_PATH . '/includes/layout/footer.php';
}

/** Render a view without the dashboard chrome (auth pages). */
function view_bare(string $__view, array $__data = [], string $__title = ''): void
{
    extract($__data, EXTR_SKIP);
    $pageTitle = $__title;
    require BASE_PATH . '/views/' . $__view . '.php';
}

/** Safely encode a value for embedding inside a <script> block (XSS-hardened). */
function js(mixed $value): string
{
    return json_encode(
        $value,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
    ) ?: 'null';
}

/** Emit a JSON response and stop. */
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

/** Format a datetime for display. */
function fmt_dt(?string $dt): string
{
    if (empty($dt)) {
        return '-';
    }
    return date('d M Y, H:i', strtotime($dt));
}

/** Coloured status pill for campaigns / automations / queue. */
function status_badge(string $status): string
{
    $map = [
        'draft'       => 'secondary',
        'scheduled'   => 'info',
        'running'     => 'primary',
        'sending'     => 'primary',
        'active'      => 'success',
        'paused'      => 'warning',
        'completed'   => 'success',
        'cancelled'   => 'secondary',
        'failed'      => 'danger',
        'queued'      => 'info',
        'sent'        => 'success',
        'verified'    => 'success',
        'unknown'     => 'secondary',
    ];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '-subtle text-' . $cls . '-emphasis badge-soft text-capitalize">' . e($status) . '</span>';
}

/** Friendly label for an SMTP provider key. */
function provider_label(string $key): string
{
    return [
        'google_workspace' => 'Google Workspace',
        'gmail'            => 'Gmail',
        'brevo'            => 'Brevo',
        'ses'              => 'Amazon SES',
        'mailgun'          => 'Mailgun',
        'sendgrid'         => 'SendGrid',
        'custom'           => 'Custom SMTP',
    ][$key] ?? ucfirst($key);
}

/**
 * Render a tiny inline SVG sparkline (smooth area) from a numeric series.
 * Lightweight, no JS — used on the dashboard stat cards.
 */
function sparkline_svg(array $values, string $color = '#6366f1', int $w = 120, int $h = 40): string
{
    $values = array_map('floatval', $values);
    if (count($values) < 2) {
        $values = [0, 0];
    }
    $min = min($values);
    $max = max($values);
    $range = ($max - $min) ?: 1;
    $n = count($values);
    $step = $w / ($n - 1);
    $pad = 3;
    $usable = $h - $pad * 2;

    $pts = [];
    foreach ($values as $i => $v) {
        $x = round($i * $step, 2);
        $y = round($h - $pad - (($v - $min) / $range) * $usable, 2);
        $pts[] = [$x, $y];
    }
    $line = '';
    foreach ($pts as $i => [$x, $y]) {
        $line .= ($i === 0 ? "M$x,$y" : " L$x,$y");
    }
    $area = $line . " L{$w},{$h} L0,{$h} Z";
    $id = 'sg' . substr(md5($color . $n . implode(',', $values)), 0, 6);

    return '<svg class="spark" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">'
        . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="0" y2="1">'
        . '<stop offset="0%" stop-color="' . $color . '" stop-opacity="0.28"/>'
        . '<stop offset="100%" stop-color="' . $color . '" stop-opacity="0"/></linearGradient></defs>'
        . '<path d="' . $area . '" fill="url(#' . $id . ')"/>'
        . '<path d="' . $line . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>';
}

/**
 * Render a row of compact stat cards (gradient icon + value + delta, no
 * sparkline). Each card: ['label','value','delta','icon','grad', 'suffix'?].
 */
function render_stat_cards(array $cards, int $col = 3): string
{
    $html = '<div class="row g-3 mb-3">';
    foreach ($cards as $c) {
        $delta = (float) ($c['delta'] ?? 0);
        $dir   = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
        $arrow = $delta > 0 ? 'arrow-up' : ($delta < 0 ? 'arrow-down' : 'dash');
        $deltaHtml = isset($c['delta'])
            ? '<span class="delta ' . $dir . '"><i class="bi bi-' . $arrow . '"></i>'
              . ($delta == 0.0 ? '0' : abs($delta)) . '%</span>'
            : '';
        $html .= '<div class="col-6 col-xl-' . $col . '">'
            . '<div class="stat-card" style="grid-template-rows:auto;">'
            . '<div class="stat-icon grad-' . e($c['grad']) . '"><i class="bi bi-' . e($c['icon']) . '"></i></div>'
            . '<div class="stat-top">'
            . '<div class="stat-label">' . e($c['label']) . '</div>'
            . '<div class="d-flex align-items-baseline gap-2"><span class="stat-value">' . e((string) $c['value']) . '</span>' . $deltaHtml . '</div>'
            . '</div></div></div>';
    }
    return $html . '</div>';
}

/** Build a route URL (works with or without pretty URLs). */
function route_url(string $route, array $params = []): string
{
    $u = url($route);
    if ($params !== []) {
        $u .= '?' . http_build_query($params);
    }
    return $u;
}
