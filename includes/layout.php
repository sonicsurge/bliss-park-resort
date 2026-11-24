<?php
/**
 * layout.php — the shared HTML chrome for PHP pages.
 *
 * The Bootstrap files are loaded from a CDN with an integrity hash (SRI).
 * The browser computes the file hash and blocks the file if it does not match.
 * That is the A08 (software and data integrity) control for this dependency.
 * A06 still wants us to self-host these files later so a CDN outage or a
 * dropped SRI attribute cannot change the page. The hash is the pin we have now.
 *
 * Call page_head() before any body markup, and page_end() after it.
 */

function page_head(string $title, array $pageStyles = []): void
{
    /*
     * A page may request an extra stylesheet only from this project's CSS
     * folder. The names below are written by developers, never taken from a
     * request. This allow-list still prevents a future caller accidentally
     * turning a user-controlled value into a <link href="..."> injection.
     */
    $allowedStyles = [
        'CSS/marketing.css',
        'CSS/login.css',
        'CSS/register.css',
    ];

    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . h($title) . '</title>';
    echo '<link rel="stylesheet" href="CSS/app.css">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
    foreach ($pageStyles as $style) {
        if (is_string($style) && in_array($style, $allowedStyles, true)) {
            echo '<link rel="stylesheet" href="' . h($style) . '">';
        }
    }
    echo '</head><body>';
}

function page_end(): void
{
    // External script from an allow-listed host. It does not need the CSP nonce.
    // Inline scripts would. This project avoids inline scripts for that reason.
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>';
    echo '</body></html>';
}
