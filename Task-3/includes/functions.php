<?php
/**
 * Wraps every occurrence of $keyword inside $text with a <mark> tag
 * so it appears highlighted in the browser.
 * The text is escaped first (XSS-safe), then highlighting is applied.
 */
function highlightKeyword($text, $keyword)
{
    $safeText = htmlspecialchars($text);

    if (trim($keyword) === "") {
        return $safeText;
    }

    // Case-insensitive highlight, escape the keyword for regex safety
    $pattern = '/' . preg_quote(htmlspecialchars($keyword), '/') . '/i';
    return preg_replace($pattern, '<mark>$0</mark>', $safeText);
}
