<?php
namespace Core\UI\Components;

class Form
{
    public static function render(array $fields, string $action, array $data = [], string $method = 'POST'): string
    {
        $html = "<form method='POST' action='{$action}' enctype='multipart/form-data' class='max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-2xl shadow-xl space-y-6' onsubmit='return validateForm(this)'>";
        foreach ($fields as $field) {
            $name = $field['name'];
            if (in_array($name, ['id', 'created_at', 'updated_at'])) continue;
            $eName = e($name);
            $val = $data[$name] ?? '';
            $eVal = e($val);
            $required = !($field['nullable'] ?? true) ? 'required' : '';
            $html .= "<div><label class='block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2'>{$eName}";
            if ($required) {
                $html .= " <span class='text-red-500'>*</span>";
            }
            $html .= "</label>";
            $html .= "<div class='relative'>";
            if (!empty($field['references']['table'])) {
                $refTable = $field['references']['table'];
                $refCol = !empty($field['references']['column']) ? $field['references']['column'] : 'id';
                try {
                    $refData = \Core\Database\DB::query("SELECT id, " . preg_replace('/[^a-zA-Z0-9_]/', '', $refCol) . " FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $refTable) . "`");
                    $html .= "<div class='relative'>";
                    $html .= "<input type='text' id='autocomplete-{$eName}' {$required} placeholder='Type to search...' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-inner' autocomplete='off'>";
                    $html .= "<input type='hidden' name='{$eName}' id='hidden-{$eName}' value='{$eVal}'>";
                    $html .= "<div id='dropdown-{$eName}' class='hidden absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg max-h-60 overflow-y-auto'></div>";
                    $html .= "<script>window.refData_{$eName} = " . json_encode($refData) . ";";
                    $html .= "window.refCol_{$eName} = '" . e($refCol) . "';</script>";
                    $html .= "</div>";
                } catch (\Exception $e) {
                    $html .= "<input type='text' name='{$eName}' value='{$eVal}' {$required} placeholder='Error loading " . e($refTable) . "' class='w-full bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-3 text-slate-900 dark:text-slate-200 focus:outline-none transition-all shadow-inner'>";
                }
            } else {
                $inputType = 'text';
                $fType = strtoupper($field['type'] ?? 'TEXT');
                if ($fType === 'DATE') {
                    $inputType = 'date';
                } elseif (in_array($fType, ['DATETIME', 'TIMESTAMP'])) {
                    $inputType = 'datetime-local';
                } elseif ($fType === 'TIME') {
                    $inputType = 'time';
                } elseif (in_array($fType, ['INTEGER', 'BIGINT', 'SMALLINT', 'TINYINT'])) {
                    $inputType = 'number';
                } elseif (stripos($name, 'color') !== false || stripos($name, 'colour') !== false) {
                    $html .= "<div class='flex gap-2 items-center'>";
                    $html .= "<input type='color' name='{$eName}' value='{$eVal}' {$required} class='h-12 w-20 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg cursor-pointer'>";
                    $html .= "<input type='text' id='color-text-{$eName}' value='{$eVal}' {$required} class='flex-1 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-inner font-mono uppercase'>";
                    $html .= "</div>";
                    $html .= "<span class='error-message hidden text-xs text-red-500 mt-1'>This field is required</span>";
                    $html .= "</div></div>";
                    continue;
                } elseif ($fType === 'BLOB') {
                    // File upload for BLOB fields
                    $html .= "<div class='space-y-3'>";
                    $html .= "<div class='border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-6 text-center hover:border-indigo-500 dark:hover:border-indigo-500 transition-all cursor-pointer' id='drop-zone-{$eName}'>";
                    $html .= "<input type='file' name='{$eName}' id='file-{$eName}' {$required} class='hidden' accept='image/*,application/pdf,.doc,.docx,.txt'>";
                    $html .= "<div id='upload-prompt-{$eName}'>";
                    $html .= "<i data-lucide='upload-cloud' class='w-12 h-12 mx-auto text-slate-400 mb-3'></i>";
                    $html .= "<p class='text-sm font-medium text-slate-700 dark:text-slate-300 mb-1'>Click to upload or drag and drop</p>";
                    $html .= "<p class='text-xs text-slate-500'>Images, PDF, DOC, TXT (Max 10MB)</p>";
                    $html .= "</div>";
                    $html .= "<div id='preview-{$eName}' class='hidden'>";
                    $html .= "<img id='preview-img-{$eName}' class='max-w-full max-h-48 mx-auto rounded-lg mb-3 hidden'>";
                    $html .= "<div id='preview-file-{$eName}' class='flex items-center justify-center gap-2 text-sm text-slate-700 dark:text-slate-300 mb-3 hidden'>";
                    $html .= "<i data-lucide='file' class='w-5 h-5'></i>";
                    $html .= "<span id='file-name-{$eName}'></span>";
                    $html .= "</div>";
                    $html .= "<button type='button' onclick='clearFile(\"{$eName}\")' class='text-xs text-red-500 hover:text-red-600 font-medium'>Remove</button>";
                    $html .= "</div>";
                    $html .= "</div>";
                    if (!empty($val)) {
                        $html .= "<div class='text-xs text-slate-500 mt-2'>Current file stored in database</div>";
                    }
                    $html .= "</div>";
                    $html .= "<span class='error-message hidden text-xs text-red-500 mt-1'>This field is required</span>";
                    $html .= "</div></div>";
                    continue;
                } elseif ($fType === 'ENUM') {
                    $rawOptions = $field['options'] ?? $field['enum'] ?? $field['values'] ?? $field['length'] ?? '';
                    $options = is_array($rawOptions) ? $rawOptions : array_filter(array_map('trim', explode(',', (string)$rawOptions)), fn($v) => $v !== '');
                    if (!empty($options)) {
                        $html .= "<select name='{$eName}' {$required} class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-inner'>";
                        if (!$required) $html .= "<option value=''>-- Select --</option>";
                        foreach ($options as $opt) {
                            $eOpt = e((string)$opt);
                            $selected = ((string)$val === (string)$opt) ? 'selected' : '';
                            $html .= "<option value='{$eOpt}' {$selected}>{$eOpt}</option>";
                        }
                        $html .= "</select>";
                    } else {
                        $html .= "<input type='text' name='{$eName}' value='{$eVal}' {$required} placeholder='Set enum values in schema Length: A,B,C' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-inner'>";
                    }
                    $html .= "<span class='error-message hidden text-xs text-red-500 mt-1'>This field is required</span>";
                    $html .= "</div></div>";
                    continue;
                } elseif ($fType === 'TEXT') {
                    $isRichText = stripos($name, 'description') !== false || stripos($name, 'content') !== false || stripos($name, 'body') !== false;
                    $isJson = stripos($name, 'json') !== false || stripos($name, 'config') !== false || stripos($name, 'settings') !== false || stripos($name, 'metadata') !== false;
                    if ($isRichText) {
                        $html .= "<div id='editor-{$eName}' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-slate-200 focus-within:ring-2 focus-within:ring-indigo-500/50 focus-within:border-indigo-500 transition-all shadow-inner min-h-[200px]'></div>";
                        $html .= "<textarea name='{$eName}' {$required} id='textarea-{$eName}' class='hidden'>{$eVal}</textarea>";
                    } elseif ($isJson) {
                        $html .= "<div id='json-editor-{$eName}' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-slate-200 focus-within:ring-2 focus-within:ring-indigo-500/50 focus-within:border-indigo-500 transition-all shadow-inner min-h-[200px] font-mono text-sm'></div>";
                        $html .= "<textarea name='{$eName}' {$required} id='textarea-{$eName}' class='hidden'>{$eVal}</textarea>";
                    } else {
                        $html .= "<textarea name='{$eName}' {$required} rows='6' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-inner resize-y'>{$eVal}</textarea>";
                    }
                    $html .= "<span class='error-message hidden text-xs text-red-500 mt-1'>This field is required</span>";
                    $html .= "</div></div>";
                    continue;
                }

                $html .= "<input type='{$inputType}' name='{$eName}' value='{$eVal}' {$required} class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-inner'>";
            }
            $html .= "<span class='error-message hidden text-xs text-red-500 mt-1'>This field is required</span>";
            $html .= "</div></div>";
        }
        $html .= "<div class='flex justify-end gap-3 pt-4'>";
        $html .= "<a href='javascript:history.back()' class='px-6 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all'>Cancel</a>";
        $html .= "<button type='submit' class='px-8 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-lg shadow-lg shadow-indigo-500/20 hover:bg-indigo-500 hover:-translate-y-0.5 active:translate-y-0 transition-all'>Save Changes</button>";
        $html .= "</div></form>";
        $html .= "<script>
            function validateForm(form) {
                // Handle file uploads - convert to base64
                const fileInputs = form.querySelectorAll('input[type=\"file\"]');
                fileInputs.forEach(fileInput => {
                    if (fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = fileInput.name + '_base64';
                            hiddenInput.value = e.target.result;
                            form.appendChild(hiddenInput);
                            
                            const nameInput = document.createElement('input');
                            nameInput.type = 'hidden';
                            nameInput.name = fileInput.name + '_filename';
                            nameInput.value = file.name;
                            form.appendChild(nameInput);
                        };
                        reader.readAsDataURL(file);
                    }
                });
                
                document.querySelectorAll('.ql-editor').forEach(editor => {
                    const textarea = editor.closest('.relative').querySelector('textarea');
                    if (textarea) {
                        textarea.value = editor.innerHTML;
                    }
                });
                let isValid = true;
                const inputs = form.querySelectorAll('[required]');
                inputs.forEach(input => {
                    const errorMsg = input.parentElement.querySelector('.error-message');
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('border-red-500');
                        if (errorMsg) errorMsg.classList.remove('hidden');
                    } else {
                        input.classList.remove('border-red-500');
                        if (errorMsg) errorMsg.classList.add('hidden');
                    }
                });
                return isValid;
            }
            document.querySelectorAll('[id^=\"editor-\"]').forEach(editorDiv => {
                if (editorDiv.dataset.initialized || typeof Quill === 'undefined') return;
                editorDiv.dataset.initialized = '1';
                const fieldName = editorDiv.id.replace('editor-', '');
                const textarea = document.getElementById('textarea-' + fieldName);
                const quill = new Quill('#' + editorDiv.id, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'color': [] }, { 'background': [] }],
                            ['link', 'blockquote', 'code-block'],
                            ['clean']
                        ]
                    }
                });
                if (textarea && textarea.value) {
                    quill.root.innerHTML = textarea.value;
                }
                quill.on('text-change', function() {
                    if (textarea) {
                        textarea.value = quill.root.innerHTML;
                    }
                });
            });
            document.querySelectorAll('[id^=\"json-editor-\"]').forEach(editorDiv => {
                if (editorDiv.dataset.initialized || typeof CodeMirror === 'undefined') return;
                editorDiv.dataset.initialized = '1';
                const fieldName = editorDiv.id.replace('json-editor-', '');
                const textarea = document.getElementById('textarea-' + fieldName);
                const cm = CodeMirror(editorDiv, {
                    value: textarea ? textarea.value : '',
                    mode: 'application/json',
                    theme: 'monokai',
                    lineNumbers: true,
                    lineWrapping: true,
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    indentUnit: 2,
                    tabSize: 2
                });
                cm.on('change', function() {
                    if (textarea) {
                        textarea.value = cm.getValue();
                    }
                });
            });
            document.querySelectorAll('input[type=\"color\"]').forEach(colorInput => {
                const fieldName = colorInput.name;
                const textInput = document.getElementById('color-text-' + fieldName);
                if (textInput) {
                    colorInput.addEventListener('input', function() {
                        textInput.value = this.value.toUpperCase();
                    });
                    textInput.addEventListener('input', function() {
                        if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                            colorInput.value = this.value;
                        }
                    });
                }
            });
            document.querySelectorAll('input[id^=\"autocomplete-\"]').forEach(input => {
                const fieldName = input.id.replace('autocomplete-', '');
                const hidden = document.getElementById('hidden-' + fieldName);
                const dropdown = document.getElementById('dropdown-' + fieldName);
                const refData = window['refData_' + fieldName] || [];
                const refCol = window['refCol_' + fieldName] || 'id';
                
                function getDisplay(item) {
                    return item.name || item.title || item.label || item.username || item[refCol] || '';
                }
                
                function showDropdown(items) {
                    if (items.length === 0) {
                        dropdown.classList.add('hidden');
                        return;
                    }
                    dropdown.innerHTML = items.map(item => {
                        const display = getDisplay(item);
                        const value = item[refCol];
                        return `<div class='px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition-colors' data-value='\${value}'>\${display}</div>`;
                    }).join('');
                    dropdown.classList.remove('hidden');
                    
                    dropdown.querySelectorAll('div').forEach(div => {
                        div.addEventListener('click', function() {
                            const value = this.dataset.value;
                            hidden.value = value;
                            input.value = this.textContent;
                            dropdown.classList.add('hidden');
                        });
                    });
                }
                
                input.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    if (!query) {
                        hidden.value = '';
                        showDropdown(refData.slice(0, 10));
                        return;
                    }
                    const filtered = refData.filter(item => {
                        const display = getDisplay(item).toLowerCase();
                        return display.includes(query);
                    }).slice(0, 10);
                    showDropdown(filtered);
                });
                
                input.addEventListener('focus', function() {
                    if (!this.value) {
                        showDropdown(refData.slice(0, 10));
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
                
                if (hidden.value) {
                    const item = refData.find(r => r[refCol] == hidden.value);
                    if (item) {
                        input.value = getDisplay(item);
                    }
                }
            });
            if (typeof flatpickr !== 'undefined') {
                flatpickr('input[type=\"date\"]:not(.flatpickr-input)', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'F j, Y'
                });
                flatpickr('input[type=\"datetime-local\"]:not(.flatpickr-input)', {
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    altInput: true,
                    altFormat: 'F j, Y h:i K'
                });
                flatpickr('input[type=\"time\"]:not(.flatpickr-input)', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true
                });
            }
            document.querySelectorAll('[required]').forEach(input => {
                input.addEventListener('input', function() {
                    const errorMsg = this.parentElement.querySelector('.error-message');
                    if (this.value.trim()) {
                        this.classList.remove('border-red-500');
                        if (errorMsg) errorMsg.classList.add('hidden');
                    }
                });
            });
            
            // File upload handlers
            window.clearFile = function(fieldName) {
                const fileInput = document.getElementById('file-' + fieldName);
                const uploadPrompt = document.getElementById('upload-prompt-' + fieldName);
                const preview = document.getElementById('preview-' + fieldName);
                const previewImg = document.getElementById('preview-img-' + fieldName);
                const previewFile = document.getElementById('preview-file-' + fieldName);
                
                fileInput.value = '';
                uploadPrompt.classList.remove('hidden');
                preview.classList.add('hidden');
                previewImg.classList.add('hidden');
                previewFile.classList.add('hidden');
            };
            
            document.querySelectorAll('input[type=\"file\"]').forEach(fileInput => {
                const fieldName = fileInput.id.replace('file-', '');
                const dropZone = document.getElementById('drop-zone-' + fieldName);
                const uploadPrompt = document.getElementById('upload-prompt-' + fieldName);
                const preview = document.getElementById('preview-' + fieldName);
                const previewImg = document.getElementById('preview-img-' + fieldName);
                const previewFile = document.getElementById('preview-file-' + fieldName);
                const fileName = document.getElementById('file-name-' + fieldName);
                
                function handleFile(file) {
                    if (file.size > 10 * 1024 * 1024) {
                        alert('File size must be less than 10MB');
                        return;
                    }
                    
                    uploadPrompt.classList.add('hidden');
                    preview.classList.remove('hidden');
                    
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            previewImg.classList.remove('hidden');
                            previewFile.classList.add('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        fileName.textContent = file.name;
                        previewFile.classList.remove('hidden');
                        previewImg.classList.add('hidden');
                    }
                }
                
                dropZone.addEventListener('click', function() {
                    fileInput.click();
                });
                
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        handleFile(this.files[0]);
                    }
                });
                
                dropZone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/20');
                });
                
                dropZone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/20');
                });
                
                dropZone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/20');
                    
                    if (e.dataTransfer.files.length > 0) {
                        fileInput.files = e.dataTransfer.files;
                        handleFile(e.dataTransfer.files[0]);
                    }
                });
            });
            
            lucide.createIcons();
        </script>";
        return $html;
    }

}
