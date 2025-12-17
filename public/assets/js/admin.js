/**
 * Calandria RSS - Admin Panel JavaScript
 * Handles CRUD operations for sources, categories, and users
 */

class AdminPanel {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.currentView = 'sources';
        this.init();
    }

    init() {
        this.setupEventListeners();
        // Don't load data here - wait for currentView to be set by page
    }

    setupEventListeners() {
        // Add buttons
        document.querySelectorAll('[data-action="add"]').forEach(btn => {
            btn.addEventListener('click', (e) => this.showAddForm(e.target.dataset.type));
        });

        // Refresh buttons
        document.querySelectorAll('[data-action="refresh"]').forEach(btn => {
            btn.addEventListener('click', () => this.loadData());
        });
    }

    setView(view) {
        this.currentView = view;
        this.loadData();
    }

    async loadData() {
        const view = this.currentView;
        const endpoint = `/api/admin/${view}`;

        try {
            const response = await fetch(endpoint);
            const data = await response.json();
            this.renderTable(view, data.data || data);
        } catch (error) {
            this.showError('Failed to load data: ' + error.message);
        }
    }

    renderTable(type, items) {
        const container = document.getElementById(`${type}-table`);
        if (!container) return;

        if (items.length === 0) {
            container.innerHTML = '<div class="empty-state">No items found</div>';
            return;
        }

        let html = '<table class="data-table"><thead><tr>';

        // Headers based on type
        if (type === 'sources') {
            html += '<th>Name</th><th>URL</th><th>Category</th><th>Status</th><th>Articles</th><th>Actions</th>';
        } else if (type === 'categories') {
            html += '<th>Name</th><th>Slug</th><th>Icon</th><th>Color</th><th>Articles</th><th>Actions</th>';
        } else if (type === 'users') {
            html += '<th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th>';
        }

        html += '</tr></thead><tbody>';

        items.forEach(item => {
            html += '<tr>';

            if (type === 'sources') {
                html += `
                    <td>${this.escape(item.name)}</td>
                    <td><a href="${this.escape(item.rss_feed_url)}" target="_blank" style="color: var(--terminal-accent);">Feed</a></td>
                    <td>${this.escape(item.category || 'N/A')}</td>
                    <td><span class="status-badge ${item.is_active ? 'active' : 'inactive'}">${item.is_active ? 'active' : 'inactive'}</span></td>
                    <td>${item.article_count || 0}</td>
                `;
            } else if (type === 'categories') {
                html += `
                    <td>${this.escape(item.name)}</td>
                    <td>${this.escape(item.slug)}</td>
                    <td>${item.icon || '📁'}</td>
                    <td><span style="color: ${item.color || '#fff'}">${item.color || 'N/A'}</span></td>
                    <td>${item.article_count || 0}</td>
                `;
            } else if (type === 'users') {
                html += `
                    <td>${this.escape(item.username)}</td>
                    <td>${this.escape(item.email)}</td>
                    <td><span style="color: var(--terminal-accent)">${this.escape(item.role)}</span></td>
                    <td><span class="status-badge ${item.is_active ? 'active' : 'inactive'}">${item.is_active ? 'active' : 'inactive'}</span></td>
                    <td>${item.last_login_at ? new Date(item.last_login_at).toLocaleString() : 'Never'}</td>
                `;
            }

            html += `
                <td class="action-buttons">
                    <button class="action-btn" onclick="admin.edit('${type}', ${item.id})">Edit</button>
                    <button class="action-btn danger" onclick="admin.delete('${type}', ${item.id})">Delete</button>
                </td>
            `;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    showAddForm(type) {
        const modal = this.createModal(type);
        document.body.appendChild(modal);
    }

    createModal(type, item = null) {
        const isEdit = item !== null;
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 style="color: var(--terminal-accent);">${isEdit ? 'Edit' : 'Add'} ${type.slice(0, -1)}</h2>
                    <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">×</button>
                </div>
                <form id="crud-form" class="crud-form">
                    ${this.getFormFields(type, item)}
                    <div class="form-actions" style="margin-top: 2rem;">
                        <button type="submit" class="terminal-button">${isEdit ? 'Update' : 'Create'}</button>
                        <button type="button" class="terminal-button" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                    </div>
                </form>
            </div>
        `;

        modal.querySelector('form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm(type, item?.id);
        });

        return modal;
    }

    getFormFields(type, item) {
        let fields = '';

        if (type === 'sources') {
            fields = `
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Name:</label>
                    <input type="text" name="name" class="terminal-input" value="${item?.name || ''}" required>
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> RSS Feed URL:</label>
                    <input type="url" name="rss_feed_url" class="terminal-input" value="${item?.rss_feed_url || ''}" required>
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Category ID:</label>
                    <input type="number" name="category_id" class="terminal-input" value="${item?.category_id || ''}">
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Fetch Interval (minutes):</label>
                    <input type="number" name="fetch_interval" class="terminal-input" value="${item?.fetch_interval || 60}">
                </div>
                <div class="form-group">
                    <label class="prompt">
                        <input type="checkbox" name="is_active" ${item?.is_active ? 'checked' : 'checked'}>
                        Active
                    </label>
                </div>
            `;
        } else if (type === 'categories') {
            fields = `
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Name:</label>
                    <input type="text" name="name" class="terminal-input" value="${item?.name || ''}" required>
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Slug:</label>
                    <input type="text" name="slug" class="terminal-input" value="${item?.slug || ''}" required>
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Description:</label>
                    <textarea name="description" class="terminal-input" rows="3">${item?.description || ''}</textarea>
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Icon (emoji):</label>
                    <input type="text" name="icon" class="terminal-input" value="${item?.icon || '📁'}" maxlength="2">
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Color (hex):</label>
                    <input type="color" name="color" class="terminal-input" value="${item?.color || '#ffffff'}">
                </div>
            `;
        } else if (type === 'users') {
            fields = `
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Username:</label>
                    <input type="text" name="username" class="terminal-input" value="${item?.username || ''}" required>
                </div>
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Email:</label>
                    <input type="email" name="email" class="terminal-input" value="${item?.email || ''}" required>
                </div>
                ${!item ? `
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Password:</label>
                    <input type="password" name="password" class="terminal-input" required>
                </div>
                ` : ''}
                <div class="form-group">
                    <label class="prompt"><span class="prompt-symbol">></span> Role:</label>
                    <select name="role" class="terminal-input" required>
                        <option value="moderator" ${item?.role === 'moderator' ? 'selected' : ''}>Moderator</option>
                        <option value="admin" ${item?.role === 'admin' ? 'selected' : ''}>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="prompt">
                        <input type="checkbox" name="is_active" ${item?.is_active ? 'checked' : 'checked'}>
                        Active
                    </label>
                </div>
            `;
        }

        return fields;
    }

    async submitForm(type, id = null) {
        const form = document.getElementById('crud-form');
        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            if (key === 'is_active') {
                data[key] = form.querySelector(`[name="${key}"]`).checked;
            } else {
                data[key] = value;
            }
        });

        const method = id ? 'PUT' : 'POST';
        const endpoint = id ? `/api/admin/${type}/${id}` : `/api/admin/${type}`;

        try {
            const response = await fetch(endpoint, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                this.showSuccess(`${type.slice(0, -1)} ${id ? 'updated' : 'created'} successfully`);
                document.querySelector('.modal-overlay')?.remove();
                this.loadData();
            } else {
                this.showError(result.error || 'Operation failed');
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        }
    }

    async edit(type, id) {
        try {
            const response = await fetch(`/api/admin/${type}`);
            const data = await response.json();
            const item = (data.data || data).find(i => i.id == id);

            if (item) {
                const modal = this.createModal(type, item);
                document.body.appendChild(modal);
            }
        } catch (error) {
            this.showError('Failed to load item: ' + error.message);
        }
    }

    async delete(type, id) {
        if (!confirm(`Are you sure you want to delete this ${type.slice(0, -1)}?`)) {
            return;
        }

        try {
            const response = await fetch(`/api/admin/${type}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            const result = await response.json();

            if (response.ok) {
                this.showSuccess(`${type.slice(0, -1)} deleted successfully`);
                this.loadData();
            } else {
                this.showError(result.error || 'Delete failed');
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            background: ${type === 'error' ? 'var(--terminal-error)' : 'var(--terminal-accent)'};
            color: var(--terminal-bg);
            border-radius: 4px;
            z-index: 10000;
            font-family: var(--font-mono);
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    escape(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// Initialize admin panel
let admin;
document.addEventListener('DOMContentLoaded', () => {
    admin = new AdminPanel();
});
