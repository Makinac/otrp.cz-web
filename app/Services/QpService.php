<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\DiscordOAuth;
use App\Models\QpBonusModel;
use App\Models\QpRoleConfigModel;

/**
 * QuePoints (QP) service.
 *
 * On every call the user's Discord roles are fetched live from the Discord API,
 * so the value always reflects current role assignments.
 */
class QpService
{
    /**
     * Calculate the total QP for a Discord user.
     *
     * Fetches live role data from Discord every time. Returns 0 on API error.
     *
     * @param string $discordId  Discord user ID (snowflake).
     * @param int    $userId     Internal users.id for bonus lookup.
     */
    public static function calculate(string $discordId, int $userId): int
    {
        $breakdown = self::getBreakdown($discordId, $userId);
        return $breakdown['total'];
    }

    /**
     * Returns a detailed QP breakdown for display in admin UI.
     *
     * ```
     * [
     *   'total'          => int,
     *   'roles_total'    => int,
     *   'bonuses_total'  => int,
     *   'role_hits'      => [['role_id'=>'...','qp_value'=>50], ...],  // only configured roles the user has
     *   'bonuses'        => [...],   // active bonus rows
     *   'all_bonuses'    => [...],   // all bonuses incl. expired (for history display)
     *   'discord_error'  => bool,    // true if Discord API call failed
     * ]
     * ```
     *
     * @param string $discordId
     * @param int    $userId
     * @return array<string,mixed>
     */
    public static function getBreakdown(string $discordId, int $userId): array
    {
        $configModel = new QpRoleConfigModel();
        $bonusModel  = new QpBonusModel();

        $roleMap     = $configModel->getRoleMap();   // [role_id => qp_value]
        $memberRoles = DiscordBot::getMemberRoles($discordId); // list<string>|false
        $discordError = $memberRoles === false;

        // Build role id -> name map for display (one API call, only when needed)
        $roleNameMap = [];
        if (!$discordError && !empty($roleMap)) {
            foreach (DiscordOAuth::getGuildRoles() as $gr) {
                $roleNameMap[$gr['id']] = $gr['name'];
            }
        }

        $roleHits   = [];
        $rolesTotal = 0;

        if (!$discordError) {
            foreach ($memberRoles as $roleId) {
                if (isset($roleMap[$roleId]) && $roleMap[$roleId] > 0) {
                    $roleHits[] = [
                        'role_id'   => $roleId,
                        'role_name' => $roleNameMap[$roleId] ?? $roleId,
                        'qp_value'  => $roleMap[$roleId],
                    ];
                    $rolesTotal += $roleMap[$roleId];
                }
            }
        }

        $activeBonuses = $bonusModel->getActiveByUserId($userId);
        $allBonuses    = $bonusModel->getByUserId($userId);
        $bonusesTotal  = 0;
        foreach ($activeBonuses as $b) {
            $bonusesTotal += (int)$b['amount'];
        }

        $total = min(100_000, $rolesTotal + $bonusesTotal);

        return [
            'total'         => $total,
            'roles_total'   => $rolesTotal,
            'bonuses_total' => $bonusesTotal,
            'role_hits'     => $roleHits,
            'bonuses'       => $activeBonuses,
            'all_bonuses'   => $allBonuses,
            'discord_error' => $discordError,
        ];
    }
}
