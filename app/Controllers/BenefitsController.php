<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Permission;
use App\Core\Logger;
use App\Core\Session;
use App\Models\CharBonusModel;
use App\Models\PedBonusModel;
use App\Models\QpBonusModel;
use App\Models\RedeemCodeModel;
use App\Services\CharService;
use App\Services\QpService;

/**
 * Výhody (Benefits) page — shows QP, character slots, and code redemption.
 */
class BenefitsController extends BaseController
{
    /**
     * GET /vyhody
     */
    public function index(array $params = []): void
    {
        if (!Permission::isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId    = (int)Permission::userId();
        $discordId = (string)Session::get('discord_id', '');

        $qpBreakdown   = QpService::getBreakdown($discordId, $userId);
        $charBreakdown = CharService::getBreakdown($discordId, $userId);
        $pedAccess     = (new PedBonusModel())->hasAccess($userId);
        $redeemHistory = (new RedeemCodeModel())->getHistoryByUserId($userId);

        $this->render('benefits', [
            'pageTitle'     => 'Výhody',
            'qpBreakdown'   => $qpBreakdown,
            'charBreakdown' => $charBreakdown,
            'pedAccess'     => $pedAccess,
            'redeemHistory' => $redeemHistory,
        ]);
    }

    /**
     * POST /vyhody/redeem — redeem a code for QP or character slots.
     */
    public function redeem(array $params = []): void
    {
        if (!Permission::isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $this->requirePost();
        $this->verifyCsrf();

        // Rate limit: max 10 redemption attempts per 5 minutes.
        if (!Session::rateLimit('code_redeem', 10, 300)) {
            Session::flash('error', 'Příliš mnoho pokusů. Zkus to za chvíli.');
            $this->redirect('/vyhody');
            return;
        }

        $userId = (int)Permission::userId();
        $code   = strtoupper(trim($_POST['code'] ?? ''));

        if ($code === '') {
            Session::flash('error', 'Zadej kód.');
            $this->redirect('/vyhody');
            return;
        }

        // Basic format check: XXXX-XXXX-XXXX
        if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code)) {
            Session::flash('error', 'Neplatný formát kódu.');
            $this->redirect('/vyhody');
            return;
        }

        $codeModel = new RedeemCodeModel();
        $row       = $codeModel->findByCode($code);

        if (!$row) {
            Session::flash('error', 'Kód nebyl nalezen.');
            $this->redirect('/vyhody');
            return;
        }

        if ((int)$row['used_count'] >= (int)$row['max_uses']) {
            Session::flash('error', 'Kód byl již plně využit.');
            $this->redirect('/vyhody');
            return;
        }

        if ($codeModel->hasUserRedeemed((int)$row['id'], $userId)) {
            Session::flash('error', 'Tento kód jsi již uplatnil/a.');
            $this->redirect('/vyhody');
            return;
        }

        try {
            $amount    = (int)$row['amount'];
            $type      = (string)$row['type'];
            $expiresAt = $row['expires_at'];

            $codeModel->redeem((int)$row['id'], $userId);

            if ($type === 'qp') {
                (new QpBonusModel())->add($userId, $amount, 'Kód: ' . $code, $expiresAt, null);
                Session::flash('success', 'Kód uplatněn! Získal/a jsi +' . number_format($amount, 0, ',', ' ') . ' QP.');
            } elseif ($type === 'ped') {
                (new PedBonusModel())->add($userId, 'Kód: ' . $code, $expiresAt, null);
                Session::flash('success', 'Kód uplatněn! Získal/a jsi přístup k ped menu.');
            } else {
                (new CharBonusModel())->add($userId, $amount, 'Kód: ' . $code, $expiresAt, null);
                $label = $amount === 1 ? 'slot pro postavu' : 'sloty pro postavy';
                Session::flash('success', 'Kód uplatněn! Získal/a jsi +' . $amount . ' ' . $label . '.');
            }
        } catch (\Throwable $e) {
            Logger::error('Redeem code: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uplatnit kód. Zkus to znovu.');
        }

        $this->redirect('/vyhody');
    }
}
