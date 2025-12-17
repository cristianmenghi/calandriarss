<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ~ Calandria RSS Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-terminal.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Left Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-header">
                <div class="admin-logo">calandria@rss</div>
                <div class="admin-status">
                    <span class="status-indicator">></span> ALL
                    <br>
                    <span style="color: var(--terminal-fg-dim); font-size: 0.875rem;">
                        <?= number_format($stats['total_articles']) ?> items found
                    </span>
                </div>
            </div>

            <nav class="nav-section">
                <div class="nav-section-title">Navigation</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="/admin" class="nav-link active">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/sources" class="nav-link">
                            Sources
                            <span class="badge"><?= $stats['total_sources'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/categories" class="nav-link">
                            Categories
                            <span class="badge"><?= $stats['total_categories'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/users" class="nav-link">
                            Users
                            <span class="badge"><?= $stats['total_users'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/logs" class="nav-link">
                            Fetch Logs
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="/admin/settings" class="nav-link">
                            Configuration
                        </a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="/logout" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="nav-link" style="width: 100%; text-align: left; background: none; border: none; cursor: pointer; font-family: inherit; font-size: inherit; color: inherit; padding: var(--spacing-sm) var(--spacing-md);">
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <div class="admin-footer">
                <div class="footer-links">
                    <a href="https://github.com/cristianmenghi/calandriarss" class="footer-link" target="_blank">GitHub</a>
                    <span class="separator">·</span>
                    <a href="/README.md" class="footer-link">About</a>
                    <span class="separator">·</span>
                    <span class="footer-link" style="cursor: default;">v2.0</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-topbar">
                <div class="tabs">
                    <button class="tab active">Dashboard</button>
                    <button class="tab" onclick="window.open('/', '_blank')">Frontend</button>
                </div>
                <div class="top-nav">
                    <span style="color: var(--terminal-fg-dim);">
                        <?= htmlspecialchars($user['username'] ?? 'Guest') ?> 
                        <span style="color: var(--terminal-accent);">[<?= $user['role'] ?? 'none' ?>]</span>
                    </span>
                </div>
            </div>

            <div class="admin-content">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Articles</div>
                        <div class="stat-value"><?= number_format($stats['total_articles']) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Sources</div>
                        <div class="stat-value"><?= number_format($stats['total_sources']) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Categories</div>
                        <div class="stat-value"><?= number_format($stats['total_categories']) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Users</div>
                        <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    </div>
                </div>

                <!-- Top Sources -->
                <div style="margin-top: 2rem;">
                    <h2 style="color: var(--terminal-accent); margin-bottom: 1rem; font-size: 1.25rem;">
                        > Top Sources (30d)
                    </h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Category</th>
                                <th>Articles</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_sources'] as $source): ?>
                            <tr>
                                <td><?= htmlspecialchars($source['name']) ?></td>
                                <td><?= htmlspecialchars($source['category'] ?? 'N/A') ?></td>
                                <td><?= number_format($source['article_count']) ?></td>
                                <td>
                                    <span class="status-badge <?= $source['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $source['is_active'] ? 'active' : 'inactive' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Articles -->
                <div style="margin-top: 2rem;">
                    <h2 style="color: var(--terminal-accent); margin-bottom: 1rem; font-size: 1.25rem;">
                        > Recent Articles
                    </h2>
                    <ul class="article-list">
                        <?php foreach ($stats['recent_articles'] as $article): ?>
                        <li class="article-item">
                            <div class="article-header">
                                <span class="article-type">[Article]</span>
                                <div class="article-title">
                                    <?= htmlspecialchars($article['title']) ?>
                                </div>
                            </div>
                            <div class="article-meta">
                                <span><?= htmlspecialchars($article['source_name']) ?></span>
                                <span class="separator">·</span>
                                <span class="article-time">
                                    <?php
                                    $time = strtotime($article['published_at']);
                                    $diff = time() - $time;
                                    if ($diff < 3600) {
                                        echo floor($diff / 60) . 'm ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . 'h ago';
                                    } else {
                                        echo floor($diff / 86400) . 'd ago';
                                    }
                                    ?>
                                </span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
