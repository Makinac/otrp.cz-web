<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <!-- Generate new API key -->
        <div class="card" style="margin-bottom:2rem;">
            <h2 class="card-title">Vygenerovat nový API klíč</h2>
            <form method="POST" action="/management/api-keys/create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label class="form-label" for="keyLabel">Název <span class="req">*</span></label>
                        <input type="text" id="keyLabel" name="label" class="form-control" placeholder="např. FiveM Server, Discord Bot…" maxlength="100" required>
                    </div>
                    <div class="form-group" style="flex:3;">
                        <label class="form-label" for="keyIps">Povolené IP <span style="color:var(--text-muted);font-weight:400;">(oddělené čárkou, prázdné = libovolná)</span></label>
                        <input type="text" id="keyIps" name="allowed_ips" class="form-control" placeholder="např. 123.45.67.89, 10.0.0.1">
                    </div>
                </div>

                <div style="margin-top:.5rem;">
                    <button type="submit" class="btn btn-primary">Vygenerovat klíč</button>
                </div>
            </form>
        </div>

        <!-- API keys list -->
        <div class="card">
            <h2 class="card-title">Všechny API klíče</h2>

            <?php if (empty($apiKeys)): ?>
                <p class="empty-notice">Žádné API klíče.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Název</th>
                                <th>API Klíč</th>
                                <th>Povolené IP</th>
                                <th style="width:90px;">Stav</th>
                                <th>Naposledy použit</th>
                                <th>Vytvořil</th>
                                <th style="width:200px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apiKeys as $k): ?>
                                <?php $inactive = !(bool)$k['is_active']; ?>
                                <tr<?= $inactive ? ' style="opacity:.55;"' : '' ?>>
                                    <td><strong><?= htmlspecialchars($k['label']) ?></strong></td>
                                    <td>
                                        <code class="codes-code-display" style="font-size:.75rem;word-break:break-all;"><?= htmlspecialchars(substr($k['api_key'], 0, 8)) ?>…<?= htmlspecialchars(substr($k['api_key'], -4)) ?></code>
                                        <button type="button" class="btn btn-sm btn-outline-primary" style="margin-left:.3rem;padding:2px 8px;font-size:.7rem;" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($k['api_key'], ENT_QUOTES) ?>').then(()=>{this.textContent='Zkopírováno!';setTimeout(()=>this.textContent='Kopírovat',1500)})">Kopírovat</button>
                                    </td>
                                    <td>
                                        <form method="POST" action="/management/api-keys/<?= (int)$k['id'] ?>/update-ips" style="display:flex;gap:.3rem;align-items:center;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="text" name="allowed_ips" class="form-control" style="font-size:.8rem;padding:3px 6px;min-width:160px;" value="<?= htmlspecialchars($k['allowed_ips'] ?? '') ?>" placeholder="Libovolná">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" style="padding:2px 8px;font-size:.7rem;white-space:nowrap;">Uložit</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($k['is_active']): ?>
                                            <span class="status-badge status-active" style="font-size:.7rem;">Aktivní</span>
                                        <?php else: ?>
                                            <span class="status-badge status-blocked" style="font-size:.7rem;">Neaktivní</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $k['last_used_at'] ? date('j. n. Y H:i', strtotime($k['last_used_at'])) : '<span style="color:var(--text-muted);">—</span>' ?>
                                    </td>
                                    <td><?= htmlspecialchars($k['created_by_name'] ?? '—') ?></td>
                                    <td style="display:flex;gap:.3rem;">
                                        <form method="POST" action="/management/api-keys/<?= (int)$k['id'] ?>/toggle">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <button type="submit" class="btn btn-sm <?= $k['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" style="font-size:.7rem;">
                                                <?= $k['is_active'] ? 'Deaktivovat' : 'Aktivovat' ?>
                                            </button>
                                        </form>
                                        <form method="POST" action="/management/api-keys/<?= (int)$k['id'] ?>/delete" onsubmit="return confirm('Opravdu smazat API klíč \'<?= htmlspecialchars($k['label'], ENT_QUOTES) ?>\'?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.7rem;">Smazat</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- API Documentation -->
        <h2 class="section-heading" style="margin-top:2rem;">API Dokumentace</h2>

        <!-- Authentication -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <span class="api-doc-section-title">Autentizace</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Všechny endpointy vyžadují API klíč v HTTP hlavičce. Každý klíč může mít omezení na IP adresy.</p>
                <div class="api-doc-code-block"><span class="api-doc-code-key">X-API-Key:</span> <span class="api-doc-code-val">&lt;váš_api_klíč&gt;</span></div>
                <table class="api-doc-table" style="margin-top:1rem;">
                    <thead><tr><th>HTTP</th><th>Odpověď</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><span class="api-badge api-badge-401">401</span></td><td><code>{"error": "Missing API key"}</code></td><td>Chybí hlavička X-API-Key</td></tr>
                        <tr><td><span class="api-badge api-badge-401">401</span></td><td><code>{"error": "Invalid API key"}</code></td><td>Klíč neexistuje nebo je neaktivní</td></tr>
                        <tr><td><span class="api-badge api-badge-403">403</span></td><td><code>{"error": "IP not allowed"}</code></td><td>Volající IP není v povoleném seznamu</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Endpoint 1: Access Check -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <div class="api-doc-methods">
                    <span class="api-method api-method-get">GET</span>
                    <span class="api-method api-method-post">POST</span>
                </div>
                <code class="api-doc-endpoint">/api/access/<span class="api-param">:server</span>/<span class="api-param">:discord_id</span></code>
                <span class="api-doc-title">Kontrola přístupu hráče</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Zkontroluje, zda má hráč přístup na daný server. Vrací QP, počet slotů postav a příznak ped menu. Metoda POST navíc synchronizuje FiveM identifikátory a detekuje multi-accounty.</p>
                <div class="api-doc-subsection">Parametry URL</div>
                <table class="api-doc-table">
                    <thead><tr><th>Parametr</th><th>Typ</th><th>Povolené hodnoty</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>server</code></td><td>string</td><td><code>main</code>, <code>dev</code>, <code>maps</code></td><td>Název serveru</td></tr>
                        <tr><td><code>discord_id</code></td><td>string</td><td>—</td><td>Discord snowflake ID hráče</td></tr>
                    </tbody>
                </table>
                <div class="api-doc-subsection" style="margin-top:1rem;">Request body <span style="color:var(--grey-dim);font-weight:400;">(pouze POST, JSON)</span></div>
                <pre class="api-doc-pre">{
  "identifiers": {
    "license":  "license:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "license2": "license:yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
    "steam":    "steam:110000xxxxxxxxx",
    "fivem":    "fivem:xxxxxxx",
    "discord":  "discord:123456789012345678",
    "ip":       "ip:1.2.3.4"
  }
}</pre>
                <div class="api-doc-subsection" style="margin-top:1rem;">Odpověď <span class="api-badge api-badge-200">200</span></div>
                <pre class="api-doc-pre">{
  "discord_id": "123456789012345678",
  "server":     "main",
  "allowed":    true,
  "reason":     null,
  "qp":         1500,
  "chars":      3,
  "ped":        true
}</pre>
                <table class="api-doc-table" style="margin-top:.75rem;">
                    <thead><tr><th>Pole</th><th>Typ</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>allowed</code></td><td>bool</td><td>Má hráč přístup na server</td></tr>
                        <tr><td><code>reason</code></td><td>string|null</td><td><code>not_found</code>, <code>blacklisted</code>, <code>banned</code>, <code>no_allowlist</code> nebo <code>null</code></td></tr>
                        <tr><td><code>expires_at</code></td><td>string</td><td>Jen pokud reason = <code>banned</code> — datum vypršení banu</td></tr>
                        <tr><td><code>qp</code></td><td>int</td><td>Počet QP (QuePoints) hráče</td></tr>
                        <tr><td><code>chars</code></td><td>int</td><td>Počet slotů postav</td></tr>
                        <tr><td><code>ped</code></td><td>bool</td><td>Má přístup k ped menu</td></tr>
                    </tbody>
                </table>
                <div class="api-doc-subsection" style="margin-top:1rem;">Chyby</div>
                <table class="api-doc-table">
                    <thead><tr><th>HTTP</th><th>Odpověď</th></tr></thead>
                    <tbody>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "Invalid server. Use: main, dev, maps"}</code></td></tr>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "Missing discord_id"}</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Endpoint 2: Benefits -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <div class="api-doc-methods">
                    <span class="api-method api-method-get">GET</span>
                </div>
                <code class="api-doc-endpoint">/api/benefits/<span class="api-param">:discord_id</span></code>
                <span class="api-doc-title">Výhody hráče</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Vrátí všechny výhody hráče — QP, počet slotů postav a přístup k ped menu.</p>
                <div class="api-doc-subsection">Parametry URL</div>
                <table class="api-doc-table">
                    <thead><tr><th>Parametr</th><th>Typ</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>discord_id</code></td><td>string</td><td>Discord snowflake ID hráče</td></tr>
                    </tbody>
                </table>
                <div class="api-doc-subsection" style="margin-top:1rem;">Odpověď <span class="api-badge api-badge-200">200</span></div>
                <pre class="api-doc-pre">{
  "discord_id": "123456789012345678",
  "qp":         2350,
  "chars":      3,
  "ped":        true
}</pre>
                <table class="api-doc-table" style="margin-top:.75rem;">
                    <thead><tr><th>Pole</th><th>Typ</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>qp</code></td><td>int</td><td>Počet QP (QuePoints)</td></tr>
                        <tr><td><code>chars</code></td><td>int</td><td>Počet slotů postav</td></tr>
                        <tr><td><code>ped</code></td><td>bool</td><td>Má přístup k ped menu</td></tr>
                    </tbody>
                </table>
                <div class="api-doc-subsection" style="margin-top:1rem;">Chyby</div>
                <table class="api-doc-table">
                    <thead><tr><th>HTTP</th><th>Odpověď</th></tr></thead>
                    <tbody>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "Missing discord_id"}</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Endpoint 3: Permissions -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <div class="api-doc-methods">
                    <span class="api-method api-method-get">GET</span>
                </div>
                <code class="api-doc-endpoint">/api/permissions/<span class="api-param">:discord_id</span></code>
                <span class="api-doc-title">Oprávnění hráče</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Vrátí admin, management a in-game oprávnění hráče. Vedení role mají automaticky vše <code>true</code>.</p>
                <div class="api-doc-subsection">Parametry URL</div>
                <table class="api-doc-table">
                    <thead><tr><th>Parametr</th><th>Typ</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>discord_id</code></td><td>string</td><td>Discord snowflake ID hráče</td></tr>
                    </tbody>
                </table>
                <div class="api-doc-subsection" style="margin-top:1rem;">Odpověď <span class="api-badge api-badge-200">200</span></div>
                <pre class="api-doc-pre">{
  "discord_id":        "123456789012345678",
  "admin_permissions": {
    "admin.allowlist":             true,
    "admin.allowlist.reinterview": false,
    "admin.players":               true,
    "admin.players.punishments":   true,
    "admin.players.access":        false,
    "admin.players.appeals":       false,
    "admin.ck":                    false,
    "admin.activity":              false,
    "admin.vacation":              false,
    "admin.security":              false,
    "admin.qp_bonus":              false,
    "admin.char_bonus":            false
  },
  "management_permissions": {
    "management.form":            true,
    "management.content":         true,
    "management.rules":           false,
    "management.blacklist":       false,
    "management.appeals":         false,
    "management.team":            false,
    "management.cheatsheet":      false,
    "management.partners":        false,
    "management.homepage":        false,
    "management.allowlist_stats": false,
    "management.qp":              false,
    "management.chars":           false,
    "management.codes":           false,
    "management.api_keys":        false,
    "management.settings":        false
  },
  "rsg_permissions": {
    "ingame.admin":      true,
    "ingame.management": false
  },
  "lib_permissions": {
    "dev": {
      "lib.jobscreator":  false,
      "lib.blipscreator": false,
      "lib.shopscreator": false
    },
    "admin": {}
  },
  "admin_settings": {
    "admin_prefix_chat":    true,
    "report_notifications": true
  }
}</pre>
                <table class="api-doc-table" style="margin-top:.75rem;">
                    <thead><tr><th>Pole</th><th>Typ</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>admin_permissions</code></td><td>object</td><td>Mapa admin klíčů → bool</td></tr>
                        <tr><td><code>management_permissions</code></td><td>object</td><td>Mapa management klíčů → bool</td></tr>
                        <tr><td><code>rsg_permissions</code></td><td>object</td><td>In-game práva: <code>ingame.admin</code>, <code>ingame.management</code></td></tr>
                        <tr><td><code>lib_permissions.dev</code></td><td>object</td><td>LIB Dev práva: <code>lib.jobscreator</code>, <code>lib.blipscreator</code>, <code>lib.shopscreator</code></td></tr>
                        <tr><td><code>lib_permissions.admin</code></td><td>object</td><td>LIB Admin práva (zatím prázdné)</td></tr>
                        <tr><td><code>admin_settings</code></td><td>object</td><td>Osobní nastavení admina (<code>admin_prefix_chat</code>, <code>report_notifications</code>)</td></tr>
                    </tbody>
                </table>
                <div class="api-doc-subsection" style="margin-top:1rem;">Chyby</div>
                <table class="api-doc-table">
                    <thead><tr><th>HTTP</th><th>Odpověď</th></tr></thead>
                    <tbody>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "Missing discord_id"}</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Endpoint 4: Generate Code -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <div class="api-doc-methods">
                    <span class="api-method api-method-post">POST</span>
                </div>
                <code class="api-doc-endpoint">/api/codes</code>
                <span class="api-doc-title">Vygenerovat kód</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Vygeneruje nový vykupitelný kód (QP, sloty postav nebo ped). Určeno pro Tebex nebo jiné externí integrace.</p>
                <div class="api-doc-subsection">Request body (JSON)</div>
                <pre class="api-doc-pre">{
  "type":       "qp",
  "amount":     500,
  "max_uses":   1,
  "note":       "Tebex order #123",
  "expires_at": "2026-12-31 23:59:59"
}</pre>
                <table class="api-doc-table" style="margin-top:.75rem;">
                    <thead><tr><th>Pole</th><th>Typ</th><th>Povinné</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>type</code></td><td>string</td><td>✅</td><td><code>"qp"</code>, <code>"chars"</code> nebo <code>"ped"</code></td></tr>
                        <tr><td><code>amount</code></td><td>int</td><td>✅</td><td>Hodnota (musí být &gt; 0; u ped ignorováno)</td></tr>
                        <tr><td><code>max_uses</code></td><td>int</td><td>❌</td><td>Počet použití (výchozí: 1)</td></tr>
                        <tr><td><code>note</code></td><td>string</td><td>❌</td><td>Poznámka (max 255 znaků)</td></tr>
                        <tr><td><code>expires_at</code></td><td>string|null</td><td>❌</td><td>Datum platnosti výhody po uplatnění (<code>YYYY-MM-DD HH:MM:SS</code>), nebo <code>null</code> = trvalé</td></tr>
                    </tbody>
                </table>
                <div class="api-doc-subsection" style="margin-top:1rem;">Odpověď <span class="api-badge api-badge-201">201</span></div>
                <pre class="api-doc-pre">{
  "code": "AB3K-XY7N-QW4R"
}</pre>
                <div class="api-doc-subsection" style="margin-top:1rem;">Chyby</div>
                <table class="api-doc-table">
                    <thead><tr><th>HTTP</th><th>Odpověď</th></tr></thead>
                    <tbody>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "Invalid JSON body"}</code></td></tr>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "type must be \"qp\", \"chars\" or \"ped\""}</code></td></tr>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "amount must be a positive integer"}</code></td></tr>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "Invalid expires_at format"}</code></td></tr>
                        <tr><td><span class="api-badge api-badge-500">500</span></td><td><code>{"error": "Internal server error"}</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Endpoint 5: Update Admin Settings -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <div class="api-doc-methods">
                    <span class="api-method api-method-post">POST</span>
                </div>
                <code class="api-doc-endpoint">/api/settings/<span class="api-param">:discord_id</span></code>
                <span class="api-doc-title">Aktualizovat nastavení admina</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Aktualizuje osobní nastavení admina (prefix v chatu, oznámení o reportech). Pole která nejsou uvedena zůstanou nezměněna.</p>

                <div class="api-doc-perm-row">
                    <span class="api-doc-perm-label">Vyžaduje:</span>
                    <span class="api-doc-perm-badge">API Klíč</span>
                </div>

                <div class="api-doc-subsection">Parametry URL</div>
                <table class="api-doc-table">
                    <thead><tr><th>Parametr</th><th>Typ</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>discord_id</code></td><td>string</td><td>Discord snowflake ID hráče</td></tr>
                    </tbody>
                </table>

                <div class="api-doc-subsection" style="margin-top:1rem;">Request body (JSON)</div>
                <pre class="api-doc-pre">{
  "admin_prefix_chat":    false,
  "report_notifications": true
}</pre>
                <table class="api-doc-table" style="margin-top:.75rem;">
                    <thead><tr><th>Pole</th><th>Typ</th><th>Povinné</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>admin_prefix_chat</code></td><td>bool</td><td>❌</td><td>Zapnout/vypnout admin prefix v chatu</td></tr>
                        <tr><td><code>report_notifications</code></td><td>bool</td><td>❌</td><td>Zapnout/vypnout oznámení o reportech</td></tr>
                    </tbody>
                </table>

                <div class="api-doc-subsection" style="margin-top:1rem;">Odpověď <span class="api-badge api-badge-200">200</span></div>
                <pre class="api-doc-pre">{
  "discord_id": "123456789",
  "admin_prefix_chat": false,
  "report_notifications": true
}</pre>

                <div class="api-doc-subsection" style="margin-top:1rem;">Chyby</div>
                <table class="api-doc-table">
                    <thead><tr><th>HTTP</th><th>Odpověď</th></tr></thead>
                    <tbody>
                        <tr><td><span class="api-badge api-badge-400">400</span></td><td><code>{"error": "Invalid JSON body"}</code></td></tr>
                        <tr><td><span class="api-badge api-badge-404">404</span></td><td><code>{"error": "User not found"}</code></td></tr>
                        <tr><td><span class="api-badge api-badge-500">500</span></td><td><code>{"error": "Internal server error"}</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
