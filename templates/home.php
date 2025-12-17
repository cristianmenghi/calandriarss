<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calandria RSS Terminal</title>
    <link rel="stylesheet" href="/assets/css/terminal-frontend.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-layout">
        <!-- Column 1: Categories -->
        <div class="column column-categories">
            <div class="column-header">
                > CATEGORIES
            </div>
            <div class="column-content" id="categories-list">
                <div class="loading">_loading_data...</div>
            </div>
        </div>

        <!-- Column 2: Articles List -->
        <div class="column column-articles">
            <div class="column-header" id="articles-header">
                > FEED
            </div>
            <div class="column-content" id="articles-list">
                <div class="empty-state">Select a category to view feed</div>
            </div>
        </div>

        <!-- Column 3: Article Preview -->
        <div class="column column-preview">
            <div class="column-header">
                > PREVIEW
            </div>
            <div class="column-content" id="article-preview">
                <div class="empty-state">
                    NO SIGNAL INPUT<br>
                    <span style="font-size:0.8em; opacity:0.5">Select an article to decrypt content</span>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/app.js?v=2"></script>
</body>
</html>
