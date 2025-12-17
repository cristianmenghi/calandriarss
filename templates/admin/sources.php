<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>Sources ~ Calandria RSS Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-terminal.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <div class="admin-logo">calandria@rss</div>
                <div class="admin-status">
                    <span class="status-indicator">></span> SOURCES
                </div>
            </div>
            <nav class="nav-section">
                <div class="nav-section-title">Navigation</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="/admin" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="/admin/sources" class="nav-link active">Sources</a></li>
                    <li class="nav-item"><a href="/admin/categories" class="nav-link">Categories</a></li>
                    <li class="nav-item"><a href="/admin/users" class="nav-link">Users</a></li>
                    <li class="nav-item"><a href="/admin/logs" class="nav-link">Fetch Logs</a></li>
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
                    <button class="tab active">Sources</button>
                </div>
                <div class="top-nav">
                    <span style="color: var(--terminal-fg-dim);">
                        <?= htmlspecialchars($user['username'] ?? 'Guest') ?> 
                        <span style="color: var(--terminal-accent);">[<?= $user['role'] ?? 'none' ?>]</span>
                    </span>
                </div>
            </div>
            <div class="admin-content">
                <div class="toolbar">
                    <h1 style="color: var(--terminal-accent); margin: 0;">> Sources Management</h1>
                    <div class="toolbar-actions">
                        <button class="terminal-button" data-action="add" data-type="sources">+ Add Source</button>
                        <button class="terminal-button" data-action="refresh">↻ Refresh</button>
                    </div>
                </div>
                
                <div id="sources-table" class="loading">Loading sources...</div>
            </div>
        </main>
    </div>

    <script src="/assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            admin.setView('sources');
        });
    </script>
</body>
</html>
