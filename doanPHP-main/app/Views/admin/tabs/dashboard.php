<?php
$daily = $data['dailyStats'] ?? [];
$tot   = $data['totals'] ?? ['users' => 0, 'posts' => 0, 'comments' => 0, 'activeUsers' => 0];

// Chuẩn bị dữ liệu cho JS
$labels = array_map(fn($r) => $r['stat_date'], $daily);
$totalPosts = array_map(fn($r) => (int)$r['total_posts_count'], $daily);
$activeUsers = array_map(fn($r) => (int)$r['active_users_count'], $daily);
$newUsers = array_map(fn($r) => (int)$r['new_users_count'], $daily);
?>
<div class="grid-4">
    <div class="card stat">
        <p class="stat-title">Tổng User</p>
        <h3 class="stat-value"><?= (int)$tot['users'] ?></h3>
        <span class="stat-sub sub-green">+ hôm nay</span>
    </div>
    <div class="card stat">
        <p class="stat-title">User Hoạt động </p>
        <h3 class="stat-value"><?= (int)$tot['activeUsers'] ?></h3>
        <span
            class="stat-sub sub-green"><?= $tot['users'] > 0 ? round($tot['activeUsers'] / $tot['users'] * 100) : 0 ?>%
            tổng
            user</span>
    </div>
    <div class="card stat">
        <p class="stat-title">Reports Chờ xử lý</p>
        <h3 class="stat-value"><?= (int)$data['pendingReports'] ?></h3>
        <span class="stat-sub sub-red">Cần hành động</span>
    </div>
    <div class="card stat">
        <p class="stat-title">Tổng Bài viết</p>
        <h3 class="stat-value"><?= (int)$tot['posts'] ?></h3>
        <span class="stat-sub sub-purple">+<?= (int)$tot['comments'] ?> Comments</span>
    </div>
</div>

<div class="grid-2-1">
    <div class="card">
        <h3 class="card-title">Tăng trưởng hệ thống </h3>
        <div class="chart-box">
            <canvas id="chartCombined"></canvas>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">New Users</h3>
        <div class="chart-box">
            <canvas id="chartNewUsers"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const labels = <?= json_encode($labels) ?>;
        const totalPosts = <?= json_encode($totalPosts) ?>;
        const activeUsers = <?= json_encode($activeUsers) ?>;
        const newUsers = <?= json_encode($newUsers) ?>;

        const ctx1 = document.getElementById('chartCombined');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            type: 'bar',
                            label: 'Tổng Post',
                            data: totalPosts,
                            backgroundColor: '#8884d8',
                            borderRadius: 6,
                            yAxisID: 'y'
                        },
                        {
                            type: 'line',
                            label: 'Active Users',
                            data: activeUsers,
                            borderColor: '#22c55e',
                            backgroundColor: '#22c55e',
                            tension: .3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            position: 'left',
                            grid: {
                                drawBorder: false
                            }
                        },
                        y1: {
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('chartNewUsers');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'New Users',
                        data: newUsers,
                        borderColor: '#3b82f6',
                        backgroundColor: '#93c5fd80',
                        fill: true,
                        tension: .3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    })();
</script>