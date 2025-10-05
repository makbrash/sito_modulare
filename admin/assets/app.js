const bootstrap = window.ADMIN_BOOTSTRAP || {};
const state = {
    pages: bootstrap.pages || [],
    modules: bootstrap.modules || [],
    content: bootstrap.content || [],
    results: bootstrap.results || [],
    races: bootstrap.races || [],
    moduleInstances: [],
    currentPageId: bootstrap.pages?.[0]?.id ?? null,
    activeView: 'builder',
    selected: null,
    manifests: new Map(),
};

const dom = {
    views: document.querySelectorAll('.admin-view'),
    navButtons: document.querySelectorAll('[data-action="view"]'),
    pageSelector: document.getElementById('page-selector'),
    refreshButton: document.getElementById('refresh-data'),
    createPageButton: document.getElementById('create-page'),
    catalog: document.getElementById('modules-catalog'),
    canvas: document.getElementById('page-canvas'),
    canvasEmpty: document.getElementById('canvas-empty'),
    inspector: document.getElementById('module-inspector'),
    modulesTable: document.getElementById('modules-table'),
    contentTable: document.getElementById('content-table'),
    resultsTable: document.getElementById('results-table'),
    dashboardMetrics: {
        pages: document.querySelector('[data-metric="pages-count"]'),
        modules: document.querySelector('[data-metric="modules-count"]'),
        content: document.querySelector('[data-metric="content-count"]'),
        results: document.querySelector('[data-metric="results-count"]'),
    },
    dashboardLists: {
        pages: document.querySelector('[data-dashboard="pages"]'),
        content: document.querySelector('[data-dashboard="content"]'),
        results: document.querySelector('[data-dashboard="results"]'),
    },
};

const deepClone = typeof structuredClone === 'function'
    ? (value) => structuredClone(value)
    : (value) => JSON.parse(JSON.stringify(value));

const templates = {
    moduleCard: document.getElementById('module-card-template'),
    canvasItem: document.getElementById('canvas-item-template'),
    childItem: document.getElementById('child-item-template'),
    inspectorField: document.getElementById('inspector-field-template'),
    modal: document.getElementById('modal-template'),
};

function init() {
    bindNavigation();
    populatePageSelector();
    renderModuleCatalog();
    renderModulesTable();
    renderContentTable();
    renderResultsTable();
    updateDashboard();
    bindPrimaryActions();
    if (state.currentPageId) {
        loadModuleInstances();
    } else {
        toggleEmptyCanvas(true);
    }
}

function bindNavigation() {
    dom.navButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const view = button.dataset.view;
            setActiveView(view);
        });
    });
}

function setActiveView(view) {
    state.activeView = view;
    dom.navButtons.forEach((button) => {
        button.classList.toggle('is-active', button.dataset.view === view);
    });
    dom.views.forEach((panel) => {
        panel.classList.toggle('is-active', panel.dataset.view === view);
    });
}

function populatePageSelector() {
    dom.pageSelector.innerHTML = '';
    state.pages.forEach((page) => {
        const option = document.createElement('option');
        option.value = page.id;
        option.textContent = `${page.title} (${page.slug})`;
        if (Number(state.currentPageId) === Number(page.id)) {
            option.selected = true;
        }
        dom.pageSelector.appendChild(option);
    });

    dom.pageSelector.addEventListener('change', () => {
        state.currentPageId = Number(dom.pageSelector.value);
        state.selected = null;
        loadModuleInstances();
    });
}

function bindPrimaryActions() {
    dom.refreshButton.addEventListener('click', () => {
        refreshAllData();
    });

    dom.createPageButton.addEventListener('click', async () => {
        const result = await openFormModal({
            title: 'Nuova pagina',
            fields: [
                { name: 'title', label: 'Titolo', type: 'text', required: true },
                { name: 'slug', label: 'Slug', type: 'text', required: true },
            ],
        });

        if (!result) {
            return;
        }

        try {
            const response = await apiFetch('pages', {
                method: 'POST',
                body: JSON.stringify({
                    title: result.title,
                    slug: result.slug,
                    status: 'draft',
                }),
            });
            state.pages.push(response.data);
            state.currentPageId = response.data.id;
            populatePageSelector();
            await loadModuleInstances();
            updateDashboard();
        } catch (error) {
            notifyError(error);
        }
    });

    const addFirstButton = document.querySelector('[data-action="add-first"]');
    if (addFirstButton) {
        addFirstButton.addEventListener('click', () => {
            const firstActiveModule = state.modules.find((module) => module.is_active);
            if (firstActiveModule) {
                createModuleInstance(firstActiveModule.slug || firstActiveModule.name, 0);
            }
        });
    }
}

