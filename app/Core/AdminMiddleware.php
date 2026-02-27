<?php

namespace Core;

class AdminMiddleware
{
    public static function handle(): void
    {
        Auth::start();
        Auth::check();

        if (!Auth::isAdmin()) {

            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';

            // VERSION API → JSON
            if (str_starts_with($uri, '/api/')) {
                ErrorPage::json(403, 'Accès réservé aux administrateurs');
            }

            // ✅ FIX : VERSION WEB → page HTML propre au lieu de '403 - Accès réservé aux administrateurs'
            ErrorPage::render(403, 'Cette section est réservée aux administrateurs. Votre compte n\'a pas les droits nécessaires.');
        }
    }
}