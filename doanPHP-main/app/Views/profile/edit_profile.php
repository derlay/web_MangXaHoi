<?php
function cloud_avatar_url(string $cloudName, ?string $publicId, string $defaultPid, int $w = 140, int $h = 140): string
{
    $pid = $publicId ?: $defaultPid;
    return "https://res.cloudinary.com/{$cloudName}/image/upload/c_fill,w_{$w},h_{$h},q_auto,f_auto/" . rawurlencode($pid) . ".jpg";
}
function cloud_cover_url(string $cloudName, ?string $publicId, string $defaultPid, ?int $version = null, int $w = 1200, int $h = 380): string
{
    $pid = $publicId ?: $defaultPid;
    $ver = $version ? "/v{$version}" : '';
    return "https://res.cloudinary.com/{$cloudName}/image/upload/c_fill,w_{$w},h_{$h},q_auto,f_auto{$ver}/" . rawurlencode($pid) . ".jpg";
}

$cloudName = $cloud['cloud_name'] ?? '';
$avatarUrl = cloud_avatar_url($cloudName, $currentUser['profile_picture_public_id'] ?? null, $cloud['default_avatar_public_id'] ?? 'qe9evl3wbbvjs8ekq21u');
$coverUrl  = cloud_cover_url($cloudName, $currentUser['cover_photo_public_id'] ?? null, $cloud['default_cover_public_id'] ?? 'iirjbjtmdsjmlmmdazjr', $cloud['default_cover_version'] ?? null);
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Edit profile</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/public/asset/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/css/profile_edit.css">
    <script>
        window.CLOUDINARY_CLOUD_NAME = "<?= htmlspecialchars($cloudName) ?>";
        window.CLOUDINARY_UNSIGNED_PRESET = "<?= htmlspecialchars($cloud['unsigned_preset'] ?? '') ?>";
    </script>
</head>

<body>



    <div class="cover-box">
        <div id="coverPreview" class="cover-preview" style="background-image:url('<?= htmlspecialchars($coverUrl) ?>')">
        </div>
        <div class="cover-actions">
            <button type="button" id="btnSelectCover" class="btn btn-light btn-sm btn-file">Chọn cover</button>
            <button type="button" id="btnClearCover" class="btn btn-outline-danger btn-sm">Xóa</button>
        </div>
        <input type="file" id="coverFile" accept="image/*" class="d-none">
    </div>

    <main class="edit-main">
        <section class="card">
            <h6 class="mb-3">Avatar</h6>
            <div style="display:flex;align-items:center;gap:16px;">
                <div id="avatarPreview" class="avatar-preview"
                    style="background-image:url('<?= htmlspecialchars($avatarUrl) ?>')"></div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <button type="button" id="btnSelectAvatar" class="btn btn-light btn-sm btn-file">Chọn
                        avatar</button>
                    <button type="button" id="btnClearAvatar" class="btn btn-outline-danger btn-sm">Xóa</button>
                    <div class="small-text">Ảnh tối đa 5MB. jpg/png/webp/gif.</div>
                </div>
            </div>
            <input type="file" id="avatarFile" accept="image/*" class="d-none">
        </section>

        <section class="card">
            <h6 class="mb-3">Thông tin</h6>
            <form id="form-edit-profile">
                <div class="mb-3">
                    <label class="form-label">Tên</label>
                    <input type="text" name="full_name" class="form-control"
                        value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tên người dùng</label>
                    <input type="text" name="username" class="form-control"
                        value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tiểu sử</label>
                    <textarea name="bio" rows="3"
                        class="form-control"><?= htmlspecialchars((string)($currentUser['bio'] ?? '')) ?></textarea>
                </div>

                <!-- Hidden public_id -->
                <input type="hidden" name="profile_picture_public_id" id="profile_picture_public_id"
                    value="<?= htmlspecialchars($currentUser['profile_picture_public_id'] ?? '') ?>">
                <input type="hidden" name="cover_photo_public_id" id="cover_photo_public_id"
                    value="<?= htmlspecialchars($currentUser['cover_photo_public_id'] ?? '') ?>">
                <input type="hidden" name="mode" value="cloudinary_direct">

                <div class="d-flex gap-2 align-items-center">
                    <button type="submit" id="btnSave" class="btn btn-primary btn-sm">Save</button>
                    <span id="saveState" class="small-text" style="display:none;">Đang lưu...</span>
                </div>
            </form>
        </section>
    </main>

    <script src="/public/js/profile_edit_cloudinary.js"></script>
</body>

</html>