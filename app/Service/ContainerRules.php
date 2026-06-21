<?php
namespace App\Service;

/**
 * Fachregeln für die Behälter-Hierarchie (Tüte → Karton → Container → Lagerort).
 * Wird sowohl beim Anlegen (Standort setzen) als auch beim Umräumen genutzt.
 */
class ContainerRules
{
    /**
     * Darf ein Behälter vom Typ $kind unter einen Ort vom Typ $parentKind?
     * $parentKind === null bedeutet „kein Standort gesetzt" und ist erlaubt.
     */
    public static function parentAllowed(string $kind, ?string $parentKind): bool
    {
        if ($parentKind === null) {
            return true;
        }
        switch ($kind) {
            case 'tuete':
                return $parentKind === 'karton';
            case 'karton':
                return $parentKind === 'container';
            case 'container':
                return !CodeGenerator::isContainerKind($parentKind);
        }
        return false;
    }

    /** Verständliche Fehlermeldung zur Regel des jeweiligen Typs. */
    public static function ruleMessage(string $kind): string
    {
        switch ($kind) {
            case 'tuete':
                return 'Eine Tüte darf nur in einen Karton.';
            case 'karton':
                return 'Ein Karton darf nur in einen Container.';
            case 'container':
                return 'Ein Container darf nur an einen normalen Lagerort (Raum, Schrank …), '
                     . 'nicht in einen anderen Behälter.';
        }
        return 'Ungültiger Zielort.';
    }

    /** Lesbare Bezeichnung eines Behältertyps. */
    public static function label(string $kind): string
    {
        $map = ['container' => 'Container', 'karton' => 'Karton', 'tuete' => 'Tüte'];
        return $map[$kind] ?? $kind;
    }
}
