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
    'check-circle' => ['<circle cx="12" cy="12" r="8"/><path d="M8.5 12L11 14.5L15.8 9.5"/>', 'stroke'],
    'check' => ['<path d="M6 12L10 16L18 8"/>', 'stroke'],
    'close' => ['<path d="M6 6L18 18M18 6L6 18"/>', 'stroke'],
    'plus' => ['<path d="M12 6V18M6 12H18"/>', 'stroke'],
    'minus' => ['<path d="M6 12H18"/>', 'stroke'],
    'dot' => ['<circle cx="12" cy="12" r="2.25"/>', 'fill'],
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
    'image' => ['<rect x="4" y="6" width="16" height="12" rx="3"/><circle cx="9" cy="10" r="1.5"/><path d="M6.5 16L11 11.5L17.5 16"/>', 'stroke'],
    'images' => ['<rect x="6" y="5" width="11" height="9" rx="2"/><rect x="8" y="8" width="11" height="11" rx="2"/>', 'stroke'],
    'video' => ['<rect x="4" y="7" width="11" height="10" rx="2.5"/><path d="M15 10L20 8.2V15.8L15 14Z"/>', 'stroke'],
    'play' => ['<circle cx="12" cy="12" r="8"/><path d="M10 8.8L16 12L10 15.2V8.8Z"/>', 'stroke'],
    'folder' => ['<path d="M4 8C4 7.4 4.4 7 5 7H9L10.5 9H19C19.6 9 20 9.4 20 10V18C20 18.6 19.6 19 19 19H5C4.4 19 4 18.6 4 18V8Z"/>', 'stroke'],
    'folder-plus' => ['<path d="M4 8C4 7.4 4.4 7 5 7H9L10.5 9H19C19.6 9 20 9.4 20 10V18C20 18.6 19.6 19 19 19H5C4.4 19 4 18.6 4 18V8Z"/><path d="M15 13V17M13 15H17"/>', 'stroke'],
    'folder-move' => ['<path d="M4 8C4 7.4 4.4 7 5 7H9L10.5 9H19C19.6 9 20 9.4 20 10V18C20 18.6 19.6 19 19 19H5C4.4 19 4 18.6 4 18V8Z"/><path d="M9 14H16M13.5 11.5L16 14L13.5 16.5"/>', 'stroke'],
    'file-text' => ['<path d="M6 4H14L18 8V20H6V4Z"/><path d="M14 4V8H18M9 11H15M9 14H14M9 17H13"/>', 'stroke'],
    'article' => ['<rect x="5" y="5" width="14" height="14" rx="2.5"/><path d="M8 9H16M8 12H14M8 15H12"/>', 'stroke'],
    'banner' => ['<rect x="4" y="7" width="16" height="10" rx="2.5"/><circle cx="9" cy="10" r="1.2"/><path d="M7 15L11 11.5L13.2 13.5L16.5 11L18 12.5V15H7Z"/>', 'stroke'],
    'pages' => ['<rect x="4" y="5" width="16" height="14" rx="2.5"/><path d="M4 9H20M10 9V19"/>', 'stroke'],
    'nav-menu' => ['<rect x="5" y="5" width="14" height="3.2" rx="1.6"/><path d="M8 12H16M8 16H14"/>', 'stroke'],

    // -------------------------------------------------------- dashboard ---
    'home' => ['<path d="M4.5 10.5L12 4.5L19.5 10.5V19H14V14H10V19H4.5V10.5Z"/>', 'stroke'],
    'chart' => ['<path d="M5 5V19H19"/><rect x="8" y="12" width="2.5" height="5" rx="1"/><rect x="12" y="9" width="2.5" height="8" rx="1"/><rect x="16" y="6" width="2.5" height="11" rx="1"/>', 'stroke'],
    'trend-up' => ['<path d="M5 17L10 12L13 15L19 8"/><path d="M15 8H19V12"/>', 'stroke'],
    'eye' => ['<path d="M3.5 12C5.5 8.5 8.5 6.5 12 6.5C15.5 6.5 18.5 8.5 20.5 12C18.5 15.5 15.5 17.5 12 17.5C8.5 17.5 5.5 15.5 3.5 12Z"/><circle cx="12" cy="12" r="2.5"/>', 'stroke'],
    'download' => ['<path d="M12 6V17M8.5 13.5L12 17L15.5 13.5"/><path d="M5 18.5H19"/>', 'stroke'],
    'upload' => ['<path d="M12 17V6M8.5 9.5L12 6L15.5 9.5"/><path d="M5 18.5H19"/>', 'stroke'],
    'share' => ['<circle cx="7" cy="12" r="2.2"/><circle cx="17" cy="7" r="2.2"/><circle cx="17" cy="17" r="2.2"/><path d="M9 11L15 8M9 13L15 16"/>', 'stroke'],
    'bookmark' => ['<path d="M7 5H17V19L12 15.8L7 19V5Z"/>', 'stroke'],
    'star' => ['<path d="M12 4L14.2 8.5L19 9.1L15.5 12.4L16.4 17L12 14.7L7.6 17L8.5 12.4L5 9.1L9.8 8.5L12 4Z"/>', 'stroke'],
    'help' => ['<circle cx="12" cy="12" r="8"/><path d="M9.6 9.2C9.9 8.1 10.9 7.3 12.1 7.3C13.6 7.3 14.8 8.4 14.8 9.8C14.8 10.8 14.2 11.5 13.2 12.2C12.4 12.7 12 13.2 12 14"/><circle cx="12" cy="17" r="1" fill="currentColor" stroke="none"/>', 'stroke'],
    'users' => ['<circle cx="10.5" cy="9" r="2.5"/><path d="M6.5 17C6.5 14.8 8.3 13 10.5 13C12.7 13 14.5 14.8 14.5 17"/><path d="M15 14C15.2 12.5 16.4 11.5 18 11.5C19.5 11.5 20.5 12.5 20.5 14M16.2 7.8C16.2 6.4 17.3 5.3 18.7 5.3C20.1 5.3 21.2 6.4 21.2 7.8"/>', 'stroke'],
    'user' => ['<circle cx="12" cy="8" r="3"/><path d="M6 19C6 15.7 8.7 13 12 13C15.3 13 18 15.7 18 19"/>', 'stroke'],
    'settings' => ['<circle cx="12" cy="12" r="3.2"/><path d="M12 4.5V6M12 18V19.5M4.5 12H6M18 12H19.5M6.7 6.7L7.8 7.8M16.2 16.2L17.3 17.3M17.3 6.7L16.2 7.8M7.8 16.2L6.7 17.3"/><circle cx="12" cy="12" r="7"/>', 'stroke'],
    'sliders' => ['<path d="M5 7H19M5 12H19M5 17H19"/><circle cx="9" cy="7" r="2" fill="var(--surface)"/><circle cx="15" cy="12" r="2" fill="var(--surface)"/><circle cx="11" cy="17" r="2" fill="var(--surface)"/>', 'stroke'],
    'bell' => ['<path d="M7 16V11C7 8.2 9.2 6 12 6C14.8 6 17 8.2 17 11V16L19 18H5L7 16Z"/><path d="M10 20C10.5 20.7 11.2 21 12 21C12.8 21 13.5 20.7 14 20"/>', 'stroke'],
    'message' => ['<path d="M6 6H18C19.1 6 20 6.9 20 8V14C20 15.1 19.1 16 18 16H10L6 19V16H6C4.9 16 4 15.1 4 14V8C4 6.9 4.9 6 6 6Z"/>', 'stroke'],
    'inbox' => ['<path d="M5 9H8L10 13H14L16 9H19V18H5V9Z"/>', 'stroke'],
    'robot' => ['<path d="M9 4.5V7M12 3V4.5"/><circle cx="12" cy="2.5" r="1" fill="currentColor" stroke="none"/><rect x="5" y="7" width="14" height="11" rx="3"/><circle cx="10" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="14" cy="12" r="1" fill="currentColor" stroke="none"/><path d="M9.5 15H14.5"/>', 'stroke'],
    'server' => ['<rect x="5" y="6" width="14" height="5" rx="2"/><rect x="5" y="13" width="14" height="5" rx="2"/><circle cx="8" cy="8.5" r="0.9" fill="currentColor" stroke="none"/><circle cx="8" cy="15.5" r="0.9" fill="currentColor" stroke="none"/>', 'stroke'],
    'shield' => ['<path d="M12 4L18 6V11.5C18 15.5 15.7 18.2 12 20C8.3 18.2 6 15.5 6 11.5V6L12 4Z"/><path d="M9 12L11 14L15 10"/>', 'stroke'],
    'database' => ['<ellipse cx="12" cy="6.5" rx="7" ry="2.5"/><path d="M5 6.5V12C5 13.4 8.1 14.5 12 14.5C15.9 14.5 19 13.4 19 12V6.5M5 12V17.5C5 18.9 8.1 20 12 20C15.9 20 19 18.9 19 17.5V12"/>', 'stroke'],
    'logout' => ['<path d="M11 5H6V19H11"/><path d="M10 12H19M16 9L19 12L16 15"/>', 'stroke'],
    'lock' => ['<rect x="6" y="10" width="12" height="10" rx="3"/><path d="M8.5 10V8C8.5 6.1 10.1 4.5 12 4.5C13.9 4.5 15.5 6.1 15.5 8V10"/>', 'stroke'],
    'key' => ['<circle cx="8" cy="16" r="3"/><path d="M10.2 13.8L18 6M15 9L18 12M17 7L20 10"/>', 'stroke'],

    // ------------------------------------------------------------ tools ---
    'trash' => ['<path d="M7 8H17L16.2 19H7.8L7 8Z"/><path d="M6 6H18M9.5 6L10.2 4.5H13.8L14.5 6M10 11V16M14 11V16"/>', 'stroke'],
    'edit' => ['<path d="M6 17.5L7 14.2L15.8 5.4C16.6 4.6 17.8 4.6 18.6 5.4C19.4 6.2 19.4 7.4 18.6 8.2L9.8 17L6 17.5Z"/><path d="M13.8 7.4L16.6 10.2M5 19H12"/>', 'stroke'],
    'copy' => ['<rect x="5" y="5" width="11" height="11" rx="2.5"/><rect x="8" y="8" width="11" height="11" rx="2.5"/>', 'stroke'],
    'link' => ['<path d="M10 14L8.5 15.5C7 17 4.6 17 3.1 15.5C1.6 14 1.6 11.6 3.1 10.1L6.1 7.1C7.6 5.6 10 5.6 11.5 7.1C13 8.6 13 11 11.5 12.5L10 14"/><path d="M14 10L15.5 8.5C17 7 19.4 7 20.9 8.5C22.4 10 22.4 12.4 20.9 13.9L17.9 16.9C16.4 18.4 14 18.4 12.5 16.9C11 15.4 11 13 12.5 11.5L14 10"/>', 'stroke'],
    'filter' => ['<path d="M5 6H19L14 12V17L10 19V12L5 6Z"/>', 'stroke'],
    'sort' => ['<path d="M9 18V6M6.5 8.5L9 6L11.5 8.5"/><path d="M15 6V18M12.5 15.5L15 18L17.5 15.5"/>', 'stroke'],
    'grid-lg' => ['<rect x="5" y="5" width="5.5" height="5.5" rx="1.5"/><rect x="13.5" y="5" width="5.5" height="5.5" rx="1.5"/><rect x="5" y="13.5" width="5.5" height="5.5" rx="1.5"/><rect x="13.5" y="13.5" width="5.5" height="5.5" rx="1.5"/>', 'stroke'],
    'grid-sm' => ['<path d="M5 5H8V8H5V5ZM10.5 5H13.5V8H10.5V5ZM16 5H19V8H16V5ZM5 10.5H8V13.5H5V10.5ZM10.5 10.5H13.5V13.5H10.5V10.5ZM16 10.5H19V13.5H16V10.5ZM5 16H8V19H5V16ZM10.5 16H13.5V19H10.5V16ZM16 16H19V19H16V16Z"/>', 'stroke'],
    'list' => ['<circle cx="6" cy="7" r="1" fill="currentColor" stroke="none"/><circle cx="6" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="6" cy="17" r="1" fill="currentColor" stroke="none"/><path d="M9 7H19M9 12H19M9 17H19"/>', 'stroke'],
    'select-all' => ['<rect x="5" y="5" width="14" height="14" rx="3"/><path d="M8.5 12L11 14.5L15.5 9.5"/>', 'stroke'],
    'select-none' => ['<rect x="5" y="5" width="14" height="14" rx="3"/><path d="M9 9L15 15M15 9L9 15"/>', 'stroke'],
    'save' => ['<path d="M5 5H17L19 7V19H5V5Z"/><rect x="8" y="5" width="7" height="5" rx="1.5"/><rect x="8" y="13" width="8" height="6" rx="2"/>', 'stroke'],
    'refresh' => ['<path d="M18 9A7 7 0 1 0 18.5 15"/><path d="M18 5V9H14"/>', 'stroke'],
    'zoom' => ['<circle cx="10.5" cy="10.5" r="5.5"/><path d="M14.5 14.5L19 19M10.5 8V13M8 10.5H13"/>', 'stroke'],
    'drag' => ['<circle cx="9" cy="7" r="1"/><circle cx="15" cy="7" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="17" r="1"/><circle cx="15" cy="17" r="1"/>', 'fill'],

    // --------------------------------------------------------- contact ----
    'phone' => ['<path d="M7.6 5.4C8.4 4.7 9.7 4.7 10.5 5.5L11.8 6.8C12.6 7.6 12.6 8.9 11.8 9.7L10.9 10.6C11.7 12.2 13 13.5 14.6 14.3L15.5 13.4C16.3 12.6 17.6 12.6 18.4 13.4L19.7 14.7C20.5 15.5 20.5 16.8 19.8 17.6L18.9 18.5C18 19.4 16.7 19.7 15.5 19.2C11.5 17.5 8.5 14.5 6.8 10.5C6.3 9.3 6.6 8 7.5 7.1L7.6 5.4Z"/>', 'stroke'],
    'mail' => ['<rect x="4" y="6" width="16" height="12" rx="2.5"/><path d="M6.5 9L12 13L17.5 9"/>', 'stroke'],
    'map-pin' => ['<path d="M12 20C12 20 18 14.8 18 9.8C18 6.6 15.3 4 12 4C8.7 4 6 6.6 6 9.8C6 14.8 12 20 12 20Z"/><circle cx="12" cy="10" r="2"/>', 'stroke'],
    'clock' => ['<circle cx="12" cy="12" r="8"/><path d="M12 12L9 9M12 12L15 9.5"/>', 'stroke'],
    'calendar' => ['<rect x="5" y="6" width="14" height="13" rx="2.5"/><path d="M5 10H19M9 4V7M15 4V7"/>', 'stroke'],

    // ---------------------------------------------------------- social ----
    'line' => ['<path fill-rule="evenodd" d="M12 3C6.5 3 2 6.6 2 11.1C2 15 5 18 9.1 19L8 22L12.2 19.2H12.3C17.7 19.2 22 15.6 22 11.1C22 6.6 17.5 3 12 3ZM7.1 8.3H8.7V12.7H11V14H7.1V8.3ZM11.8 8.3H13.4V14H11.8V8.3ZM14.3 8.3H15.8L18 11.4V8.3H19.5V14H18.1L15.8 10.8V14H14.3V8.3ZM20.3 8.3V9.6H17.7V10.5H19.9V11.8H17.7V12.7H20.3V14H16.1V8.3H20.3Z"/>', 'fill'],
    'facebook' => ['<path d="M13.6 21V13.8H16L16.4 10.9H13.6V9.1C13.6 8.3 13.8 7.7 15 7.7H16.5V5.1C16.2 5.1 15.4 5 14.4 5C12.2 5 10.9 6.3 10.9 8.8V10.9H8.5V13.8H10.9V21H13.6Z"/>', 'fill'],
    'instagram' => ['<rect x="4.5" y="4.5" width="15" height="15" rx="4"/><circle cx="12" cy="12" r="3.4"/><circle cx="16.6" cy="7.4" r="1.1" fill="currentColor" stroke="none"/>', 'stroke'],
    'youtube' => ['<path fill-rule="evenodd" d="M20.6 7.3C20.2 5.9 19.1 4.8 17.7 4.4C16.4 4 12 4 12 4C12 4 7.6 4 6.3 4.4C4.9 4.8 3.8 5.9 3.4 7.3C3 8.6 3 12 3 12C3 12 3 15.4 3.4 16.7C3.8 18.1 4.9 19.2 6.3 19.6C7.6 20 12 20 12 20C12 20 16.4 20 17.7 19.6C19.1 19.2 20.2 18.1 20.6 16.7C21 15.4 21 12 21 12C21 12 21 8.6 20.6 7.3ZM10 15.5V8.5L16 12L10 15.5Z"/>', 'fill'],
    'tiktok' => ['<path d="M14.7 4C15.4 6 16.7 7.2 18.8 7.5V9.8C17.4 9.7 16.1 9.3 15 8.5V13.8C15 16.9 12.8 19 9.9 19C7.1 19 5 17 5 14.3C5 11.4 7.4 9.3 10.2 9.6V12C10 12 9.8 11.9 9.5 11.9C8.2 11.9 7.1 13 7.1 14.3C7.1 15.7 8.4 16.8 9.8 16.6C11 16.4 12 15.3 12 13.8V4H14.7Z"/>', 'fill'],
    ];
}

/** Convenience for the social row in the footer. */
function social_icon(string $network): string
{
    return icon(in_array($network, ['line', 'facebook', 'instagram', 'youtube', 'tiktok'], true) ? $network : 'link');
}
