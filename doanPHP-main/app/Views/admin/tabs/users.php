<?php

?>
<div class="card">
    <div class="card-head">
        <div>
            <h3 class="card-title">Quản lý người dùng</h3>
            <p class="muted">Table: users (cuộn tải thêm mỗi 7)</p>
        </div>
        <input type="text" id="userSearch" class="search" placeholder="Tìm theo user, email..." />
    </div>

    <div class="table-scroll" id="usersScroll">
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Info</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Status</th>
                    <th class="t-right">Action</th>
                </tr>
            </thead>
            <tbody id="usersTbody">

            </tbody>
        </table>
        <div id="usersSentinel" class="sentinel"></div>
    </div>
</div>

<style>
    .sentinel {
        height: 1px;
    }

    .loading-row {
        text-align: center;
        padding: 12px;
        color: #6b7280;
    }
</style>

<script type="module" src="/public/js/admin_users.js"></script>