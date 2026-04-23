<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <div class="card" style="margin-bottom:1rem;">
            <h2 class="card-title">Systémové vedení role</h2>
            <p class="tester-meta" style="margin:0;line-height:1.6;">
                Tyto Discord role ID mají vždy plný přístup do Admin i Management panelu a nejdou odebrat:
                <?php foreach (($vedeniRoleIds ?? []) as $rid): ?>
                    <strong><?= htmlspecialchars((string)$rid) ?></strong>
                <?php endforeach; ?>
            </p>
        </div>

        <!-- Add new subject -->
        <div class="card" style="margin-bottom:1.5rem;">
            <h2 class="card-title">Přidat skupinu / uživatele</h2>
            <form method="POST" action="/management/settings/grant">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="fields-grid">
                    <div class="form-group">
                        <label class="form-label" for="subject_type">Typ</label>
                        <select id="subject_type" name="subject_type" class="form-control" required>
                            <option value="role">Discord role</option>
                            <option value="user">Konkrétní uživatel</option>
                        </select>
                    </div>
                </div>

                <div id="rolePick" class="form-group">
                    <label class="form-label" for="role_value">Role</label>
                    <select id="role_value" class="form-control">
                        <?php foreach ($knownRoles as $role): ?>
                            <option value="<?= htmlspecialchars((string)$role['id']) ?>"><?= htmlspecialchars((string)$role['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="userPick" class="form-group" style="display:none;">
                    <label class="form-label" for="user_value">Uživatel</label>
                    <select id="user_value" class="form-control">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['discord_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="subject_value" id="subject_value_hidden" value="<?= !empty($knownRoles) ? htmlspecialchars((string)$knownRoles[0]['id']) : '' ?>">

                <button type="submit" class="btn btn-primary">Přidat</button>
            </form>
        </div>

        <!-- Permission matrix per subject -->
        <h2 class="section-heading">Oprávnění</h2>
        <?php if (empty($permissionGroups)): ?>
            <p class="empty-notice">Zatím žádná delegovaná oprávnění. Přidej skupinu nebo uživatele výše.</p>
        <?php else: ?>
            <?php
                $isVedeniSubject = function(array $group) use ($vedeniRoleIds): bool {
                    return $group['subject_type'] === 'role'
                        && in_array((string)$group['subject_value'], $vedeniRoleIds ?? [], true);
                };
                // Build role ID → name lookup from known roles.
                $roleNameById = [];
                foreach ($knownRoles as $kr) {
                    $roleNameById[(string)$kr['id']] = $kr['name'];
                }
            ?>
            <?php foreach ($permissionGroups as $groupKey => $group): ?>
                <?php $vedeni = $isVedeniSubject($group); ?>
                <div class="perm-subject-card">
                    <div class="perm-subject-header">
                        <div>
                            <span class="perm-subject-type"><?= $group['subject_type'] === 'role' ? 'Role' : 'Uživatel' ?></span>
                            <?php if ($group['subject_type'] === 'role' && isset($roleNameById[$group['subject_value']])): ?>
                                <span class="perm-subject-label"><?= htmlspecialchars($roleNameById[$group['subject_value']]) ?></span>
                                <span class="perm-subject-id"><?= htmlspecialchars($group['subject_value']) ?></span>
                            <?php else: ?>
                                <span class="perm-subject-label"><?= htmlspecialchars($group['label']) ?></span>
                            <?php endif; ?>
                            <?php if ($vedeni): ?>
                                <span class="perm-vedeni-badge">Vedení</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$vedeni): ?>
                            <form method="POST" action="/management/settings/remove-subject" onsubmit="return confirm('Odebrat tento subjekt a všechna jeho oprávnění?')" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="subject_type" value="<?= htmlspecialchars($group['subject_type']) ?>">
                                <input type="hidden" name="subject_value" value="<?= htmlspecialchars($group['subject_value']) ?>">
                                <button class="btn btn-reject btn-sm">Odebrat</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="/management/settings/sync-perms">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="subject_type" value="<?= htmlspecialchars($group['subject_type']) ?>">
                        <input type="hidden" name="subject_value" value="<?= htmlspecialchars($group['subject_value']) ?>">
                        <div class="perm-sections-grid">
                        <?php foreach ($permissionGroupedLabels as $sectionKey => $section): ?>
                            <div class="perm-section" data-section="<?= htmlspecialchars($sectionKey) ?>">
                                <div class="perm-section-heading"><?= htmlspecialchars($section['label']) ?></div>
                                <?php if (!empty($section['custom']) && $section['custom'] === 'benefits' && $group['subject_type'] === 'role'): ?>
                                    <?php
                                        $sv = $group['subject_value'];
                                        $qpVal  = (int)($qpRoleMap[$sv] ?? 0);
                                        $chVal  = (int)($charRoleMap[$sv] ?? 0);
                                        $pedVal = !empty($pedRoleMap[$sv]);
                                    ?>
                                    <div class="benefits-row">
                                        <div class="form-group" style="margin:0;">
                                            <label class="form-label">QP</label>
                                            <input type="number" name="benefit_qp" class="form-control"
                                                   value="<?= $qpVal ?>" min="0" max="100000" style="max-width:120px;">
                                        </div>
                                        <div class="form-group" style="margin:0;">
                                            <label class="form-label">Char sloty</label>
                                            <input type="number" name="benefit_chars" class="form-control"
                                                   value="<?= $chVal ?>" min="0" max="15" style="max-width:120px;">
                                        </div>
                                        <label class="perm-checkbox-label" style="align-self:flex-end;">
                                            <input type="checkbox" name="benefit_ped" value="1" <?= $pedVal ? 'checked' : '' ?>>
                                            <span class="perm-checkbox-text">Ped Menu</span>
                                        </label>
                                    </div>
                                <?php elseif (!empty($section['custom']) && $section['custom'] === 'benefits'): ?>
                                    <p class="perm-empty">Pouze pro role</p>
                                <?php elseif (!empty($section['subcategories'])): ?>
                                    <?php foreach ($section['subcategories'] as $subKey => $sub): ?>
                                        <div class="perm-subcategory">
                                            <div class="perm-subcategory-heading"><?= htmlspecialchars($sub['label']) ?></div>
                                            <?php if (!empty($sub['perms'])): ?>
                                                <div class="perm-checkboxes">
                                                    <?php foreach ($sub['perms'] as $permKey => $permLabel): ?>
                                                        <?php
                                                            $checked = isset($group['keys'][$permKey]);
                                                            $inputId = 'perm_' . md5($groupKey . $permKey);
                                                        ?>
                                                        <label class="perm-checkbox-label" for="<?= $inputId ?>">
                                                            <input
                                                                type="checkbox"
                                                                id="<?= $inputId ?>"
                                                                name="perms[]"
                                                                value="<?= htmlspecialchars($permKey) ?>"
                                                                <?= $checked ? 'checked' : '' ?>
                                                                <?= $vedeni ? 'disabled' : '' ?>
                                                            >
                                                            <span class="perm-checkbox-text"><?= htmlspecialchars($permLabel) ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="perm-empty">Žádná oprávnění</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="perm-checkboxes">
                                        <?php foreach ($section['perms'] as $permKey => $permLabel): ?>
                                            <?php
                                                $checked = isset($group['keys'][$permKey]);
                                                $inputId = 'perm_' . md5($groupKey . $permKey);
                                            ?>
                                            <label class="perm-checkbox-label" for="<?= $inputId ?>">
                                                <input
                                                    type="checkbox"
                                                    id="<?= $inputId ?>"
                                                    name="perms[]"
                                                    value="<?= htmlspecialchars($permKey) ?>"
                                                    <?= $checked ? 'checked' : '' ?>
                                                    <?= $vedeni ? 'disabled' : '' ?>
                                                >
                                                <span class="perm-checkbox-text"><?= htmlspecialchars($permLabel) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <?php if (!$vedeni): ?>
                            <div class="perm-save-row">
                                <button type="submit" class="btn btn-primary btn-sm">Uložit oprávnění</button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Discord Bot Settings -->
        <!-- Discord odkaz -->
        <div class="card" style="margin-top:2rem;margin-bottom:1.5rem;border-left:3px solid #5865F2;">
            <h2 class="card-title">🤖 Discord Bot</h2>
            <p class="tester-meta" style="margin:0 0 1rem;line-height:1.6;">
                Nastavení Discord bota (automatizace allowlistu, ticket systém, server statistiky) bylo přesunuto na samostatnou stránku.
            </p>
            <a href="/management/discord" class="btn btn-primary">Přejít na Discord nastavení</a>
        </div>

        <!-- Discord cache nuke -->
        <div class="card" style="margin-top:2rem;border-left:3px solid #c0392b;">
            <h2 class="card-title">⚡ Vymazat cache Discord rolí</h2>
            <p class="tester-meta" style="margin:0 0 1rem;line-height:1.6;">
                Okamžitě vymaže uloženou cache Discord rolí pro všechny aktivní sessions a odhlásí ostatní uživatele.
                Po přihlášení se role každému načtou znovu přímo z Discordu.<br>
                <strong>Použij pokud jsi odebral/a admina a potřebuješ, aby to platilo okamžitě.</strong>
            </p>
            <form method="POST" action="/management/settings/clear-cache"
                  onsubmit="return confirm('Všichni ostatní přihlášení uživatelé budou odhlášeni. Pokračovat?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" class="btn btn-reject">Vymazat cache a odhlásit všechny</button>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    var typeSel = document.getElementById('subject_type');
    var rolePick = document.getElementById('rolePick');
    var userPick = document.getElementById('userPick');
    var roleValue = document.getElementById('role_value');
    var userValue = document.getElementById('user_value');
    var hiddenValue = document.getElementById('subject_value_hidden');

    function sync() {
        var isRole = typeSel.value === 'role';
        rolePick.style.display = isRole ? '' : 'none';
        userPick.style.display = isRole ? 'none' : '';
        if (isRole) {
            hiddenValue.value = roleValue ? roleValue.value : '';
        } else {
            hiddenValue.value = userValue ? userValue.value : '';
        }
    }

    typeSel.addEventListener('change', sync);
    if (roleValue) roleValue.addEventListener('change', sync);
    if (userValue) userValue.addEventListener('change', sync);
    sync();
}());
</script>
