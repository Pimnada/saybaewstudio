<?php
/**
 * Small building blocks so every letter looks like the same studio wrote it.
 * All styles are inline — email clients strip most stylesheets.
 */

if (!function_exists('em_h1')) {

    function em_h1(string $text): string
    {
        return '<h1 class="sbs-h1" style="margin:0 0 14px;font-size:24px;line-height:1.45;'
             . 'color:#2A241C;font-weight:700;">' . e($text) . '</h1>';
    }

    function em_p(string $html, string $color = '#4A4238'): string
    {
        return '<p style="margin:0 0 14px;font-size:15px;line-height:1.85;color:' . $color . ';">'
             . $html . '</p>';
    }

    function em_eyebrow(string $text): string
    {
        return '<div style="font-size:11.5px;letter-spacing:2.2px;text-transform:uppercase;'
             . 'color:#B0803A;font-weight:700;margin-bottom:10px;">' . e($text) . '</div>';
    }

    function em_button(string $label, string $href): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 8px;">'
             . '<tr><td align="center" bgcolor="#B0803A" style="border-radius:10px;">'
             . '<a href="' . e($href) . '" style="display:inline-block;padding:13px 30px;color:#ffffff;'
             . 'font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">'
             . e($label) . '</a></td></tr></table>';
    }

    function em_button_ghost(string $label, string $href): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0;">'
             . '<tr><td align="center" style="border:1.5px solid #B0803A;border-radius:10px;">'
             . '<a href="' . e($href) . '" style="display:inline-block;padding:11px 26px;color:#B0803A;'
             . 'font-size:14px;font-weight:700;text-decoration:none;">' . e($label) . '</a></td></tr></table>';
    }

    /** A cream panel used for order details and message quotes. */
    function em_panel(string $innerHtml): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
             . 'style="background:#FBF8F2;border:1px solid #E5DCCB;border-radius:12px;margin:18px 0;">'
             . '<tr><td style="padding:18px 20px;">' . $innerHtml . '</td></tr></table>';
    }

    /** One label/value line inside a panel. */
    function em_row(string $label, ?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            $value = '—';
        }
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
             . '<tr>'
             . '<td width="130" valign="top" style="padding:5px 0;font-size:13.5px;color:#7C7267;">'
             . e($label) . '</td>'
             . '<td valign="top" style="padding:5px 0;font-size:14.5px;color:#2A241C;font-weight:600;">'
             . nl2br(e($value)) . '</td>'
             . '</tr></table>';
    }

    /** Four stat tiles used in the album letters. */
    function em_stats(array $pairs): string
    {
        $cells = '';
        foreach ($pairs as $label => $value) {
            $cells .= '<td class="sbs-col" width="50%" valign="top" style="padding:0 8px 12px 0;">'
                    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
                    . 'style="background:#FBF8F2;border:1px solid #E5DCCB;border-radius:10px;">'
                    . '<tr><td align="center" style="padding:14px 8px;">'
                    . '<div style="font-size:22px;font-weight:700;color:#B0803A;line-height:1.2;">' . e((string) $value) . '</div>'
                    . '<div style="font-size:12px;color:#7C7267;margin-top:2px;">' . e($label) . '</div>'
                    . '</td></tr></table></td>';
        }
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
             . 'style="margin:18px 0 6px;"><tr>' . $cells . '</tr></table>';
    }

    function em_divider(): string
    {
        return '<div style="height:1px;background:#E5DCCB;margin:24px 0;"></div>';
    }

    function em_note(string $text): string
    {
        return '<div style="background:#FDF6E8;border-left:3px solid #D4A855;border-radius:0 8px 8px 0;'
             . 'padding:12px 16px;margin:18px 0;font-size:13.5px;line-height:1.8;color:#6B5A38;">'
             . $text . '</div>';
    }
}
