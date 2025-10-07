/**
 * Page Builder - JavaScript Core
 * Sistema modulare per costruzione pagine con drag & drop
 */

class PageBuilderApp {
    constructor(rootElement) {
        this.root = rootElement;
        this.apiBase = this.root.dataset.apiBase || 'api/page_builder.php';
        this.viewUrl = this.root.dataset.viewUrl || '../index.php?id_pagina=';
        this.currentPageId = null;
        this.currentPage = null;
        this.availableModules = [];
        this.moduleInstances = [];
        this.selectedInstance = null;
        this.sortable = null;
        
        // DOM elements
        this.sidebar = null;
        this.workspace = null;
        this.configPanel = null;
        this.moduleLibrary = null;
        this.canvas = null;
        this.pageSelect = null;
        this.configForm = null;
        
        // Initialize
        this.init();
    }

    init() {
        this.parseInitialData();
        this.bindEvents();
        this.renderPageOptions();
        this.renderModuleLibrary();
        this.renderCanvas();
        this.setupSortable();
    }

    parseInitialData() {
        const initialData = this.root.dataset.initial;
        if (initialData) {
            try {
                const data = JSON.parse(initialData);
                this.currentPageId = data.currentPageId || null;
                this.currentPage = data.currentPage || null;
                this.availableModules = data.availableModules || [];
                this.moduleInstances = data.moduleInstances || [];
            } catch (e) {
                console.error('Errore nel parsing dei dati iniziali:', e);
            }
        }
    }

