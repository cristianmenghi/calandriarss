<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings ~ Calandria RSS Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-terminal.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <div class="admin-logo">calandria@rss</div>
                <div class="admin-status">
                    <span class="status-indicator">></span> SETTINGS
                </div>
            </div>
            <nav class="nav-section">
                <div class="nav-section-title">Navigation</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="/admin" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="/admin/sources" class="nav-link">Sources</a></li>
                    <li class="nav-item"><a href="/admin/categories" class="nav-link">Categories</a></li>
                    <li class="nav-item"><a href="/admin/users" class="nav-link">Users</a></li>
                    <li class="nav-item"><a href="/admin/logs" class="nav-link">Fetch Logs</a></li>
                    <li class="nav-item"><a href="/admin/settings" class="nav-link active">Settings</a></li>
                </ul>
            </nav>
            <div class="admin-footer">
                <form method="POST" action="/logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="terminal-button" style="width: 100%;">Logout</button>
                </form>
            </div>
        </aside>
        <main class="admin-main">
            <div class="admin-topbar">
                <div class="tabs">
                    <button class="tab active">Settings</button>
                </div>
                <div class="top-nav">
                    <span style="color: var(--terminal-fg-dim);">
                        <?= htmlspecialchars($user['username'] ?? 'Guest') ?> 
                        <span style="color: var(--terminal-accent);">[<?= $user['role'] ?? 'none' ?>]</span>
                    </span>
                </div>
            </div>
            <div class="admin-content">
                <h1 style="color: var(--terminal-accent); margin-bottom: 2rem;">> System Settings</h1>
                <p style="color: var(--terminal-fg-dim); margin-bottom: 1rem;">⚠️ Admin only. Configure system parameters.</p>
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--terminal-accent);">Configuration Files:</h3>
                    <ul style="list-style: none; padding: 0; color: var(--terminal-fg-dim);">
                        <li>> .env - Environment variables</li>
                        <li>> config/database.php - Database configuration</li>
                        <li>> config/app.php - Application settings</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
