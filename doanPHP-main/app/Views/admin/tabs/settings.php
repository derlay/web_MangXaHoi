<div class="card">
    <div class="card-head">
        <h3 class="card-title">Cài đặt hệ thống</h3>
        <p class="muted">System Configuration</p>
    </div>

    <form method="post" action="/public/actions/admin_save_settings.php">
        <div class="grid-2">
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="Social Media DB" class="input">
            </div>
            <div class="form-group">
                <label>Default Timezone</label>
                <input type="text" value="+00:00" class="input" disabled>
            </div>
        </div>
        <button class="btn btn-blue">Lưu thay đổi</button>
    </form>
</div>