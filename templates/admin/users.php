<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users ~ Calandria RSS Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-terminal.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <div class="admin-logo">calandria@rss</div>
                <div class="admin-status">
                    <span class="status-indicator">></span> USERS
                </div>
            </div>
            <nav class="nav-section">
                <div class="nav-section-title">Navigation</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="/admin" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="/admin/sources" class="nav-link">Sources</a></li>
                    <li class="nav-item"><a href="/admin/categories" class="nav-link">Categories</a></li>
                    <li class="nav-item"><a href="/admin/users" class="nav-link active">Users</a></li>
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
                    <button class="tab active">Users</button>
                </div>
                <div class="top-nav">
                    <span style="color: var(--terminal-fg-dim);">
                        <?= htmlspecialchars($user['username'] ?? 'Guest') ?> 
                        <span style="color: var(--terminal-accent);">[<?= $user['role'] ?? 'none' ?>]</span>
                    </span>
                </div>
            </div>
            <div class="admin-content">
                <h1 style="color: var(--terminal-accent); margin-bottom: 2rem;">> Users Management</h1>
                <p style="color: var(--terminal-fg-dim); margin-bottom: 1rem;">⚠️ Admin only. Manage system users and permissions.</p>
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--terminal-accent);">API Endpoints:</h3>
                    <pre style="background: var(--terminal-bg-light); padding: 1rem; border-radius: 4px; overflow-x: auto;">
GET    /api/admin/users              - List all users
POST   /api/admin/users              - Create new user
PUT    /api/admin/users/{id}         - Update user
DELETE /api/admin/users/{id}         - Delete user
POST   /api/admin/users/{id}/password - Change password
                    </pre>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
