<!-- Görünürlük Seçici Component -->
<!-- Bu component checklist, not ve dosya formlarında kullanılacak -->
<div class="mb-3">
    <label class="form-label small fw-bold">
        <i class="fas fa-eye me-1"></i>Kimler Görebilir?
    </label>
    <select name="<?php echo $prefix; ?>gorulebilirlik" class="form-select form-select-sm gorulebilirlik-select"
        data-prefix="<?php echo $prefix; ?>" onchange="toggleCustomUsers(this)">
        <option value="ekip" selected>👥 Sadece Ekip Üyeleri</option>
        <option value="herkes">🌐 Projedeki Herkes</option>
        <option value="sadece_ben">🔒 Sadece Ben</option>
        <option value="ozel">⚙️ Özel (Belirli Kişiler)</option>
    </select>

    <!-- Özel kullanıcı seçimi (sadece 'ozel' seçiliyse göster) -->
    <div class="custom-users-container mt-2" id="<?php echo $prefix; ?>custom_users_container" style="display: none;">
        <label class="form-label small">Görebilecek Kişiler:</label>
        <select name="<?php echo $prefix; ?>ozel_kullanicilar[]" class="form-select form-select-sm" multiple size="4">
            <?php foreach ($projectUsers as $pu): ?>
                <option value="<?php echo $pu['kullanici_id']; ?>">
                    <?php echo htmlspecialchars($pu['ad'] . ' ' . $pu['soyad']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">Ctrl/Cmd tuşu ile çoklu seçim yapabilirsiniz</small>
    </div>
</div>

<script>
    function toggleCustomUsers(selectElement) {
        const prefix = selectElement.getAttribute('data-prefix');
        const container = document.getElementById(prefix + 'custom_users_container');

        if (selectElement.value === 'ozel') {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }
</script>