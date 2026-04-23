<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <h2 class="section-heading">API Dokumentace</h2>

        <!-- Authentication -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <span class="api-doc-section-title">Autentizace</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Všechny endpointy vyžadují API klíč v HTTP hlavičce. Klíče se spravují v <a href="/management/api-keys" style="color:var(--gold);">API Klíče</a>. Každý klíč může mít omezení na IP adresy.</p>
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

                <div class="api-doc-perm-row">
                    <span class="api-doc-perm-label">Vyžaduje:</span>
                    <span class="api-doc-perm-badge">API Klíč</span>
                </div>

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

                <div class="api-doc-subsection" style="margin-top:1rem;">Odpověď <span class="api-badge api-badge-200">200</span></div>
                <pre class="api-doc-pre">{
  "discord_id":        "123456789012345678",
  "is_admin":          true,
  "is_management":     true,
  "ingame_admin":      true,
  "ingame_management": false,
  "admin_permissions": {
    "admin.allowlist":              true,
    "admin.allowlist.reinterview":  false,
    "admin.players":                true,
    "admin.players.punishments":    true,
    "admin.players.access":         false,
    "admin.players.appeals":        false,
    "admin.ck":                     false,
    "admin.activity":               false,
    "admin.vacation":               false,
    "admin.security":               false,
    "admin.qp_bonus":               false,
    "admin.char_bonus":             false
  },
  "management_permissions": {
    "management.form":             true,
    "management.content":          true,
    "management.rules":            false,
    "management.blacklist":        false,
    "management.appeals":          false,
    "management.team":             false,
    "management.cheatsheet":       false,
    "management.partners":         false,
    "management.homepage":         false,
    "management.allowlist_stats":  false,
    "management.qp":               false,
    "management.chars":            false,
    "management.codes":            false,
    "management.api_keys":         false,
    "management.settings":         false
  },
  "admin_settings": {
    "admin_prefix_chat":    true,
    "report_notifications": true
  }
}</pre>
                <table class="api-doc-table" style="margin-top:.75rem;">
                    <thead><tr><th>Pole</th><th>Typ</th><th>Popis</th></tr></thead>
                    <tbody>
                        <tr><td><code>is_admin</code></td><td>bool</td><td>Má alespoň jedno admin oprávnění</td></tr>
                        <tr><td><code>is_management</code></td><td>bool</td><td>Má alespoň jedno management oprávnění</td></tr>
                        <tr><td><code>ingame_admin</code></td><td>bool</td><td>Oprávnění <code>ingame.admin</code></td></tr>
                        <tr><td><code>ingame_management</code></td><td>bool</td><td>Oprávnění <code>ingame.management</code></td></tr>
                        <tr><td><code>admin_permissions</code></td><td>object</td><td>Mapa admin klíčů → bool</td></tr>
                        <tr><td><code>management_permissions</code></td><td>object</td><td>Mapa management klíčů → bool</td></tr>
                        <tr><td><code>admin_settings.admin_prefix_chat</code></td><td>bool</td><td>Admin prefix v chatu zapnut</td></tr>
                        <tr><td><code>admin_settings.report_notifications</code></td><td>bool</td><td>Oznámení o reportech zapnuta</td></tr>
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

                <div class="api-doc-perm-row">
                    <span class="api-doc-perm-label">Vyžaduje:</span>
                    <span class="api-doc-perm-badge">API Klíč</span>
                </div>

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
                <code class="api-doc-endpoint">/api/settings/:discord_id</code>
                <span class="api-doc-title">Aktualizovat nastavení admina</span>
            </div>
            <div class="api-doc-card-body">
                <p class="api-doc-desc">Aktualizuje osobní nastavení admina (prefix v chatu, oznámení o reportech). Pole která nejsou uvedena zůstanou nezměněna.</p>

                <div class="api-doc-perm-row">
                    <span class="api-doc-perm-label">Vyžaduje:</span>
                    <span class="api-doc-perm-badge">API Klíč</span>
                </div>

                <div class="api-doc-subsection">Request body (JSON)</div>
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

        <!-- Summary table -->
        <div class="api-doc-card">
            <div class="api-doc-card-header">
                <span class="api-doc-section-title">Přehled endpointů</span>
            </div>
            <div class="api-doc-card-body">
                <table class="api-doc-table">
                    <thead><tr><th>Metoda</th><th>Endpoint</th><th>Popis</th><th>Práva</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>
                                <span class="api-method api-method-get">GET</span>
                                <span class="api-method api-method-post">POST</span>
                            </td>
                            <td><code>/api/access/:server/:discord_id</code></td>
                            <td>Kontrola přístupu + sync identifikátorů (POST)</td>
                            <td><span class="api-doc-perm-badge">API Klíč</span></td>
                        </tr>
                        <tr>
                            <td><span class="api-method api-method-get">GET</span></td>
                            <td><code>/api/benefits/:discord_id</code></td>
                            <td>Výhody hráče (QP, chars, ped)</td>
                            <td><span class="api-doc-perm-badge">API Klíč</span></td>
                        </tr>
                        <tr>
                            <td><span class="api-method api-method-get">GET</span></td>
                            <td><code>/api/permissions/:discord_id</code></td>
                            <td>Admin/management/in-game oprávnění</td>
                            <td><span class="api-doc-perm-badge">API Klíč</span></td>
                        </tr>
                        <tr>
                            <td><span class="api-method api-method-post">POST</span></td>
                            <td><code>/api/codes</code></td>
                            <td>Vygenerovat vykupitelný kód</td>
                            <td><span class="api-doc-perm-badge">API Klíč</span></td>
                        </tr>
                        <tr>
                            <td><span class="api-method api-method-post">POST</span></td>
                            <td><code>/api/settings/:discord_id</code></td>
                            <td>Aktualizovat nastavení admina</td>
                            <td><span class="api-doc-perm-badge">API Klíč</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
