/**
 * Calandria RSS - Terminal Frontend
 * Handles 3-column layout interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // State
    const state = {
        currentCategory: null,
        currentArticle: null,
        articles: [],
        page: 1,
        loadingMore: false,
        hasMore: true
    };

    // DOM Elements
    const els = {
        categoriesList: document.getElementById('categories-list'),
        articlesList: document.getElementById('articles-list'),
        articlesHeader: document.getElementById('articles-header'),
        preview: document.getElementById('article-preview')
    };

    // Initialize
    init();

    function init() {
        loadCategories();
        // Load all articles initially
        loadArticles();

        // Infinite scroll listener
        els.articlesList.addEventListener('scroll', () => {
            if (state.loadingMore || !state.hasMore) return;

            const { scrollTop, scrollHeight, clientHeight } = els.articlesList;
            if (scrollTop + clientHeight >= scrollHeight - 100) {
                loadArticles(state.currentCategory, true);
            }
        });
    }

    // --- Data Fetching ---

    async function loadCategories() {
        try {
            const res = await fetch('/api/categories');
            const data = await res.json();
            renderCategories(data.data || []);
        } catch (error) {
            console.error('Failed to load categories:', error);
            els.categoriesList.innerHTML = '<div class="error">Error loading categories</div>';
        }
    }

    async function loadArticles(categoryId = null, append = false) {
        if (state.loadingMore) return;

        if (!append) {
            state.page = 1;
            state.hasMore = true;
            state.articles = [];
            els.articlesList.innerHTML = '<div class="loading">_fetching_feed...</div>';
            els.articlesList.scrollTop = 0;
        } else {
            state.page++;
            // Show a small loading indicator at the bottom
            const loader = document.createElement('div');
            loader.className = 'loading-mini';
            loader.id = 'infinite-loader';
            loader.textContent = '_scrolling_deeper...';
            els.articlesList.appendChild(loader);
        }

        state.loadingMore = true;

        try {
            let url = categoryId
                ? `/api/articles?category_id=${categoryId}&page=${state.page}`
                : `/api/articles?page=${state.page}`;

            const res = await fetch(url);
            const data = await res.json();

            // Remove mini loader
            document.getElementById('infinite-loader')?.remove();

            const newArticles = data.data || [];
            const pagination = data.pagination || {};

            if (append) {
                state.articles = [...state.articles, ...newArticles];
            } else {
                state.articles = newArticles;
            }

            state.hasMore = state.page < pagination.pages;

            renderArticles(newArticles, append);
        } catch (error) {
            console.error('Failed to load articles:', error);
            if (!append) {
                els.articlesList.innerHTML = '<div class="error">Error loading feed</div>';
            }
        } finally {
            state.loadingMore = false;
        }
    }

    // --- Rendering ---

    function renderCategories(categories) {
        let html = `
            <div class="category-item ${state.currentCategory === null ? 'active' : ''}" 
                 onclick="app.selectCategory(null)">
                <span>> ALL_FEEDS</span>
                <span class="category-count">*</span>
            </div>
        `;

        categories.forEach(cat => {
            html += `
                <div class="category-item" 
                     data-id="${cat.id}"
                     onclick="app.selectCategory(${cat.id}, this)">
                    <span>${cat.name}</span>
                    <span class="category-count">${cat.article_count || 0}</span>
                </div>
            `;
        });

        els.categoriesList.innerHTML = html;
    }

    function renderArticles(articles, append = false) {
        if (!append && articles.length === 0) {
            els.articlesList.innerHTML = '<div class="empty-state">No articles found in this sector</div>';
            return;
        }

        let html = '';
        articles.forEach(article => {
            const date = new Date(article.published_at).toLocaleDateString();
            const time = new Date(article.published_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            html += `
                <div class="article-item" 
                     data-id="${article.id}"
                     onclick="app.selectArticle(${article.id})">
                    <div class="article-meta">
                        <span class="article-source">[${article.source_name}]</span>
                        <span>${date} ${time}</span>
                    </div>
                    <div class="article-title">${article.title}</div>
                </div>
            `;
        });

        if (append) {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            while (temp.firstChild) {
                els.articlesList.appendChild(temp.firstChild);
            }
        } else {
            els.articlesList.innerHTML = html;
        }
    }

    function renderPreview(article) {
        if (!article) {
            els.preview.innerHTML = `
                <div class="empty-state">
                    NO SIGNAL INPUT<br>
                    <span style="font-size:0.8em; opacity:0.5">Select an article to decrypt content</span>
                </div>
            `;
            return;
        }

        const date = new Date(article.published_at).toLocaleString();

        els.preview.innerHTML = `
            <div class="preview-container">
                <div class="preview-header">
                    <div class="preview-title">${article.title}</div>
                    <div class="preview-meta">
                        SOURCE: <span style="color: var(--terminal-accent)">${article.source_name}</span><br>
                        RECEIVED: ${date}<br>
                        ID: #00${article.id}
                    </div>
                </div>
                
                <div class="preview-body">
                    <div class="preview-full-content">
                        ${article.content || article.description || '<em>No content available</em>'}
                    </div>
                </div>
                
                <div class="preview-actions">
                    <a href="${article.url}" target="_blank" class="btn-terminal">
                        > ACCESS_ORIGINAL_SOURCE
                    </a>
                </div>
            </div>
        `;
    }

    // --- Actions ---

    window.app = {
        selectCategory: (id, element) => {
            if (state.currentCategory === id && id !== null) return;

            state.currentCategory = id;

            // Update UI
            document.querySelectorAll('.category-item').forEach(el => el.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            } else {
                // "All" selected
                els.categoriesList.firstElementChild.classList.add('active');
            }

            // Update Header
            els.articlesHeader.textContent = id ? `> FEED :: CATEGORY_${id}` : '> FEED :: ALL_SOURCES';

            // Reset preview
            state.currentArticle = null;
            renderPreview(null);

            // Load articles
            loadArticles(id);
        },

        selectArticle: (id) => {
            state.currentArticle = id;

            // Update UI
            document.querySelectorAll('.article-item').forEach(el => el.classList.remove('active'));
            document.querySelector(`.article-item[data-id="${id}"]`)?.classList.add('active');

            // Find and render article
            const article = state.articles.find(a => a.id === id);
            renderPreview(article);
        }
    };
});

