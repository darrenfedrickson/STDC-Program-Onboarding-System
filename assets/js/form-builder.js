// assets/js/form-builder.js
document.addEventListener("DOMContentLoaded", () => {
    const addFieldBtn = document.getElementById("add-field-btn");
    const fieldsContainer = document.getElementById("fields-container");
    const formSchemaInput = document.getElementById("form_schema");
    
    let fields = [];

    if (addFieldBtn && fieldsContainer) {
        addFieldBtn.addEventListener("click", () => {
            const fieldId = Date.now();
            
            const fieldHTML = `
                <div class="builder-field" id="field-${fieldId}">
                    <div class="builder-field-header">
                        <strong>New Field</strong>
                        <button type="button" class="btn btn-sm btn-outline btn-danger remove-field-btn" data-id="${fieldId}">Remove</button>
                    </div>
                    <div class="grid grid-cols-2">
                        <div class="form-group">
                            <label class="form-label">Field Label</label>
                            <input type="text" class="form-control field-label" placeholder="e.g. What is your role?" required>
                        </div>
                    <div class="form-group mt-2">
                        <label class="form-label">Field Description (Optional sub-text)</label>
                        <input type="text" class="form-control field-description" placeholder="e.g. Kindly provide your full LinkedIn link.">
                    </div>
                    <div class="grid grid-cols-2 mt-2">
                        <div class="form-group">
                            <label class="form-label">Field Type</label>
                            <select class="form-control field-type">
                                <option value="text">Text (Single Line)</option>
                                <option value="textarea">Text (Multi Line)</option>
                                <option value="email">Email</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="select">Dropdown</option>
                                <option value="radio">Multiple Choice (Radio)</option>
                                <option value="checkbox">Checkboxes</option>
                                <option value="file">File Upload</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" class="field-required"> Required Field
                            </label>
                        </div>
                    </div>
                    <div class="builder-options mt-2" style="display: none;">
                        <label class="form-label">Options (comma separated)</label>
                        <input type="text" class="form-control field-options" placeholder="e.g. Option 1, Option 2, Option 3">
                    </div>
                </div>
            `;
            
            fieldsContainer.insertAdjacentHTML("beforeend", fieldHTML);
            
            const newField = document.getElementById(`field-${fieldId}`);
            
            // Toggle options input based on type
            const typeSelect = newField.querySelector(".field-type");
            const optionsDiv = newField.querySelector(".builder-options");
            
            typeSelect.addEventListener("change", (e) => {
                if (["select", "radio", "checkbox"].includes(e.target.value)) {
                    optionsDiv.style.display = "block";
                } else {
                    optionsDiv.style.display = "none";
                }
                updateSchema();
            });

            // Add input listeners to update schema dynamically
            newField.querySelectorAll("input, select").forEach(input => {
                input.addEventListener("input", updateSchema);
                input.addEventListener("change", updateSchema);
            });

            // Remove button listener
            newField.querySelector(".remove-field-btn").addEventListener("click", function() {
                newField.remove();
                updateSchema();
            });
            
            updateSchema();
        });
        
        // Initialize from existing data if available
        if (window.initialFormSchema && Array.isArray(window.initialFormSchema)) {
            window.initialFormSchema.forEach(field => {
                addFieldBtn.click();
                const lastField = fieldsContainer.lastElementChild;
                lastField.querySelector('.field-label').value = field.label;
                lastField.querySelector('.field-type').value = field.type;
                lastField.querySelector('.field-description').value = field.description || '';
                lastField.querySelector('.field-required').checked = field.required == 1;
                if (["select", "radio", "checkbox"].includes(field.type) && field.options) {
                    const optionsDiv = lastField.querySelector('.builder-options');
                    optionsDiv.style.display = 'block';
                    let optsArray = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
                    lastField.querySelector('.field-options').value = Array.isArray(optsArray) ? optsArray.join(', ') : '';
                }
            });
            updateSchema();
        }
    }

    function updateSchema() {
        if(!formSchemaInput) return;
        
        fields = [];
        document.querySelectorAll(".builder-field").forEach((fieldEl, index) => {
            const label = fieldEl.querySelector(".field-label").value.trim();
            const type = fieldEl.querySelector(".field-type").value;
            const description = fieldEl.querySelector(".field-description").value.trim();
            const isRequired = fieldEl.querySelector(".field-required").checked;
            
            // Generate a safe name based on label or index
            let name = label.toLowerCase().replace(/[^a-z0-9]/g, '_');
            if (!name) name = `field_${index}`;

            let fieldData = {
                name: name,
                label: label || `Field ${index + 1}`,
                description: description,
                type: type,
                required: isRequired
            };

            if (["select", "radio", "checkbox"].includes(type)) {
                const optionsStr = fieldEl.querySelector(".field-options").value;
                fieldData.options = optionsStr.split(",").map(s => s.trim()).filter(s => s !== "");
            }

            fields.push(fieldData);
        });

        formSchemaInput.value = JSON.stringify(fields);
    }
    
    // Template System Logic
    const saveTemplateBtn = document.getElementById("save-template-btn");
    const saveModal = document.getElementById("saveTemplateModal");
    
    // Save Template Logic
    if (saveTemplateBtn && saveModal) {
        saveTemplateBtn.addEventListener("click", () => {
            let currentSchemaStr = formSchemaInput ? formSchemaInput.value : "[]";
            if (fields.length === 0 && currentSchemaStr === "[]") {
                alert("Please add some fields to your form before saving it as a template.");
                return;
            }
            saveModal.style.display = 'flex';
        });
        
        document.getElementById("confirm-save-template").addEventListener("click", () => {
            const name = document.getElementById("new_template_name").value.trim();
            if (!name) {
                alert("Please enter a template name.");
                return;
            }
            
            if (addFieldBtn && fieldsContainer) {
                updateSchema(); // Ensure latest schema is in input if in builder mode
            }
            
            fetch('/stdc-program-onboarding-system/admin/api_templates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    schema_json: formSchemaInput.value
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    alert("Template saved successfully!");
                    saveModal.style.display = 'none';
                    document.getElementById("new_template_name").value = '';
                } else {
                    alert("Error: " + res.message);
                }
            })
            .catch(err => {
                alert("An error occurred while saving the template.");
            });
        });
    }
});
