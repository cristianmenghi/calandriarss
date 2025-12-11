document.addEventListener('DOMContentLoaded', () => {
    let page = 1;
    let isLoading = false;
    let currentFilters = {};
    const container = document.getElementById('news-container');
    const loading = document.getElementById('loading');

    // Load initial data
    loadSources();
    loadArticles();

    // Infinite Scroll
    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500 && !isLoading) {
            loadArticles();
        }
    });

    // Filters
    document.getElementById('source-filter').addEventListener('change', (e) => {
        currentFilters.source_id = e.target.value;
        resetFeed();
    });

    document.getElementById('search-input').addEventListener('input', debounce((e) => {
        currentFilters.search = e.target.value;
        resetFeed();
    }, 500));

    async function loadSources() {
        const res = await fetch('/api/sources');
        const data = await res.json();
        const select = document.getElementById('source-filter');
        data.data.forEach(source => {
            const option = document.createElement('option');
            option.value = source.id;
            option.textContent = source.name;
            select.appendChild(option);
        });
    }

    async function loadArticles() {
        isLoading = true;
        loading.style.display = 'block';

        const params = new URLSearchParams({ page, ...currentFilters });
        const res = await fetch(`/api/articles?${params}`);
        const data = await res.json();

        if (data.data.length === 0) {
            loading.style.display = 'none'; // End of results
            return;
        }

        data.data.forEach(renderArticle);
        page++;
        isLoading = false;
        loading.style.display = 'none';
    }

    function renderArticle(article) {
        const card = document.createElement('div');
        card.className = 'article-card';
        card.innerHTML = `
            <div class="article-image" style="background-image: url('${article.image_url || '/assets/images/placeholder.png'}')"></div>
            <div class="article-content">
                <div class="article-meta">
                    <img src="${article.source_logo || ''}" class="source-icon" onerror="this.style.display='none'">
                    <span>${article.source_name}</span>
                    <span>•</span>
                    <span>${new Date(article.published_at).toLocaleDateString()}</span>
                </div>
                <h3>${article.title}</h3>
                <p>${article.description ? article.description.substring(0, 150) + '...' : ''}</p>
            </div>
        `;
        card.addEventListener('click', () => openModal(article));
        container.appendChild(card);
    }

    function resetFeed() {
        container.innerHTML = '';
        page = 1;
        loadArticles();
    }

    // Modal Logic
    const modal = document.getElementById('article-modal');
    const closeBtn = document.querySelector('.close-modal');

    function openModal(article) {
        document.getElementById('modal-title').textContent = article.title;
        document.getElementById('modal-source').textContent = article.source_name;
        document.getElementById('modal-date').textContent = new Date(article.published_at).toLocaleDateString();
        document.getElementById('modal-body').innerHTML = article.content || article.description;
        document.getElementById('modal-link').href = article.url;
        modal.classList.remove('hidden');
    }

    closeBtn.onclick = () => modal.classList.add('hidden');
    window.onclick = (e) => {
        if (e.target == modal) modal.classList.add('hidden');
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
});
