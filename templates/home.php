<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calandria RSS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div id="app">
        <header class="app-header">
            <div class="logo">Calandria RSS</div>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Search news...">
            </div>
            <div class="filters">
                <select id="source-filter">
                    <option value="">All Sources</option>
                </select>
            </div>
        </header>

        <main class="news-grid" id="news-container">
            <!-- Articles will be loaded here -->
        </main>
        
        <div id="loading" class="loading-state">Loading...</div>

        <!-- Article Modal -->
        <div id="article-modal" class="modal hidden">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2 id="modal-title"></h2>
                <div class="meta">
                    <span id="modal-source"></span> | <span id="modal-date"></span>
                </div>
                <div id="modal-body"></div>
                <a id="modal-link" href="#" target="_blank" class="read-more-btn">Read Original</a>
            </div>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>
