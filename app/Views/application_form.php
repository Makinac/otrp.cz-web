<section class="section">
    <div class="container container-narrow">
        <h1 class="page-title">Žádost o allowlist</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <p class="section-intro">Vyplň formulář níže. Všechna povinná pole jsou označena <span class="req">*</span>.</p>

        <form method="POST" action="/apply" class="app-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <?php foreach ($fields as $field):
                $name     = htmlspecialchars($field['name']);
                $label    = htmlspecialchars($field['label'] ?? $field['name']);
                $type     = $field['type'] ?? 'text';
                $required = !empty($field['required']);
                $options  = array_filter(array_map('trim', explode(',', $field['options'] ?? '')));
            ?>
                <div class="form-group">
                    <label for="field_<?= $name ?>" class="form-label">
                        <?= $label ?><?= $required ? ' <span class="req">*</span>' : '' ?>
                    </label>

                    <?php if ($type === 'textarea'): ?>
                        <textarea
                            id="field_<?= $name ?>"
                            name="<?= $name ?>"
                            class="form-control"
                            rows="5"
                            <?= $required ? 'required' : '' ?>
                        ></textarea>

                    <?php elseif ($type === 'select'): ?>
                        <select id="field_<?= $name ?>" name="<?= $name ?>" class="form-control" <?= $required ? 'required' : '' ?>>
                            <option value="">— Vyberte —</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($type === 'radio'): ?>
                        <div class="form-options">
                            <?php foreach ($options as $opt): ?>
                                <label class="option-label">
                                    <input type="radio" name="<?= $name ?>" value="<?= htmlspecialchars($opt) ?>" <?= $required ? 'required' : '' ?>>
                                    <?= htmlspecialchars($opt) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($type === 'checkbox'): ?>
                        <div class="form-options">
                            <?php foreach ($options as $opt): ?>
                                <label class="option-label">
                                    <input type="checkbox" name="<?= $name ?>[]" value="<?= htmlspecialchars($opt) ?>">
                                    <?= htmlspecialchars($opt) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <input
                            type="text"
                            id="field_<?= $name ?>"
                            name="<?= $name ?>"
                            class="form-control"
                            <?= $required ? 'required' : '' ?>
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Odeslat žádost</button>
                <a href="/allowlist" class="btn btn-ghost">Zrušit</a>
            </div>
        </form>
    </div>
</section>
