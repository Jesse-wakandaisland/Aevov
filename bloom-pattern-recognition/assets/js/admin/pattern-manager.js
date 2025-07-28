// 2. /assets/js/admin/pattern-manager.js
// Pattern management interface functionality

const BloomPatternManager = {
    patternList: null,
    patternFilter: null,
    currentPage: 1,
    itemsPerPage: 20,

    init: function() {
        this.patternList = document.getElementById('pattern-list');
        this.patternFilter = document.getElementById('pattern-filter');
        this.bindEvents();
        this.loadPatterns();
    },

    bindEvents: function() {
        // Pattern filtering
        this.patternFilter.addEventListener('change', () => {
            this.currentPage = 1;
            this.loadPatterns();
        });

        // Pagination
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.currentPage = parseInt(e.target.dataset.page);
                this.loadPatterns();
            });
        });

        // Pattern actions
        this.patternList.addEventListener('click', (e) => {
            if (e.target.classList.contains('pattern-action')) {
                this.handlePatternAction(e);
            }
        });
    },

    loadPatterns: function() {
        const params = new URLSearchParams({
            action: 'bloom_get_patterns',
            nonce: bloomAdmin.nonce,
            page: this.currentPage,
            per_page: this.itemsPerPage,
            filter: this.patternFilter.value
        });

        fetch(bloomAdmin.ajaxUrl + '?' + params)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderPatterns(data.data);
                }
            })
            .catch(error => {
                console.error('Error loading patterns:', error);
            });
    },

    renderPatterns: function(data) {
        this.patternList.innerHTML = data.patterns.map(pattern => `
            <tr>
                <td>${pattern.pattern_hash}</td>
                <td>${pattern.type}</td>
                <td>${pattern.confidence.toFixed(2)}</td>
                <td>${pattern.created_at}</td>
                <td>
                    <button class="pattern-action" data-action="view" data-id="${pattern.id}">View</button>
                    <button class="pattern-action" data-action="analyze" data-id="${pattern.id}">Analyze</button>
                </td>
            </tr>
        `).join('');

        this.updatePagination(data.total_pages);
    },

    handlePatternAction: function(e) {
        const action = e.target.dataset.action;
        const patternId = e.target.dataset.id;

        switch (action) {
            case 'view':
                this.viewPattern(patternId);
                break;
            case 'analyze':
                this.analyzePattern(patternId);
                break;
        }
    },

    viewPattern: function(patternId) {
        // Implementation for viewing pattern details
    },

    analyzePattern: function(patternId) {
        // Implementation for pattern analysis
    },

    updatePagination: function(totalPages) {
        // Update pagination UI
    }
};

// Initialize pattern manager when document is ready
document.addEventListener('DOMContentLoaded', () => {
    BloomPatternManager.init();
});
