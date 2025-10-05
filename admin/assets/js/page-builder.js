(() => {
    const state = {
        pages: [],
        currentPageId: null,
        currentPage: null,
        tree: [],
        modules: [],
        availableModules: [],
        selectedNodeId: null,
        inlineMode: false,
        tempCounter: 0,
        moduleCounters: {},
        changes: {
            added: new Set(),
            updated: new Set(),
            deleted: new Set(),
        },
        nodeIndex: new Map(),
        inspector: null,
    };

    const dom = {
        pageSelector: document.getElementById('pb-page-selector'),
        createPageBtn: document.getElementById('pb-create-page'),
        renamePageBtn: document.getElementById('pb-rename-page'),
        deletePageBtn: document.getElementById('pb-delete-page'),
        moduleCount: document.getElementById('pb-module-count'),
        moduleSearch: document.getElementById('pb-module-search'),
        modulesList: document.getElementById('pb-modules'),
        stage: document.getElementById('pb-stage'),
        stageName: document.getElementById('pb-current-page-name'),
        stageSlug: document.getElementById('pb-current-page-slug'),
        inlineToggle: document.getElementById('pb-inline-toggle'),
        previewBtn: document.getElementById('pb-preview-page'),
        inspectorBody: document.getElementById('pb-inspector-body'),
        inspectorLabel: document.getElementById('pb-selected-label'),
        saveConfigBtn: document.getElementById('pb-save-config'),
        cancelInlineBtn: document.getElementById('pb-cancel-inline'),
        changeBadges: document.getElementById('pb-change-badges'),
        toast: document.getElementById('pb-toast'),
    };

    const sortables = [];
    let inspectorState = null;

    function init() {
        if (!window.PB_INITIAL_STATE) {
            return;
        }

        state.pages = PB_INITIAL_STATE.pages || [];
        state.currentPageId = PB_INITIAL_STATE.currentPageId || (state.pages[0]?.id ?? null);
        state.currentPage = PB_INITIAL_STATE.currentPage || null;
        state.modules = PB_INITIAL_STATE.moduleInstances || [];
        state.availableModules = PB_INITIAL_STATE.availableModules || [];
        state.tree = buildTree(state.modules);
        rebuildIndex();
        initModuleCounters();

        bindEvents();
        renderPageSelector();
        renderModuleLibrary();
        renderStage();
        updateStageHeader();
        updateChangeBadges();
    }

    function bindEvents() {
        dom.pageSelector.addEventListener('change', () => {
            const nextId = parseInt(dom.pageSelector.value, 10);
            if (!Number.isNaN(nextId)) {
                loadPage(nextId);
            }
        });

        dom.createPageBtn.addEventListener('click', handleCreatePage);
        dom.renamePageBtn.addEventListener('click', handleRenamePage);
        dom.deletePageBtn.addEventListener('click', handleDeletePage);
        dom.moduleSearch.addEventListener('input', renderModuleLibrary);
        dom.inlineToggle.addEventListener('click', toggleInlineMode);
        dom.previewBtn.addEventListener('click', previewCurrentPage);
        dom.saveConfigBtn.addEventListener('click', persistCurrentInspector);
        dom.cancelInlineBtn.addEventListener('click', cancelInlineDraft);
    }

    function buildTree(instances) {
        if (!Array.isArray(instances)) {
            return [];
        }

        const byParent = new Map();
        instances.forEach((instance) => {
            const parentKey = instance.parent_instance_id ?? null;
            if (!byParent.has(parentKey)) {
                byParent.set(parentKey, []);
            }
            const node = {
                id: instance.id,
                module: instance.module,
                instance_name: instance.instance_name,
                order_index: instance.order_index ?? 0,
                parent_instance_id: instance.parent_instance_id ?? null,
                html: instance.html ?? '',
                config: instance.config || {},
                children: [],
                isNew: false,
                inlineDraft: null,
                _configDirty: false,
            };
            byParent.get(parentKey).push(node);
        });

        const attachChildren = (parentId = null) => {
            const bucket = byParent.get(parentId) || [];
            return bucket
                .sort((a, b) => (a.order_index ?? 0) - (b.order_index ?? 0))
                .map((node) => {
                    node.children = attachChildren(node.id);
                    return node;
                });
        };

        return attachChildren(null);
    }

    function rebuildIndex() {
        state.nodeIndex.clear();
        const walk = (nodes, parentId = null) => {
            nodes.forEach((node, index) => {
                node.order_index = index;
                node.parent_instance_id = parentId;
                state.nodeIndex.set(node.id, { node, parentId, siblings: nodes });
                if (Array.isArray(node.children) && node.children.length) {
                    walk(node.children, node.id);
                }
            });
        };
        walk(state.tree, null);
    }

    function initModuleCounters() {
        state.moduleCounters = {};
        state.modules.forEach((instance) => {
            const key = instance.module;
            const match = (instance.instance_name || '').match(/_(\d+)$/);
            const value = match ? parseInt(match[1], 10) : 1;
            state.moduleCounters[key] = Math.max(state.moduleCounters[key] || 0, value);
        });
    }

    function renderPageSelector() {
        dom.pageSelector.innerHTML = '';
        state.pages.forEach((page) => {
            const option = document.createElement('option');
            option.value = page.id;
            option.textContent = `${page.title} · ${page.slug}`;
            if (page.id === state.currentPageId) {
                option.selected = true;
            }
            dom.pageSelector.appendChild(option);
        });
    }

    function renderModuleLibrary() {
        const query = dom.moduleSearch.value?.toLowerCase().trim() ?? '';
        dom.modulesList.innerHTML = '';

        const filtered = state.availableModules.filter((module) => {
            if (!query) return true;
            const haystack = [module.name, module.label, module.category, ...(module.tags || [])]
                .join(' ')
                .toLowerCase();
            return haystack.includes(query);
        });

        filtered.forEach((module) => {
            const card = document.createElement('div');
            card.className = 'pb-module-card';
            card.dataset.module = module.name;
            card.dataset.label = module.label;
            card.dataset.description = module.description || '';
            card.innerHTML = `
                <strong>${module.label}</strong>
                ${module.category ? `<small style="opacity:0.7;">${module.category}</small>` : ''}
                ${module.description ? `<p style="opacity:0.65;font-size:0.8rem;">${module.description}</p>` : ''}
            `;
            dom.modulesList.appendChild(card);
        });

        dom.moduleCount.textContent = `${filtered.length} moduli`;

        sortables.forEach((sortable) => sortable.destroy());
        sortables.length = 0;

        sortables.push(Sortable.create(dom.modulesList, {
            group: { name: 'modules', pull: 'clone', revertClone: true, put: false },
            sort: false,
            animation: 150,
            fallbackOnBody: true,
            onClone: (evt) => {
                evt.clone.style.opacity = '0.9';
            },
        }));

        setupStageSortables();
    }

    function renderStage() {
        dom.stage.innerHTML = '';
        if (!state.tree.length) {
            const empty = document.createElement('div');
            empty.className = 'pb-stage-empty';
            empty.innerHTML = `
                <i class="fa-solid fa-plus-circle" style="font-size:2rem;margin-bottom:0.5rem;display:block;"></i>
                Trascina moduli dalla libreria per iniziare a comporre la pagina.
            `;
            dom.stage.appendChild(empty);
            setupStageSortables();
            return;
        }

        state.tree.forEach((node) => {
            dom.stage.appendChild(createNodeElement(node));
        });

        setupStageSortables();
        highlightSelectedNode();
        updateInlineState();
    }

    function createNodeElement(node) {
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-node pb-drop-target';
        wrapper.dataset.instanceId = node.id;
        wrapper.dataset.module = node.module;

        if (node.id === state.selectedNodeId) {
            wrapper.classList.add('is-selected');
        }

        const header = document.createElement('div');
        header.className = 'pb-node__header';
        header.innerHTML = `
            <div class="pb-node__title">
                <strong>${node.module}</strong>
                <span>${node.instance_name}</span>
            </div>
            <div class="pb-node__controls">
                <button type="button" data-action="edit"><i class="fa-solid fa-gear"></i></button>
                <button type="button" data-action="clone"><i class="fa-solid fa-clone"></i></button>
                <button type="button" data-action="delete"><i class="fa-solid fa-trash"></i></button>
            </div>
        `;
        wrapper.appendChild(header);

        const preview = document.createElement('div');
        preview.className = 'pb-node__preview';
        preview.dataset.instanceId = node.id;
        preview.setAttribute('tabindex', '0');
        preview.innerHTML = node.inlineDraft ?? node.html ?? '';
        wrapper.appendChild(preview);

        const childrenContainer = document.createElement('div');
        childrenContainer.className = 'pb-children pb-drop-target';
        childrenContainer.dataset.parentId = node.id;

        node.children?.forEach((child) => {
            childrenContainer.appendChild(createNodeElement(child));
        });

        wrapper.appendChild(childrenContainer);

        header.addEventListener('click', () => selectNode(node.id));
        preview.addEventListener('click', (event) => {
            event.preventDefault();
            if (state.inlineMode) {
                preview.focus();
            }
            selectNode(node.id);
        });

        header.querySelectorAll('button').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const action = button.dataset.action;
                if (action === 'delete') {
                    confirmDeleteNode(node.id);
                } else if (action === 'edit') {
                    selectNode(node.id);
                    focusInspector();
                } else if (action === 'clone') {
                    duplicateNode(node.id);
                }
            });
        });

        return wrapper;
    }

    function setupStageSortables() {
        // Destroy existing stage sortables (keep library intact)
        sortables
            .filter((instance) => instance.el !== dom.modulesList)
            .forEach((sortable) => sortable.destroy());

        for (let i = sortables.length - 1; i >= 0; i -= 1) {
            if (sortables[i].el !== dom.modulesList) {
                sortables.splice(i, 1);
            }
        }

        const targets = [dom.stage, ...dom.stage.querySelectorAll('.pb-children')];
        targets.forEach((target) => {
            sortables.push(Sortable.create(target, {
                group: { name: 'modules', pull: true, put: true },
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'pb-drop-placeholder',
                onAdd: handleDrop,
                onUpdate: handleReorder,
            }));
        });
    }

    function handleDrop(evt) {
        const { item, to } = evt;
        const moduleName = item.dataset.module;
        const label = item.dataset.label;
        const parentAttr = to.dataset.parentId || to.dataset.parent || null;
        const parentId = parentAttr === 'root' ? null : parentAttr ? parseNodeId(parentAttr) : null;
        const newIndex = evt.newIndex;

        if (moduleName) {
            const newNode = createTempNode(moduleName, label, parentId, newIndex);
            insertNode(newNode, parentId, newIndex);
            rebuildIndex();
            renderStage();
            updateChangeBadges();
            selectNode(newNode.id);
            showToast(`${newNode.module} aggiunto allo stage`, 'success');
        } else if (item.dataset.instanceId) {
            const instanceId = parseNodeId(item.dataset.instanceId);
            moveNode(instanceId, parentId, newIndex);
            rebuildIndex();
            renderStage();
            persistOrder();
            updateChangeBadges();
            selectNode(instanceId);
        }

        if (item.parentNode) {
            item.parentNode.removeChild(item);
        }
    }

    function handleReorder(evt) {
        const { item, to } = evt;
        const parentAttr = to.dataset.parentId || to.dataset.parent || null;
        const parentId = parentAttr === 'root' ? null : parentAttr ? parseNodeId(parentAttr) : null;
        const instanceId = parseNodeId(item.dataset.instanceId);
        const newIndex = evt.newIndex;

        if (instanceId === null) {
            return;
        }

        moveNode(instanceId, parentId, newIndex);
        rebuildIndex();
        renderStage();
        persistOrder();
        updateChangeBadges();
        selectNode(instanceId);
    }

    function parseNodeId(rawId) {
        if (!rawId) return null;
        if (rawId.startsWith('temp-')) return rawId;
        const parsed = parseInt(rawId, 10);
        return Number.isNaN(parsed) ? null : parsed;
    }

    function createTempNode(moduleName, label, parentId, index) {
        state.tempCounter += 1;
        const tempId = `temp-${state.tempCounter}`;
        const instanceName = generateInstanceName(moduleName);
        const node = {
            id: tempId,
            module: moduleName,
            instance_name: instanceName,
            order_index: index ?? 0,
            parent_instance_id: parentId ?? null,
            html: `<div style="padding:1.25rem;border:1px dashed rgba(35,168,235,0.5);border-radius:12px;text-align:center;">Configura il modulo <strong>${label || moduleName}</strong> dal pannello a destra.</div>`,
            config: {},
            children: [],
            isNew: true,
            inlineDraft: null,
            _configDirty: false,
        };
        state.changes.added.add(tempId);
        return node;
    }

    function insertNode(node, parentId, index) {
        const container = parentId ? state.nodeIndex.get(parentId)?.node.children : state.tree;
        if (!container) {
            state.tree.push(node);
            return;
        }
        if (typeof index === 'number' && index >= 0 && index <= container.length) {
            container.splice(index, 0, node);
        } else {
            container.push(node);
        }
    }

    function moveNode(nodeId, newParentId, newIndex) {
        const current = state.nodeIndex.get(nodeId);
        if (!current) return;

        const { node, siblings } = current;
        const currentIndex = siblings.indexOf(node);
        if (currentIndex >= 0) {
            siblings.splice(currentIndex, 1);
        }

        const targetSiblings = newParentId ? state.nodeIndex.get(newParentId)?.node.children : state.tree;
        if (!targetSiblings) {
            state.tree.push(node);
        } else if (typeof newIndex === 'number' && newIndex >= 0 && newIndex <= targetSiblings.length) {
            targetSiblings.splice(newIndex, 0, node);
        } else {
            targetSiblings.push(node);
        }

        node.parent_instance_id = newParentId ?? null;
        if (!node.isNew) {
            state.changes.updated.add(nodeId);
        }
    }

    function removeNode(nodeId) {
        const current = state.nodeIndex.get(nodeId);
        if (!current) return [];
        const { node, siblings } = current;
        const removedIds = [];

        const collect = (target) => {
            removedIds.push(target.id);
            if (Array.isArray(target.children)) {
                target.children.forEach(collect);
            }
        };
        collect(node);

        const index = siblings.indexOf(node);
        if (index >= 0) {
            siblings.splice(index, 1);
        }
        removedIds.forEach((id) => state.nodeIndex.delete(id));
        return removedIds;
    }

    function duplicateNode(nodeId) {
        const current = state.nodeIndex.get(nodeId);
        if (!current) return;
        const clone = resetCloneNode(deepCloneNode(current.node), current.parentId ?? null);
        clone.instance_name = generateInstanceName(clone.module);

        const siblings = current.parentId ? state.nodeIndex.get(current.parentId)?.node.children : state.tree;
        const insertIndex = siblings ? siblings.indexOf(current.node) + 1 : state.tree.length;
        insertNode(clone, current.parentId ?? null, insertIndex);
        rebuildIndex();
        renderStage();
        updateChangeBadges();
        selectNode(clone.id);
    }

    function resetCloneNode(node, parentId = null) {
        state.tempCounter += 1;
        const newId = `temp-${state.tempCounter}`;
        node.id = newId;
        node.isNew = true;
        node.inlineDraft = null;
        node.parent_instance_id = parentId;
        node._configDirty = false;
        state.changes.added.add(newId);
        node.children = Array.isArray(node.children)
            ? node.children.map((child) => resetCloneNode(deepCloneNode(child), newId))
            : [];
        return node;
    }

    function deepCloneNode(node) {
        return JSON.parse(JSON.stringify(node));
    }

    function generateInstanceName(moduleName) {
        const current = state.moduleCounters[moduleName] || 0;
        const next = current + 1;
        state.moduleCounters[moduleName] = next;
        return `${moduleName}_${next}`;
    }

    function highlightSelectedNode() {
        dom.stage.querySelectorAll('.pb-node').forEach((nodeEl) => {
            if (parseNodeId(nodeEl.dataset.instanceId) === state.selectedNodeId) {
                nodeEl.classList.add('is-selected');
            } else {
                nodeEl.classList.remove('is-selected');
            }
        });
    }

    function selectNode(nodeId) {
        if (!nodeId) return;
        const parsedId = typeof nodeId === 'string' && nodeId.startsWith('temp-') ? nodeId : parseInt(nodeId, 10);
        if (!state.nodeIndex.has(parsedId)) {
            return;
        }
        state.selectedNodeId = parsedId;
        highlightSelectedNode();
        updateInlineState();
        loadInspectorForNode(parsedId);
    }

    function loadInspectorForNode(nodeId) {
        const entry = state.nodeIndex.get(nodeId);
        if (!entry) {
            dom.inspectorBody.innerHTML = '<p>Seleziona un modulo per modificarlo.</p>';
            dom.saveConfigBtn.style.display = 'none';
            dom.inspectorLabel.textContent = '';
            return;
        }

        const { node } = entry;
        dom.inspectorLabel.textContent = `${node.module} · ${node.instance_name}`;
        dom.inspectorBody.innerHTML = '<p style="opacity:0.6;">Caricamento configurazione...</p>';
        dom.saveConfigBtn.style.display = 'none';

        const formData = new FormData();
        formData.append('action', 'get-module-config');
        formData.append('module_name', node.module);
        if (typeof node.id === 'number') {
            formData.append('instance_id', node.id);
        }

        fetch('api/page_builder.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.error || 'Errore caricamento configurazione');
                }
                inspectorState = {
                    nodeId,
                    config: data.config || {},
                    manifest: data.manifest || {},
                };
                renderInspectorForm(node, inspectorState);
            })
            .catch((error) => {
                dom.inspectorBody.innerHTML = `<p style="color:#ff9f9f;">${error.message}</p>`;
            });
    }

    function renderInspectorForm(node, inspector) {
        const container = document.createElement('div');
        container.className = 'pb-form';

        // Instance name
        const nameGroup = document.createElement('div');
        nameGroup.className = 'pb-form-group';
        nameGroup.innerHTML = `
            <label>Nome istanza</label>
            <input type="text" data-field="instance_name" value="${node.instance_name}">
        `;
        nameGroup.querySelector('input').addEventListener('input', (event) => {
            node.instance_name = event.target.value;
            node._configDirty = true;
            state.changes.updated.add(node.id);
            updateChangeBadges();
        });
        container.appendChild(nameGroup);

        const schema = inspector.manifest?.ui_schema || {};
        if (Object.keys(schema).length === 0) {
            const jsonGroup = document.createElement('div');
            jsonGroup.className = 'pb-form-group';
            jsonGroup.innerHTML = `
                <label>Configurazione JSON</label>
                <textarea data-field="json">${JSON.stringify(inspector.config, null, 2)}</textarea>
            `;
            jsonGroup.querySelector('textarea').addEventListener('input', () => {
                node._configDirty = true;
                state.changes.updated.add(node.id);
                updateChangeBadges();
            });
            container.appendChild(jsonGroup);
        } else {
            Object.entries(schema).forEach(([path, field]) => {
                const formGroup = document.createElement('div');
                formGroup.className = 'pb-form-group';
                const label = document.createElement('label');
                label.textContent = field.label || path;
                if (field.required) {
                    label.innerHTML += ' <span style="color:#ff9f9f;">*</span>';
                }
                formGroup.appendChild(label);

                const value = getByPath(inspector.config, path);
                let input;
                switch (field.type) {
                    case 'textarea':
                        input = document.createElement('textarea');
                        input.value = value ?? '';
                        break;
                    case 'select':
                        input = document.createElement('select');
                        (field.options || []).forEach((option) => {
                            const opt = document.createElement('option');
                            opt.value = option.value;
                            opt.textContent = option.label;
                            if (option.value === value) opt.selected = true;
                            input.appendChild(opt);
                        });
                        break;
                    case 'boolean':
                        input = document.createElement('input');
                        input.type = 'checkbox';
                        input.checked = Boolean(value);
                        break;
                    case 'color':
                        input = document.createElement('input');
                        input.type = 'color';
                        input.value = value || '#ffffff';
                        break;
                    case 'repeater':
                        input = document.createElement('textarea');
                        input.value = Array.isArray(value) ? JSON.stringify(value, null, 2) : '';
                        input.setAttribute('data-repeater', 'true');
                        break;
                    default:
                        input = document.createElement('input');
                        input.type = field.type === 'number' ? 'number' : 'text';
                        input.value = value ?? '';
                        break;
                }
                input.dataset.path = path;

                input.addEventListener(field.type === 'boolean' ? 'change' : 'input', (event) => {
                    const target = event.target;
                    let nextValue;
                    if (target.type === 'checkbox') {
                        nextValue = target.checked;
                    } else if (target.dataset.repeater === 'true') {
                        try {
                            nextValue = JSON.parse(target.value || '[]');
                        } catch (error) {
                            nextValue = [];
                        }
                    } else {
                        nextValue = target.value;
                    }
                    setByPath(inspector.config, path, nextValue);
                    node._configDirty = true;
                    state.changes.updated.add(node.id);
                    updateChangeBadges();
                });

                formGroup.appendChild(input);
                container.appendChild(formGroup);
            });
        }

        dom.inspectorBody.innerHTML = '';
        dom.inspectorBody.appendChild(container);
        dom.saveConfigBtn.style.display = 'block';
        dom.cancelInlineBtn.style.display = node.inlineDraft ? 'block' : 'none';
    }

    function getByPath(obj, path) {
        if (!obj) return undefined;
        const parts = path.split('.');
        return parts.reduce((acc, part) => (acc && Object.prototype.hasOwnProperty.call(acc, part) ? acc[part] : undefined), obj);
    }

    function setByPath(obj, path, value) {
        const parts = path.split('.');
        let current = obj;
        parts.forEach((part, index) => {
            if (index === parts.length - 1) {
                current[part] = value;
            } else {
                if (!current[part] || typeof current[part] !== 'object') {
                    current[part] = {};
                }
                current = current[part];
            }
        });
    }

    function persistCurrentInspector() {
        if (!inspectorState) return;
        const entry = state.nodeIndex.get(inspectorState.nodeId);
        if (!entry) return;
        const { node } = entry;

        let configPayload = {};
        if (dom.inspectorBody.querySelector('textarea[data-field="json"]')) {
            try {
                configPayload = JSON.parse(dom.inspectorBody.querySelector('textarea[data-field="json"]').value || '{}');
            } catch (error) {
                showToast('JSON non valido', 'error');
                return;
            }
        } else {
            configPayload = inspectorState.config || {};
        }

        if (node.inlineDraft !== null) {
            configPayload.__inline_html = node.inlineDraft;
        }

        const payload = {
            action: 'save-instance',
            page_id: state.currentPageId,
            module_name: node.module,
            instance_name: node.instance_name,
            config: configPayload,
            order_index: node.order_index || 0,
            parent_instance_id: node.parent_instance_id,
        };

        if (typeof node.id === 'number') {
            payload.instance_id = node.id;
        } else {
            payload.instance_id = 'temp';
        }

        fetch('api/page_builder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.error || 'Errore durante il salvataggio');
                }
                applySaveResult(node, data.instance);
                showToast('Modulo salvato con successo', 'success');
            })
            .catch((error) => {
                showToast(error.message, 'error');
            });
    }

    function applySaveResult(node, result) {
        const previousId = node.id;
        node.instance_name = result.instance_name;
        node.html = result.html;
        node.config = result.config || {};
        node.inlineDraft = null;
        node._configDirty = false;
        dom.cancelInlineBtn.style.display = 'none';

        if (typeof previousId === 'string' && previousId.startsWith('temp-')) {
            state.changes.added.delete(previousId);
            state.changes.updated.delete(previousId);
            node.id = result.id;
            node.isNew = false;
            state.selectedNodeId = node.id;
        }
        state.changes.updated.delete(node.id);

        rebuildIndex();
        renderStage();
        updateChangeBadges();
        loadInspectorForNode(node.id);
        persistOrder();
    }

    function persistOrder() {
        const updates = [];
        const walk = (nodes, parentId = null) => {
            nodes.forEach((node, index) => {
                node.order_index = index;
                node.parent_instance_id = parentId;
                if (typeof node.id === 'number') {
                    updates.push({
                        id: node.id,
                        order_index: index,
                        parent_instance_id: parentId,
                    });
                }
                if (node.children?.length) {
                    walk(node.children, typeof node.id === 'number' ? node.id : null);
                }
            });
        };
        walk(state.tree, null);

        if (!updates.length) {
            return;
        }

        fetch('api/page_builder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update-order', updates }),
        }).catch(() => {});
    }

    function confirmDeleteNode(nodeId) {
        const entry = state.nodeIndex.get(nodeId);
        if (!entry) return;
        if (!window.confirm('Eliminare questo modulo e i suoi elementi annidati?')) {
            return;
        }

        if (typeof nodeId === 'number') {
            fetch('api/page_builder.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete-instance', instance_id: nodeId }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.error || 'Errore durante l\'eliminazione');
                    }
                    const removedIds = removeNode(nodeId);
                    rebuildIndex();
                    removedIds.forEach((id) => {
                        state.changes.added.delete(id);
                        state.changes.updated.delete(id);
                        if (typeof id === 'number') {
                            state.changes.deleted.add(id);
                        }
                    });
                    renderStage();
                    updateChangeBadges();
                    dom.inspectorBody.innerHTML = '<p>Modulo eliminato. Seleziona un altro elemento.</p>';
                    dom.saveConfigBtn.style.display = 'none';
                    showToast('Modulo eliminato', 'success');
                    state.selectedNodeId = null;
                })
                .catch((error) => showToast(error.message, 'error'));
        } else {
            const removedIds = removeNode(nodeId);
            rebuildIndex();
            removedIds.forEach((id) => {
                state.changes.added.delete(id);
                state.changes.updated.delete(id);
            });
            renderStage();
            updateChangeBadges();
            dom.inspectorBody.innerHTML = '<p>Modulo eliminato.</p>';
            dom.saveConfigBtn.style.display = 'none';
            state.selectedNodeId = null;
        }
    }

    function toggleInlineMode() {
        state.inlineMode = !state.inlineMode;
        if (state.inlineMode) {
            dom.inlineToggle.classList.add('is-active');
            showToast('Modalità inline attiva. Clicca sul contenuto per modificarlo.', 'success');
        } else {
            dom.inlineToggle.classList.remove('is-active');
            cancelInlineDraft();
        }
        updateInlineState();
    }

    function updateInlineState() {
        dom.stage.querySelectorAll('.pb-node__preview').forEach((preview) => {
            const nodeId = parseNodeId(preview.dataset.instanceId);
            if (state.inlineMode && nodeId === state.selectedNodeId) {
                preview.contentEditable = 'true';
                preview.addEventListener('focus', handleInlineFocus);
                preview.addEventListener('input', handleInlineInput);
            } else {
                preview.contentEditable = 'false';
                preview.removeEventListener('focus', handleInlineFocus);
                preview.removeEventListener('input', handleInlineInput);
            }
        });
    }

    function handleInlineFocus(event) {
        const nodeId = parseNodeId(event.currentTarget.dataset.instanceId);
        const entry = state.nodeIndex.get(nodeId);
        if (!entry) return;
        const { node } = entry;
        if (node.inlineDraft === null) {
            node.inlineDraft = node.html;
        }
        dom.cancelInlineBtn.style.display = 'block';
    }

    function handleInlineInput(event) {
        const nodeId = parseNodeId(event.currentTarget.dataset.instanceId);
        const entry = state.nodeIndex.get(nodeId);
        if (!entry) return;
        const { node } = entry;
        node.inlineDraft = event.currentTarget.innerHTML;
        state.changes.updated.add(nodeId);
        updateChangeBadges();
    }

    function cancelInlineDraft() {
        if (state.selectedNodeId === null) return;
        const entry = state.nodeIndex.get(state.selectedNodeId);
        if (!entry) return;
        const { node } = entry;
        if (node.inlineDraft !== null) {
            node.inlineDraft = null;
            renderStage();
            loadInspectorForNode(node.id);
        }
        if (!node._configDirty) {
            state.changes.updated.delete(node.id);
            updateChangeBadges();
        }
        dom.cancelInlineBtn.style.display = 'none';
    }

    function updateStageHeader() {
        if (!state.currentPage) {
            dom.stageName.textContent = 'Nessuna pagina selezionata';
            dom.stageSlug.textContent = '';
            return;
        }
        const status = state.currentPage.status ?? 'draft';
        dom.stageName.textContent = state.currentPage.title;
        dom.stageSlug.textContent = `Slug: ${state.currentPage.slug} · Stato: ${status}`;
    }

    function updateChangeBadges() {
        dom.changeBadges.innerHTML = '';
        const entries = [
            { label: 'Nuovi', set: state.changes.added, icon: 'fa-plus', tone: 'success' },
            { label: 'Modificati', set: state.changes.updated, icon: 'fa-pen', tone: 'warning' },
            { label: 'Eliminati', set: state.changes.deleted, icon: 'fa-trash', tone: 'danger' },
        ];
        entries.forEach((entry) => {
            if (entry.set.size === 0) return;
            const badge = document.createElement('span');
            badge.className = 'pb-badge';
            badge.innerHTML = `<span class="pb-status-dot ${entry.tone}"></span><i class="fa-solid ${entry.icon}"></i> ${entry.label}: ${entry.set.size}`;
            dom.changeBadges.appendChild(badge);
        });
    }

    function showToast(message, type = 'info') {
        if (!dom.toast) return;
        dom.toast.textContent = '';
        dom.toast.className = 'pb-toast';
        if (type === 'error') dom.toast.classList.add('pb-toast--error');
        if (type === 'success') dom.toast.classList.add('pb-toast--success');
        dom.toast.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-circle-xmark' : 'fa-circle-check'}"></i> ${message}`;
        dom.toast.style.display = 'flex';
        clearTimeout(dom.toast._timeout);
        dom.toast._timeout = setTimeout(() => {
            dom.toast.style.display = 'none';
        }, 3000);
    }

    function focusInspector() {
        dom.inspectorBody?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function previewCurrentPage() {
        if (!state.currentPageId) return;
        window.open(`../index.php?id_pagina=${state.currentPageId}`, '_blank');
    }

    function loadPage(pageId) {
        fetch(`api/page_builder.php?action=page&page_id=${pageId}`)
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.error || 'Errore caricamento pagina');
                }
                state.currentPageId = pageId;
                state.currentPage = data.page;
                state.modules = (data.instances || []).map((instance) => ({
                    id: instance.id,
                    module: instance.module,
                    instance_name: instance.instance_name,
                    order_index: instance.order_index,
                    parent_instance_id: instance.parent_instance_id ?? null,
                    html: instance.html,
                    config: instance.config || {},
                }));
                state.tree = buildTree(state.modules);
                rebuildIndex();
                initModuleCounters();
                state.selectedNodeId = null;
                inspectorState = null;
                dom.inspectorBody.innerHTML = '<p>Seleziona un modulo per modificarlo.</p>';
                dom.saveConfigBtn.style.display = 'none';
                state.changes.added.clear();
                state.changes.updated.clear();
                state.changes.deleted.clear();
                renderPageSelector();
                updateStageHeader();
                renderStage();
                updateChangeBadges();
            })
            .catch((error) => {
                showToast(error.message, 'error');
            });
    }

    function handleCreatePage() {
        const title = window.prompt('Titolo nuova pagina');
        if (!title) return;
        const slug = window.prompt('Slug (facoltativo, verrà generato dal titolo se vuoto)', '');
        fetch('api/page_builder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create-page', title, slug }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.error || 'Errore creazione pagina');
                }
                state.pages.push(data.page);
                renderPageSelector();
                loadPage(data.page.id);
                showToast('Pagina creata', 'success');
            })
            .catch((error) => showToast(error.message, 'error'));
    }

    function handleRenamePage() {
        if (!state.currentPageId) return;
        const current = state.pages.find((page) => page.id === state.currentPageId);
        const title = window.prompt('Nuovo titolo pagina', current?.title || '');
        if (!title) return;
        const slug = window.prompt('Slug (lascia vuoto per auto-generare)', current?.slug || '');
        fetch('api/page_builder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update-page', page_id: state.currentPageId, title, slug }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.error || 'Errore aggiornamento pagina');
                }
                const pageIndex = state.pages.findIndex((page) => page.id === state.currentPageId);
                if (pageIndex >= 0) {
                    state.pages[pageIndex] = { ...state.pages[pageIndex], ...data.page };
                }
                state.currentPage = { ...state.currentPage, ...data.page };
                renderPageSelector();
                updateStageHeader();
                showToast('Pagina aggiornata', 'success');
            })
            .catch((error) => showToast(error.message, 'error'));
    }

    function handleDeletePage() {
        if (!state.currentPageId) return;
        if (!window.confirm('Eliminare definitivamente questa pagina?')) {
            return;
        }
        fetch('api/page_builder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete-page', page_id: state.currentPageId }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.error || 'Errore eliminazione pagina');
                }
                state.pages = state.pages.filter((page) => page.id !== state.currentPageId);
                state.currentPageId = state.pages[0]?.id ?? null;
                if (state.currentPageId) {
                    loadPage(state.currentPageId);
                } else {
                    state.currentPage = null;
                    state.tree = [];
                    state.modules = [];
                    renderPageSelector();
                    renderStage();
                    updateStageHeader();
                }
                showToast('Pagina eliminata', 'success');
            })
            .catch((error) => showToast(error.message, 'error'));
    }

    init();
})();
