/**
 * UI Components Module
 * Reusable UI components and utilities
 */

class UI {
    /**
     * Show toast notification
     */
    static toast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-enter px-4 py-3 rounded-lg shadow-lg text-white ${
            type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
            type === 'warning' ? 'bg-yellow-600' :
            'bg-blue-600'
        }`;
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    /**
     * Show loading spinner
     */
    static showLoading(container, size = 'md') {
        const spinnerSize = size === 'sm' ? 'spinner-sm' : size === 'lg' ? 'spinner-lg' : 'spinner-md';
        container.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <div class="spinner ${spinnerSize}"></div>
            </div>
        `;
    }

    /**
     * Hide loading
     */
    static hideLoading(container) {
        container.innerHTML = '';
    }

    /**
     * Format date
     */
    static formatDate(dateString, format = 'relative') {
        const date = new Date(dateString);
        
        if (format === 'relative') {
            return this.timeAgo(date);
        }
        
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    /**
     * Time ago format
     */
    static timeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60,
        };

        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
            }
        }
        
        return 'Just now';
    }

    /**
     * Truncate text
     */
    static truncate(text, length = 100) {
        if (text.length <= length) return text;
        return text.substring(0, length) + '...';
    }

    /**
     * Escape HTML
     */
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Create post card HTML
     */
    static createPostCard(post) {
        return `
            <article class="card-hover group cursor-pointer" onclick="window.location.href='/pages/blog-detail.html?slug=${post.slug}'">
                ${post.featured_image ? `
                    <div class="aspect-video overflow-hidden">
                        <img src="${post.featured_image}" alt="${post.title}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                ` : ''}
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="badge-primary">${post.category?.name || 'Uncategorized'}</span>
                        <span class="text-xs text-gray-500">${this.formatDate(post.published_at)}</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors line-clamp-2">
                        ${this.escapeHtml(post.title)}
                    </h3>
                    <p class="text-gray-600 mb-4 line-clamp-2">${this.escapeHtml(post.excerpt || '')}</p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="avatar avatar-sm">
                                ${post.author?.avatar ? 
                                    `<img src="${post.author.avatar}" alt="${post.author.name}">` :
                                    `<span class="text-sm font-medium">${post.author?.name?.charAt(0) || '?'}</span>`
                                }
                            </div>
                            <span class="text-sm text-gray-600">${this.escapeHtml(post.author?.name || 'Anonymous')}</span>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                ${post.views_count || 0}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                ${post.comments_count || 0}
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        `;
    }

    /**
     * Create comment HTML
     */
    static createComment(comment, depth = 0) {
        const isReply = depth > 0;
        return `
            <div class="${isReply ? 'ml-8 mt-4' : ''}" data-comment-id="${comment.id}">
                <div class="flex gap-3">
                    <div class="avatar flex-shrink-0">
                        ${comment.author?.avatar ? 
                            `<img src="${comment.author.avatar}" alt="${comment.author.name}">` :
                            `<span class="text-sm font-medium text-gray-600">${comment.author?.name?.charAt(0) || '?'}</span>`
                        }
                    </div>
                    <div class="flex-1">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-gray-900">${this.escapeHtml(comment.author?.name || 'Anonymous')}</span>
                                <span class="text-xs text-gray-500">${this.formatDate(comment.created_at)}</span>
                            </div>
                            <p class="text-gray-700">${this.escapeHtml(comment.content)}</p>
                        </div>
                        <div class="flex items-center gap-4 mt-2 ml-2">
                            <button class="text-xs text-gray-500 hover:text-blue-600" onclick="replyToComment(${comment.id})">
                                Reply
                            </button>
                            ${comment.author?.id === authService.getUser()?.id ? `
                                <button class="text-xs text-gray-500 hover:text-blue-600" onclick="editComment(${comment.id})">
                                    Edit
                                </button>
                                <button class="text-xs text-gray-500 hover:text-red-600" onclick="deleteComment(${comment.id})">
                                    Delete
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
                ${comment.replies?.length ? `
                    <div class="replies">
                        ${comment.replies.map(reply => this.createComment(reply, depth + 1)).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    }

    /**
     * Create pagination HTML
     */
    static createPagination(meta, onPageChange) {
        if (meta.total_pages <= 1) return '';

        let html = '<nav class="flex items-center justify-center gap-2 mt-8" aria-label="Pagination">';
        
        // Previous button
        if (meta.current_page > 1) {
            html += `<button onclick="loadPage(${meta.current_page - 1})" class="px-3 py-2 text-sm border rounded-lg hover:bg-gray-50">Previous</button>`;
        }

        // Page numbers
        for (let i = 1; i <= meta.total_pages; i++) {
            if (i === 1 || i === meta.total_pages || (i >= meta.current_page - 1 && i <= meta.current_page + 1)) {
                html += `<button onclick="loadPage(${i})" class="px-3 py-2 text-sm border rounded-lg ${
                    i === meta.current_page ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-50'
                }">${i}</button>`;
            } else if (i === meta.current_page - 2 || i === meta.current_page + 2) {
                html += '<span class="px-2 text-gray-400">...</span>';
            }
        }

        // Next button
        if (meta.current_page < meta.total_pages) {
            html += `<button onclick="loadPage(${meta.current_page + 1})" class="px-3 py-2 text-sm border rounded-lg hover:bg-gray-50">Next</button>`;
        }

        html += '</nav>';
        return html;
    }
}

window.UI = UI;
