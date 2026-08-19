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
    'camera' => ['<rect x="3" y="6" width="18" height="14" rx="3"/><path d="M6 6V4H10L11.5 6"/><circle cx="12" cy="13" r="4"/><circle cx="17.5" cy="9" r="0.9" fill="currentColor" stroke="none"/>', 'stroke'],
    'aperture' => ['<circle cx="12" cy="12" r="7"/><path d="M12 5V8M19 12H16M12 19V16M5 12H8"/>', 'stroke'],

    // ---------------------------------------------------- hero features ---
    'folder-organised' => ['<path d="M4 8C4 7.4 4.4 7 5 7H9L10.5 9H19C19.6 9 20 9.4 20 10V18C20 18.6 19.6 19 19 19H5C4.4 19 4 18.6 4 18V8Z"/><path d="M8 12H16M8 15H14"/>', 'stroke'],
    'video-frames' => ['<rect x="4" y="7" width="16" height="10" rx="3"/><path d="M6 9V10M6 12V13M6 15V15M18 9V10M18 12V13M18 15V15"/><path d="M10 10L14 12L10 14Z"/>', 'stroke'],
    'download-cloud' => ['<path d="M8 18H16C18.2 18 20 16.3 20 14.2C20 12.3 18.5 10.7 16.6 10.5C16.1 8.4 14.2 7 12 7C9.4 7 7.3 9 7.1 11.5C5.3 11.8 4 13.3 4 15.1C4 16.7 5.3 18 7 18H8Z"/><path d="M12 10V17M9.5 14.5L12 17L14.5 14.5"/>', 'stroke'],
    'mobile' => ['<rect x="7" y="3" width="10" height="18" rx="3"/><path d="M10 6H14"/><circle cx="12" cy="17.5" r="1" fill="currentColor" stroke="none"/>', 'stroke'],

    // ------------------------------------------------------- why cards ----
    'image-sharp' => ['<rect x="4" y="6" width="16" height="12" rx="3"/><circle cx="9" cy="10" r="1.5"/><path d="M7 16L11 12L13.5 14.5L16 11L18 13.5V16H7Z"/>', 'stroke'],
    'moment-star' => ['<path d="M12 4L14.2 8.5L19 9.1L15.5 12.4L16.4 17L12 14.7L7.6 17L8.5 12.4L5 9.1L9.8 8.5L12 4Z"/>', 'stroke'],
    'share-link' => ['<path d="M10 14L8.5 15.5C7 17 4.6 17 3.1 15.5C1.6 14 1.6 11.6 3.1 10.1L6.1 7.1C7.6 5.6 10 5.6 11.5 7.1C13 8.6 13 11 11.5 12.5L10 14"/><path d="M14 10L15.5 8.5C17 7 19.4 7 20.9 8.5C22.4 10 22.4 12.4 20.9 13.9L17.9 16.9C16.4 18.4 14 18.4 12.5 16.9C11 15.4 11 13 12.5 11.5L14 10"/>', 'stroke'],
    'chat-fast' => ['<path d="M5 6H19C19.6 6 20 6.4 20 7V15C20 15.6 19.6 16 19 16H10L6 19V16H5C4.4 16 4 15.6 4 15V7C4 6.4 4.4 6 5 6Z"/><circle cx="9" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/>', 'stroke'],

    // ------------------------------------------------------------ core ----
    'check-circle' => ['<circle cx="12" cy="12" r="9"/><path d="M8.4 12.2l2.5 2.5 4.7-4.9"/>', 'stroke'],
    'check' => ['<path d="M6 12L10 16L18 8"/>', 'stroke'],
    'close' => ['<path d="M6 6L18 18M18 6L6 18"/>', 'stroke'],
    'plus' => ['<path d="M12 6V18M6 12H18"/>', 'stroke'],
    'minus' => ['<path d="M6 12H18"/>', 'stroke'],
    'dot' => ['<circle cx="12" cy="12" r="3"/>', 'stroke'],
    'search' => ['<circle cx="10" cy="10" r="5.5"/><path d="M14 14L19 19"/>', 'stroke'],
    'chevron-down' => ['<path d="M7 9L12 14L17 9"/>', 'stroke'],
    'chevron-up' => ['<path d="M7 15L12 10L17 15"/>', 'stroke'],
    'chevron-left' => ['<path d="M15 7L10 12L15 17"/>', 'stroke'],
    'chevron-right' => ['<path d="M9 7L14 12L9 17"/>', 'stroke'],
    'arrow-right' => ['<path d="M5 12H18M14 8L18 12L14 16"/>', 'stroke'],
    'arrow-left' => ['<path d="M19 12H6M10 8L6 12L10 16"/>', 'stroke'],
    'external' => ['<path d="M11 5H6C5.4 5 5 5.4 5 6V18C5 18.6 5.4 19 6 19H18C18.6 19 19 18.6 19 18V13"/><path d="M13 5H19V11M19 5L11 13"/>', 'stroke'],
    'menu' => ['<path d="M5 7H19M5 12H19M5 17H19"/>', 'stroke'],
    'more-vertical' => ['<circle cx="12" cy="6" r="1.25"/><circle cx="12" cy="12" r="1.25"/><circle cx="12" cy="18" r="1.25"/>', 'fill'],
    'more-horizontal' => ['<circle cx="6" cy="12" r="1.25"/><circle cx="12" cy="12" r="1.25"/><circle cx="18" cy="12" r="1.25"/>', 'fill'],
    'sun' => ['<circle cx="12" cy="12" r="4"/><path d="M12 3V5M12 19V21M3 12H5M19 12H21M5.6 5.6L7 7M17 17L18.4 18.4M18.4 5.6L17 7M7 17L5.6 18.4"/>', 'stroke'],
    'moon' => ['<path d="M16 4A8 8 0 1 0 19 17A6.5 6.5 0 0 1 16 4"/>', 'stroke'],

    // ---------------------------------------------------------- content ---
    'image' => ['<rect x="3" y="4.5" width="18" height="15" rx="2.2"/><circle cx="8.6" cy="9.6" r="1.6"/><path d="M3.4 16.8l4.6-4.3a1.8 1.8 0 0 1 2.4 0l6.4 5.9"/>', 'stroke'],
    'images' => ['<rect x="6" y="5" width="11" height="9" rx="2"/><rect x="8" y="8" width="11" height="11" rx="2"/>', 'stroke'],
    'video' => ['<rect x="4" y="7" width="11" height="10" rx="2.5"/><path d="M15 10L20 8.2V15.8L15 14Z"/>', 'stroke'],
    'play' => ['<circle cx="12" cy="12" r="9"/><path d="M10.2 8.8l5.2 3.2-5.2 3.2z"/>', 'stroke'],
    'folder' => ['<path d="M4 8C4 7.4 4.4 7 5 7H9L10.5 9H19C19.6 9 20 9.4 20 10V18C20 18.6 19.6 19 19 19H5C4.4 19 4 18.6 4 18V8Z"/>', 'stroke'],
    'folder-plus' => ['<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4l1.8 2H19a1.5 1.5 0 0 1 1.5 1.5V17A1.5 1.5 0 0 1 19 18.5H4.5A1.5 1.5 0 0 1 3 17z"/><path d="M12 11.4v4.2M9.9 13.5h4.2"/>', 'stroke'],
    'folder-move' => ['<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4l1.8 2H19a1.5 1.5 0 0 1 1.5 1.5V17A1.5 1.5 0 0 1 19 18.5H4.5A1.5 1.5 0 0 1 3 17z"/><path d="M8.5 13.5h6m0 0l-2-2m2 2l-2 2"/>', 'stroke'],
    'file-text' => ['<path d="M13.5 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8.5z"/><path d="M13.5 3v5.5H19"/><path d="M8.6 13h6.8M8.6 16.4h4.6"/>', 'stroke'],
    'article' => ['<rect x="5" y="5" width="14" height="14" rx="2.5"/><path d="M8 9H16M8 12H14M8 15H12"/>', 'stroke'],
    'banner' => ['<rect x="4" y="7" width="16" height="10" rx="2.5"/><circle cx="9" cy="10" r="1.2"/><path d="M7 15L11 11.5L13.2 13.5L16.5 11L18 12.5V15H7Z"/>', 'stroke'],
    'pages' => ['<rect x="4" y="5" width="16" height="14" rx="2.5"/><path d="M4 9H20M10 9V19"/>', 'stroke'],
    'nav-menu' => ['<rect x="5" y="5" width="14" height="3.2" rx="1.6"/><path d="M8 12H16M8 16H14"/>', 'stroke'],

    // -------------------------------------------------------- dashboard ---
    'home' => ['<path d="M4.5 10.5L12 4.5L19.5 10.5V19H14V14H10V19H4.5V10.5Z"/>', 'stroke'],
    'chart' => ['<path d="M5 5V19H19"/><rect x="8" y="12" width="2.5" height="5" rx="1"/><rect x="12" y="9" width="2.5" height="8" rx="1"/><rect x="16" y="6" width="2.5" height="11" rx="1"/>', 'stroke'],
    'trend-up' => ['<path d="M3.5 16.5l5.2-5.2 3.3 3.3 6-6"/><path d="M14.4 8.6H18v3.6"/>', 'stroke'],
    'eye' => ['<path d="M2.6 12S6 5.8 12 5.8 21.4 12 21.4 12 18 18.2 12 18.2 2.6 12 2.6 12z"/><circle cx="12" cy="12" r="3"/>', 'stroke'],
    'download' => ['<path d="M12 4v11m0 0l-4-4m4 4l4-4"/><path d="M4.5 18.5h15"/>', 'stroke'],
    'upload' => ['<path d="M12 19V8m0 0L8 12m4-4l4 4"/><path d="M4.5 4.5h15"/>', 'stroke'],
    'share' => ['<circle cx="17.5" cy="6" r="2.6"/><circle cx="6.5" cy="12" r="2.6"/><circle cx="17.5" cy="18" r="2.6"/><path d="M8.9 10.8l6.2-3.5M8.9 13.2l6.2 3.5"/>', 'stroke'],
    'bookmark' => ['<path d="M6.5 4.5h11a1 1 0 0 1 1 1v14.2l-6.5-3.6-6.5 3.6V5.5a1 1 0 0 1 1-1z"/>', 'stroke'],
    'star' => ['<path d="M12 4L14.2 8.5L19 9.1L15.5 12.4L16.4 17L12 14.7L7.6 17L8.5 12.4L5 9.1L9.8 8.5L12 4Z"/>', 'stroke'],
    'help' => ['<circle cx="12" cy="12" r="8"/><path d="M9.6 9.2C9.9 8.1 10.9 7.3 12.1 7.3C13.6 7.3 14.8 8.4 14.8 9.8C14.8 10.8 14.2 11.5 13.2 12.2C12.4 12.7 12 13.2 12 14"/><circle cx="12" cy="17" r="1" fill="currentColor" stroke="none"/>', 'stroke'],
    'users' => ['<circle cx="10.5" cy="9" r="2.5"/><path d="M6.5 17C6.5 14.8 8.3 13 10.5 13C12.7 13 14.5 14.8 14.5 17"/><path d="M15 14C15.2 12.5 16.4 11.5 18 11.5C19.5 11.5 20.5 12.5 20.5 14M16.2 7.8C16.2 6.4 17.3 5.3 18.7 5.3C20.1 5.3 21.2 6.4 21.2 7.8"/>', 'stroke'],
    'user' => ['<circle cx="12" cy="8.4" r="3.6"/><path d="M5.4 19.6a6.8 6.8 0 0 1 13.2 0"/>', 'stroke'],
    'settings' => ['<circle cx="12" cy="12" r="3.2"/><path d="M12 4.5V6M12 18V19.5M4.5 12H6M18 12H19.5M6.7 6.7L7.8 7.8M16.2 16.2L17.3 17.3M17.3 6.7L16.2 7.8M7.8 16.2L6.7 17.3"/><circle cx="12" cy="12" r="7"/>', 'stroke'],
    'sliders' => ['<path d="M4 7h9M17 7h3M4 12h3M11 12h9M4 17h7M15 17h5"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="13" cy="17" r="2"/>', 'stroke'],
    'bell' => ['<path d="M18 9.5a6 6 0 1 0-12 0c0 4.2-1.6 5.6-1.6 5.6h15.2S18 13.7 18 9.5z"/><path d="M13.7 18.5a2 2 0 0 1-3.4 0"/>', 'stroke'],
    'message' => ['<path d="M20.5 11.6c0 3.9-3.8 7-8.5 7a9.8 9.8 0 0 1-2.6-.35L4.5 20l1.2-3.3A6.5 6.5 0 0 1 3.5 11.6c0-3.9 3.8-7 8.5-7s8.5 3.1 8.5 7z"/>', 'stroke'],
    'inbox' => ['<path d="M5 9H8L10 13H14L16 9H19V18H5V9Z"/>', 'stroke'],
    'robot' => ['<path d="M9 4.5V7M12 3V4.5"/><circle cx="12" cy="2.5" r="1" fill="currentColor" stroke="none"/><rect x="5" y="7" width="14" height="11" rx="3"/><circle cx="10" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="14" cy="12" r="1" fill="currentColor" stroke="none"/><path d="M9.5 15H14.5"/>', 'stroke'],
    'server' => ['<rect x="5" y="6" width="14" height="5" rx="2"/><rect x="5" y="13" width="14" height="5" rx="2"/><circle cx="8" cy="8.5" r="0.9" fill="currentColor" stroke="none"/><circle cx="8" cy="15.5" r="0.9" fill="currentColor" stroke="none"/>', 'stroke'],
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
    'phone' => ['<path d="M7.6 5.4C8.4 4.7 9.7 4.7 10.5 5.5L11.8 6.8C12.6 7.6 12.6 8.9 11.8 9.7L10.9 10.6C11.7 12.2 13 13.5 14.6 14.3L15.5 13.4C16.3 12.6 17.6 12.6 18.4 13.4L19.7 14.7C20.5 15.5 20.5 16.8 19.8 17.6L18.9 18.5C18 19.4 16.7 19.7 15.5 19.2C11.5 17.5 8.5 14.5 6.8 10.5C6.3 9.3 6.6 8 7.5 7.1L7.6 5.4Z"/>', 'stroke'],
    'mail' => ['<rect x="4" y="6" width="16" height="12" rx="2.5"/><path d="M6.5 9L12 13L17.5 9"/>', 'stroke'],
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