    bindEvents() {
        // Page selection
        this.pageSelect = this.root.querySelector('.sidebar__select[name="page_id"]');
        if (this.pageSelect) {
            this.pageSelect.addEventListener('change', (e) => {
                this.loadPage(parseInt(e.target.value));
            });
        }

        // Add module buttons
        this.root.addEventListener('click', (e) => {
            if (e.target.matches('[data-module-name]')) {
                this.handleAddModule(e.target.dataset.moduleName);
            }
        });

        // Instance actions
        this.root.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="select"]')) {
                const instanceId = parseInt(e.target.closest('[data-instance-id]').dataset.instanceId);
                this.selectInstance(instanceId);
            } else if (e.target.matches('[data-action="delete"]')) {
                const instanceId = parseInt(e.target.closest('[data-instance-id]').dataset.instanceId);
                this.deleteInstance(instanceId);
            } else if (e.target.matches('[data-action="preview"]')) {
                const instanceId = parseInt(e.target.closest('[data-instance-id]').dataset.instanceId);
                this.previewInstance(instanceId);
            }
        });

        // Config form
        this.root.addEventListener('submit', (e) => {
            if (e.target.matches('.config-form')) {
                e.preventDefault();
                this.saveCurrentInstance();
            }
        });

        // View page button
        const viewBtn = this.root.querySelector('[data-action="view-page"]');
        if (viewBtn) {
            viewBtn.addEventListener('click', () => {
                if (this.currentPageId) {
                    window.open(this.viewUrl + this.currentPageId, '_blank');
                }
            });
        }

        // Responsive toggles
        const sidebarToggle = this.root.querySelector('[data-action="toggle-sidebar"]');
        const configToggle = this.root.querySelector('[data-action="toggle-config"]');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }
        if (configToggle) {
            configToggle.addEventListener('click', () => this.toggleConfig());
        }

        // Close overlays
        this.root.addEventListener('click', (e) => {
            if (e.target.matches('.page-builder__overlay')) {
                this.closeSidebar();
                this.closeConfig();
            }
        });
    }

    async loadPage(pageId) {
        try {
            const response = await this.apiRequest('GET', 'page', { page_id: pageId });
            if (response.success) {
                this.currentPageId = pageId;
                this.currentPage = response.page;
                this.moduleInstances = response.instances || [];
                this.renderCanvas();
                this.flashMessage('Pagina caricata con successo', 'success');
            }
        } catch (error) {
            this.flashMessage('Errore nel caricamento della pagina: ' + error.message, 'error');
        }
    }

    renderPageOptions() {
        if (!this.pageSelect) return;
        
        // Get pages from initial data or make API call
        const pages = window.pageBuilderData?.pages || [];
        
        this.pageSelect.innerHTML = '<option value="">Seleziona una pagina...</option>';
        pages.forEach(page => {
            const option = document.createElement('option');
            option.value = page.id;
            option.textContent = page.title;
            if (page.id === this.currentPageId) {
                option.selected = true;
            }
            this.pageSelect.appendChild(option);
        });
    }

    renderModuleLibrary() {
        this.moduleLibrary = this.root.querySelector('.module-library');
        if (!this.moduleLibrary) return;

        this.moduleLibrary.innerHTML = '';
        this.availableModules.forEach(module => {
            const card = document.createElement('div');
            card.className = 'module-card';
            card.dataset.moduleName = module.name;
            card.innerHTML = `
                <div class="module-card__title">
                    <i class="${module.icon || 'fa-solid fa-cube'}"></i>
                    ${module.display_name || module.name}
                </div>
                <div class="module-card__meta">
                    <span class="module-card__badge">${module.version || '1.0'}</span>
                </div>
                <div class="module-card__description">${module.description || 'Modulo personalizzabile'}</div>
            `;
            this.moduleLibrary.appendChild(card);
        });
    }

    renderCanvas() {
        this.canvas = this.root.querySelector('.page-builder__canvas');
        if (!this.canvas) return;

        if (this.moduleInstances.length === 0) {
            this.canvas.innerHTML = `
                <div class="page-builder__empty">
                    <i class="fa-solid fa-plus-circle"></i>
                    <h3>Nessun modulo presente</h3>
                    <p>Seleziona un modulo dalla libreria per iniziare a costruire la tua pagina</p>
                </div>
            `;
            return;
        }

        this.canvas.innerHTML = '';
        this.moduleInstances.forEach((instance, index) => {
            const element = this.createInstanceElement(instance, index);
            this.canvas.appendChild(element);
        });

        this.setupSortable();
    }

    createInstanceElement(instance, index) {
        const element = document.createElement('div');
        element.className = 'module-instance';
        element.dataset.instanceId = instance.id;
        element.dataset.orderIndex = index;
        
        const moduleInfo = this.availableModules.find(m => m.name === instance.module_name);
        
        element.innerHTML = `
            <div class="module-instance__header">
                <div class="module-instance__title">
                    <div class="module-instance__label">${moduleInfo?.display_name || instance.module_name}</div>
                    <div class="module-instance__name">${instance.instance_name || 'Istanza ' + (index + 1)}</div>
                </div>
                <div class="module-instance__actions">
                    <button class="btn btn--icon" data-action="preview" title="Anteprima">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button class="btn btn--icon" data-action="delete" title="Elimina">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="module-instance__content">
                <div class="pb-module-placeholder">
                    Modulo: ${moduleInfo?.display_name || instance.module_name}
                </div>
            </div>
        `;

        element.addEventListener('click', (e) => {
            if (!e.target.closest('[data-action]')) {
                this.selectInstance(instance.id);
            }
        });

        return element;
    }

    selectInstance(instanceId) {
        // Remove previous selection
        this.root.querySelectorAll('.module-instance').forEach(el => {
            el.classList.remove('is-selected');
        });

        // Add selection to new instance
        const instanceElement = this.root.querySelector(`[data-instance-id="${instanceId}"]`);
        if (instanceElement) {
            instanceElement.classList.add('is-selected');
            this.selectedInstance = this.moduleInstances.find(i => i.id === instanceId);
            this.loadConfigForInstance(this.selectedInstance);
        }
    }

    async loadConfigForInstance(instance) {
        if (!instance) return;

        try {
            const response = await this.apiRequest('POST', 'get-module-config', {
                instance_id: instance.id
            });

            if (response.success) {
                this.renderConfigForm(response.config, response.ui_schema);
            }
        } catch (error) {
            this.flashMessage('Errore nel caricamento configurazione: ' + error.message, 'error');
        }
    }

    renderConfigForm(config, uiSchema) {
        this.configPanel = this.root.querySelector('.config-panel__body');
        if (!this.configPanel) return;

        this.configForm = new ConfigForm(config, uiSchema);
        this.configPanel.innerHTML = this.configForm.render();
    }

    async saveCurrentInstance() {
        if (!this.selectedInstance || !this.configForm) return;

        try {
            const formData = this.configForm.getFormData();
            const response = await this.apiRequest('POST', 'save-instance', {
                instance_id: this.selectedInstance.id,
                config: formData
            });

            if (response.success) {
                this.flashMessage('Configurazione salvata con successo', 'success');
                // Update instance in memory
                const instanceIndex = this.moduleInstances.findIndex(i => i.id === this.selectedInstance.id);
                if (instanceIndex !== -1) {
                    this.moduleInstances[instanceIndex].config = formData;
                }
            }
        } catch (error) {
            this.flashMessage('Errore nel salvataggio: ' + error.message, 'error');
        }
    }

    async previewInstance(instanceId) {
        const instance = this.moduleInstances.find(i => i.id === instanceId);
        if (!instance) return;

        try {
            const response = await this.apiRequest('POST', 'preview-instance', {
                instance_id: instanceId
            });

            if (response.success) {
                this.openModal('Anteprima Modulo', response.html);
            }
        } catch (error) {
            this.flashMessage('Errore nell\'anteprima: ' + error.message, 'error');
        }
    }

    async deleteInstance(instanceId) {
        if (!confirm('Sei sicuro di voler eliminare questo modulo?')) return;

        try {
            const response = await this.apiRequest('POST', 'delete-instance', {
                instance_id: instanceId
            });

            if (response.success) {
                this.moduleInstances = this.moduleInstances.filter(i => i.id !== instanceId);
                this.renderCanvas();
                this.flashMessage('Modulo eliminato con successo', 'success');
                
                if (this.selectedInstance && this.selectedInstance.id === instanceId) {
                    this.selectedInstance = null;
                    this.configPanel.innerHTML = '<div class="config-panel__placeholder">Seleziona un modulo per configurarlo</div>';
                }
            }
        } catch (error) {
            this.flashMessage('Errore nell\'eliminazione: ' + error.message, 'error');
        }
    }

    async handleAddModule(moduleName) {
        if (!this.currentPageId) {
            this.flashMessage('Seleziona prima una pagina', 'error');
            return;
        }

        const instanceName = this.generateInstanceName(moduleName);

        try {
            const response = await this.apiRequest('POST', 'save-instance', {
                page_id: this.currentPageId,
                module_name: moduleName,
                instance_name: instanceName,
                config: {}
            });

            if (response.success) {
                this.moduleInstances.push(response.instance);
                this.renderCanvas();
                this.selectInstance(response.instance.id);
                this.flashMessage('Modulo aggiunto con successo', 'success');
            }
        } catch (error) {
            this.flashMessage('Errore nell\'aggiunta del modulo: ' + error.message, 'error');
        }
    }

    generateInstanceName(moduleName) {
        const existingNames = this.moduleInstances
            .filter(i => i.module_name === moduleName)
            .map(i => i.instance_name);
        
        let counter = 1;
        let name = `${moduleName}_${counter}`;
        
        while (existingNames.includes(name)) {
            counter++;
            name = `${moduleName}_${counter}`;
        }
        
        return name;
    }

    setupSortable() {
        if (this.sortable) {
            this.sortable.destroy();
        }

        if (this.canvas && this.moduleInstances.length > 0) {
            this.sortable = Sortable.create(this.canvas, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: (evt) => {
                    this.persistOrder();
                }
            });
        }
    }

    async persistOrder() {
        const order = Array.from(this.canvas.children).map((el, index) => ({
            instance_id: parseInt(el.dataset.instanceId),
            order_index: index
        }));

        try {
            const response = await this.apiRequest('POST', 'update-order', { order });
            if (response.success) {
                // Update instances order in memory
                this.moduleInstances.sort((a, b) => {
                    const aOrder = order.find(o => o.instance_id === a.id)?.order_index || 0;
                    const bOrder = order.find(o => o.instance_id === b.id)?.order_index || 0;
                    return aOrder - bOrder;
                });
            }
        } catch (error) {
            this.flashMessage('Errore nel salvataggio ordine: ' + error.message, 'error');
        }
    }

    async refreshModules() {
        try {
            const response = await this.apiRequest('GET', 'modules');
            if (response.success) {
                this.availableModules = response.modules;
                this.renderModuleLibrary();
            }
        } catch (error) {
            this.flashMessage('Errore nel refresh moduli: ' + error.message, 'error');
        }
    }

    openModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'pb-modal';
        modal.innerHTML = `
            <div class="pb-modal__dialog">
                <div class="pb-modal__header">
                    <h3>${title}</h3>
                    <button class="btn btn--icon" data-action="close-modal">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="pb-modal__body">
                    ${content}
                </div>
            </div>
        `;

        modal.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="close-modal"]') || e.target.matches('.pb-modal')) {
                modal.remove();
            }
        });

        document.body.appendChild(modal);
    }

    toggleSidebar() {
        this.sidebar = this.root.querySelector('.page-builder__sidebar');
        if (this.sidebar) {
            this.sidebar.classList.toggle('is-open');
        }
    }

    toggleConfig() {
        this.configPanel = this.root.querySelector('.config-panel');
        if (this.configPanel) {
            this.configPanel.classList.toggle('is-open');
        }
    }

    closeSidebar() {
        this.sidebar = this.root.querySelector('.page-builder__sidebar');
        if (this.sidebar) {
            this.sidebar.classList.remove('is-open');
        }
    }

    closeConfig() {
        this.configPanel = this.root.querySelector('.config-panel');
        if (this.configPanel) {
            this.configPanel.classList.remove('is-open');
        }
    }

    flashMessage(message, type = 'info') {
        const flash = document.createElement('div');
        flash.className = `pb-flash pb-flash--${type}`;
        flash.textContent = message;

        // Remove existing flashes
        this.root.querySelectorAll('.pb-flash').forEach(el => el.remove());

        // Add new flash
        this.root.insertBefore(flash, this.root.firstChild);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (flash.parentNode) {
                flash.remove();
            }
        }, 5000);
    }

    async apiRequest(method, action, data = {}) {
        const url = new URL(this.apiBase, window.location.origin);
        url.searchParams.set('action', action);

        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (method === 'POST' && Object.keys(data).length > 0) {
            options.body = JSON.stringify(data);
        } else if (method === 'GET' && Object.keys(data).length > 0) {
            Object.keys(data).forEach(key => {
                url.searchParams.set(key, data[key]);
            });
        }

        const response = await fetch(url.toString(), options);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Errore nella richiesta API');
        }

        return result;
    }
}

