<?php
namespace App\Service;

/**
 * Hinweise zur empfohlenen Behälter-Hierarchie (Tüte → Karton → Container → Ort).
 * Die Reihenfolge ist im freien Lagerbaum nur eine EMPFEHLUNG, kein Zwang:
 * jeder Ast darf überall hängen (auch direkt am Konto-Root).
 */
class ContainerRules
{
    /** Freier Baum: jeder Zielort ist erlaubt. */
    public static function parentAllowed(string $kind, ?string $parentKind): bool
    {
        return true;
    }

    /** Entspricht die Platzierung der empfohlenen Reihenfolge? (für Hinweise) */
    public static function isRecommended(string $kind, ?string $parentKind): bool
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
        return true;
    }

    /** Empfehlungs-Hinweis (kein Fehler). */
    public static function ruleMessage(string $kind): string
    {
        switch ($kind) {
            case 'tuete':
                return 'Empfohlen: Tüte in einen Karton.';
            case 'karton':
                return 'Empfohlen: Karton in einen Container.';
            case 'container':
                return 'Empfohlen: Container an einen normalen Lagerort (Raum, Schrank …).';
        }
        return '';
    }

    /** Lesbare Bezeichnung eines Behältertyps. */
    public static function label(string $kind): string
    {
        $map = [
            'container'     => 'Container',
            'karton'        => 'Karton',
            'tuete'         => 'Tüte',
            'sortimentsbox' => 'Sortimentsbox',
            'einsatzkasten' => 'Einsatzkasten',
        ];
        return $map[$kind] ?? $kind;
    }
}
