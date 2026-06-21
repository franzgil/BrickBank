<?php
/**
 * BrickBank – Konfigurationsvorlage.
 * Kopieren nach config/config.php und ausfüllen. config.php gehört NICHT ins Repo.
 */
return [
    'app' => [
        // URL-Basis-Pfad, unter dem BrickBank läuft (z. B. '' für Root oder '/brickbank').
        'base_url' => '',
        'debug'    => false,
    ],

    // Eigene BrickBank-Datenbank (lesend + schreibend).
    'db' => [
        'host'     => '127.0.0.1',
        'name'     => 'brickbank',
        'user'     => 'brickbank',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // WoltLab Suite 5.5 – AUSSCHLIESSLICH LESEND.
    // Tabellen-/Cookie-Namen an der laufenden 5.5-Instanz verifizieren (siehe INTEGRATION.md).
    'wsc' => [
        'db' => [
            'host'     => '127.0.0.1',
            'name'     => 'wcf',
            'user'     => 'brickbank_ro',   // DB-Benutzer mit NUR-LESE-Rechten
            'password' => '',
            'charset'  => 'utf8mb4',
        ],
        'table_prefix'  => 'wcf1_',
        'cookie_prefix' => 'wsc_',                                   // an Instanz prüfen
        'login_url'     => 'https://afol55.afol.lu/index.php?login/',
        // WSC-Benutzergruppen-IDs, die in BrickBank schreiben dürfen.
        // Leer = jeder eingeloggte Nutzer darf schreiben.
        'allowed_group_ids' => [],
    ],

    // Etiketten-/Code-Konfiguration (Modul Behälter/Inventur).
    'labels' => [
        // Stellenbreite der laufenden Nummer je Behältertyp.
        'code_widths' => [
            'container' => 3,   // C-001
            'karton'    => 5,   // K-00012
            'tuete'     => 6,   // T-000345
        ],
        // Maße der Druck-Etiketten (mm) – z. B. Brother DK-Endlosrolle.
        'print' => [
            'width_mm'  => 62,
            'height_mm' => 29,
        ],
    ],
];
