<div class="card">
    <div class="card-head">
        <div>
            <h3 class="card-title">Quản lý bài viết</h3>
        </div>
        <input type="text" id="postSearch" class="search" placeholder="Tìm theo user, nội dung...">
    </div>
    <div class="table-scroll" id="postsScroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Content</th>
                    <th>Media</th>
                    <th>Privacy</th>
                    <th>Status</th>
                    <th class="t-right">Action</th>
                </tr>
            </thead>
            <tbody id="postsTbody"></tbody>
        </table>
        <div id="postsSentinel" class="sentinel"></div>
    </div>
</div>
<script type="module" src="/public/js/admin_posts.js"></script>