async function refreshAllData() {
    try {
        const [pages, modules, content, results, races] = await Promise.all([
            apiFetch('pages'),
            apiFetch('modules'),
            apiFetch('content'),
            apiFetch('results', { params: { limit: 50 } }),
            apiFetch('races'),
        ]);

        state.pages = pages.data;
        state.modules = modules.data;
        state.content = content.data;
        state.results = results.data;
        state.races = races.data;

        populatePageSelector();
        renderModuleCatalog();
        renderModulesTable();
        renderContentTable();
        renderResultsTable();
        updateDashboard();
        if (state.currentPageId) {
            await loadModuleInstances();
        }
    } catch (error) {
        notifyError(error);
    }
}

function renderModuleCatalog() {
    dom.catalog.innerHTML = '';
    const activeModules = state.modules.filter((module) => module.is_active);
    if (!activeModules.length) {
        const empty = document.createElement('p');
        empty.className = 'panel__subtitle';
        empty.textContent = 'Nessun modulo attivo. Attiva i moduli nella sezione dedicata.';
        dom.catalog.appendChild(empty);
        return;
    }

    activeModules.forEach((module) => {
        const node = templates.moduleCard.content.firstElementChild.cloneNode(true);
        node.querySelector('.module-card__title').textContent = module.name;
        node.querySelector('.module-card__slug').textContent = module.slug || module.name;
        node.querySelector('.module-card__description').textContent = module.description || 'Modulo riutilizzabile.';
        node.dataset.module = module.slug || module.name;
        node.addEventListener('dblclick', () => {
            createModuleInstance(node.dataset.module, state.moduleInstances.length);
        });
        dom.catalog.appendChild(node);
    });

    Sortable.create(dom.catalog, {
        group: { name: 'modules', pull: 'clone', put: false },
        sort: false,
        animation: 150,
        fallbackOnBody: true,
        forceFallback: true,
    });
}

async function loadModuleInstances() {
    if (!state.currentPageId) {
        toggleEmptyCanvas(true);
        return;
    }

    try {
        const response = await apiFetch('module-instances', { params: { page_id: state.currentPageId } });
        state.moduleInstances = response.data.map((instance) => ({
            ...instance,
            order_index: Number(instance.order_index || 0),
        }));
        renderCanvas();
        toggleEmptyCanvas(state.moduleInstances.length === 0);
        renderInspector();
    } catch (error) {
        notifyError(error);
    }
}

function toggleEmptyCanvas(isEmpty) {
    if (!dom.canvasEmpty) {
        return;
    }
    dom.canvasEmpty.hidden = !isEmpty;
    dom.canvas.style.display = isEmpty ? 'none' : 'flex';
}

function renderCanvas() {
    dom.canvas.innerHTML = '';
    const sorted = [...state.moduleInstances].sort((a, b) => a.order_index - b.order_index);

    sorted.forEach((instance) => {
        const node = templates.canvasItem.content.firstElementChild.cloneNode(true);
        node.dataset.instanceId = instance.id;
        const title = node.querySelector('.canvas-item__title');
        title.innerHTML = `<strong>${instance.instance_name}</strong><small>${instance.module_name}</small>`;
        const info = node.querySelector('.canvas-item__info');
        info.textContent = buildConfigSummary(instance.config);
        const selectButton = node.querySelector('[data-action="select"]');
        const deleteButton = node.querySelector('[data-action="delete"]');
        const duplicateButton = node.querySelector('[data-action="duplicate"]');

        selectButton.addEventListener('click', () => {
            state.selected = { type: 'instance', id: instance.id };
            highlightSelection();
            renderInspector();
        });

        deleteButton.addEventListener('click', async () => {
            const confirmDelete = window.confirm('Eliminare questo modulo dalla pagina?');
            if (!confirmDelete) {
                return;
            }
            await deleteInstance(instance.id);
        });

        duplicateButton.addEventListener('click', async () => {
            await duplicateInstance(instance.id);
        });

        if (state.selected && state.selected.type === 'instance' && Number(state.selected.id) === Number(instance.id)) {
            node.classList.add('is-selected');
        }

        const childrenContainer = node.querySelector('.canvas-item__children');
        renderChildrenList(childrenContainer, instance);

        dom.canvas.appendChild(node);
    });

    initCanvasSortable();
}

