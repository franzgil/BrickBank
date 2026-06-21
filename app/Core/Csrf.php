<?php
namespace App\Core;

/**
 * CSRF-Schutz für schreibende Formulare.
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Verstecktes Formularfeld mit dem aktuellen Token. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    public static function check(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    /** Bricht die Anfrage bei ungültigem Token ab. */
    public static function validate(?string $token): void
    {
        if (!self::check($token)) {
            http_response_code(400);
            exit('Ungültiges oder fehlendes CSRF-Token.');
        }
    }
}
