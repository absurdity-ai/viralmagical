document.addEventListener('DOMContentLoaded', () => {
    // State
    const state = {
        step: 1,
        panels: [{ id: 1, name: 'Panel 1' }],
        inputs: [{ id: 1, role: 'character', label: 'Character Image', type: 'image', required: true }],
        mapping: {},
        previewValues: {} // Store preview input values if needed, or just read from DOM
    };

    // DOM Elements
    const steps = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');
    const nextBtns = document.querySelectorAll('.next-step');
    const prevBtns = document.querySelectorAll('.prev-step');

    const panelsContainer = document.getElementById('panelsContainer');
    const addPanelBtn = document.getElementById('addPanelBtn');

    const inputsContainer = document.getElementById('inputsContainer');
    const addInputBtn = document.getElementById('addInputBtn');

    const mappingContainer = document.getElementById('mappingContainer');

    const previewInputsContainer = document.getElementById('previewInputsContainer');
    const generatePreviewBtn = document.getElementById('generatePreviewBtn');
    const previewResults = document.getElementById('previewResults');
    const previewStrip = document.getElementById('previewStrip');

    const publishBtn = document.getElementById('publishBtn');
    const magicFillBtn = document.getElementById('magicFillBtn');

    // --- Magic Auto-Fill ---
    if (magicFillBtn) {
        magicFillBtn.addEventListener('click', async () => {
            const appName = document.getElementById('appName').value.trim();
            const appDesc = document.getElementById('appDescription').value.trim();

            if (!appName) {
                alert('Please enter an App Name first (e.g., "Pokemon Card").');
                return;
            }

            magicFillBtn.disabled = true;
            magicFillBtn.textContent = '✨ Generating...';

            try {
                const response = await fetch('api/generate_recipe.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appName, description: appDesc })
                });
                const result = await response.json();

                if (result.success && result.recipe) {
                    applyRecipe(result.recipe);
                    alert('✨ Magic Recipe Applied! Check Inputs and Mapping.');
                } else {
                    alert('Error generating recipe: ' + (result.error || 'Unknown error'));
                }
            } catch (e) {
                console.error(e);
                alert('Network error.');
            } finally {
                magicFillBtn.disabled = false;
                magicFillBtn.textContent = '✨ Magic Auto-Fill';
            }
        });
    }

    function applyRecipe(recipe) {
        // 1. Set Inputs
        state.inputs = recipe.inputs.map((i, idx) => ({
            id: idx + 1,
            role: i.role,
            label: i.label,
            type: i.type || 'image',
            required: i.required !== false
        }));

        // Re-render inputs UI (simple way: clear and add)
        inputsContainer.innerHTML = '';
        state.inputs.forEach(input => {
            const div = document.createElement('div');
            div.className = 'input-item';
            div.dataset.id = input.id;
            div.innerHTML = `
                <div class="input-header">
                    <span class="input-number">Input ${input.id}</span>
                    <button class="btn-icon remove-input">×</button>
                </div>
                <div class="input-row">
                    <input type="text" class="input-role" placeholder="Role ID" value="${input.role}">
                    <input type="text" class="input-label" placeholder="User Label" value="${input.label}">
                </div>
                <div class="input-row">
                    <select class="input-type">
                        <option value="image" ${input.type === 'image' ? 'selected' : ''}>Image</option>
                        <option value="text" ${input.type === 'text' ? 'selected' : ''}>Text</option>
                    </select>
                    <label class="checkbox-label">
                        <input type="checkbox" class="input-required" ${input.required ? 'checked' : ''}> Required
                    </label>
                </div>
            `;
            inputsContainer.appendChild(div);
        });
        updateInputNumbers();

        // 2. Set Panels & Mapping
        state.panels = recipe.panels.map((p, idx) => ({
            id: idx + 1,
            name: p.name || `Panel ${idx + 1}`
        }));

        // Re-render panels UI
        panelsContainer.innerHTML = '';
        state.panels.forEach(panel => {
            const div = document.createElement('div');
            div.className = 'panel-item';
            div.dataset.id = panel.id;
            div.innerHTML = `
                <span class="panel-number">${panel.id}</span>
                <input type="text" class="panel-name" placeholder="Section Name" value="${panel.name}">
                <button class="btn-icon remove-panel">×</button>
            `;
            panelsContainer.appendChild(div);
        });
        updatePanelNumbers();

        // 3. Set Mapping State
        state.mapping = {};
        recipe.panels.forEach((p, idx) => {
            state.mapping[idx + 1] = p.prompt;
        });

        // If we are on step 3, re-render mapping
        if (state.step === 3) {
            renderMapping();
        }
    }

    // --- Navigation ---
    function goToStep(stepNum) {
        state.step = parseInt(stepNum);

        // Update UI
        steps.forEach(s => {
            const sNum = parseInt(s.dataset.step);
            s.classList.toggle('active', sNum === state.step);
            if (sNum < state.step) s.classList.add('completed'); // Optional style
        });

        stepContents.forEach(c => {
            c.classList.add('hidden');
            if (c.id === `step-${state.step}`) c.classList.remove('hidden');
        });

        // Trigger logic for specific steps
        if (state.step === 3) renderMapping();
        if (state.step === 4) renderPreview();
        if (state.step === 5) renderReview();
    }

    nextBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const next = btn.dataset.next;
            if (validateStep(state.step)) {
                goToStep(next);
            }
        });
    });

    prevBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const prev = btn.dataset.prev;
            goToStep(prev);
        });
    });

    function validateStep(step) {
        if (step === 1) {
            const name = document.getElementById('appName').value.trim();
            if (!name) {
                alert('Please give your app a name.');
                return false;
            }
            // Update panel names in state
            const panelInputs = document.querySelectorAll('.panel-name');
            state.panels = Array.from(panelInputs).map((input, index) => ({
                id: index + 1,
                name: input.value.trim() || `Panel ${index + 1}`
            }));
        }
        if (step === 2) {
            // Update inputs in state
            const inputItems = document.querySelectorAll('.input-item');
            state.inputs = Array.from(inputItems).map((item, index) => ({
                id: index + 1,
                role: item.querySelector('.input-role').value.trim(),
                label: item.querySelector('.input-label').value.trim(),
                type: item.querySelector('.input-type').value,
                required: item.querySelector('.input-required').checked
            }));

            // Check for duplicate roles
            const roles = state.inputs.map(i => i.role);
            if (new Set(roles).size !== roles.length) {
                alert('Role IDs must be unique.');
                return false;
            }
            if (roles.some(r => !r)) {
                alert('All inputs must have a Role ID.');
                return false;
            }
        }
        if (step === 3) {
            // Save mapping
            const mappingItems = document.querySelectorAll('.mapping-item');
            state.mapping = {};
            mappingItems.forEach(item => {
                const panelId = item.dataset.panelId;
                const prompt = item.querySelector('.mapping-prompt').value;
                state.mapping[panelId] = prompt;
            });
        }
        if (step === 4) {
            // Preview step validation (optional)
            // Maybe require at least one preview? Nah, let them skip if they want.
        }
        return true;
    }

    // --- Step 1: Panels ---
    addPanelBtn.addEventListener('click', () => {
        const newId = state.panels.length + 1;
        const div = document.createElement('div');
        div.className = 'panel-item';
        div.dataset.id = newId;
        div.innerHTML = `
            <span class="panel-number">${newId}</span>
            <input type="text" class="panel-name" placeholder="Section Name" value="Panel ${newId}">
            <button class="btn-icon remove-panel">×</button>
        `;
        panelsContainer.appendChild(div);
        updatePanelNumbers();
    });

    panelsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-panel')) {
            if (document.querySelectorAll('.panel-item').length > 1) {
                e.target.closest('.panel-item').remove();
                updatePanelNumbers();
            }
        }
    });

    function updatePanelNumbers() {
        const items = document.querySelectorAll('.panel-item');
        items.forEach((item, index) => {
            item.querySelector('.panel-number').textContent = index + 1;
            // Update placeholder if it's default
        });
        // Disable remove button if only 1
        document.querySelectorAll('.remove-panel').forEach(btn => {
            btn.disabled = items.length === 1;
        });
    }

    // --- Step 2: Inputs ---
    addInputBtn.addEventListener('click', () => {
        const newId = document.querySelectorAll('.input-item').length + 1;
        const div = document.createElement('div');
        div.className = 'input-item';
        div.dataset.id = newId;
        div.innerHTML = `
            <div class="input-header">
                <span class="input-number">Input ${newId}</span>
                <button class="btn-icon remove-input">×</button>
            </div>
            <div class="input-row">
                <input type="text" class="input-role" placeholder="Role ID" value="role_${newId}">
                <input type="text" class="input-label" placeholder="User Label" value="Input ${newId}">
            </div>
            <div class="input-row">
                <select class="input-type">
                    <option value="image">Image</option>
                    <option value="text">Text</option>
                </select>
                <label class="checkbox-label">
                    <input type="checkbox" class="input-required" checked> Required
                </label>
            </div>
        `;
        inputsContainer.appendChild(div);
        updateInputNumbers();
    });

    inputsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-input')) {
            if (document.querySelectorAll('.input-item').length > 1) {
                e.target.closest('.input-item').remove();
                updateInputNumbers();
            }
        }
    });

    function updateInputNumbers() {
        const items = document.querySelectorAll('.input-item');
        items.forEach((item, index) => {
            item.querySelector('.input-number').textContent = `Input ${index + 1}`;
        });
        document.querySelectorAll('.remove-input').forEach(btn => {
            btn.disabled = items.length === 1;
        });
    }

    // --- Step 3: Mapping ---
    function renderMapping() {
        mappingContainer.innerHTML = '';
        state.panels.forEach((panel, index) => {
            const div = document.createElement('div');
            div.className = 'mapping-item';
            div.dataset.panelId = panel.id; // Use ID or index? Let's use 1-based index as ID for simplicity

            // Create tags for inputs
            const tagsHtml = state.inputs.map(input =>
                `<span class="mapping-tag" data-role="${input.role}">[${input.role}]</span>`
            ).join('');

            div.innerHTML = `
                <h4>${panel.name} (Panel ${index + 1})</h4>
                <div class="mapping-help">
                    <p><strong>💡 Tip:</strong> You must describe the visual style here. The AI doesn't know your App Name.</p>
                    <p><em>Bad:</em> [character]</p>
                    <p><em>Good:</em> A 90s anime style trading card featuring [character] in a dynamic pose...</p>
                </div>
                <textarea class="mapping-prompt" placeholder="Describe the scene... e.g., A cinematic shot of [character]...">${state.mapping[panel.id] || ''}</textarea>
                <div class="mapping-tags">
                    ${tagsHtml}
                </div>
            `;
            mappingContainer.appendChild(div);
        });

        // Add tag click listeners
        document.querySelectorAll('.mapping-tag').forEach(tag => {
            tag.addEventListener('click', (e) => {
                const role = e.target.dataset.role;
                const textarea = e.target.closest('.mapping-item').querySelector('textarea');
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const insert = `[${role}]`;
                textarea.value = text.substring(0, start) + insert + text.substring(end);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + insert.length;
            });
        });
    }

    // --- Step 4: Preview ---
    function renderPreview() {
        previewInputsContainer.innerHTML = '';

        if (state.inputs.length === 0) {
            previewInputsContainer.innerHTML = '<p>No inputs defined.</p>';
            return;
        }

        state.inputs.forEach(input => {
            const div = document.createElement('div');
            div.className = 'form-group';
            div.innerHTML = `
                <label>${input.label} (${input.role})</label>
                ${input.type === 'image'
                    ? `<input type="file" class="preview-input" data-role="${input.role}" accept="image/*">`
                    : `<input type="text" class="preview-input" data-role="${input.role}" placeholder="Enter test value">`
                }
            `;
            previewInputsContainer.appendChild(div);
        });
    }

    if (generatePreviewBtn) {
        generatePreviewBtn.addEventListener('click', async () => {
            // Gather inputs
            const formData = new FormData();
            const inputs = document.querySelectorAll('.preview-input');
            let hasError = false;

            inputs.forEach(input => {
                const role = input.dataset.role;
                if (input.type === 'file') {
                    if (input.files.length > 0) {
                        formData.append(role, input.files[0]);
                    } else {
                        // If required, maybe warn? For preview, let's be lenient or strict?
                        // Let's just warn if it's required.
                        // Check state.inputs for required status
                        const def = state.inputs.find(i => i.role === role);
                        if (def && def.required) {
                            // alert(`Please provide a value for ${def.label}`);
                            // hasError = true;
                        }
                    }
                } else {
                    formData.append(role, input.value);
                }
            });

            // Add Mapping and Inputs Definition
            formData.append('mapping', JSON.stringify(state.mapping));
            formData.append('inputs', JSON.stringify(state.inputs));

            // Debug logging
            console.log('Preview Generator - State mapping:', state.mapping);
            console.log('Preview Generator - State inputs:', state.inputs);
            console.log('Preview Generator - FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            // UI Loading State
            generatePreviewBtn.disabled = true;
            generatePreviewBtn.querySelector('.loader').classList.remove('hidden');
            generatePreviewBtn.querySelector('.btn-text').textContent = 'Testing consistency...';
            previewResults.classList.add('hidden');
            previewStrip.innerHTML = '';

            try {
                const response = await fetch('api/preview_generator.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Show results
                    previewResults.classList.remove('hidden');
                    result.images.forEach(url => {
                        const img = document.createElement('img');
                        img.src = url;
                        img.className = 'preview-image';
                        previewStrip.appendChild(img);
                    });
                } else {
                    alert('Preview failed: ' + (result.error || 'Unknown error'));
                }
            } catch (e) {
                console.error(e);
                alert('Network error during preview.');
            } finally {
                generatePreviewBtn.disabled = false;
                generatePreviewBtn.querySelector('.loader').classList.add('hidden');
                generatePreviewBtn.querySelector('.btn-text').textContent = 'Generate Preview';
            }
        });
    }

    // --- Step 5: Review & Publish ---
    function renderReview() {
        const appName = document.getElementById('appName').value;
        const appDesc = document.getElementById('appDescription').value;

        document.getElementById('reviewAppName').textContent = appName;
        document.getElementById('reviewAppDesc').textContent = appDesc || 'No description';

        const formatList = document.getElementById('reviewFormat');
        formatList.innerHTML = state.panels.map(p => `<li>${p.name}</li>`).join('');

        const inputsList = document.getElementById('reviewInputs');
        inputsList.innerHTML = state.inputs.map(i => `<li>${i.label} (${i.role})</li>`).join('');

        // Generate JSON
        const appData = {
            id: appName.toLowerCase().replace(/[^a-z0-9]+/g, '_'),
            name: appName,
            description: appDesc,
            icon: '✨', // Default icon
            inputs: state.inputs.map((i, idx) => ({
                slot: idx + 1,
                role: i.role,
                label: i.label,
                required: i.required,
                type: i.type
            })),
            layout: {
                type: 'multi_panel',
                panels: state.panels.length
            },
            // We need to store the mapping per panel.
            // The current app_config structure uses a single 'prompt_template' for some, 
            // but for multi-panel it might need an array.
            // Let's standardize on an array of prompts for this new generator.
            prompts: state.panels.map((p, idx) => state.mapping[idx + 1] || "")
        };

        document.getElementById('reviewJson').textContent = JSON.stringify(appData, null, 2);
        state.finalData = appData;
    }

    publishBtn.addEventListener('click', async () => {
        publishBtn.disabled = true;
        publishBtn.querySelector('.loader').classList.remove('hidden');
        publishBtn.querySelector('.btn-text').textContent = 'PUBLISHING...';

        try {
            const response = await fetch('api/save_app.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(state.finalData)
            });

            const result = await response.json();

            if (result.success) {
                alert('App Published Successfully!');
                window.location.href = 'index.php'; // Redirect to home to see it
            } else {
                alert('Error publishing app: ' + result.error);
                publishBtn.disabled = false;
                publishBtn.querySelector('.loader').classList.add('hidden');
                publishBtn.querySelector('.btn-text').textContent = '🚀 PUBLISH APP';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error occurred.');
            publishBtn.disabled = false;
            publishBtn.querySelector('.loader').classList.add('hidden');
            publishBtn.querySelector('.btn-text').textContent = '🚀 PUBLISH APP';
        }
    });

});
