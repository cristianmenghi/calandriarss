<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories ~ Calandria RSS Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-terminal.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <div class="admin-logo">calandria@rss</div>
                <div class="admin-status">
                    <span class="status-indicator">></span> CATEGORIES
                </div>
            </div>
            <nav class="nav-section">
                <div class="nav-section-title">Navigation</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="/admin" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="/admin/sources" class="nav-link">Sources</a></li>
                    <li class="nav-item"><a href="/admin/categories" class="nav-link active">Categories</a></li>
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
                    <button class="tab active">Categories</button>
                </div>
                <div class="top-nav">
                    <span style="color: var(--terminal-fg-dim);">
                        <?= htmlspecialchars($user['username']) ?> 
                        <span style="color: var(--terminal-accent);">[<?= $user['role'] ?>]</span>
                    </span>
                </div>
            </div>
            <div class="admin-content">
                <h1 style="color: var(--terminal-accent); margin-bottom: 2rem;">> Categories Management</h1>
                <p style="color: var(--terminal-fg-dim);">Manage content categories. Use the API endpoints to add, edit, or delete categories.</p>
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--terminal-accent);">API Endpoints:</h3>
                    <pre style="background: var(--terminal-bg-light); padding: 1rem; border-radius: 4px; overflow-x: auto;">
GET    /api/admin/categories       - List all categories
POST   /api/admin/categories       - Create new category
PUT    /api/admin/categories/{id}  - Update category
DELETE /api/admin/categories/{id}  - Delete category
                    </pre>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
