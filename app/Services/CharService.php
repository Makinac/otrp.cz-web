<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\DiscordOAuth;
use App\Models\CharBonusModel;
use App\Models\CharRoleConfigModel;

/**
 * Character-slot service.
 *
 * Calculates how many character slots a player has based on their Discord roles
 * (stackable, same mechanism as QP) plus any manual bonuses.
 * Minimum total is always 1 slot.
 */
class CharService
{
    /**
     * Calculate the total character slots for a Discord user.
     *
     * @param string $discordId  Discord user ID (snowflake).
     * @param int    $userId     Internal users.id for bonus lookup.
     */
    public static function calculate(string $discordId, int $userId): int
    {
        return self::getBreakdown($discordId, $userId)['total'];
    }

    /**
     * Returns a detailed character-slot breakdown for display.
     *
     * ```
     * [
     *   'total'          => int,        // always >= 1
     *   'roles_total'    => int,
     *   'bonuses_total'  => int,
     *   'role_hits'      => [['role_id'=>'...','role_name'=>'...','char_value'=>2], ...],
     *   'bonuses'        => [...],      // active bonus rows
     *   'all_bonuses'    => [...],      // all bonuses incl. expired
     *   'discord_error'  => bool,
     * ]
     * ```
     *
     * @param string $discordId
     * @param int    $userId
     * @return array<string,mixed>
     */
    public static function getBreakdown(string $discordId, int $userId): array
    {
        $configModel = new CharRoleConfigModel();
        $bonusModel  = new CharBonusModel();

        $roleMap      = $configModel->getRoleMap();
        $memberRoles  = DiscordBot::getMemberRoles($discordId);
        $discordError = $memberRoles === false;

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
                        'role_id'    => $roleId,
                        'role_name'  => $roleNameMap[$roleId] ?? $roleId,
                        'char_value' => $roleMap[$roleId],
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

        // Base slot (1) + roles + bonuses, minimum 1, maximum 15
        $baseSlot = 1;
        $total = max(1, min(15, $baseSlot + $rolesTotal + $bonusesTotal));

        return [
            'total'         => $total,
            'base_slot'     => $baseSlot,
            'roles_total'   => $rolesTotal,
            'bonuses_total' => $bonusesTotal,
            'role_hits'     => $roleHits,
            'bonuses'       => $activeBonuses,
            'all_bonuses'   => $allBonuses,
            'discord_error' => $discordError,
        ];
    }
}