class ConfigForm {
    constructor(config, uiSchema) {
        this.config = config || {};
        this.uiSchema = uiSchema || {};
    }

    render() {
        const form = document.createElement('form');
        form.className = 'config-form';
        form.innerHTML = this.renderFields();
        return form.outerHTML;
    }

    renderFields() {
        let html = '';

        Object.keys(this.uiSchema).forEach(fieldName => {
            const fieldSchema = this.uiSchema[fieldName];
            const fieldValue = this.config[fieldName] || fieldSchema.default || '';

            html += this.renderField(fieldName, fieldSchema, fieldValue);
        });

        html += `
            <div class="pb-form__actions">
                <button type="submit" class="btn btn--primary">
                    <i class="fa-solid fa-save"></i>
                    Salva
                </button>
                <button type="button" class="btn btn--ghost" data-action="preview-current">
                    <i class="fa-solid fa-eye"></i>
                    Anteprima
                </button>
            </div>
        `;

        return html;
    }

    renderField(fieldName, schema, value) {
        const { type, label, description, options, min, max } = schema;
        
        let html = `
            <div class="pb-field">
                <label for="config_${fieldName}">${label || fieldName}</label>
        `;

        if (description) {
            html += `<div class="pb-field__help">${description}</div>`;
        }

        switch (type) {
            case 'text':
            case 'email':
            case 'url':
                html += `<input type="${type}" id="config_${fieldName}" name="${fieldName}" value="${this.escapeHtml(value)}" />`;
                break;

            case 'number':
                html += `<input type="number" id="config_${fieldName}" name="${fieldName}" value="${value}" ${min !== undefined ? `min="${min}"` : ''} ${max !== undefined ? `max="${max}"` : ''} />`;
                break;

            case 'textarea':
                html += `<textarea id="config_${fieldName}" name="${fieldName}" rows="4">${this.escapeHtml(value)}</textarea>`;
                break;

            case 'select':
                html += `<select id="config_${fieldName}" name="${fieldName}">`;
                if (options && Array.isArray(options)) {
                    options.forEach(option => {
                        const selected = option.value === value ? 'selected' : '';
                        html += `<option value="${option.value}" ${selected}>${option.label}</option>`;
                    });
                }
                html += `</select>`;
                break;

            case 'checkbox':
                const checked = value ? 'checked' : '';
                html += `<input type="checkbox" id="config_${fieldName}" name="${fieldName}" ${checked} />`;
                break;

            case 'array':
                html += this.renderArrayField(fieldName, schema, value);
                break;

            default:
                html += `<input type="text" id="config_${fieldName}" name="${fieldName}" value="${this.escapeHtml(value)}" />`;
        }

        html += '</div>';
        return html;
    }

