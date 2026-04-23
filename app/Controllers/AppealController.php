<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Permission;
use App\Core\Logger;
use App\Core\Session;
use App\Models\AppealModel;
use App\Models\BlacklistModel;

/**
 * Handles appeal submission and status display for logged-in users.
 */
class AppealController extends BaseController
{
    /**
     * Show appeal form or current appeal status (GET).
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function index(array $params = []): void
    {
        $userId       = Permission::userId();
        $appealModel  = new AppealModel();
        $activeAppeal = $appealModel->findPendingByUserId($userId);
        $allAppeals   = $appealModel->getAllByUserId($userId);

        $blacklistModel = new BlacklistModel();
        $blacklisted    = $blacklistModel->findByDiscordId(Permission::discordId());

        $this->render('appeal', [
            'pageTitle'    => 'Odvolání',
            'activeAppeal' => $activeAppeal,
            'allAppeals'   => $allAppeals,
            'blacklisted'  => $blacklisted,
        ]);
    }

    /**
     * Handle appeal form submission (POST).
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function submit(array $params = []): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/appeal');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Neplatný CSRF token.');
            $this->redirect('/appeal');
        }

        $userId      = Permission::userId();
        $appealModel = new AppealModel();

        // Rate limit: max 3 appeal submissions per 5 minutes.
        if (!Session::rateLimit('appeal_submit', 3, 300)) {
            Session::flash('error', 'Příliš mnoho pokusů. Zkus to za chvíli.');
            $this->redirect('/appeal');
        }

        // User may not have more than one pending appeal.
        if ($appealModel->findPendingByUserId($userId)) {
            Session::flash('error', 'Již máš aktivní odvolání čekající na vyřízení.');
            $this->redirect('/appeal');
        }

        $reason = trim($_POST['reason'] ?? '');
        $type   = $_POST['type'] ?? '';

        if (empty($reason) || mb_strlen($reason) > 5000) {
            Session::flash('error', 'Důvod odvolání musí mít 1–5000 znaků.');
            $this->redirect('/appeal');
        }

        if (!in_array($type, ['ban', 'warn', 'blacklist', 'allowlist'], true)) {
            Session::flash('error', 'Neplatný typ odvolání.');
            $this->redirect('/appeal');
        }

        try {
            $appealModel->create($userId, $reason, $type);
            Session::flash('success', 'Odvolání bylo úspěšně podáno. Vedení ho brzy posoudí.');
        } catch (\Throwable $e) {
            Logger::error('Appeal submission failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se odeslat odvolání.');
        }

        $this->redirect('/appeal');
    }
}
