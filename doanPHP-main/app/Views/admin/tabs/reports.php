<div class="card">
    <div class="card-head">
        <div>
            <h3 class="card-title">Hàng chờ kiểm duyệt</h3>
        </div>
        <input type="text" id="reportSearch" class="search" placeholder="Tìm reporter / lý do ...">
    </div>
    <div class="table-scroll" id="reportsScroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reporter</th>
                    <th>Target</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="t-right">Action</th>
                </tr>
            </thead>
            <tbody id="reportsTbody"></tbody>
        </table>
        <div id="reportsSentinel" class="sentinel"></div>
    </div>
</div>
<script type="module" src="/public/js/admin_reports.js"></script>