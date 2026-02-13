import TomSelect from 'tom-select';

const defaultConfig = {
    create: false,
    sortField: { field: 'text', direction: 'asc' },
    maxOptions: null,
    placeholder: 'Pilih...',
    closeAfterSelect: true,
    plugins: ['remove_button', 'clear_button'],
    render: {
        no_results: function(data, escape) {
            return '<div class="no-results px-3 py-2 text-sm text-gray-500">Tidak ada hasil untuk "' + escape(data.input) + '"</div>';
        },
        option: function(data, escape) {
            const isSelected = this.items.indexOf(data.value) !== -1;
            return `<div class="px-3 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 flex items-center justify-between ${isSelected ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : ''}">
                <span>${escape(data.text)}</span>
                ${isSelected ? '<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : ''}
            </div>`;
        },
        item: function(data, escape) {
            return `<div class="text-sm">${escape(data.text)}</div>`;
        }
    }
};

window.initSelect = function(selector, options = {}) {
    return new TomSelect(selector, { ...defaultConfig, ...options });
};

window.initMultiSelect = function(selector, options = {}) {
    return new TomSelect(selector, {
        ...defaultConfig,
        plugins: ['remove_button', 'clear_button', 'checkbox_options'],
        maxItems: null,
        ...options
    });
};

window.initAjaxSelect = function(selector, url, options = {}) {
    return new TomSelect(selector, {
        ...defaultConfig,
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        load: function(query, callback) {
            if (!query.length) return callback();
            fetch(url + '?search=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(json => { callback(json.data || json); })
                .catch(() => { callback(); });
        },
        ...options
    });
};

window.initUserSelect = function(selector, options = {}) {
    return initAjaxSelect(selector, '/api/users/search', {
        valueField: 'id',
        labelField: 'name',
        searchField: ['name', 'email', 'nim'],
        render: {
            option: function(data, escape) {
                const isSelected = this.items.indexOf(data.id) !== -1;
                return `<div class="px-3 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 flex items-center justify-between ${isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : ''}">
                    <div>
                        <div class="font-medium text-sm">${escape(data.name)}</div>
                        <div class="text-xs text-gray-500">${escape(data.email || '')} ${data.nim ? '- ' + escape(data.nim) : ''}</div>
                    </div>
                    ${isSelected ? '<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : ''}
                </div>`;
            },
            item: function(data, escape) { return `<div class="text-sm">${escape(data.name)}</div>`; }
        },
        ...options
    });
};

window.initProductSelect = function(selector, options = {}) {
    return initAjaxSelect(selector, '/api/products/search', {
        valueField: 'id',
        labelField: 'name',
        searchField: ['name', 'sku'],
        render: {
            option: function(data, escape) {
                const isSelected = this.items.indexOf(data.id) !== -1;
                return `<div class="px-3 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 flex items-center justify-between ${isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : ''}">
                    <div>
                        <div class="font-medium text-sm">${escape(data.name)}</div>
                        <div class="text-xs text-gray-500">SKU: ${escape(data.sku || '-')} | Harga: Rp ${escape(data.price || 0)}</div>
                    </div>
                    ${isSelected ? '<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : ''}
                </div>`;
            },
            item: function(data, escape) { return `<div class="text-sm">${escape(data.name)}</div>`; }
        },
        ...options
    });
};

// Auto-init
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-select]').forEach(el => { initSelect(el); });
    document.querySelectorAll('[data-multiselect]').forEach(el => { initMultiSelect(el); });
    document.querySelectorAll('[data-user-select]').forEach(el => { initUserSelect(el); });
    document.querySelectorAll('[data-product-select]').forEach(el => { initProductSelect(el); });
});

document.addEventListener('livewire:navigated', function() {
    document.querySelectorAll('[data-select]').forEach(el => { if (!el.tomselect) initSelect(el); });
});

export { TomSelect };
