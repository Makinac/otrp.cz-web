<section class="section">
    <div class="container" style="max-width:600px;">
        <h1 class="page-title">Nastavení admina</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <form method="POST" action="/admin/settings/save" class="form-card" style="margin-top:1.5rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="perm-checkbox-label" style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;">
                    <input type="checkbox" name="admin_prefix_chat" value="1"
                        <?= $settings['admin_prefix_chat'] ? 'checked' : '' ?>>
                    <span>
                        <strong style="color:var(--white);">Admin prefix v chatu</strong><br>
                        <small style="color:var(--grey);">Zobrazuje admin prefix před vaší zprávou v in-game chatu.</small>
                    </span>
                </label>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="perm-checkbox-label" style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;">
                    <input type="checkbox" name="report_notifications" value="1"
                        <?= $settings['report_notifications'] ? 'checked' : '' ?>>
                    <span>
                        <strong style="color:var(--white);">Oznámení o vytvoření reportu</strong><br>
                        <small style="color:var(--grey);">Dostanete in-game oznámení při vytvoření nového reportu.</small>
                    </span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Uložit nastavení</button>
        </form>
    </div>
</section>
