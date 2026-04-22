<div class="card">
    <div class="card-head">
        <div>
            <h3 class="card-title">Nhật ký Admin</h3>
        </div>
        <input type="text" id="logSearch" class="search" placeholder="Tìm admin / action / details...">
    </div>

    <div class="table-scroll" id="logsScroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Admin</th>
                    <th>Action Type</th>
                    <th>Target</th>
                    <th>Details</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody id="logsTbody"></tbody>
        </table>
        <div id="logsSentinel" class="sentinel"></div>
    </div>
</div>
<script type="module" src="/public/js/admin_logs.js"></script>