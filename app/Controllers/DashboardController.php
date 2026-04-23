<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Permission;
use App\Core\Session;
use App\Models\AllowlistModel;
use App\Models\AppealModel;
use App\Models\BlacklistModel;
use App\Models\FormSchemaModel;
use App\Models\UserModel;

/**
 * Dashboard and allowlist application handling.
 */
class DashboardController extends BaseController
{
    /**
     * Display the user dashboard with the current allowlist status.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function index(array $params = []): void
    {
        $userId = Permission::userId();

        $blacklistModel  = new BlacklistModel();
        $allowlistModel  = new AllowlistModel();
        $appealModel     = new AppealModel();

        $blacklisted     = $blacklistModel->findByDiscordId(Permission::discordId());
        $applications    = $allowlistModel->findAllByUserId($userId);
        $activeAppeal    = $appealModel->findPendingByUserId($userId);

        $this->render('dashboard', [
            'pageTitle'    => 'Allowlist',
            'blacklisted'  => $blacklisted,
            'applications' => $applications,
            'activeAppeal' => $activeAppeal,
        ]);
    }

    /**
     * Display the allowlist application form (GET).
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function applicationForm(array $params = []): void
    {
        $userId = Permission::userId();
        $this->guardApplicationAccess($userId);

        $schemaModel = new FormSchemaModel();
        $schema      = $schemaModel->getActive();

        if (!$schema) {
            Session::flash('error', 'Formulář není momentálně dostupný. Zkuste to později.');
            $this->redirect('/allowlist');
        }

        $fields = json_decode($schema['fields_json'], true) ?? [];

        $this->render('application_form', [
            'pageTitle' => 'Žádost o allowlist',
            'fields'    => $fields,
            'schema'    => $schema,
        ]);
    }

    /**
     * Handle allowlist application form submission (POST).
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function submitApplication(array $params = []): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/apply');
        }

        // CSRF check.
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Neplatný CSRF token.');
            $this->redirect('/apply');
        }

        $userId = Permission::userId();
        $this->guardApplicationAccess($userId);

        // Rate limit: max 3 application submissions per 10 minutes.
        if (!Session::rateLimit('application_submit', 3, 600)) {
            Session::flash('error', 'Příliš mnoho pokusů. Zkus to za chvíli.');
            $this->redirect('/allowlist');
        }

        $schemaModel = new FormSchemaModel();
        $schema      = $schemaModel->getActive();

        if (!$schema) {
            Session::flash('error', 'Formulář není k dispozici.');
            $this->redirect('/allowlist');
        }

        $fields    = json_decode($schema['fields_json'], true) ?? [];
        $formData  = [];
        $errors    = [];

        foreach ($fields as $field) {
            $name     = $field['name'] ?? '';
            $required = (bool)($field['required'] ?? false);
            $type     = $field['type'] ?? 'text';

            if ($type === 'checkbox') {
                $value = isset($_POST[$name]) ? (array)$_POST[$name] : [];
            } else {
                $value = trim($_POST[$name] ?? '');
            }

            if ($required && empty($value)) {
                $errors[] = "Pole \"{$field['label']}\" je povinné.";
            }

            $formData[$name] = $value;
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('/apply');
        }

        $allowlistModel = new AllowlistModel();
        $attemptNumber  = $allowlistModel->countByUserId($userId) + 1;
        $allowlistModel->create($userId, $formData, $attemptNumber);

        Session::flash('success', 'Vaše žádost byla odeslána a čeká na vyřízení.');
        $this->redirect('/allowlist');
    }

    /**
     * Guard method: blocks access to the application form under restriction conditions.
     *
     * @param int $userId Internal user DB id.
     */
    private function guardApplicationAccess(int $userId): void
    {
        $blacklistModel = new BlacklistModel();
        if ($blacklistModel->findByDiscordId(Permission::discordId())) {
            Session::flash('error', 'Nemůžeš podat žádost — jsi na denylistu.');
            $this->redirect('/allowlist');
        }

        $allowlistModel = new AllowlistModel();
        $latestApp      = $allowlistModel->findLatestByUserId($userId);

        if ($latestApp && $latestApp['status'] === 'pending') {
            Session::flash('error', 'Máš aktivní nevyřízenou žádost.');
            $this->redirect('/allowlist');
        }

        $failedCount = $allowlistModel->countFailedAttemptsByUserId($userId);
        if ($failedCount >= 3) {
            Session::flash('error', 'Žádost je zablokována — nesplnil jsi podmínky.');
            $this->redirect('/allowlist');
        }
    }
}
