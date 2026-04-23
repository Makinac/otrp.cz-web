<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <a href="/management/form" class="back-link">&larr; Formuláře</a>
        <h2 class="section-heading"><?= $schema ? 'Upravit formulář' : 'Nový formulář' ?></h2>

        <?php $action = $schema ? "/management/form/{$schema['id']}/save" : '/management/form/save'; ?>
        <form method="POST" action="<?= $action ?>" id="formSchemaEditor">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="schema_name">Název schématu <span class="req">*</span></label>
                <input type="text" id="schema_name" name="name" class="form-control"
                       value="<?= htmlspecialchars($schema['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="option-label">
                    <input type="checkbox" name="active" value="1" <?= !empty($schema['active']) ? 'checked' : '' ?>>
                    Aktivní schéma
                </label>
            </div>

            <h2 class="section-heading">Pole formuláře</h2>
            <div id="fieldsContainer">
                <?php foreach ($fields as $idx => $field): ?>
                    <div class="field-row card" data-idx="<?= $idx ?>">
                        <div class="field-row-header">
                            <strong>Pole #<?= $idx + 1 ?></strong>
                            <button type="button" class="btn btn-reject btn-sm remove-field">Odebrat</button>
                        </div>
                        <div class="fields-grid">
                            <div class="form-group">
                                <label class="form-label">Klíč (name)</label>
                                <input type="text" name="field_name[]" class="form-control"
                                       value="<?= htmlspecialchars($field['name'] ?? '') ?>" required placeholder="napr_vek">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Popisek (label)</label>
                                <input type="text" name="field_label[]" class="form-control"
                                       value="<?= htmlspecialchars($field['label'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Typ</label>
                                <select name="field_type[]" class="form-control field-type-select">
                                    <?php foreach (['text','textarea','select','radio','checkbox'] as $t): ?>
                                        <option value="<?= $t ?>" <?= ($field['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Možnosti (čárkou)</label>
                                <input type="text" name="field_options[]" class="form-control"
                                       value="<?= htmlspecialchars($field['options'] ?? '') ?>" placeholder="Ano, Ne, Nevím">
                            </div>
                            <div class="form-group">
                                <label class="option-label">
                                    <input type="checkbox" name="field_required[<?= $idx ?>]" value="1"
                                           <?= !empty($field['required']) ? 'checked' : '' ?>>
                                    Povinné
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="addField" class="btn btn-secondary mt-2">&#43; Přidat pole</button>

            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary">Uložit formulář</button>
                <a href="/management/form" class="btn btn-ghost">Zrušit</a>
            </div>
        </form>
    </div>
</section>
<script src="/assets/js/admin-form.js"></script>