    renderArrayField(fieldName, schema, value) {
        const items = Array.isArray(value) ? value : [];
        const itemSchema = schema.items || {};

        let html = `
            <div class="pb-array">
                <div class="pb-array__header">
                    <span>Elementi</span>
                    <button type="button" class="btn btn--ghost btn--small" data-action="add-array-item" data-field="${fieldName}">
                        <i class="fa-solid fa-plus"></i>
                        Aggiungi
                    </button>
                </div>
                <div class="pb-array__items" data-field="${fieldName}">
        `;

        items.forEach((item, index) => {
            html += this.renderArrayItem(fieldName, index, itemSchema, item);
        });

        html += '</div></div>';
        return html;
    }

    renderArrayItem(fieldName, index, itemSchema, value) {
        let html = `
            <div class="pb-array-item" data-index="${index}">
                <div class="pb-array-item__header">
                    <span>Elemento ${index + 1}</span>
                    <button type="button" class="btn btn--icon btn--small" data-action="remove-array-item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="pb-array-item__fields">
        `;

        Object.keys(itemSchema).forEach(subFieldName => {
            const subFieldSchema = itemSchema[subFieldName];
            const subFieldValue = value[subFieldName] || subFieldSchema.default || '';
            
            html += `
                <div class="pb-field">
                    <label>${subFieldSchema.label || subFieldName}</label>
                    <input type="text" name="${fieldName}[${index}][${subFieldName}]" value="${this.escapeHtml(subFieldValue)}" />
                </div>
            `;
        });

        html += '</div></div>';
        return html;
    }

    getFormData() {
        const form = this.root?.querySelector('.config-form');
        if (!form) return {};

        const formData = new FormData(form);
        const data = {};

        for (const [key, value] of formData.entries()) {
            if (key.includes('[') && key.includes(']')) {
                // Handle array fields
                this.setNestedValue(data, key, value);
            } else {
                data[key] = value;
            }
        }

        return data;
    }

    setNestedValue(obj, path, value) {
        const keys = path.split(/[\[\]]/).filter(Boolean);
        let current = obj;

        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            if (!current[key]) {
                current[key] = {};
            }
            current = current[key];
        }

        current[keys[keys.length - 1]] = value;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('page-builder-root');
    if (root) {
        window.pageBuilderApp = new PageBuilderApp(root);
    }
});
