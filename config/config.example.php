<?php
/**
 * BrickBank – Konfigurationsvorlage.
 * Kopieren nach config/config.php und ausfüllen. config.php gehört NICHT ins Repo.
 */
return [
    'app' => [
        // Verzeichnis-Basis für STATISCHE Dateien (CSS/JS/Bilder).
        // Leer = automatische Erkennung aus dem Pfad der index.php (empfohlen).
        // Routen laufen ohnehin über index.php (PATH_INFO), daher hier i. d. R. '' lassen.
        // Beispiel bei Bedarf: '/apps/BrickBank/public'
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
        // Liegen die WoltLab-Tabellen (wcf1_*) in DERSELBEN Datenbank wie
        // BrickBank? Dann true setzen – dann wird die App-Verbindung genutzt
        // und der separate 'db'-Block unten ignoriert.
        'same_database' => false,
        'db' => [
            'host'     => '127.0.0.1',
            'name'     => 'wcf',            // leer lassen, wenn same_database = true
            'user'     => 'brickbank_ro',   // DB-Benutzer mit NUR-LESE-Rechten
            'password' => '',
            'charset'  => 'utf8mb4',
        ],
        'table_prefix'  => 'wcf1_',
        'cookie_prefix' => 'wsc_22462a_',   // Session-Cookie: wsc_22462a_user_session
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
