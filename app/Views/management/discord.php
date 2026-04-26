<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>

        <!-- Tab navigace -->
        <div class="discord-tabs">
            <button class="discord-tab discord-tab-active" data-tab="automation">🌐 Web</button>
            <button class="discord-tab" data-tab="general">🤖 Obecné</button>
            <button class="discord-tab" data-tab="tickets">🎫 Tickety</button>
            <button class="discord-tab" data-tab="embeds">📝 Embedy</button>
            <button class="discord-tab" data-tab="stats">📊 Server statistiky</button>
        </div>

        <!-- ══ TAB: Automatizace allowlistu ══════════════════════════════════════ -->
        <div class="discord-tab-panel discord-tab-panel-active" id="tab-automation">
            <div class="card" style="border-left:3px solid #5865F2;margin-bottom:1.5rem;">
                <h2 class="card-title">⚙️ Automatizace Allowlistu</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Bot automaticky přidá roli a pošle DM hráčům na základě akcí v admin panelu.
                    Prázdná pole = akce se nevykoná.
                </p>

                <form method="POST" action="/management/discord/automation/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <h3 class="discord-section-heading">🏷️ Role přidělování</h3>
                    <div class="fields-grid" style="margin-bottom:1.5rem;">
                        <div class="form-group">
                            <label class="form-label" for="bot_role_approved">Role po schválení formuláře</label>
                            <select id="bot_role_approved" name="bot_role_approved" class="form-control">
                                <option value="">— žádná —</option>
                                <?php foreach ($discordRoles as $dr): ?>
                                    <option value="<?= htmlspecialchars($dr['id']) ?>"
                                        <?= ($automationSettings['bot_role_approved'] ?? '') === $dr['id'] ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($dr['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="bot_role_allowlisted">Role po splnění pohovoru</label>
                            <select id="bot_role_allowlisted" name="bot_role_allowlisted" class="form-control">
                                <option value="">— žádná —</option>
                                <?php foreach ($discordRoles as $dr): ?>
                                    <option value="<?= htmlspecialchars($dr['id']) ?>"
                                        <?= ($automationSettings['bot_role_allowlisted'] ?? '') === $dr['id'] ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($dr['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h3 class="discord-section-heading">💬 DM zprávy hráčům</h3>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" for="bot_dm_approved">DM po schválení formuláře</label>
                        <textarea id="bot_dm_approved" name="bot_dm_approved" class="form-control" rows="3"
                            placeholder="Např: Ahoj {username}! Tvá žádost byla schválena."><?= htmlspecialchars($automationSettings['bot_dm_approved'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code> — prázdné = nebude se posílat</small>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" for="bot_dm_allowlisted">DM po splnění pohovoru</label>
                        <textarea id="bot_dm_allowlisted" name="bot_dm_allowlisted" class="form-control" rows="3"
                            placeholder="Např: Gratulujeme {username}! Pohovor jsi splnil/a."><?= htmlspecialchars($automationSettings['bot_dm_allowlisted'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code> — prázdné = nebude se posílat</small>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" for="bot_dm_rejected">DM po zamítnutí formuláře</label>
                        <textarea id="bot_dm_rejected" name="bot_dm_rejected" class="form-control" rows="3"
                            placeholder="Např: Ahoj {username}, tvá žádost byla bohužel zamítnuta."><?= htmlspecialchars($automationSettings['bot_dm_rejected'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code> — prázdné = nebude se posílat</small>
                    </div>
                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label class="form-label" for="bot_dm_interview_failed">DM po nesplnění pohovoru</label>
                        <textarea id="bot_dm_interview_failed" name="bot_dm_interview_failed" class="form-control" rows="3"
                            placeholder="Např: Ahoj {username}, pohovor jsi nesplnil/a."><?= htmlspecialchars($automationSettings['bot_dm_interview_failed'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code> — prázdné = nebude se posílat</small>
                    </div>

                    <h3 class="discord-section-heading">📋 Log kanál — záznam akcí</h3>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" for="bot_log_channel">Discord kanál pro logy</label>
                        <select id="bot_log_channel" name="bot_log_channel" class="form-control">
                            <option value="">— žádný —</option>
                            <?php foreach ($discordChannels as $dc): ?>
                                <option value="<?= htmlspecialchars($dc['id']) ?>"
                                    <?= ($automationSettings['bot_log_channel'] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                    #<?= htmlspecialchars($dc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Kanál, kam bot zapíše kdo co schválil/zamítl. Prázdné = logy se neposílají.</small>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" for="bot_log_approved">Log — formulář schválen</label>
                        <textarea id="bot_log_approved" name="bot_log_approved" class="form-control" rows="2"
                            placeholder="Např: ✅ {tester} schválil formulář hráče {username} — {app_url}"><?= htmlspecialchars($automationSettings['bot_log_approved'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{tester}</code> <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code></small>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" for="bot_log_rejected">Log — formulář zamítnut</label>
                        <textarea id="bot_log_rejected" name="bot_log_rejected" class="form-control" rows="2"
                            placeholder="Např: ❌ {tester} zamítl formulář hráče {username} — {app_url}"><?= htmlspecialchars($automationSettings['bot_log_rejected'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{tester}</code> <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code></small>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" for="bot_log_interview_passed">Log — pohovor splněn</label>
                        <textarea id="bot_log_interview_passed" name="bot_log_interview_passed" class="form-control" rows="2"
                            placeholder="Např: ✅ {tester} — hráč {username} splnil pohovor — {app_url}"><?= htmlspecialchars($automationSettings['bot_log_interview_passed'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{tester}</code> <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code></small>
                    </div>
                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label class="form-label" for="bot_log_interview_failed">Log — pohovor nesplněn</label>
                        <textarea id="bot_log_interview_failed" name="bot_log_interview_failed" class="form-control" rows="2"
                            placeholder="Např: ❌ {tester} — hráč {username} nesplnil pohovor — {app_url}"><?= htmlspecialchars($automationSettings['bot_log_interview_failed'] ?? '') ?></textarea>
                        <small style="color:var(--grey-dim);font-size:0.78rem;">Proměnné: <code>{tester}</code> <code>{username}</code> <code>{discord_id}</code> <code>{app_url}</code></small>
                    </div>

                    <button type="submit" class="btn btn-primary">Uložit automatizaci</button>
                </form>
            </div>
        </div>

        <!-- ══ TAB: Obecné nastavení bota ════════════════════════════════════════ -->
        <div class="discord-tab-panel" id="tab-general" style="display:none;">
            <div class="card" style="border-left:3px solid #57F287;margin-bottom:1.5rem;">
                <h2 class="card-title">🤖 Obecné nastavení bota</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Základní konfigurace bota uložená přímo do databáze.
                    Bot tato nastavení načte automaticky bez restartu.
                </p>

                <form method="POST" action="/management/discord/bot/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="section" value="general">

                    <div class="fields-grid" style="margin-bottom:1.5rem;">
                        <div class="form-group">
                            <label class="form-label" for="clen_role_id">Auto-role při vstupu na server</label>
                            <select id="clen_role_id" name="clen_role_id" class="form-control">
                                <option value="">— žádná —</option>
                                <?php foreach ($discordRoles as $dr): ?>
                                    <option value="<?= htmlspecialchars($dr['id']) ?>"
                                        <?= ($botConfig['clen_role_id'] ?? '') === $dr['id'] ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($dr['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Role přidělená každému novému členovi serveru automaticky.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="mod_log_channel">Mod-log kanál</label>
                            <select id="mod_log_channel" name="mod_log_channel" class="form-control">
                                <option value="">— žádný —</option>
                                <?php foreach ($discordChannels as $dc): ?>
                                    <option value="<?= htmlspecialchars($dc['id']) ?>"
                                        <?= ($botConfig['mod_log_channel'] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                        #<?= htmlspecialchars($dc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Kanál pro log auto-rolí, vstupů členů a blokovaných odkazů.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="mute_role_id">🔇 Mute role</label>
                            <select id="mute_role_id" name="mute_role_id" class="form-control">
                                <option value="">— žádná —</option>
                                <?php foreach ($discordRoles as $dr): ?>
                                    <option value="<?= htmlspecialchars($dr['id']) ?>"
                                        <?= ($botConfig['mute_role_id'] ?? '') === $dr['id'] ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($dr['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Role přidělená zamuteným uživatelům. Musí mít zakázán Send Messages ve všech kanálech kromě ticketů.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="voice_log_channel">🎙️ Voice log kanál</label>
                            <select id="voice_log_channel" name="voice_log_channel" class="form-control">
                                <option value="">— žádný —</option>
                                <?php foreach ($discordChannels as $dc): ?>
                                    <option value="<?= htmlspecialchars($dc['id']) ?>"
                                        <?= ($botConfig['voice_log_channel'] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                        #<?= htmlspecialchars($dc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Kanál pro záznamy připojení, odpojení a přesunů ve voice kanálech.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="message_log_channel">✏️ Message log kanál</label>
                            <select id="message_log_channel" name="message_log_channel" class="form-control">
                                <option value="">— žádný —</option>
                                <?php foreach ($discordChannels as $dc): ?>
                                    <option value="<?= htmlspecialchars($dc['id']) ?>"
                                        <?= ($botConfig['message_log_channel'] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                        #<?= htmlspecialchars($dc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Kanál pro záznamy smazaných a upravených zpráv.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Uložit obecná nastavení</button>
                </form>
            </div>

            <!-- ── Zakázané odkazy ──────────────────────────────────────────── -->
            <div class="card" style="border-left:3px solid #ED4245;margin-bottom:1.5rem;">
                <h2 class="card-title">🚫 Zakázané odkazy</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Domény, jejichž zprávy bot automaticky smaže a upozorní moderátory.
                    Zadávej pouze kořenovou doménu (např. <code>pornhub.com</code>, nikoli <code>https://www.pornhub.com/…</code>).
                </p>

                <?php if (!empty($bannedLinks)): ?>
                <div style="margin-bottom:1.5rem;overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border-color);color:var(--grey-dim);text-align:left;">
                                <th style="padding:0.4rem 0.75rem;">Doména</th>
                                <th style="padding:0.4rem 0.75rem;">Přidal</th>
                                <th style="padding:0.4rem 0.75rem;">Kdy</th>
                                <th style="padding:0.4rem 0.75rem;text-align:right;">Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bannedLinks as $bl): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                <td style="padding:0.5rem 0.75rem;">
                                    <code style="background:rgba(237,66,69,0.15);color:#FF7B7E;padding:0.15rem 0.4rem;border-radius:4px;"><?= htmlspecialchars($bl['domain']) ?></code>
                                </td>
                                <td style="padding:0.5rem 0.75rem;color:var(--text-muted);"><?= htmlspecialchars($bl['added_by'] ?? 'SYSTEM') ?></td>
                                <td style="padding:0.5rem 0.75rem;color:var(--text-muted);font-size:0.82rem;"><?= htmlspecialchars($bl['added_at'] ?? '') ?></td>
                                <td style="padding:0.5rem 0.75rem;text-align:right;">
                                    <form method="POST" action="/management/discord/bannedlinks/delete" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="domain" value="<?= htmlspecialchars($bl['domain']) ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:0.2rem 0.6rem;font-size:0.8rem;"
                                            onclick="return confirm('Odebrat doménu <?= htmlspecialchars($bl['domain']) ?>?')">
                                            Odebrat
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);margin-bottom:1.5rem;">Žádné zakázané domény.</p>
                <?php endif; ?>

                <form method="POST" action="/management/discord/bannedlinks/add" style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="form-group" style="flex:1;min-width:200px;margin:0;">
                        <label class="form-label" for="new_banned_domain">Nová doména</label>
                        <input type="text" id="new_banned_domain" name="domain" class="form-control"
                            placeholder="example.com" pattern="[a-zA-Z0-9.-]+" maxlength="253" required>
                    </div>
                    <button type="submit" class="btn btn-danger" style="white-space:nowrap;">🚫 Přidat doménu</button>
                </form>
            </div>
        </div>

        <!-- ══ TAB: Ticket systém ═════════════════════════════════════════════════ -->
        <div class="discord-tab-panel" id="tab-tickets" style="display:none;">

            <!-- ── Panel refresh + embed editory ────────────────────────────── -->
            <?php
                $panelChannelId = $embedConfig['ticket_panel_channel_id'] ?? '';
                $panelMessageId = $embedConfig['ticket_panel_message_id'] ?? '';
                $panelSet = $panelChannelId && $panelMessageId;
            ?>
            <!-- Refresh tlačítko -->
            <div class="card" style="border-left:3px solid <?= $panelSet ? '#57F287' : '#ED4245' ?>;margin-bottom:1.5rem;">
                <h2 class="card-title">🔄 Aktualizovat panel v Discordu</h2>
                <p style="color:var(--text-muted);margin-bottom:1rem;">
                    Znovu odešle (PATCH) ticket panel embed se aktuálním obsahem a kategoremi do Discordu.<br>
                    Po změně embedu nebo kategorií je potřeba panel aktualizovat.
                </p>
                <?php if ($panelSet): ?>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1rem;">
                        📌 Kanál: <code><?= htmlspecialchars($panelChannelId) ?></code> &nbsp;|&nbsp;
                        Zpráva: <code><?= htmlspecialchars($panelMessageId) ?></code>
                    </p>
                    <form method="POST" action="/management/discord/panel/refresh">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button type="submit" class="btn btn-primary">🔄 Aktualizovat panel</button>
                    </form>
                <?php else: ?>
                    <div style="background:rgba(237,66,69,0.1);border:1px solid rgba(237,66,69,0.3);border-radius:8px;padding:0.85rem 1rem;color:#FF7B7E;">
                        ⚠️ Panel ještě nebyl nastaven. Použij příkaz <strong>/ticket setup</strong> v Discordu a toto tlačítko se aktivuje.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Panel embed editor -->
            <div class="card" style="border-left:3px solid #5865F2;margin-bottom:1.5rem;">
                <h2 class="card-title">🎨 Editor — Panel embed</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Obsah embedu zobrazeného v kanálu pro vytváření ticketů. Po uložení klikni na <em>Aktualizovat panel</em> výše.
                </p>
                <form method="POST" action="/management/discord/embed/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="embed_type" value="panel">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                        <!-- Levý sloupec: formulář -->
                        <div>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">Titulek</label>
                                <input type="text" name="panel_embed_title" class="form-control"
                                    value="<?= htmlspecialchars($embedConfig['panel_embed_title'] ?? '') ?>"
                                    placeholder="🎫  OTRP  •  Tickets">
                            </div>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">Popis</label>
                                <textarea name="panel_embed_description" class="form-control" rows="8"
                                    placeholder="> Potřebuješ pomoc nebo chceš nahlásit problém?&#10;> Vyber níže kategorii a otevři ticket."
                                    id="panelEmbedDesc"><?= htmlspecialchars($embedConfig['panel_embed_description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">Barva</label>
                                <div style="display:flex;align-items:center;gap:0.75rem;">
                                    <input type="color" id="panelColorPicker" value="#<?= htmlspecialchars($embedConfig['panel_embed_color'] ?? '2B2D31') ?>"
                                        style="width:42px;height:38px;border:none;background:none;cursor:pointer;padding:0;">
                                    <input type="text" name="panel_embed_color" id="panelColorHex" class="form-control"
                                        value="<?= htmlspecialchars($embedConfig['panel_embed_color'] ?? '2B2D31') ?>"
                                        placeholder="2B2D31" style="width:140px;font-family:monospace;"
                                        maxlength="6">
                                    <span style="color:var(--text-muted);font-size:0.85rem;">(hex bez #)</span>
                                </div>
                            </div>
                        </div>
                        <!-- Pravý sloupec: live preview -->
                        <div>
                            <label class="form-label">Náhled</label>
                            <div id="panelEmbedPreview" style="background:#2B2D31;border-radius:8px;padding:1rem;border-left:4px solid #2B2D31;min-height:200px;font-size:0.88rem;">
                                <div id="ppTitle" style="font-weight:700;font-size:1rem;margin-bottom:0.5rem;color:#fff;">
                                    <?= htmlspecialchars($embedConfig['panel_embed_title'] ?? '🎫  OTRP  •  Tickets') ?>
                                </div>
                                <div id="ppDesc" style="color:#ccc;white-space:pre-wrap;line-height:1.5;">
                                    <?= htmlspecialchars($embedConfig['panel_embed_description'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Uložit panel embed</button>
                </form>
            </div>

            <!-- Ticket welcome embed editor -->
            <div class="card" style="border-left:3px solid #FEE75C;margin-bottom:1.5rem;">
                <h2 class="card-title">✉️ Editor — Uvítací embed v ticketu</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Embed odeslaný do nově vytvořeného ticket kanálu. Barva vychází z kategorie ticketu.<br>
                    <small style="color:var(--grey-dim);">Proměnné: <code>{id}</code> = číslo ticketu (0001), <code>{user}</code> = zmínka uživatele, <code>{category}</code> = název kategorie</small>
                </p>
                <form method="POST" action="/management/discord/embed/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="embed_type" value="welcome">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                        <div>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">Titulek</label>
                                <input type="text" name="ticket_embed_title" class="form-control"
                                    value="<?= htmlspecialchars($embedConfig['ticket_embed_title'] ?? '') ?>"
                                    placeholder="TICKET #{id}">
                            </div>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">Popis</label>
                                <textarea name="ticket_embed_description" class="form-control" rows="7"
                                    placeholder="**Vítej v ticketu!**&#10;&#10;Napiš sem svůj problém co nejpodrobněji."
                                    id="ticketEmbedDesc"><?= htmlspecialchars($embedConfig['ticket_embed_description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">Footer <small style="color:var(--grey-dim);">(nepovinné)</small></label>
                                <input type="text" name="ticket_embed_footer" class="form-control"
                                    value="<?= htmlspecialchars($embedConfig['ticket_embed_footer'] ?? '') ?>"
                                    placeholder="Odpověď může trvat až 72 hodin">
                            </div>
                        </div>
                        <!-- preview -->
                        <div>
                            <label class="form-label">Náhled</label>
                            <div id="ticketEmbedPreview" style="background:#2B2D31;border-radius:8px;padding:1rem;border-left:4px solid #3498DB;min-height:180px;font-size:0.88rem;">
                                <div id="tpTitle" style="font-weight:700;font-size:1rem;margin-bottom:0.5rem;color:#fff;">
                                    🔵 <?= htmlspecialchars($embedConfig['ticket_embed_title'] ?? 'TICKET #0001') ?>
                                </div>
                                <div id="tpDesc" style="color:#ccc;white-space:pre-wrap;line-height:1.5;">
                                    <?= htmlspecialchars($embedConfig['ticket_embed_description'] ?? '') ?>
                                </div>
                                <?php if (!empty($embedConfig['ticket_embed_footer'])): ?>
                                <div id="tpFooter" style="margin-top:0.75rem;color:#888;font-size:0.8rem;border-top:1px solid rgba(255,255,255,0.1);padding-top:0.5rem;">
                                    <?= htmlspecialchars($embedConfig['ticket_embed_footer']) ?>
                                </div>
                                <?php else: ?>
                                <div id="tpFooter" style="margin-top:0.75rem;color:#888;font-size:0.8rem;border-top:1px solid rgba(255,255,255,0.1);padding-top:0.5rem;display:none;"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Uložit uvítací embed</button>
                </form>
            </div>

            <!-- ── Správa kategorií ──────────────────────────────────────────── -->
            <div class="card" style="border-left:3px solid #5865F2;margin-bottom:1.5rem;">
                <h2 class="card-title">📋 Kategorie ticketů</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Spravuj typy ticketů zobrazené v Discord panelu. Slug je interní identifikátor (pouze malá písmena, čísla a _).
                    Emoji a barva jsou zobrazeny v Discord embedech.
                </p>

                <?php if (!empty($ticketCategories)): ?>
                <div style="margin-bottom:1.5rem;overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border-color);color:var(--grey-dim);text-align:left;">
                                <th style="padding:0.4rem 0.75rem;">Emoji</th>
                                <th style="padding:0.4rem 0.75rem;">Slug</th>
                                <th style="padding:0.4rem 0.75rem;">Název</th>
                                <th style="padding:0.4rem 0.75rem;">Barva</th>
                                <th style="padding:0.4rem 0.75rem;text-align:right;">Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ticketCategories as $cat): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                <td style="padding:0.5rem 0.75rem;font-size:1.2rem;"><?= htmlspecialchars($cat['emoji'] ?? '📂') ?></td>
                                <td style="padding:0.5rem 0.75rem;"><code style="background:rgba(255,255,255,0.07);padding:0.15rem 0.4rem;border-radius:4px;"><?= htmlspecialchars($cat['slug']) ?></code></td>
                                <td style="padding:0.5rem 0.75rem;"><?= htmlspecialchars($cat['label']) ?></td>
                                <td style="padding:0.5rem 0.75rem;">
                                    <span style="display:inline-flex;align-items:center;gap:0.4rem;">
                                        <span style="width:18px;height:18px;border-radius:4px;background:#<?= htmlspecialchars($cat['color'] ?? 'AAAAAA') ?>;display:inline-block;"></span>
                                        <code style="font-size:0.8rem;">#<?= htmlspecialchars($cat['color'] ?? 'AAAAAA') ?></code>
                                    </span>
                                </td>
                                <td style="padding:0.5rem 0.75rem;text-align:right;">
                                    <form method="POST" action="/management/discord/categories/update" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slug" value="<?= htmlspecialchars($cat['slug']) ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:0.25rem 0.65rem;font-size:0.8rem;"
                                            onclick="return confirm('Opravdu smazat kategorii „<?= htmlspecialchars($cat['slug']) ?>"?')">
                                            Smazat
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="color:var(--grey-dim);margin-bottom:1.5rem;">Žádné kategorie. Přidej první níže.</p>
                <?php endif; ?>

                <h3 class="discord-section-heading">➕ Přidat kategorii</h3>
                <form method="POST" action="/management/discord/categories/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="fields-grid" style="margin-bottom:1rem;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
                        <div class="form-group">
                            <label class="form-label" for="new_slug">Slug <span style="color:var(--red);">*</span></label>
                            <input type="text" id="new_slug" name="slug" class="form-control"
                                placeholder="např. admin" pattern="[a-z0-9_]+" required
                                title="Pouze malá písmena, čísla a podtržítko">
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Malá písmena, čísla, _</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="new_label">Název <span style="color:var(--red);">*</span></label>
                            <input type="text" id="new_label" name="label" class="form-control"
                                placeholder="např. Admin Ticket" required maxlength="80">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="new_emoji">Emoji</label>
                            <input type="text" id="new_emoji" name="emoji" class="form-control"
                                placeholder="🔵" maxlength="10" value="📂">
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Jedno emoji</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="new_color">Barva embeddů</label>
                            <div style="display:flex;gap:0.5rem;align-items:center;">
                                <input type="color" id="new_color_picker" value="#3498DB"
                                    oninput="document.getElementById('new_color').value = this.value.replace('#','')"
                                    style="width:42px;height:36px;padding:2px;border:1px solid var(--border-color);border-radius:6px;background:transparent;cursor:pointer;">
                                <input type="text" id="new_color" name="color" class="form-control"
                                    placeholder="3498DB" maxlength="6" value="3498DB"
                                    pattern="[0-9A-Fa-f]{6}"
                                    oninput="try{document.getElementById('new_color_picker').value='#'+this.value}catch(e){}"
                                    style="font-family:monospace;">
                            </div>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Hex bez #</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Přidat kategorii</button>
                </form>
            </div>

            <!-- ── Globální ticket nastavení ─────────────────────────────── -->
            <div class="card" style="border-left:3px solid #FEE75C;margin-bottom:1.5rem;">
                <h2 class="card-title">🎫 Globální nastavení ticket systému</h2>
                <form method="POST" action="/management/discord/bot/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="section" value="tickets">

                    <div class="fields-grid" style="margin-bottom:1.5rem;">
                        <div class="form-group">
                            <label class="form-label" for="ticket_log_channel">📌 Ticket log kanál</label>
                            <select id="ticket_log_channel" name="ticket_log_channel" class="form-control">
                                <option value="">— žádný —</option>
                                <?php foreach ($discordChannels as $dc): ?>
                                    <option value="<?= htmlspecialchars($dc['id']) ?>"
                                        <?= ($botConfig['ticket_log_channel'] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                        #<?= htmlspecialchars($dc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Kanál pro záznamy otevřených/zavřených ticketů.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="transcript_channel">📜 Transcript kanál</label>
                            <select id="transcript_channel" name="transcript_channel" class="form-control">
                                <option value="">— žádný —</option>
                                <?php foreach ($discordChannels as $dc): ?>
                                    <option value="<?= htmlspecialchars($dc['id']) ?>"
                                        <?= ($botConfig['transcript_channel'] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                        #<?= htmlspecialchars($dc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Kanál kam bot uloží HTML přepis zavřeného ticketu.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="ticket_category_closed">🔒 Výchozí složka uzavřených ticketů</label>
                            <select id="ticket_category_closed" name="ticket_category_closed" class="form-control">
                                <option value="">— žádná (smaže kanál) —</option>
                                <?php foreach ($discordCategories as $dc): ?>
                                    <option value="<?= htmlspecialchars($dc['id']) ?>"
                                        <?= ($botConfig['ticket_category_closed'] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                        📁 <?= htmlspecialchars($dc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--grey-dim);font-size:0.78rem;">Fallback složka pokud kategorie nemá vlastní nastavení uzavřených.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Uložit globální nastavení</button>
                </form>
            </div>

            <!-- ── Nastavení per-kategorie ────────────────────────────────── -->
            <?php if (!empty($ticketCategories)): ?>
            <div class="card" style="border-left:3px solid #5865F2;margin-bottom:1.5rem;">
                <h2 class="card-title">⚙️ Nastavení kategorií</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Každá kategorie má vlastní Discord složku, staff role, oprávnění pro vytváření a akci při uzavření.
                </p>

                <?php foreach ($ticketCategories as $cat):
                    $slug       = $cat['slug'];
                    $catKey     = "ticket_category_{$slug}";
                    $closedKey  = "ticket_closed_category_{$slug}";
                    $staffKey   = "staff_roles_{$slug}";
                    $creatorKey = "creator_roles_{$slug}";
                    $actionKey  = "ticket_close_action_{$slug}";
                    $embedTitleKey = "ticket_embed_title_{$slug}";
                    $embedDescKey  = "ticket_embed_description_{$slug}";
                    $embedFootKey  = "ticket_embed_footer_{$slug}";
                    $closeAction = $botConfig[$actionKey] ?? 'move';
                    $isConfigured = !empty($botConfig[$catKey]);
                ?>
                <details class="ticket-cat-accordion" style="border:1px solid var(--border-color);border-radius:8px;margin-bottom:0.75rem;overflow:hidden;">
                    <summary style="cursor:pointer;display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1rem;background:rgba(255,255,255,0.03);list-style:none;user-select:none;">
                        <span style="font-size:1.25rem;line-height:1;"><?= htmlspecialchars($cat['emoji'] ?? '📂') ?></span>
                        <strong style="flex:1;"><?= htmlspecialchars($cat['label']) ?></strong>
                        <code style="background:rgba(255,255,255,0.07);padding:0.15rem 0.45rem;border-radius:4px;font-size:0.8rem;"><?= htmlspecialchars($slug) ?></code>
                        <?php if ($isConfigured): ?>
                            <span style="background:#57F287;color:#000;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.75rem;font-weight:600;">✓ Nastaveno</span>
                        <?php else: ?>
                            <span style="background:#ED4245;color:#fff;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.75rem;font-weight:600;">! Nenastaveno</span>
                        <?php endif; ?>
                        <span style="color:var(--grey-dim);font-size:0.85rem;">▼</span>
                    </summary>

                    <div style="padding:1.25rem 1rem;border-top:1px solid var(--border-color);">
                        <form method="POST" action="/management/discord/category/save">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">

                            <div class="fields-grid" style="margin-bottom:1.25rem;">
                                <!-- Discord složka pro otevřené tickety -->
                                <div class="form-group">
                                    <label class="form-label" for="<?= $catKey ?>">📁 Discord složka (otevřené)</label>
                                    <select id="<?= $catKey ?>" name="<?= $catKey ?>" class="form-control">
                                        <option value="">— žádná —</option>
                                        <?php foreach ($discordCategories as $dc): ?>
                                            <option value="<?= htmlspecialchars($dc['id']) ?>"
                                                <?= ($botConfig[$catKey] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                                📁 <?= htmlspecialchars($dc['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small style="color:var(--grey-dim);font-size:0.78rem;">Nové tickety se vytvoří v této Discord kategorii.</small>
                                </div>

                                <!-- Staff role -->
                                <div class="form-group">
                                    <label class="form-label" for="<?= $staffKey ?>">👮 Staff role</label>
                                    <select id="<?= $staffKey ?>_select" class="form-control" multiple
                                        style="min-height:100px;"
                                        onchange="syncRoleInput(this, '<?= $staffKey ?>')">
                                        <?php
                                        $currentStaff = array_filter(
                                            explode(',', $botConfig[$staffKey] ?? ''),
                                            fn($v) => trim($v) !== ''
                                        );
                                        $currentStaff = array_map('trim', $currentStaff);
                                        ?>
                                        <?php foreach ($discordRoles as $dr): ?>
                                            <option value="<?= htmlspecialchars($dr['id']) ?>"
                                                <?= in_array($dr['id'], $currentStaff, true) ? ' selected' : '' ?>>
                                                <?= htmlspecialchars($dr['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" id="<?= $staffKey ?>" name="<?= $staffKey ?>"
                                        value="<?= htmlspecialchars($botConfig[$staffKey] ?? '') ?>">
                                    <small style="color:var(--grey-dim);font-size:0.78rem;">Ctrl/Cmd+klik pro výběr více rolí. Staff vidí a spravuje tickety v této kategorii.</small>
                                </div>

                                <!-- Kdo může vytvářet -->
                                <div class="form-group">
                                    <label class="form-label" for="<?= $creatorKey ?>">🔑 Kdo může vytvářet</label>
                                    <select id="<?= $creatorKey ?>_select" class="form-control" multiple
                                        style="min-height:100px;"
                                        onchange="syncRoleInput(this, '<?= $creatorKey ?>')">
                                        <?php
                                        $currentCreator = array_filter(
                                            explode(',', $botConfig[$creatorKey] ?? ''),
                                            fn($v) => trim($v) !== ''
                                        );
                                        $currentCreator = array_map('trim', $currentCreator);
                                        ?>
                                        <?php foreach ($discordRoles as $dr): ?>
                                            <option value="<?= htmlspecialchars($dr['id']) ?>"
                                                <?= in_array($dr['id'], $currentCreator, true) ? ' selected' : '' ?>>
                                                <?= htmlspecialchars($dr['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" id="<?= $creatorKey ?>" name="<?= $creatorKey ?>"
                                        value="<?= htmlspecialchars($botConfig[$creatorKey] ?? '') ?>">
                                    <small style="color:var(--grey-dim);font-size:0.78rem;">Prázdný výběr = kdokoliv může vytvořit ticket. Vybráním rolí omezíš přístup.</small>
                                </div>
                            </div>

                            <!-- Akce při zavření -->
                            <div style="margin-bottom:1.25rem;">
                                <label class="form-label">🔒 Akce při zavření ticketu</label>
                                <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:0.5rem;">
                                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;padding:0.6rem 1rem;border:1px solid var(--border-color);border-radius:8px;<?= $closeAction !== 'delete' ? 'border-color:#5865F2;background:rgba(88,101,242,0.1);' : '' ?>">
                                        <input type="radio" name="<?= $actionKey ?>" value="move"
                                            <?= $closeAction !== 'delete' ? 'checked' : '' ?>
                                            onchange="updateClosedCatVisibility('<?= $slug ?>', this.value)">
                                        <span>📦 Přesunout do složky uzavřených</span>
                                    </label>
                                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;padding:0.6rem 1rem;border:1px solid var(--border-color);border-radius:8px;<?= $closeAction === 'delete' ? 'border-color:#ED4245;background:rgba(237,66,69,0.1);' : '' ?>">
                                        <input type="radio" name="<?= $actionKey ?>" value="delete"
                                            <?= $closeAction === 'delete' ? 'checked' : '' ?>
                                            onchange="updateClosedCatVisibility('<?= $slug ?>', this.value)">
                                        <span>🗑️ Smazat kanál</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Složka uzavřených (zobrazí se jen pokud akce = move) -->
                            <div id="closed-cat-<?= $slug ?>" style="margin-bottom:1.25rem;<?= $closeAction === 'delete' ? 'display:none;' : '' ?>">
                                <div class="form-group">
                                    <label class="form-label" for="<?= $closedKey ?>">📦 Discord složka (uzavřené)</label>
                                    <select id="<?= $closedKey ?>" name="<?= $closedKey ?>" class="form-control">
                                        <option value="">— použít výchozí (globální nastavení) —</option>
                                        <?php foreach ($discordCategories as $dc): ?>
                                            <option value="<?= htmlspecialchars($dc['id']) ?>"
                                                <?= ($botConfig[$closedKey] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                                📁 <?= htmlspecialchars($dc['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small style="color:var(--grey-dim);font-size:0.78rem;">Uzavřené tickety se přesunou sem. Prázdné = použije se výchozí složka uzavřených nahoře.</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-top:0.25rem;">
                                Uložit nastavení <?= htmlspecialchars($cat['label']) ?>
                            </button>
                        </form>

                        <!-- Per-category welcome embed editor -->
                        <details style="margin-top:1.25rem;border:1px solid rgba(254,231,92,0.25);border-radius:8px;overflow:hidden;">
                            <summary style="cursor:pointer;padding:0.75rem 1rem;background:rgba(254,231,92,0.06);list-style:none;user-select:none;display:flex;align-items:center;gap:0.6rem;">
                                <span>✉️</span>
                                <strong style="flex:1;font-size:0.95rem;">Uvítací embed — <?= htmlspecialchars($cat['label']) ?></strong>
                                <?php if (!empty($botConfig[$embedTitleKey]) || !empty($botConfig[$embedDescKey])): ?>
                                    <span style="background:#FEE75C;color:#000;padding:0.1rem 0.45rem;border-radius:4px;font-size:0.72rem;font-weight:700;">vlastní</span>
                                <?php else: ?>
                                    <span style="color:var(--grey-dim);font-size:0.78rem;font-style:italic;">používá globální</span>
                                <?php endif; ?>
                                <span style="color:var(--grey-dim);font-size:0.8rem;">▼</span>
                            </summary>
                            <div style="padding:1rem;border-top:1px solid rgba(254,231,92,0.15);">
                                <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:1rem;">
                                    Vlastní obsah pro tickety kategorie <strong><?= htmlspecialchars($cat['label']) ?></strong>. Prázdné pole = použije se globální hodnota.<br>
                                    <span style="color:var(--grey-dim);font-size:0.8rem;">Proměnné: <code>{id}</code> = číslo ticketu, <code>{user}</code> = zmínka uživatele, <code>{category}</code> = <?= htmlspecialchars($cat['label']) ?></span>
                                </p>
                                <form method="POST" action="/management/discord/category/save">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                                    <!-- Preserve existing non-embed fields so they aren't cleared -->
                                    <input type="hidden" name="<?= $catKey ?>"     value="<?= htmlspecialchars($botConfig[$catKey] ?? '') ?>">
                                    <input type="hidden" name="<?= $closedKey ?>"  value="<?= htmlspecialchars($botConfig[$closedKey] ?? '') ?>">
                                    <input type="hidden" name="<?= $staffKey ?>"   value="<?= htmlspecialchars($botConfig[$staffKey] ?? '') ?>">
                                    <input type="hidden" name="<?= $creatorKey ?>" value="<?= htmlspecialchars($botConfig[$creatorKey] ?? '') ?>">
                                    <input type="hidden" name="<?= $actionKey ?>"  value="<?= htmlspecialchars($botConfig[$actionKey] ?? 'move') ?>">

                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1rem;">
                                        <div>
                                            <div class="form-group" style="margin-bottom:0.85rem;">
                                                <label class="form-label" style="font-size:0.85rem;">Titulek <small style="color:var(--grey-dim);">(nepovinné)</small></label>
                                                <input type="text"
                                                    name="<?= $embedTitleKey ?>"
                                                    id="cat_embed_title_<?= $slug ?>"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($botConfig[$embedTitleKey] ?? '') ?>"
                                                    placeholder="TICKET #{id}">
                                            </div>
                                            <div class="form-group" style="margin-bottom:0.85rem;">
                                                <label class="form-label" style="font-size:0.85rem;">Popis <small style="color:var(--grey-dim);">(nepovinné)</small></label>
                                                <textarea
                                                    name="<?= $embedDescKey ?>"
                                                    id="cat_embed_desc_<?= $slug ?>"
                                                    class="form-control" rows="6"
                                                    placeholder="**Vítej v ticketu!**&#10;&#10;Napiš sem svůj problém..."><?= htmlspecialchars($botConfig[$embedDescKey] ?? '') ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" style="font-size:0.85rem;">Footer <small style="color:var(--grey-dim);">(nepovinné)</small></label>
                                                <input type="text"
                                                    name="<?= $embedFootKey ?>"
                                                    id="cat_embed_footer_<?= $slug ?>"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($botConfig[$embedFootKey] ?? '') ?>"
                                                    placeholder="Odpověď může trvat až 72 hodin">
                                            </div>
                                        </div>
                                        <!-- Live preview -->
                                        <div>
                                            <label class="form-label" style="font-size:0.85rem;">Náhled</label>
                                            <div id="cat_preview_<?= $slug ?>"
                                                style="background:#2B2D31;border-radius:8px;padding:0.85rem;border-left:4px solid #<?= htmlspecialchars($cat['color'] ?? 'AAAAAA') ?>;min-height:150px;font-size:0.84rem;">
                                                <div id="cat_prev_title_<?= $slug ?>" style="font-weight:700;margin-bottom:0.4rem;color:#fff;">
                                                    <?= htmlspecialchars($cat['emoji'] ?? '') ?>  <?= htmlspecialchars($botConfig[$embedTitleKey] ?? 'TICKET #0001') ?>
                                                </div>
                                                <div id="cat_prev_desc_<?= $slug ?>" style="color:#ccc;white-space:pre-wrap;line-height:1.5;">
                                                    <?= htmlspecialchars($botConfig[$embedDescKey] ?? '') ?>
                                                </div>
                                                <div id="cat_prev_footer_<?= $slug ?>"
                                                    style="margin-top:0.6rem;color:#888;font-size:0.78rem;border-top:1px solid rgba(255,255,255,0.1);padding-top:0.4rem;<?= empty($botConfig[$embedFootKey]) ? 'display:none;' : '' ?>">
                                                    <?= htmlspecialchars($botConfig[$embedFootKey] ?? '') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary" style="font-size:0.88rem;padding:0.4rem 1rem;">
                                        💾 Uložit embed pro <?= htmlspecialchars($cat['label']) ?>
                                    </button>
                                </form>
                            </div>
                        </details>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══ TAB: Embedy ═══════════════════════════════════════════════════════ -->
        <div class="discord-tab-panel" id="tab-embeds" style="display:none;">

            <form method="post" action="/management/discord/embeds/save">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Log embedy -->
                <div class="card" style="border-left:3px solid #3498DB;margin-bottom:1.5rem;">
                    <h2 class="card-title">📋 Log embedy</h2>
                    <p style="color:var(--text-muted);margin-bottom:1rem;">Embedy odesílané do log kanálu. Pokud pole necháš prázdné, použije se výchozí hodnota.</p>

                    <?php
                    $logActions = [
                        'ticket_open'      => ['label' => 'Nový ticket otevřen',        'defTitle' => '🎟️  NOVÝ TICKET OTEVŘEN',         'defColor' => '2ECC71'],
                        'ticket_claim'     => ['label' => 'Ticket převzat',              'defTitle' => '🤠  TICKET PŘEVZAT',               'defColor' => 'E67E22'],
                        'ticket_close'     => ['label' => 'Ticket uzavřen',              'defTitle' => '⚰️  TICKET UZAVŘEN',               'defColor' => 'E74C3C'],
                        'blacklist_add'    => ['label' => 'Doména přidána na blacklist',  'defTitle' => '🚫  DOMÉNA PŘIDÁNA NA BLACKLIST',  'defColor' => 'E74C3C'],
                        'blacklist_remove' => ['label' => 'Doména odebrána z blacklistu','defTitle' => '✅  DOMÉNA ODEBRÁNA Z BLACKLISTU','defColor' => '2ECC71'],
                        'link_blocked'     => ['label' => 'Zakázaný odkaz smazán',       'defTitle' => '🔫  ZAKÁZANÝ ODKAZ SMAZÁN',       'defColor' => 'E74C3C'],
                        'autoRole'         => ['label' => 'Nový člen',                   'defTitle' => '🤠  NOVÝ ČLEN',                    'defColor' => '2ECC71'],
                        'mute_add'         => ['label' => 'Hráč umlčen',                 'defTitle' => '🔇  HRÁČ UMLČEN',                 'defColor' => 'E67E22'],
                        'mute_remove'      => ['label' => 'Mute odstraněn',              'defTitle' => '🔊  MUTE ODSTRANĚN',              'defColor' => '2ECC71'],
                    ];
                    ?>

                    <?php foreach ($logActions as $action => $meta): ?>
                    <?php
                        $titleKey = "embed_log_{$action}_title";
                        $colorKey = "embed_log_{$action}_color";
                        $titleVal = $embedConfig[$titleKey] ?? '';
                        $colorVal = $embedConfig[$colorKey] ?? '';
                    ?>
                    <div class="embed-row" style="display:grid;grid-template-columns:1fr 200px 120px;gap:0.75rem;align-items:end;margin-bottom:0.75rem;">
                        <div>
                            <label class="form-label" style="font-size:.85rem;"><?= htmlspecialchars($meta['label']) ?> — Nadpis</label>
                            <input class="form-control" type="text" name="<?= $titleKey ?>"
                                   value="<?= htmlspecialchars($titleVal) ?>"
                                   placeholder="<?= htmlspecialchars($meta['defTitle']) ?>">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:.85rem;">Barva</label>
                            <input class="form-control" type="text" name="<?= $colorKey ?>"
                                   value="<?= htmlspecialchars($colorVal) ?>"
                                   placeholder="<?= htmlspecialchars($meta['defColor']) ?>"
                                   maxlength="6" style="font-family:monospace;">
                        </div>
                        <div style="padding-bottom:4px;">
                            <span class="embed-color-preview" style="display:inline-block;width:24px;height:24px;border-radius:4px;border:1px solid rgba(255,255,255,.15);background:#<?= htmlspecialchars($colorVal ?: $meta['defColor']) ?>;vertical-align:middle;"></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Mute embedy -->
                <div class="card" style="border-left:3px solid #E67E22;margin-bottom:1.5rem;">
                    <h2 class="card-title">🔇 Mute embedy</h2>
                    <p style="color:var(--text-muted);margin-bottom:1rem;">Embedy zobrazované při udělení mute. Prázdné pole = výchozí hodnota.</p>

                    <?php
                    $muteTypes = [
                        'response' => ['label' => 'Potvrzení (odpověď staffu)',     'defTitle' => '🔇 Mute udělen',               'defColor' => 'E67E22', 'defFooter' => '🤠 Old Times RP  •  Moderace'],
                        'modlog'   => ['label' => 'Mod-log záznam',                'defTitle' => '🔇 Hráč umlčen',               'defColor' => 'E67E22', 'defFooter' => '🤠 Old Times RP  •  Moderace'],
                        'dm'       => ['label' => 'DM uživateli',                  'defTitle' => '🔇 Byl/a jsi umlčen/a',        'defColor' => 'E67E22', 'defFooter' => '🤠 Old Times RP  •  Moderace', 'hasDesc' => true, 'defDesc' => 'Byl/a jsi umlčen/a na serveru Old Times RP.'],
                        'unmute'   => ['label' => 'Automatické odmuteování (log)',  'defTitle' => '🔊 Mute automaticky odstraněn', 'defColor' => '2ECC71', 'defFooter' => '🤠 Old Times RP  •  Moderace'],
                    ];
                    ?>

                    <?php foreach ($muteTypes as $type => $meta): ?>
                    <?php
                        $prefix  = "embed_mute_{$type}";
                        $tVal    = $embedConfig["{$prefix}_title"] ?? '';
                        $cVal    = $embedConfig["{$prefix}_color"] ?? '';
                        $fVal    = $embedConfig["{$prefix}_footer"] ?? '';
                        $dVal    = $embedConfig["{$prefix}_description"] ?? '';
                    ?>
                    <div style="background:rgba(0,0,0,.15);border-radius:8px;padding:1rem;margin-bottom:1rem;">
                        <h3 style="margin:0 0 .75rem;font-size:.95rem;color:var(--text-muted);"><?= htmlspecialchars($meta['label']) ?></h3>
                        <div style="display:grid;grid-template-columns:1fr 160px;gap:0.75rem;margin-bottom:.5rem;">
                            <div>
                                <label class="form-label" style="font-size:.85rem;">Nadpis</label>
                                <input class="form-control" type="text" name="<?= $prefix ?>_title"
                                       value="<?= htmlspecialchars($tVal) ?>"
                                       placeholder="<?= htmlspecialchars($meta['defTitle']) ?>">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:.85rem;">Barva</label>
                                <input class="form-control" type="text" name="<?= $prefix ?>_color"
                                       value="<?= htmlspecialchars($cVal) ?>"
                                       placeholder="<?= htmlspecialchars($meta['defColor']) ?>"
                                       maxlength="6" style="font-family:monospace;">
                            </div>
                        </div>
                        <?php if (!empty($meta['hasDesc'])): ?>
                        <div style="margin-bottom:.5rem;">
                            <label class="form-label" style="font-size:.85rem;">Popis</label>
                            <input class="form-control" type="text" name="<?= $prefix ?>_description"
                                   value="<?= htmlspecialchars($dVal) ?>"
                                   placeholder="<?= htmlspecialchars($meta['defDesc'] ?? '') ?>">
                        </div>
                        <?php endif; ?>
                        <div>
                            <label class="form-label" style="font-size:.85rem;">Patička (footer)</label>
                            <input class="form-control" type="text" name="<?= $prefix ?>_footer"
                                   value="<?= htmlspecialchars($fVal) ?>"
                                   placeholder="<?= htmlspecialchars($meta['defFooter']) ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Systémové embedy -->
                <div class="card" style="border-left:3px solid #2ECC71;margin-bottom:1.5rem;">
                    <h2 class="card-title">⚙️ Systémové embedy</h2>
                    <p style="color:var(--text-muted);margin-bottom:1rem;">Chybové a úspěchové zprávy bota.</p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                        <!-- Error -->
                        <div style="background:rgba(0,0,0,.15);border-radius:8px;padding:1rem;">
                            <h3 style="margin:0 0 .75rem;font-size:.95rem;color:#E74C3C;">❌ Chybový embed</h3>
                            <div style="margin-bottom:.5rem;">
                                <label class="form-label" style="font-size:.85rem;">Nadpis</label>
                                <input class="form-control" type="text" name="embed_error_title"
                                       value="<?= htmlspecialchars($embedConfig['embed_error_title'] ?? '') ?>"
                                       placeholder="❌  Chyba">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:.85rem;">Barva</label>
                                <input class="form-control" type="text" name="embed_error_color"
                                       value="<?= htmlspecialchars($embedConfig['embed_error_color'] ?? '') ?>"
                                       placeholder="E74C3C" maxlength="6" style="font-family:monospace;">
                            </div>
                        </div>

                        <!-- Success -->
                        <div style="background:rgba(0,0,0,.15);border-radius:8px;padding:1rem;">
                            <h3 style="margin:0 0 .75rem;font-size:.95rem;color:#2ECC71;">✅ Úspěchový embed</h3>
                            <div style="margin-bottom:.5rem;">
                                <label class="form-label" style="font-size:.85rem;">Nadpis</label>
                                <input class="form-control" type="text" name="embed_success_title"
                                       value="<?= htmlspecialchars($embedConfig['embed_success_title'] ?? '') ?>"
                                       placeholder="✅  Hotovo">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:.85rem;">Barva</label>
                                <input class="form-control" type="text" name="embed_success_color"
                                       value="<?= htmlspecialchars($embedConfig['embed_success_color'] ?? '') ?>"
                                       placeholder="2ECC71" maxlength="6" style="font-family:monospace;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ostatní embedy -->
                <div class="card" style="border-left:3px solid #C9A227;margin-bottom:1.5rem;">
                    <h2 class="card-title">📦 Ostatní embedy</h2>
                    <p style="color:var(--text-muted);margin-bottom:1rem;">Statistiky staffu a blacklist seznam.</p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                        <!-- Stats -->
                        <div style="background:rgba(0,0,0,.15);border-radius:8px;padding:1rem;">
                            <h3 style="margin:0 0 .75rem;font-size:.95rem;color:#C9A227;">📊 Staff statistiky</h3>
                            <div style="margin-bottom:.5rem;">
                                <label class="form-label" style="font-size:.85rem;">Nadpis</label>
                                <input class="form-control" type="text" name="embed_stats_title"
                                       value="<?= htmlspecialchars($embedConfig['embed_stats_title'] ?? '') ?>"
                                       placeholder="📊  STATISTIKY STAFFU">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:.85rem;">Barva</label>
                                <input class="form-control" type="text" name="embed_stats_color"
                                       value="<?= htmlspecialchars($embedConfig['embed_stats_color'] ?? '') ?>"
                                       placeholder="C9A227" maxlength="6" style="font-family:monospace;">
                            </div>
                        </div>

                        <!-- Blacklist list -->
                        <div style="background:rgba(0,0,0,.15);border-radius:8px;padding:1rem;">
                            <h3 style="margin:0 0 .75rem;font-size:.95rem;color:#E74C3C;">🚫 Blacklist seznam</h3>
                            <div style="margin-bottom:.5rem;">
                                <label class="form-label" style="font-size:.85rem;">Nadpis</label>
                                <input class="form-control" type="text" name="embed_blacklist_title"
                                       value="<?= htmlspecialchars($embedConfig['embed_blacklist_title'] ?? '') ?>"
                                       placeholder="🚫  Blacklist Domén">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:.85rem;">Barva</label>
                                <input class="form-control" type="text" name="embed_blacklist_color"
                                       value="<?= htmlspecialchars($embedConfig['embed_blacklist_color'] ?? '') ?>"
                                       placeholder="E74C3C" maxlength="6" style="font-family:monospace;">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="text-align:right;">
                    <button type="submit" class="btn btn-primary" style="padding:.6rem 2rem;">💾 Uložit všechny embedy</button>
                </div>
            </form>
        </div>

        <!-- ══ TAB: Server statistiky ═════════════════════════════════════════════ -->
        <div class="discord-tab-panel" id="tab-stats" style="display:none;">
            <div class="card" style="border-left:3px solid #EB459E;margin-bottom:1.5rem;">
                <h2 class="card-title">📊 Server statistiky</h2>
                <p style="color:var(--text-muted);margin-bottom:1.5rem;">
                    Hlasové kanály, jejichž název bot každých 10 minut aktualizuje na aktuální statistiky serveru.
                    V poli Formát použij <code>%</code> jako placeholder pro číslo.
                </p>

                <form method="POST" action="/management/discord/bot/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="section" value="stats">

                    <h3 class="discord-section-heading">📊 Kanály statistik</h3>
                    <div class="discord-stats-grid" style="margin-bottom:1.5rem;">
                        <?php
                        $statsItems = [
                            ['total',     '👥 Celkový počet členů',           '👥 Members: %'],
                            ['al',        '✅ Allowlistovaní',                  '✅ Allowlisted: %'],
                            ['interview', '🎤 Čeká na pohovor',               '🎤 Pohovor: %'],
                            ['redm',      '🎮 Online na herním serveru (RedM)', '🎮 Online: %/64'],
                        ];
                        foreach ($statsItems as [$slug, $label, $defaultFmt]): ?>
                        <div class="discord-stats-row">
                            <div class="discord-stats-label"><?= $label ?></div>
                            <div class="form-group">
                                <label class="form-label" for="stats_channel_<?= $slug ?>">Hlasový kanál</label>
                                <select id="stats_channel_<?= $slug ?>" name="stats_channel_<?= $slug ?>" class="form-control">
                                    <option value="">— žádný —</option>
                                    <?php foreach ($discordVoiceChannels as $dc): ?>
                                        <option value="<?= htmlspecialchars($dc['id']) ?>"
                                            <?= ($botConfig["stats_channel_{$slug}"] ?? '') === $dc['id'] ? ' selected' : '' ?>>
                                            🔊 <?= htmlspecialchars($dc['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="stats_format_<?= $slug ?>">Formát názvu</label>
                                <input
                                    type="text"
                                    id="stats_format_<?= $slug ?>"
                                    name="stats_format_<?= $slug ?>"
                                    class="form-control"
                                    value="<?= htmlspecialchars($botConfig["stats_format_{$slug}"] ?? '') ?>"
                                    placeholder="<?= htmlspecialchars($defaultFmt) ?>"
                                >
                                <small style="color:var(--grey-dim);font-size:0.78rem;"><code>%</code> = číslo. Prázdné = výchozí <em><?= htmlspecialchars($defaultFmt) ?></em></small>
                            </div>
                            <?php if ($slug === 'al' || $slug === 'interview'): ?>
                            <div class="form-group">
                                <label class="form-label" for="stats_role_<?= $slug ?>">Role k počítání</label>
                                <select id="stats_role_<?= $slug ?>" name="stats_role_<?= $slug ?>" class="form-control">
                                    <option value="">— žádná —</option>
                                    <?php foreach ($discordRoles as $dr): ?>
                                        <option value="<?= htmlspecialchars($dr['id']) ?>"
                                            <?= ($botConfig["stats_role_{$slug}"] ?? '') === $dr['id'] ? ' selected' : '' ?>>
                                            <?= htmlspecialchars($dr['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color:var(--grey-dim);font-size:0.78rem;">Počet členů s touto rolí se zobrazí ve statistice.</small>
                            </div>
                            <?php endif; ?>
                            <?php if ($slug === 'redm'): ?>
                            <div class="form-group">
                                <label class="form-label" for="stats_redm_url">RedM server URL</label>
                                <input
                                    type="url"
                                    id="stats_redm_url"
                                    name="stats_redm_url"
                                    class="form-control"
                                    value="<?= htmlspecialchars($botConfig['stats_redm_url'] ?? '') ?>"
                                    placeholder="https://cfx.re/join/xxxxxx"
                                >
                                <small style="color:var(--grey-dim);font-size:0.78rem;">URL herního serveru. Bot fetchne <code>/players.json</code> pro počet hráčů.</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Uložit statistiky</button>
                </form>
            </div>
        </div>

    </div>
</section>

<style>
.discord-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.discord-tab {
    padding: 0.55rem 1.15rem;
    border-radius: 6px;
    border: 1px solid var(--line-bright, #333);
    background: var(--card-bg, #1e1e2e);
    color: var(--text-muted, #888);
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.discord-tab:hover {
    background: var(--card-hover, #2a2a3a);
    color: var(--text-primary, #fff);
}
.discord-tab.discord-tab-active {
    background: #5865F2;
    border-color: #5865F2;
    color: #fff;
}
.discord-section-heading {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 1.5rem 0 0.75rem;
    padding-bottom: 0.35rem;
    border-bottom: 1px solid var(--line-bright, #333);
    color: var(--text-primary, #fff);
}
.discord-section-heading:first-child {
    margin-top: 0;
}
.discord-stats-row {
    background: var(--card-inner, rgba(255,255,255,0.03));
    border: 1px solid var(--line-bright, #333);
    border-radius: 8px;
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
}
.discord-stats-label {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    color: var(--text-primary, #fff);
}
.discord-stats-row .form-group {
    margin-bottom: 0.75rem;
}
.discord-stats-row .form-group:last-child {
    margin-bottom: 0;
}
</style>

<script>
(function () {
    const tabs    = document.querySelectorAll('.discord-tab');
    const panels  = document.querySelectorAll('.discord-tab-panel');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.getAttribute('data-tab');
            tabs.forEach(function (t) { t.classList.remove('discord-tab-active'); });
            panels.forEach(function (p) { p.style.display = 'none'; });
            tab.classList.add('discord-tab-active');
            const panel = document.getElementById('tab-' + target);
            if (panel) panel.style.display = '';
        });
    });

    // Otevři tab dle URL hash
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        const matchTab = document.querySelector('[data-tab="' + hash + '"]');
        if (matchTab) matchTab.click();
    }

    // Synchronizuje multi-select rolí → hidden input (comma-separated IDs)
    window.syncRoleInput = function (selectEl, inputId) {
        const selected = Array.from(selectEl.selectedOptions).map(o => o.value);
        document.getElementById(inputId).value = selected.join(',');
    };

    // Zobrazí/skryje sekci "složka uzavřených" dle zvolené akce
    window.updateClosedCatVisibility = function (slug, value) {
        const el = document.getElementById('closed-cat-' + slug);
        if (el) el.style.display = value === 'delete' ? 'none' : '';
    };

    // Inicializace: nastav hidden inputy dle aktuálního stavu multi-selectů
    document.querySelectorAll('.ticket-cat-accordion').forEach(function (acc) {
        acc.querySelectorAll('select[multiple]').forEach(function (sel) {
            const hid = sel.id.replace('_select', '');
            syncRoleInput(sel, hid);
        });
    });

    // ── Live preview pro panel embed ─────────────────────────────────────────
    (function () {
        const titleIn   = document.querySelector('input[name="panel_embed_title"]');
        const descIn    = document.getElementById('panelEmbedDesc');
        const colorPick = document.getElementById('panelColorPicker');
        const colorHex  = document.getElementById('panelColorHex');
        const preview   = document.getElementById('panelEmbedPreview');
        const ppTitle   = document.getElementById('ppTitle');
        const ppDesc    = document.getElementById('ppDesc');

        function updatePanel() {
            if (!preview) return;
            ppTitle.textContent = titleIn.value  || '🎫  OTRP  •  Tickets';
            ppDesc.textContent  = descIn.value   || '';
            const hex = colorHex.value.replace(/[^0-9a-fA-F]/g,'').slice(0,6) || '2B2D31';
            preview.style.borderLeftColor = '#' + hex;
            colorPick.value = '#' + hex.padEnd(6,'0');
        }

        if (titleIn) titleIn.addEventListener('input', updatePanel);
        if (descIn)  descIn.addEventListener('input', updatePanel);
        if (colorPick) colorPick.addEventListener('input', function () {
            colorHex.value = this.value.replace('#','').toUpperCase();
            updatePanel();
        });
        if (colorHex) colorHex.addEventListener('input', function () {
            const h = this.value.replace(/[^0-9a-fA-F]/g,'').slice(0,6);
            if (h.length === 6) colorPick.value = '#' + h;
            updatePanel();
        });
        updatePanel();
    }());

    // ── Live preview pro ticket welcome embed ────────────────────────────────
    (function () {
        const titleIn = document.querySelector('input[name="ticket_embed_title"]');
        const descIn  = document.getElementById('ticketEmbedDesc');
        const footIn  = document.querySelector('input[name="ticket_embed_footer"]');
        const tpTitle  = document.getElementById('tpTitle');
        const tpDesc   = document.getElementById('tpDesc');
        const tpFooter = document.getElementById('tpFooter');

        function updateTicket() {
            if (!tpTitle) return;
            tpTitle.textContent = '🔵 ' + (titleIn.value || 'TICKET #0001').replace('{id}','0001');
            tpDesc.textContent  = (descIn.value || '').replace('{id}','0001').replace('{user}','@Uživatel').replace('{category}','Admin Ticket');
            if (footIn) {
                tpFooter.textContent  = footIn.value || '';
                tpFooter.style.display = footIn.value ? '' : 'none';
            }
        }

        if (titleIn) titleIn.addEventListener('input', updateTicket);
        if (descIn)  descIn.addEventListener('input', updateTicket);
        if (footIn)  footIn.addEventListener('input', updateTicket);
        updateTicket();
    }());

    // ── Per-category welcome embed live preview ──────────────────────────────
    <?php foreach ($ticketCategories as $cat):
        $slug = $cat['slug'];
        $emoji = $cat['emoji'] ?? '📂';
    ?>
    (function () {
        var slug    = <?= json_encode($slug) ?>;
        var emoji   = <?= json_encode($emoji) ?>;
        var titleIn = document.getElementById('cat_embed_title_'  + slug);
        var descIn  = document.getElementById('cat_embed_desc_'   + slug);
        var footIn  = document.getElementById('cat_embed_footer_' + slug);
        var pTitle  = document.getElementById('cat_prev_title_'   + slug);
        var pDesc   = document.getElementById('cat_prev_desc_'    + slug);
        var pFooter = document.getElementById('cat_prev_footer_'  + slug);

        function update() {
            if (!pTitle) return;
            pTitle.textContent = emoji + '  ' + (titleIn.value || 'TICKET #0001').replace('{id}','0001');
            pDesc.textContent  = (descIn.value || '')
                .replace('{id}','0001')
                .replace('{user}','@Uživatel')
                .replace('{category}', <?= json_encode($cat['label']) ?>);
            if (footIn) {
                pFooter.textContent  = footIn.value || '';
                pFooter.style.display = footIn.value ? '' : 'none';
            }
        }

        if (titleIn) titleIn.addEventListener('input', update);
        if (descIn)  descIn.addEventListener('input', update);
        if (footIn)  footIn.addEventListener('input', update);
        update();
    }());
    <?php endforeach; ?>
}());
</script>
