<?php
$friendsList = $friendsList ?? [];
$pendingIncoming = $pendingIncoming ?? [];
$pendingOutgoing = $pendingOutgoing ?? [];
$friendSuggestions = $friendSuggestions ?? [];
$events = $events ?? [];
?>
<aside class="right-sidebar">

    <!-- Friend Requests (pending incoming) -->
    <section class="card">
        <div class="right-header">
            <div class="right-title">Friend Requests</div>
            <button class="right-link" type="button">See All</button>
        </div>

        <?php if (!empty($pendingIncoming)): ?>
            <?php foreach ($pendingIncoming as $u): ?>
                <div class="suggest-item">
                    <div class="suggest-avatar"
                        style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>');">
                    </div>
                    <div class="suggest-main">
                        <div class="suggest-name"><?= htmlspecialchars($u['full_name'] ?? '') ?></div>
                        <div class="suggest-username">@<?= htmlspecialchars($u['username'] ?? '') ?></div>
                    </div>
                    <span class="badge bg-warning text-dark">pending</span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">Không có lời mời.</div>
        <?php endif; ?>
    </section>

    <!-- Pending you sent (optional) -->
    <section class="card">
        <div class="right-header">
            <div class="right-title">Sent Requests</div>
            <button class="right-link" type="button">See All</button>
        </div>

        <?php if (!empty($pendingOutgoing)): ?>
            <?php foreach ($pendingOutgoing as $u): ?>
                <div class="suggest-item">
                    <div class="suggest-avatar"
                        style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>');">
                    </div>
                    <div class="suggest-main">
                        <div class="suggest-name"><?= htmlspecialchars($u['full_name'] ?? '') ?></div>
                        <div class="suggest-username">@<?= htmlspecialchars($u['username'] ?? '') ?></div>
                    </div>
                    <span class="badge bg-secondary">sent</span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">Không có.</div>
        <?php endif; ?>
    </section>

    <!-- Friends (accepted) - GIỮ NGUYÊN cấu trúc Message -->
    <section class="card">
        <div class="right-header">
            <div class="right-title">Friends</div>
            <button class="right-link" type="button">See All</button>
        </div>

        <?php if (!empty($friendsList)): ?>
            <?php foreach ($friendsList as $u): ?>
                <div class="suggest-item">
                    <div class="suggest-avatar"
                        style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>');">
                    </div>
                    <div class="suggest-main">
                        <div class="suggest-name"><?= htmlspecialchars($u['full_name'] ?? '') ?></div>
                        <div class="suggest-username">@<?= htmlspecialchars($u['username'] ?? '') ?></div>
                    </div>

                    <!-- giữ nguyên message button -->
                    <button class="friend-msg-btn" type="button" data-user-id="<?= (int)($u['user_id'] ?? 0) ?>">
                        Message
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">Chưa có bạn bè.</div>
        <?php endif; ?>
    </section>

    <!-- Friend Suggestions (strangers) -->
    <section class="card">
        <div class="right-header">
            <div class="right-title">Friend Suggestions</div>
            <button class="right-link" type="button">See All</button>
        </div>

        <?php foreach ((array)$friendSuggestions as $u): ?>
            <div class="suggest-item">
                <div class="suggest-avatar"
                    style="background-image:url('<?= htmlspecialchars($u['profile_picture_url'] ?? '/public/img/default_avatar.jpg') ?>');">
                </div>
                <div class="suggest-main">
                    <div class="suggest-name"><?= htmlspecialchars($u['full_name'] ?? '') ?></div>
                    <div class="suggest-username">@<?= htmlspecialchars($u['username'] ?? '') ?></div>
                </div>
                <button class="suggest-add-btn" type="button" data-user-id="<?= (int)($u['user_id'] ?? 0) ?>">+</button>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Upcoming Events -->
    <section class="card">
        <div class="right-header">
            <div class="right-title">Upcoming Events</div>
        </div>
        <div class="events-list">
            <?php foreach ((array)$events as $e): ?>
                <div class="event-item">
                    <div class="event-date">
                        <?= htmlspecialchars(date('d/m', strtotime($e['start_time'] ?? ''))) ?>
                    </div>
                    <div class="event-main">
                        <?= htmlspecialchars($e['title'] ?? '') ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</aside>