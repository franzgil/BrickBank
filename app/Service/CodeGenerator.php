<?php
namespace App\Service;

use App\Core\Config;

/**
 * Erzeugt und zerlegt ortsneutrale Behältercodes (C-/K-/T-).
 * Der Code trägt KEINEN Standort – nur Typ-Präfix + laufende Nummer.
 */
class CodeGenerator
{
    /** Behältertyp → Code-Präfix. */
    const PREFIXES = [
        'container'     => 'C-',
        'karton'        => 'K-',
        'tuete'         => 'T-',
        'sortimentsbox' => 'SB-',
        'einsatzkasten' => 'EK-',
    ];

    public static function isContainerKind(string $kind): bool
    {
        return isset(self::PREFIXES[$kind]);
    }

    public static function prefix(string $kind): string
    {
        if (!isset(self::PREFIXES[$kind])) {
            throw new \InvalidArgumentException('Kein Behältertyp: ' . $kind);
        }
        return self::PREFIXES[$kind];
    }

    /** Stellenbreite der laufenden Nummer (aus Konfiguration). */
    public static function width(string $kind): int
    {
        $widths = Config::get('labels.code_widths', []);
        return isset($widths[$kind]) ? (int) $widths[$kind] : 6;
    }

    /** Formatiert z. B. ('tuete', 345) → 'T-000345'. */
    public static function format(string $kind, int $seq): string
    {
        return self::prefix($kind)
            . str_pad((string) $seq, self::width($kind), '0', STR_PAD_LEFT);
    }

    /**
     * Zerlegt einen Code in [kind, seq]; null bei unbekanntem/ungültigem Format.
     */
    public static function parse(string $code): ?array
    {
        $code = trim($code);
        foreach (self::PREFIXES as $kind => $prefix) {
            if (strpos($code, $prefix) === 0) {
                $num = substr($code, strlen($prefix));
                if ($num !== '' && ctype_digit($num)) {
                    return [$kind, (int) $num];
                }
            }
        }
        return null;
    }

    /** Grobe Formatprüfung ohne DB. */
    public static function isValid(string $code): bool
    {
        return self::parse($code) !== null;
    }
}
