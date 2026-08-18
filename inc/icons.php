<?php
/**
 * The whole icon set, inline.
 *
 * One 24×24 stroke-based grid, `currentColor` throughout, no icon font and no
 * network request — so an icon inherits the colour of whatever it sits in and
 * looks identical on the public site and in the admin panel.
 *
 * Usage:  <?= icon('camera') ?>   or   <?= icon('camera', 'w-18') ?>
 */

function icon(string $name, string $class = '', int $size = 24): string
{
    $paths = icon_paths();
    if (!isset($paths[$name])) {
        $name = 'dot';
    }

    [$body, $fillMode] = $paths[$name];

    $attrs = $fillMode === 'fill'
        ? 'fill="currentColor"'
        : 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';

    return '<svg class="icon ' . e($class) . '" width="' . $size . '" height="' . $size . '"'
         . ' viewBox="0 0 24 24" ' . $attrs . ' aria-hidden="true" focusable="false">'
         . $body . '</svg>';
}

/** name => [svg body, 'stroke'|'fill'] */
function icon_paths(): array
{
    static $p = null;
    if ($p !== null) {
        return $p;
    }

    return $p = [

    // ---------------------------------------------------------- brand ----
    'camera' => ['<path d="M3 8.5A2.5 2.5 0 0 1 5.5 6h1.8a1 1 0 0 0 .83-.45l.74-1.1A1 1 0 0 1 9.7 4h4.6a1 1 0 0 1 .83.45l.74 1.1a1 1 0 0 0 .83.45h1.8A2.5 2.5 0 0 1 21 8.5v8A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5z"/><circle cx="12" cy="12.5" r="3.6"/>', 'stroke'],
    'aperture' => ['<circle cx="12" cy="12" r="9"/><path d="M12 3v7M21 12h-7M12 21v-7M3 12h7"/>', 'stroke'],

    // ---------------------------------------------------- hero features ---
    'folder-organised' => ['<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4l1.8 2H19a1.5 1.5 0 0 1 1.5 1.5V17A1.5 1.5 0 0 1 19 18.5H4.5A1.5 1.5 0 0 1 3 17z"/><path d="M7.5 12h9M7.5 15h5.5"/>', 'stroke'],
    'video-frames' => ['<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 9.5h3M3 14.5h3M18 9.5h3M18 14.5h3"/><path d="M10.5 9.8l4 2.2-4 2.2z"/>', 'stroke'],
    'download-cloud' => ['<path d="M7 15.5A3.5 3.5 0 0 1 7.4 8.6a5 5 0 0 1 9.5 1.4A3.2 3.2 0 0 1 17 16.4"/><path d="M12 11v7m0 0l-2.6-2.6M12 18l2.6-2.6"/>', 'stroke'],
    'mobile' => ['<rect x="6.5" y="2.5" width="11" height="19" rx="2.4"/><path d="M10.6 5.6h2.8"/><path d="M12 18.3h.01"/>', 'stroke'],

    // ------------------------------------------------------- why cards ----
    'image-sharp' => ['<rect x="3" y="4.5" width="18" height="15" rx="2.2"/><circle cx="8.6" cy="9.6" r="1.7"/><path d="M3.4 16.6l4.4-4.1a1.8 1.8 0 0 1 2.4 0l4.2 3.9"/><path d="M14 14.2l1.9-1.7a1.8 1.8 0 0 1 2.4 0l2.3 2"/>', 'stroke'],
    'moment-star' => ['<path d="M12 3.6l2.35 4.9 5.35.75-3.9 3.75.94 5.35L12 15.75l-4.74 2.6.94-5.35-3.9-3.75 5.35-.75z"/>', 'stroke'],
    'share-link' => ['<path d="M10 13.5a3.6 3.6 0 0 0 5.4.4l2.6-2.6a3.6 3.6 0 0 0-5.1-5.1l-1.5 1.5"/><path d="M14 10.5a3.6 3.6 0 0 0-5.4-.4L6 12.7a3.6 3.6 0 0 0 5.1 5.1l1.5-1.5"/>', 'stroke'],
    'chat-fast' => ['<path d="M20.5 11.6c0 3.9-3.8 7-8.5 7a9.8 9.8 0 0 1-2.6-.35L4.5 20l1.2-3.3A6.5 6.5 0 0 1 3.5 11.6c0-3.9 3.8-7 8.5-7s8.5 3.1 8.5 7z"/><path d="M9 11.3h.01M12 11.3h.01M15 11.3h.01"/>', 'stroke'],

    // ------------------------------------------------------------ core ----
    'check-circle' => ['<circle cx="12" cy="12" r="9"/><path d="M8.4 12.2l2.5 2.5 4.7-4.9"/>', 'stroke'],
    'check' => ['<path d="M4.5 12.5l5 5 10-11"/>', 'stroke'],
    'close' => ['<path d="M6 6l12 12M18 6L6 18"/>', 'stroke'],
    'plus' => ['<path d="M12 5v14M5 12h14"/>', 'stroke'],
    'minus' => ['<path d="M5 12h14"/>', 'stroke'],
    'dot' => ['<circle cx="12" cy="12" r="3"/>', 'stroke'],
    'search' => ['<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4"/>', 'stroke'],
    'chevron-down' => ['<path d="M6 9.5l6 6 6-6"/>', 'stroke'],
    'chevron-up' => ['<path d="M6 14.5l6-6 6 6"/>', 'stroke'],
    'chevron-left' => ['<path d="M14.5 6l-6 6 6 6"/>', 'stroke'],
    'chevron-right' => ['<path d="M9.5 6l6 6-6 6"/>', 'stroke'],
    'arrow-right' => ['<path d="M4 12h15M13 6l6 6-6 6"/>', 'stroke'],
    'arrow-left' => ['<path d="M20 12H5M11 6l-6 6 6 6"/>', 'stroke'],
    'external' => ['<path d="M14 4h6v6"/><path d="M20 4l-8.5 8.5"/><path d="M18 14.5V18a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.5"/>', 'stroke'],
    'menu' => ['<path d="M4 7h16M4 12h16M4 17h16"/>', 'stroke'],
    'more-vertical' => ['<circle cx="12" cy="5.5" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="12" cy="18.5" r="1.4"/>', 'fill'],
    'more-horizontal' => ['<circle cx="5.5" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="18.5" cy="12" r="1.4"/>', 'fill'],
    'sun' => ['<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.2 5.2l1.4 1.4M17.4 17.4l1.4 1.4M18.8 5.2l-1.4 1.4M6.6 17.4l-1.4 1.4"/>', 'stroke'],
    'moon' => ['<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"/>', 'stroke'],

    // ---------------------------------------------------------- content ---
    'image' => ['<rect x="3" y="4.5" width="18" height="15" rx="2.2"/><circle cx="8.6" cy="9.6" r="1.6"/><path d="M3.4 16.8l4.6-4.3a1.8 1.8 0 0 1 2.4 0l6.4 5.9"/>', 'stroke'],
    'images' => ['<rect x="7" y="3.5" width="14" height="12" rx="2"/><path d="M17 15.5v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"/><circle cx="11.5" cy="8" r="1.4"/><path d="M7.6 13.4l3-2.8a1.6 1.6 0 0 1 2.2 0l4.4 4"/>', 'stroke'],
    'video' => ['<rect x="3" y="6" width="12.5" height="12" rx="2.2"/><path d="M15.5 10.2l4.2-2.4a.6.6 0 0 1 .9.5v7.4a.6.6 0 0 1-.9.5l-4.2-2.4z"/>', 'stroke'],
    'play' => ['<circle cx="12" cy="12" r="9"/><path d="M10.2 8.8l5.2 3.2-5.2 3.2z"/>', 'stroke'],
    'folder' => ['<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4l1.8 2H19a1.5 1.5 0 0 1 1.5 1.5V17A1.5 1.5 0 0 1 19 18.5H4.5A1.5 1.5 0 0 1 3 17z"/>', 'stroke'],
    'folder-plus' => ['<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4l1.8 2H19a1.5 1.5 0 0 1 1.5 1.5V17A1.5 1.5 0 0 1 19 18.5H4.5A1.5 1.5 0 0 1 3 17z"/><path d="M12 11.4v4.2M9.9 13.5h4.2"/>', 'stroke'],
    'folder-move' => ['<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4l1.8 2H19a1.5 1.5 0 0 1 1.5 1.5V17A1.5 1.5 0 0 1 19 18.5H4.5A1.5 1.5 0 0 1 3 17z"/><path d="M8.5 13.5h6m0 0l-2-2m2 2l-2 2"/>', 'stroke'],
    'file-text' => ['<path d="M13.5 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8.5z"/><path d="M13.5 3v5.5H19"/><path d="M8.6 13h6.8M8.6 16.4h4.6"/>', 'stroke'],
    'article' => ['<rect x="3" y="4.5" width="18" height="15" rx="2"/><path d="M6.5 8.6h6M6.5 12h11M6.5 15.4h8"/>', 'stroke'],
    'banner' => ['<rect x="2.5" y="6" width="19" height="12" rx="2"/><path d="M2.5 14l4.6-3.6a1.7 1.7 0 0 1 2.1 0l3.4 2.8"/><circle cx="16.6" cy="10.2" r="1.4"/>', 'stroke'],
    'pages' => ['<rect x="3" y="3.5" width="18" height="17" rx="2"/><path d="M3 8.6h18M8.2 8.6v11.9"/>', 'stroke'],
    'nav-menu' => ['<rect x="3" y="4.5" width="18" height="4" rx="1.4"/><path d="M6.4 12.6h11.2M6.4 17.4h7.4"/>', 'stroke'],

    // -------------------------------------------------------- dashboard ---
    'home' => ['<path d="M4 10.3l8-6.3 8 6.3V19a1.6 1.6 0 0 1-1.6 1.6H5.6A1.6 1.6 0 0 1 4 19z"/><path d="M9.6 20.6v-6.2h4.8v6.2"/>', 'stroke'],
    'chart' => ['<path d="M4 20V4"/><path d="M4 20h16"/><rect x="7.5" y="12" width="3" height="5" rx=".8"/><rect x="12.5" y="8.5" width="3" height="8.5" rx=".8"/><rect x="17" y="14" width="3" height="3" rx=".8"/>', 'stroke'],
    'trend-up' => ['<path d="M3.5 16.5l5.2-5.2 3.3 3.3 6-6"/><path d="M14.4 8.6H18v3.6"/>', 'stroke'],
    'eye' => ['<path d="M2.6 12S6 5.8 12 5.8 21.4 12 21.4 12 18 18.2 12 18.2 2.6 12 2.6 12z"/><circle cx="12" cy="12" r="3"/>', 'stroke'],
    'download' => ['<path d="M12 4v11m0 0l-4-4m4 4l4-4"/><path d="M4.5 18.5h15"/>', 'stroke'],
    'upload' => ['<path d="M12 19V8m0 0L8 12m4-4l4 4"/><path d="M4.5 4.5h15"/>', 'stroke'],
    'share' => ['<circle cx="17.5" cy="6" r="2.6"/><circle cx="6.5" cy="12" r="2.6"/><circle cx="17.5" cy="18" r="2.6"/><path d="M8.9 10.8l6.2-3.5M8.9 13.2l6.2 3.5"/>', 'stroke'],
    'bookmark' => ['<path d="M6.5 4.5h11a1 1 0 0 1 1 1v14.2l-6.5-3.6-6.5 3.6V5.5a1 1 0 0 1 1-1z"/>', 'stroke'],
    'star' => ['<path d="M12 3.6l2.35 4.9 5.35.75-3.9 3.75.94 5.35L12 15.75l-4.74 2.6.94-5.35-3.9-3.75 5.35-.75z"/>', 'stroke'],
    'help' => ['<circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 0 1 4.8.9c0 1.7-2.4 2.2-2.4 3.7"/><path d="M12 17.2h.01"/>', 'stroke'],
    'users' => ['<circle cx="9" cy="8.5" r="3.3"/><path d="M3.6 19.4a5.6 5.6 0 0 1 10.8 0"/><path d="M16 5.6a3.3 3.3 0 0 1 0 6.4"/><path d="M17.4 14.5a5.6 5.6 0 0 1 3 4.9"/>', 'stroke'],
    'user' => ['<circle cx="12" cy="8.4" r="3.6"/><path d="M5.4 19.6a6.8 6.8 0 0 1 13.2 0"/>', 'stroke'],
    'settings' => ['<circle cx="12" cy="12" r="3"/><path d="M19.2 14.4a1.4 1.4 0 0 0 .3 1.5l.05.05a1.7 1.7 0 1 1-2.4 2.4l-.05-.05a1.4 1.4 0 0 0-1.5-.3 1.4 1.4 0 0 0-.85 1.3v.15a1.7 1.7 0 1 1-3.4 0v-.08a1.4 1.4 0 0 0-.92-1.3 1.4 1.4 0 0 0-1.5.3l-.05.05a1.7 1.7 0 1 1-2.4-2.4l.05-.05a1.4 1.4 0 0 0 .3-1.5 1.4 1.4 0 0 0-1.3-.85H4.9a1.7 1.7 0 1 1 0-3.4h.08a1.4 1.4 0 0 0 1.3-.92 1.4 1.4 0 0 0-.3-1.5l-.05-.05a1.7 1.7 0 1 1 2.4-2.4l.05.05a1.4 1.4 0 0 0 1.5.3h.07a1.4 1.4 0 0 0 .85-1.3V4.9a1.7 1.7 0 1 1 3.4 0v.08a1.4 1.4 0 0 0 .85 1.3 1.4 1.4 0 0 0 1.5-.3l.05-.05a1.7 1.7 0 1 1 2.4 2.4l-.05.05a1.4 1.4 0 0 0-.3 1.5v.07a1.4 1.4 0 0 0 1.3.85h.15a1.7 1.7 0 1 1 0 3.4h-.08a1.4 1.4 0 0 0-1.3.85z"/>', 'stroke'],
    'sliders' => ['<path d="M4 7h9M17 7h3M4 12h3M11 12h9M4 17h7M15 17h5"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="13" cy="17" r="2"/>', 'stroke'],
    'bell' => ['<path d="M18 9.5a6 6 0 1 0-12 0c0 4.2-1.6 5.6-1.6 5.6h15.2S18 13.7 18 9.5z"/><path d="M13.7 18.5a2 2 0 0 1-3.4 0"/>', 'stroke'],
    'message' => ['<path d="M20.5 11.6c0 3.9-3.8 7-8.5 7a9.8 9.8 0 0 1-2.6-.35L4.5 20l1.2-3.3A6.5 6.5 0 0 1 3.5 11.6c0-3.9 3.8-7 8.5-7s8.5 3.1 8.5 7z"/>', 'stroke'],
    'inbox' => ['<path d="M3 13.5h4.2l1.4 2.4h6.8l1.4-2.4H21"/><path d="M5.4 5h13.2l2.4 8.5V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4.5z"/>', 'stroke'],
    'robot' => ['<rect x="4" y="7.5" width="16" height="11" rx="3"/><path d="M12 4v3.5"/><circle cx="12" cy="3.2" r="1.2"/><path d="M9.2 12.2h.01M14.8 12.2h.01"/><path d="M9.8 15.6h4.4"/>', 'stroke'],
    'server' => ['<rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M6.8 7.2h.01M6.8 16.8h.01"/>', 'stroke'],
    'shield' => ['<path d="M12 3.2l7 2.6v5.6c0 4.2-2.9 7.6-7 9-4.1-1.4-7-4.8-7-9V5.8z"/><path d="M9.2 12l2 2 3.6-3.8"/>', 'stroke'],
    'database' => ['<ellipse cx="12" cy="6" rx="7.5" ry="3"/><path d="M4.5 6v12c0 1.66 3.36 3 7.5 3s7.5-1.34 7.5-3V6"/><path d="M4.5 12c0 1.66 3.36 3 7.5 3s7.5-1.34 7.5-3"/>', 'stroke'],
    'logout' => ['<path d="M14.5 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6.5a2 2 0 0 0 2-2v-2"/><path d="M9.5 12H21m0 0l-3.4-3.4M21 12l-3.4 3.4"/>', 'stroke'],
    'lock' => ['<rect x="4.5" y="10.5" width="15" height="10" rx="2.2"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7"/><path d="M12 14.6v2.2"/>', 'stroke'],
    'key' => ['<circle cx="8" cy="14" r="4"/><path d="M11 11.5L20 3"/><path d="M17 6l2.2 2.2M14.5 8.5l2 2"/>', 'stroke'],

    // ------------------------------------------------------------ tools ---
    'trash' => ['<path d="M4.5 6.5h15"/><path d="M9.5 6.5V5a1.5 1.5 0 0 1 1.5-1.5h2A1.5 1.5 0 0 1 14.5 5v1.5"/><path d="M6.5 6.5l.8 12.1a1.6 1.6 0 0 0 1.6 1.4h6.2a1.6 1.6 0 0 0 1.6-1.4l.8-12.1"/><path d="M10.4 10.4v6M13.6 10.4v6"/>', 'stroke'],
    'edit' => ['<path d="M4 20h4l10.5-10.5a2.4 2.4 0 0 0-3.4-3.4L4.6 16.6z"/><path d="M14.4 7.4l2.2 2.2"/>', 'stroke'],
    'copy' => ['<rect x="9" y="9" width="11.5" height="11.5" rx="2"/><path d="M15 6.5V5.5a2 2 0 0 0-2-2H5.5a2 2 0 0 0-2 2V13a2 2 0 0 0 2 2h1"/>', 'stroke'],
    'link' => ['<path d="M10 13.5a3.6 3.6 0 0 0 5.4.4l2.6-2.6a3.6 3.6 0 0 0-5.1-5.1l-1.5 1.5"/><path d="M14 10.5a3.6 3.6 0 0 0-5.4-.4L6 12.7a3.6 3.6 0 0 0 5.1 5.1l1.5-1.5"/>', 'stroke'],
    'filter' => ['<path d="M3.5 5.5h17l-6.6 7.6v5.6l-3.8 2v-7.6z"/>', 'stroke'],
    'sort' => ['<path d="M7 4.5v15m0 0l-3-3m3 3l3-3"/><path d="M17 19.5v-15m0 0l-3 3m3-3l3 3"/>', 'stroke'],
    'grid-lg' => ['<rect x="3.5" y="3.5" width="7.5" height="7.5" rx="1.6"/><rect x="13" y="3.5" width="7.5" height="7.5" rx="1.6"/><rect x="3.5" y="13" width="7.5" height="7.5" rx="1.6"/><rect x="13" y="13" width="7.5" height="7.5" rx="1.6"/>', 'stroke'],
    'grid-sm' => ['<rect x="3.5" y="3.5" width="4.6" height="4.6" rx="1.2"/><rect x="9.7" y="3.5" width="4.6" height="4.6" rx="1.2"/><rect x="15.9" y="3.5" width="4.6" height="4.6" rx="1.2"/><rect x="3.5" y="9.7" width="4.6" height="4.6" rx="1.2"/><rect x="9.7" y="9.7" width="4.6" height="4.6" rx="1.2"/><rect x="15.9" y="9.7" width="4.6" height="4.6" rx="1.2"/><rect x="3.5" y="15.9" width="4.6" height="4.6" rx="1.2"/><rect x="9.7" y="15.9" width="4.6" height="4.6" rx="1.2"/><rect x="15.9" y="15.9" width="4.6" height="4.6" rx="1.2"/>', 'stroke'],
    'list' => ['<path d="M8.5 6.5h12M8.5 12h12M8.5 17.5h12"/><circle cx="4.4" cy="6.5" r="1.3"/><circle cx="4.4" cy="12" r="1.3"/><circle cx="4.4" cy="17.5" r="1.3"/>', 'stroke'],
    'select-all' => ['<rect x="3.5" y="3.5" width="17" height="17" rx="2.4"/><path d="M8 12.2l2.6 2.6 5.4-5.6"/>', 'stroke'],
    'select-none' => ['<rect x="3.5" y="3.5" width="17" height="17" rx="2.4"/><path d="M9 9l6 6M15 9l-6 6"/>', 'stroke'],
    'save' => ['<path d="M5 4.5h11L19.5 8v11a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 19V6A1.5 1.5 0 0 1 5 4.5z"/><path d="M7.5 4.5v5h8v-5"/><path d="M7.5 20.5v-6h9v6"/>', 'stroke'],
    'refresh' => ['<path d="M20 11.5a8 8 0 1 0-.7 4.6"/><path d="M20 5.5v6h-6"/>', 'stroke'],
    'zoom' => ['<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4M8.6 11h4.8M11 8.6v4.8"/>', 'stroke'],
    'drag' => ['<circle cx="9" cy="6" r="1.4"/><circle cx="15" cy="6" r="1.4"/><circle cx="9" cy="12" r="1.4"/><circle cx="15" cy="12" r="1.4"/><circle cx="9" cy="18" r="1.4"/><circle cx="15" cy="18" r="1.4"/>', 'fill'],

    // --------------------------------------------------------- contact ----
    'phone' => ['<path d="M6.6 3.6h3l1.5 3.8-2 1.4a12 12 0 0 0 6.1 6.1l1.4-2 3.8 1.5v3a1.8 1.8 0 0 1-2 1.8A16.4 16.4 0 0 1 4.8 5.6a1.8 1.8 0 0 1 1.8-2z"/>', 'stroke'],
    'mail' => ['<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="M3.6 6.6l7.3 5.4a1.9 1.9 0 0 0 2.2 0l7.3-5.4"/>', 'stroke'],
    'map-pin' => ['<path d="M12 21.2s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.7"/>', 'stroke'],
    'clock' => ['<circle cx="12" cy="12" r="8.6"/><path d="M12 7.2V12l3.2 2"/>', 'stroke'],
    'calendar' => ['<rect x="3.5" y="5" width="17" height="15.5" rx="2.2"/><path d="M3.5 9.6h17"/><path d="M8.2 3.2v3.6M15.8 3.2v3.6"/>', 'stroke'],

    // ---------------------------------------------------------- social ----
    'line' => ['<path d="M12 3.4c-5 0-9 3.2-9 7.2 0 3.6 3.2 6.6 7.5 7.1.3.06.7.2.8.45.1.23.06.6.03.83l-.13.78c-.04.23-.18.9.8.5s5.3-3.1 7.2-5.3c1.3-1.4 1.9-2.9 1.9-4.4 0-4-4-7.2-9-7.2z" fill="currentColor"/><path d="M9.6 9.1v3.5M7.6 9.1v3.5h1.4M15 9.1h-1.7v3.5H15M13.3 10.9h1.4M16.6 12.6V9.1l2.2 3.5V9.1" stroke="#fff" stroke-width="1.05" fill="none" stroke-linecap="round" stroke-linejoin="round"/>', 'fill'],
    'facebook' => ['<path d="M13.5 21.9V13.4h2.9l.44-3.35H13.5V7.9c0-.97.27-1.63 1.66-1.63h1.78V3.27a23.7 23.7 0 0 0-2.6-.13c-2.56 0-4.32 1.57-4.32 4.45v2.46H7.1v3.35h2.92v8.5z"/>', 'fill'],
    'instagram' => ['<rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="16.9" cy="7.1" r="1.15" fill="currentColor" stroke="none"/>', 'stroke'],
    'youtube' => ['<path d="M22.2 8.1a2.7 2.7 0 0 0-1.9-1.9C18.6 5.7 12 5.7 12 5.7s-6.6 0-8.3.46A2.7 2.7 0 0 0 1.8 8.1 28 28 0 0 0 1.35 12a28 28 0 0 0 .45 3.9 2.7 2.7 0 0 0 1.9 1.9c1.7.46 8.3.46 8.3.46s6.6 0 8.3-.46a2.7 2.7 0 0 0 1.9-1.9A28 28 0 0 0 22.65 12a28 28 0 0 0-.45-3.9zM9.9 15.15V8.85L15.35 12z"/>', 'fill'],
    'tiktok' => ['<path d="M16.3 2.2h-3.1v13.1a2.6 2.6 0 1 1-2.3-2.58v-3.14a5.72 5.72 0 1 0 5.4 5.7V8.9a6.4 6.4 0 0 0 3.9 1.3V7.06a3.66 3.66 0 0 1-3.9-3.6z"/>', 'fill'],
    ];
}

/** Convenience for the social row in the footer. */
function social_icon(string $network): string
{
    return icon(in_array($network, ['line', 'facebook', 'instagram', 'youtube', 'tiktok'], true) ? $network : 'link');
}