function renderChildrenList(container, instance) {
    container.innerHTML = '';
    const slot = container.dataset.slot || 'default';
    const children = getChildren(instance, slot);

    if (!children.length) {
        const placeholder = document.createElement('div');
        placeholder.className = 'canvas-dropzone';
        placeholder.dataset.slotPlaceholder = 'true';
        placeholder.textContent = 'Trascina qui per annidare un modulo';
        container.appendChild(placeholder);
    }

    children.forEach((child, index) => {
        const node = templates.childItem.content.firstElementChild.cloneNode(true);
        node.dataset.childIndex = index.toString();
        node.dataset.slot = slot;
        node.dataset.instanceId = instance.id;
        node.dataset.module = child.module || child.module_name;
        node.querySelector('.child-item__name').textContent = child.instance_name || child.module || child.module_name;
        const select = node.querySelector('[data-action="child-select"]');
        const remove = node.querySelector('[data-action="child-delete"]');

        select.addEventListener('click', () => {
            state.selected = {
                type: 'child',
                parentId: instance.id,
                slot,
                index,
            };
            highlightSelection();
            renderInspector();
        });

        remove.addEventListener('click', async () => {
            await deleteChild(instance.id, slot, index);
        });

        container.appendChild(node);
    });

    if (container._sortable) {
        container._sortable.destroy();
    }

    container._sortable = Sortable.create(container, {
        group: { name: 'modules', pull: true, put: true },
        animation: 150,
        fallbackOnBody: true,
        onAdd(evt) {
            const moduleSlug = evt.item.dataset.module;
            if (!moduleSlug) {
                evt.item.remove();
                return;
            }
            evt.item.remove();
            addChildModule(instance.id, slot, moduleSlug, evt.newIndex);
        },
        onUpdate(evt) {
            const newOrder = Array.from(container.querySelectorAll('.child-item')).map((element, orderIndex) => ({
                index: Number(element.dataset.childIndex),
                orderIndex,
            }));
            reorderChildren(instance.id, slot, newOrder);
        },
    });
}

function highlightSelection() {
    document.querySelectorAll('.canvas-item').forEach((node) => {
        node.classList.remove('is-selected');
    });

    if (!state.selected) {
        return;
    }

    if (state.selected.type === 'instance') {
        const node = dom.canvas.querySelector(`[data-instance-id="${state.selected.id}"]`);
        if (node) {
            node.classList.add('is-selected');
        }
    }
}

function initCanvasSortable() {
    if (dom.canvas._sortable) {
        dom.canvas._sortable.destroy();
    }

    dom.canvas._sortable = Sortable.create(dom.canvas, {
        group: { name: 'modules', pull: true, put: true },
        animation: 180,
        handle: '.canvas-item__header',
        draggable: '.canvas-item',
        onAdd(evt) {
            const moduleSlug = evt.item.dataset.module;
            if (!moduleSlug) {
                evt.item.remove();
                return;
            }
            evt.item.remove();
            createModuleInstance(moduleSlug, evt.newIndex);
        },
        onUpdate(evt) {
            const order = Array.from(dom.canvas.children).map((element, index) => ({
                id: Number(element.dataset.instanceId),
                order_index: index,
            }));
            reorderInstances(order);
        },
    });
}

function buildConfigSummary(config) {
    if (!config || typeof config !== 'object') {
        return 'Configurazione predefinita';
    }
    const entries = Object.entries(config)
        .filter(([key]) => key !== 'children')
        .slice(0, 3)
        .map(([key, value]) => `${key}: ${stringifyValue(value)}`);
    return entries.length ? entries.join(' • ') : 'Configurazione personalizzata';
}

