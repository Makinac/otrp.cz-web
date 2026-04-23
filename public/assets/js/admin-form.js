/**
 * Admin Form Schema editor JS.
 * Handles add/remove field rows and drag-based reordering.
 */
(function () {
    'use strict';

    const container = document.getElementById('fieldsContainer');
    const addBtn    = document.getElementById('addField');
    let   fieldIdx  = container ? container.querySelectorAll('.field-row').length : 0;

    /**
     * Create markup for a new blank field row.
     *
     * @param {number} idx Field index for required[] naming.
     * @returns {string} HTML string.
     */
    function fieldRowHtml(idx) {
        return `
<div class="field-row card" data-idx="${idx}">
    <div class="field-row-header">
        <strong>Pole #${idx + 1}</strong>
        <button type="button" class="btn btn-reject btn-sm remove-field">Odebrat</button>
    </div>
    <div class="fields-grid">
        <div class="form-group">
            <label class="form-label">Klíč (name)</label>
            <input type="text" name="field_name[]" class="form-control" required placeholder="napr_vek">
        </div>
        <div class="form-group">
            <label class="form-label">Popisek (label)</label>
            <input type="text" name="field_label[]" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">Typ</label>
            <select name="field_type[]" class="form-control field-type-select">
                <option value="text">text</option>
                <option value="textarea">textarea</option>
                <option value="select">select</option>
                <option value="radio">radio</option>
                <option value="checkbox">checkbox</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Možnosti (čárkou)</label>
            <input type="text" name="field_options[]" class="form-control" placeholder="Ano, Ne, Nevím">
        </div>
        <div class="form-group">
            <label class="option-label">
                <input type="checkbox" name="field_required[${idx}]" value="1">
                Povinné
            </label>
        </div>
    </div>
</div>`;
    }

    if (addBtn && container) {
        addBtn.addEventListener('click', function () {
            const tmpDiv = document.createElement('div');
            tmpDiv.innerHTML = fieldRowHtml(fieldIdx).trim();
            const row = tmpDiv.firstChild;
            container.appendChild(row);
            bindRemove(row);
            fieldIdx++;
        });
    }

    /**
     * Bind remove-button behaviour to a field row element.
     *
     * @param {HTMLElement} row The field row element.
     */
    function bindRemove(row) {
        const btn = row.querySelector('.remove-field');
        if (btn) {
            btn.addEventListener('click', function () {
                row.remove();
            });
        }
    }

    // Bind existing remove buttons.
    if (container) {
        container.querySelectorAll('.field-row').forEach(bindRemove);
    }

})();
