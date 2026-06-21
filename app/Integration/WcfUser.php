<?php
namespace App\Integration;

use App\Core\Config;

/**
 * Read-only-Abbild eines WoltLab-Benutzers (aus der SSO-Session).
 */
class WcfUser
{
    /** @var int */
    public $userId;
    /** @var string */
    public $username;
    /** @var string */
    public $email;
    /** @var int[] */
    public $groupIds;

    public function __construct(int $userId, string $username, string $email, array $groupIds)
    {
        $this->userId   = $userId;
        $this->username = $username;
        $this->email    = $email;
        $this->groupIds = $groupIds;
    }

    /** Darf dieser Benutzer in BrickBank schreiben? Steuerung über WSC-Gruppen. */
    public function canWrite(): bool
    {
        $allowed = Config::get('wsc.allowed_group_ids', []);
        if (empty($allowed)) {
            return true; // leere Liste = jeder eingeloggte Nutzer
        }
        return count(array_intersect($allowed, $this->groupIds)) > 0;
    }
}