function stringifyValue(value) {
    if (Array.isArray(value)) {
        return value.length ? value.join(', ') : '[]';
    }
    if (typeof value === 'object' && value !== null) {
        return '[...]';
    }
    if (typeof value === 'boolean') {
        return value ? 'sì' : 'no';
    }
    return String(value);
}

async function createModuleInstance(moduleSlug, orderIndex) {
    try {
        const targetIndex = typeof orderIndex === 'number' ? orderIndex : state.moduleInstances.length;
        const moduleInfo = findModule(moduleSlug);
        const defaultConfig = moduleInfo?.default_config || {};
        const instanceName = generateInstanceName(moduleSlug);
        const payload = {
            page_id: state.currentPageId,
            module_name: moduleSlug,
            instance_name: instanceName,
            config: defaultConfig,
            order_index: targetIndex,
        };

        const response = await apiFetch('module-instances', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        const newInstance = { ...response.data, order_index: Number(targetIndex) };
        state.moduleInstances.splice(targetIndex, 0, newInstance);
        state.moduleInstances.forEach((instance, index) => {
            instance.order_index = index;
        });
        await reorderInstances(state.moduleInstances.map((instance, index) => ({
            id: Number(instance.id),
            order_index: index,
        })));
        state.selected = { type: 'instance', id: newInstance.id };
        renderCanvas();
        renderInspector();
        toggleEmptyCanvas(false);
        return newInstance;
    } catch (error) {
        notifyError(error);
    }
}

async function duplicateInstance(instanceId) {
    const original = state.moduleInstances.find((instance) => Number(instance.id) === Number(instanceId));
    if (!original) {
        return;
    }
    const cloneConfig = deepClone(original.config || {});
    const newName = generateInstanceName(original.module_name);
    const newInstance = await createModuleInstance(original.module_name, state.moduleInstances.length);
    if (newInstance) {
        newInstance.config = cloneConfig;
        newInstance.instance_name = newName;
        await saveInstance(newInstance);
    }
}

async function deleteInstance(instanceId) {
    try {
        await apiFetch('module-instances', {
            method: 'DELETE',
            params: { id: instanceId, page_id: state.currentPageId },
        });
        state.moduleInstances = state.moduleInstances.filter((instance) => Number(instance.id) !== Number(instanceId));
        state.moduleInstances.forEach((instance, index) => {
            instance.order_index = index;
        });
        await reorderInstances(state.moduleInstances.map((instance) => ({
            id: Number(instance.id),
            order_index: instance.order_index,
        })));
        if (state.selected && state.selected.type === 'instance' && Number(state.selected.id) === Number(instanceId)) {
            state.selected = null;
        }
        toggleEmptyCanvas(state.moduleInstances.length === 0);
        renderInspector();
    } catch (error) {
        notifyError(error);
    }
}

async function addChildModule(parentId, slot, moduleSlug, position) {
    const parent = state.moduleInstances.find((instance) => Number(instance.id) === Number(parentId));
    if (!parent) {
        return;
    }
    const children = ensureChildren(parent, slot);
    const moduleInfo = findModule(moduleSlug);
    const child = {
        module: moduleSlug,
        instance_name: generateInstanceName(moduleSlug, children.length + 1),
        config: deepClone(moduleInfo?.default_config || {}),
    };
    if (typeof position === 'number') {
        children.splice(position, 0, child);
    } else {
        children.push(child);
    }
    await saveInstance(parent);
    renderInspector();
}

async function deleteChild(parentId, slot, index) {
    const parent = state.moduleInstances.find((instance) => Number(instance.id) === Number(parentId));
    if (!parent) {
        return;
    }
    const children = ensureChildren(parent, slot);
    children.splice(index, 1);
    await saveInstance(parent);
    renderInspector();
    if (state.selected && state.selected.type === 'child' && Number(state.selected.parentId) === Number(parentId) && Number(state.selected.index) === Number(index)) {
        state.selected = null;
    }
}

async function reorderChildren(parentId, slot, order) {
    const parent = state.moduleInstances.find((instance) => Number(instance.id) === Number(parentId));
    if (!parent) {
        return;
    }
    const children = ensureChildren(parent, slot);
    const reordered = new Array(children.length);
    order.forEach(({ index, orderIndex }) => {
        reordered[orderIndex] = children[index];
    });
    parent.config.children[slot] = reordered.filter(Boolean);
    await saveInstance(parent, { silent: true });
    renderCanvas();
}

async function reorderInstances(order) {
    try {
        await apiFetch('module-instances', {
            method: 'PATCH',
            body: JSON.stringify({
                page_id: state.currentPageId,
                order,
            }),
        });
        order.forEach((item) => {
            const instance = state.moduleInstances.find((entry) => Number(entry.id) === Number(item.id));
            if (instance) {
                instance.order_index = item.order_index;
            }
        });
        reorderStateByCanvas();
        renderCanvas();
    } catch (error) {
        notifyError(error);
    }
}

function reorderStateByCanvas() {
    state.moduleInstances.sort((a, b) => a.order_index - b.order_index);
}

function generateInstanceName(moduleSlug, counter) {
    const base = moduleSlug.replace(/[^a-zA-Z0-9-_]/g, '-');
    let index = counter || 1;
    while (state.moduleInstances.some((instance) => instance.instance_name === `${base}_${index}`)) {
        index += 1;
    }
    return `${base}_${index}`;
}

function ensureChildren(instance, slot) {
    if (!instance.config) {
        instance.config = {};
    }
    if (!instance.config.children) {
        instance.config.children = {};
    }
    if (!Array.isArray(instance.config.children[slot])) {
        instance.config.children[slot] = [];
    }
    return instance.config.children[slot];
}

function getChildren(instance, slot) {
    if (!instance.config || !instance.config.children) {
        return [];
    }
    if (Array.isArray(instance.config.children)) {
        return instance.config.children;
    }
    const slotChildren = instance.config.children[slot];
    return Array.isArray(slotChildren) ? slotChildren : [];
}

async function saveInstance(instance, options = {}) {
    const payload = {
        id: instance.id,
        page_id: state.currentPageId,
        module_name: instance.module_name,
        instance_name: instance.instance_name,
        config: instance.config || {},
        order_index: instance.order_index || 0,
    };

    try {
        const response = await apiFetch('module-instances', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        const index = state.moduleInstances.findIndex((entry) => Number(entry.id) === Number(instance.id));
        if (index !== -1) {
            state.moduleInstances[index] = {
                ...response.data,
                order_index: Number(response.data.order_index || instance.order_index || 0),
            };
        }
        if (!options.silent) {
            renderCanvas();
        }
    } catch (error) {
        notifyError(error);
    }
}

function renderInspector() {
    dom.inspector.innerHTML = '';

    if (!state.selected) {
        const empty = document.createElement('div');
        empty.className = 'inspector__empty';
        empty.textContent = 'Seleziona un modulo per modificarlo.';
        dom.inspector.appendChild(empty);
        return;
    }

    if (state.selected.type === 'instance') {
        const instance = state.moduleInstances.find((entry) => Number(entry.id) === Number(state.selected.id));
        if (!instance) {
            return;
        }
        buildInstanceInspector(instance);
        return;
    }

    if (state.selected.type === 'child') {
        const parent = state.moduleInstances.find((entry) => Number(entry.id) === Number(state.selected.parentId));
        if (!parent) {
            return;
        }
        const children = getChildren(parent, state.selected.slot);
        const child = children[state.selected.index];
        if (!child) {
            renderInspector();
            return;
        }
        buildChildInspector(parent, state.selected.slot, state.selected.index, child);
    }
}

async function buildInstanceInspector(instance) {
    const fragment = document.createDocumentFragment();

    const header = document.createElement('div');
    header.className = 'inspector-field';
    header.innerHTML = `
        <span class="inspector-field__label">Nome istanza</span>
        <input class="inspector-field__control" type="text" value="${instance.instance_name}">
    `;
    const input = header.querySelector('input');
    input.addEventListener('change', async (event) => {
        instance.instance_name = event.target.value;
        await saveInstance(instance);
    });
    fragment.appendChild(header);

    const manifest = await loadManifest(instance.module_name);
    const fields = extractFieldsFromManifest(manifest, instance.config);
    fields.forEach((field) => {
        const fieldNode = templates.inspectorField.content.firstElementChild.cloneNode(true);
        fieldNode.querySelector('.inspector-field__label').textContent = field.label;

        let control;
        if (field.type === 'textarea' || field.type === 'json') {
            control = document.createElement('textarea');
            control.value = field.type === 'json'
                ? JSON.stringify(field.value ?? {}, null, 2)
                : (field.value || '');
        } else if (field.type === 'checkbox') {
            control = document.createElement('input');
            control.type = 'checkbox';
            control.checked = Boolean(field.value);
        } else {
            control = document.createElement('input');
            control.type = field.type;
            control.value = field.value ?? '';
        }

        control.className = 'inspector-field__control';
        control.name = field.name;

        control.addEventListener('change', async (event) => {
            const value = parseControlValue(field.type, event.target);
            instance.config = instance.config || {};
            instance.config[field.name] = value;
            await saveInstance(instance, { silent: true });
            renderCanvas();
        });

        fieldNode.replaceChild(control, fieldNode.querySelector('.inspector-field__control'));
        fragment.appendChild(fieldNode);
    });

    const childrenSection = document.createElement('section');
    childrenSection.className = 'panel';
    const heading = document.createElement('header');
    heading.className = 'panel__header';
    heading.innerHTML = '<h3>Moduli annidati</h3>';
    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'admin-button admin-button--ghost';
    addButton.innerHTML = '<i class="fa-solid fa-plus"></i> Aggiungi modulo';
    addButton.addEventListener('click', async () => {
        const moduleSlug = await pickModule();
        if (moduleSlug) {
            await addChildModule(instance.id, 'default', moduleSlug);
            renderInspector();
        }
    });
    heading.appendChild(addButton);
    childrenSection.appendChild(heading);

    const childrenList = document.createElement('div');
    const children = getChildren(instance, 'default');
    if (children.length) {
        children.forEach((child, index) => {
            const childNode = document.createElement('div');
            childNode.className = 'child-item';
            childNode.innerHTML = `
                <span class="child-item__name">${child.instance_name || child.module}</span>
                <div class="child-item__actions">
                    <button type="button" class="icon-button" title="Modifica"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="icon-button" title="Rimuovi"><i class="fa-solid fa-xmark"></i></button>
                </div>
            `;
            childNode.querySelectorAll('button')[0].addEventListener('click', () => {
                state.selected = { type: 'child', parentId: instance.id, slot: 'default', index };
                highlightSelection();
                renderInspector();
            });
            childNode.querySelectorAll('button')[1].addEventListener('click', async () => {
                await deleteChild(instance.id, 'default', index);
                renderInspector();
            });
            childrenList.appendChild(childNode);
        });
    } else {
        const empty = document.createElement('p');
        empty.className = 'panel__subtitle';
        empty.textContent = 'Nessun modulo annidato.';
        childrenList.appendChild(empty);
    }

    childrenSection.appendChild(childrenList);
    fragment.appendChild(childrenSection);

    dom.inspector.appendChild(fragment);
}

async function buildChildInspector(parent, slot, index, child) {
    dom.inspector.innerHTML = '';
    const fragment = document.createDocumentFragment();
    const title = document.createElement('h3');
    title.textContent = `Modulo annidato: ${child.module || child.module_name}`;
    fragment.appendChild(title);

    const manifest = await loadManifest(child.module || child.module_name);
    const fields = extractFieldsFromManifest(manifest, child.config || {});

    fields.forEach((field) => {
        const node = templates.inspectorField.content.firstElementChild.cloneNode(true);
        node.querySelector('.inspector-field__label').textContent = field.label;
        let control;
        if (field.type === 'textarea' || field.type === 'json') {
            control = document.createElement('textarea');
            control.value = field.type === 'json'
                ? JSON.stringify(field.value ?? {}, null, 2)
                : (field.value || '');
        } else if (field.type === 'checkbox') {
            control = document.createElement('input');
            control.type = 'checkbox';
            control.checked = Boolean(field.value);
        } else {
            control = document.createElement('input');
            control.type = field.type;
            control.value = field.value ?? '';
        }
        control.className = 'inspector-field__control';
        control.addEventListener('change', async (event) => {
            const value = parseControlValue(field.type, event.target);
            const children = ensureChildren(parent, slot);
            children[index].config = children[index].config || {};
            children[index].config[field.name] = value;
            await saveInstance(parent, { silent: true });
            renderCanvas();
        });
        node.replaceChild(control, node.querySelector('.inspector-field__control'));
        fragment.appendChild(node);
    });

    const backButton = document.createElement('button');
    backButton.type = 'button';
    backButton.className = 'admin-button admin-button--ghost';
    backButton.textContent = 'Torna all\'istanza principale';
    backButton.addEventListener('click', () => {
        state.selected = { type: 'instance', id: parent.id };
        renderInspector();
    });
    fragment.appendChild(backButton);

    dom.inspector.appendChild(fragment);
}

function parseControlValue(type, element) {
    if (type === 'checkbox') {
        return element.checked;
    }
    if (type === 'number') {
        return Number(element.value);
    }
    if (type === 'json') {
        try {
            return element.value ? JSON.parse(element.value) : {};
        } catch (error) {
            notifyError(new Error('JSON non valido.'));
            return {};
        }
    }
    return element.value;
}

function extractFieldsFromManifest(manifest, config) {
    const defaultConfig = manifest?.default_config || {};
    const keys = new Set(Object.keys(defaultConfig));
    Object.keys(config || {}).forEach((key) => keys.add(key));

    const fields = [];
    keys.forEach((key) => {
        if (key === 'children') {
            return;
        }
        const value = config?.[key] ?? defaultConfig[key];
        const type = detectFieldType(value);
        fields.push({
            name: key,
            label: key.replace(/_/g, ' '),
            value,
            type,
        });
    });

    return fields;
}

function detectFieldType(value) {
    if (typeof value === 'boolean') {
        return 'checkbox';
    }
    if (typeof value === 'number') {
        return 'number';
    }
    if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
        return 'json';
    }
    if (typeof value === 'string' && value.length > 80) {
        return 'textarea';
    }
    return 'text';
}

async function loadManifest(moduleName) {
    if (state.manifests.has(moduleName)) {
        return state.manifests.get(moduleName);
    }
    try {
        const response = await apiFetch('module-manifest', { params: { module: moduleName } });
        state.manifests.set(moduleName, response.data);
        return response.data;
    } catch (error) {
        notifyError(error);
        return {};
    }
}

function findModule(slug) {
    return state.modules.find((module) => module.slug === slug || module.name === slug);
}

function updateDashboard() {
    dom.dashboardMetrics.pages.textContent = state.pages.length.toString();
    dom.dashboardMetrics.modules.textContent = state.modules.filter((module) => module.is_active).length.toString();
    dom.dashboardMetrics.content.textContent = state.content.length.toString();
    dom.dashboardMetrics.results.textContent = state.results.length.toString();

    populateDashboardList(dom.dashboardLists.pages, state.pages, (page) => `${page.title} • ${page.status}`);
    populateDashboardList(dom.dashboardLists.content, state.content.slice(0, 5), (item) => `${item.content_type} • ${item.title || 'Senza titolo'}`);
    populateDashboardList(dom.dashboardLists.results, state.results.slice(0, 5), (result) => `${result.runner_name} • ${result.time_result || '--:--'}`);
}

function populateDashboardList(container, data, formatter) {
    container.innerHTML = '';
    if (!data.length) {
        const empty = document.createElement('li');
        empty.textContent = 'Nessun elemento disponibile.';
        container.appendChild(empty);
        return;
    }
    data.forEach((item) => {
        const li = document.createElement('li');
        li.textContent = formatter(item);
        container.appendChild(li);
    });
}

function renderModulesTable() {
    dom.modulesTable.innerHTML = '';
    state.modules.forEach((module) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${module.name}</td>
            <td>${module.slug || module.name}</td>
            <td>${module.css_class || '-'}</td>
            <td>${module.version || '1.0.0'}</td>
            <td>${module.is_active ? 'Attivo' : 'Disattivo'}</td>
            <td>
                <button type="button" class="admin-button admin-button--ghost" data-action="toggle" data-id="${module.id}">
                    ${module.is_active ? 'Disattiva' : 'Attiva'}
                </button>
            </td>
        `;
        row.querySelector('[data-action="toggle"]').addEventListener('click', async () => {
            await toggleModule(module.id, !module.is_active);
        });
        dom.modulesTable.appendChild(row);
    });
}

async function toggleModule(id, active) {
    try {
        await apiFetch('modules', {
            method: 'PATCH',
            body: JSON.stringify({ id, is_active: active }),
        });
        const module = state.modules.find((item) => Number(item.id) === Number(id));
        if (module) {
            module.is_active = active ? 1 : 0;
        }
        renderModulesTable();
        renderModuleCatalog();
        updateDashboard();
    } catch (error) {
        notifyError(error);
    }
}

function renderContentTable() {
    dom.contentTable.innerHTML = '';
    state.content.forEach((item) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.id}</td>
            <td>${item.content_type}</td>
            <td>${item.title || '—'}</td>
            <td>${item.is_active ? 'Attivo' : 'Bozza'}</td>
            <td>${item.updated_at || item.created_at || ''}</td>
        `;
        dom.contentTable.appendChild(row);
    });
}

