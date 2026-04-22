<section class="card stories-card">
    <div class="stories-track">
        <?php foreach ($stories as $s): ?>
        <div class="story-item">
            <div class="story-ring">
                <div class="story-avatar"
                    style="background-image:url('/public/img/<?= htmlspecialchars($s['profile_picture_url']) ?>');">
                </div>
            </div>
            <div class="story-name">
                <?= htmlspecialchars($s['full_name']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>