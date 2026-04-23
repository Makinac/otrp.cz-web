<section class="section">
    <div class="container">
        <h1 class="page-title">Nové CK Hlasování</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <div class="card" style="max-width:700px;margin:0 auto;">
            <form method="POST" action="/admin/ck/save">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-group">
                    <label for="applicant" class="form-label">Žadatel (jméno postavy) *</label>
                    <input type="text" id="applicant" name="applicant" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="victim" class="form-label">Oběť (jméno postavy) *</label>
                    <input type="text" id="victim" name="victim" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Popis situace *</label>
                    <textarea id="description" name="description" class="form-control" rows="6" required></textarea>
                </div>

                <div class="form-group">
                    <label for="context_urls" class="form-label">Discord odkazy (jeden na řádek)</label>
                    <textarea id="context_urls" name="context_urls" class="form-control" rows="3" placeholder="https://discord.com/channels/..."></textarea>
                    <small class="form-hint">Vlož odkazy na Discord zprávy/místnosti pro kontext. Každý odkaz na nový řádek.</small>
                </div>

                <div class="form-actions">
                    <a href="/admin/ck" class="btn btn-secondary">Zpět</a>
                    <button type="submit" class="btn btn-primary">Vytvořit hlasování</button>
                </div>
            </form>
        </div>
    </div>
</section>