function renderResultsTable() {
    dom.resultsTable.innerHTML = '';
    state.results.forEach((result) => {
        const row = document.createElement('tr');
        const raceName = state.races.find((race) => Number(race.id) === Number(result.race_id))?.name || '—';
        row.innerHTML = `
            <td>${raceName}</td>
            <td>${result.position}</td>
            <td>${result.bib_number || '—'}</td>
            <td>${result.runner_name}</td>
            <td>${result.category || '—'}</td>
            <td>${result.time_result || '—'}</td>
        `;
        dom.resultsTable.appendChild(row);
    });
}

async function pickModule() {
    const activeModules = state.modules.filter((module) => module.is_active);
    if (!activeModules.length) {
        return null;
    }
    const options = activeModules.map((module) => ({ value: module.slug || module.name, label: module.name }));
    const result = await openFormModal({
        title: 'Seleziona modulo',
        fields: [
            {
                name: 'module',
                label: 'Modulo',
                type: 'select',
                options,
            },
        ],
    });
    return result?.module || null;
}

function openFormModal({ title, fields }) {
    const modalNode = templates.modal.content.firstElementChild.cloneNode(true);
    const dialog = modalNode;
    const form = dialog.querySelector('form');
    dialog.querySelector('.admin-modal__title').textContent = title;
    const body = dialog.querySelector('.admin-modal__body');
    body.innerHTML = '';

    fields.forEach((field) => {
        const wrapper = document.createElement('label');
        wrapper.className = 'inspector-field';
        wrapper.innerHTML = `<span class="inspector-field__label">${field.label}</span>`;
        let control;
        if (field.type === 'textarea') {
            control = document.createElement('textarea');
        } else if (field.type === 'select') {
            control = document.createElement('select');
            field.options.forEach((option) => {
                const optionNode = document.createElement('option');
                optionNode.value = option.value;
                optionNode.textContent = option.label;
                control.appendChild(optionNode);
            });
        } else {
            control = document.createElement('input');
            control.type = field.type;
        }
        control.name = field.name;
        control.required = Boolean(field.required);
        control.className = 'inspector-field__control';
        wrapper.appendChild(control);
        body.appendChild(wrapper);
    });

    return new Promise((resolve) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const isConfirm = event.submitter?.value === 'confirm';
            if (!isConfirm) {
                dialog.close('cancel');
                return;
            }
            const formData = new FormData(form);
            const values = {};
            fields.forEach((field) => {
                values[field.name] = formData.get(field.name);
            });
            dialog.close('confirm');
            resolve(values);
        });

        dialog.addEventListener('close', () => {
            if (dialog.returnValue !== 'confirm') {
                resolve(null);
            }
            dialog.remove();
        });

        document.body.appendChild(dialog);
        dialog.showModal();
    });
}

async function apiFetch(resource, options = {}) {
    const url = new URL('./api/index.php', window.location.href);
    url.searchParams.set('resource', resource);
    if (options.params) {
        Object.entries(options.params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                url.searchParams.set(key, value);
            }
        });
    }

    const fetchOptions = {
        method: options.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (options.body) {
        fetchOptions.body = options.body;
    }

    const response = await fetch(url.toString(), fetchOptions);
    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new Error(error.error || 'Errore di comunicazione con il server.');
    }
    return response.json();
}

function notifyError(error) {
    console.error(error);
    window.alert(error.message || 'Si è verificato un errore.');
}

init();
