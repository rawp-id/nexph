<?php
namespace Core\UI\Components;

class SchemaForm
{
    public static function render(array $meta = []): string
    {
        $table = $meta['table'] ?? '';
        $fields = $meta['fields'] ?? [['name' => '', 'type' => 'TEXT', 'nullable' => true, 'default' => '', 'unique' => false, 'primary' => false, 'autoincrement' => false, 'length' => '', 'references' => ['table' => '', 'column' => '']]];
        $indexes = $meta['indexes'] ?? [];
        $isEdit = !empty($table);
        $title = $isEdit ? "Edit Table: {$table}" : "Create New Table";
        $action = $isEdit ? "/admin/schema/edit/{$table}" : "/admin/schema";

        $types = [
            'TEXT' => ['icon' => 'align-left', 'desc' => 'Long text'],
            'VARCHAR' => ['icon' => 'type', 'desc' => 'Short text'],
            'CHAR' => ['icon' => 'text', 'desc' => 'Fixed text'],
            'INTEGER' => ['icon' => 'hash', 'desc' => 'Whole number'],
            'BIGINT' => ['icon' => 'binary', 'desc' => 'Large number'],
            'SMALLINT' => ['icon' => 'minus', 'desc' => 'Small number'],
            'TINYINT' => ['icon' => 'minimize-2', 'desc' => 'Tiny number'],
            'REAL' => ['icon' => 'percent', 'desc' => 'Decimal'],
            'FLOAT' => ['icon' => 'activity', 'desc' => 'Float'],
            'DOUBLE' => ['icon' => 'trending-up', 'desc' => 'Double'],
            'DECIMAL' => ['icon' => 'dollar-sign', 'desc' => 'Money'],
            'BOOLEAN' => ['icon' => 'toggle-left', 'desc' => 'True/False'],
            'DATE' => ['icon' => 'calendar', 'desc' => 'Date only'],
            'DATETIME' => ['icon' => 'calendar-clock', 'desc' => 'Date + Time'],
            'TIMESTAMP' => ['icon' => 'clock', 'desc' => 'Timestamp'],
            'TIME' => ['icon' => 'watch', 'desc' => 'Time only'],
            'BLOB' => ['icon' => 'file', 'desc' => 'Binary file'],
            'JSON' => ['icon' => 'braces', 'desc' => 'JSON data'],
            'ENUM' => ['icon' => 'list', 'desc' => 'Enum'],
            'UUID' => ['icon' => 'fingerprint', 'desc' => 'UUID']
        ];

        $allTables = \Core\Database\Metadata::all();
        $tableOptions = '<option value=""> No Relation </option>';
        foreach ($allTables as $t) {
            $tableOptions .= "<option value=\"{$t['table']}\">{$t['table']}</option>";
        }

        $tableData = [];
        foreach ($allTables as $t) {
            $cols = array_map(fn($f) => $f['name'], $t['fields']);
            if (!in_array('id', $cols)) array_unshift($cols, 'id');
            $tableData[$t['table']] = $cols;
        }
        $tableDataJson = json_encode($tableData);

        $hasTimestamps = false;
        $filteredFields = [];
        foreach ($fields as $f) {
            if (in_array($f['name'], ['created_at', 'updated_at'])) {
                $hasTimestamps = true;
            } else {
                $filteredFields[] = $f;
            }
        }
        if (empty($filteredFields)) {
            $filteredFields = [['name' => '', 'type' => 'TEXT', 'nullable' => true, 'default' => '', 'unique' => false, 'primary' => false, 'autoincrement' => false, 'length' => '', 'references' => ['table' => '', 'column' => '']]];
        }
        $fields = $filteredFields;
        $tsChecked = $hasTimestamps ? 'checked' : '';

        $fieldsHtml = '';
        foreach ($fields as $i => $f) {
            $currentType = strtoupper($f['type'] ?? 'TEXT');
            $typeOptions = '';
            foreach ($types as $t => $info) {
                $sel = ($currentType === $t) ? 'selected' : '';
                $typeOptions .= "<option value=\"{$t}\" {$sel}>{$t}</option>";
            }

            $relTableOptions = '<option value=""> No Relation </option>';
            foreach ($allTables as $t) {
                $sel = (($f['references']['table'] ?? '') === ($t['table'] ?? '')) ? 'selected' : '';
                $relTableOptions .= "<option value=\"{$t['table']}\" {$sel}>{$t['table']}</option>";
            }

            $notNull = empty($f['nullable']) ? 'checked' : '';
            $unique = !empty($f['unique']) ? 'checked' : '';
            $primary = !empty($f['primary']) ? 'checked' : '';
            $autoInc = !empty($f['autoincrement']) ? 'checked' : '';
            $default = htmlspecialchars($f['default'] ?? '');
            $length = htmlspecialchars($f['length'] ?? '');
            $name = htmlspecialchars($f['name'] ?? '');
            $refCol = $f['references']['column'] ?? 'id';

            $relColOptions = '';
            $refTable = $f['references']['table'] ?? '';
            if (!empty($refTable) && isset($tableData[$refTable])) {
                foreach ($tableData[$refTable] as $c) {
                    $sel = ($c === $refCol) ? 'selected' : '';
                    $relColOptions .= "<option value=\"{$c}\" {$sel}>{$c}</option>";
                }
            }

            $fieldsHtml .= "
            <div class=\"field-row bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex gap-4\">
                <div class=\"flex flex-col gap-1 pt-6\">
                    <button type=\"button\" onclick=\"moveField(this, -1)\" class=\"p-1 text-slate-400 hover:text-indigo-500 transition-colors\"><i data-lucide=\"chevron-up\" class=\"w-4 h-4\"></i></button>
                    <button type=\"button\" onclick=\"moveField(this, 1)\" class=\"p-1 text-slate-400 hover:text-indigo-500 transition-colors\"><i data-lucide=\"chevron-down\" class=\"w-4 h-4\"></i></button>
                </div>
                <div class=\"flex-1 space-y-3\">
                    <div class=\"flex gap-3 items-center\">
                        <div class=\"flex-1 space-y-1\">
                            <label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Column Name</label>
                            <input type=\"text\" name=\"field_names[]\" value=\"{$name}\" placeholder=\"e.g. email\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500 transition-all\" required>
                        </div>
                        <div class=\"w-36 space-y-1 relative\">
                            <label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Type</label>
                            <button type=\"button\" onclick=\"openTypePicker(this)\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-700 dark:text-slate-300 outline-none hover:border-indigo-500 transition-all text-left flex items-center justify-between\">
                                <span class=\"type-display\">{$currentType}</span>
                                <i data-lucide=\"chevron-down\" class=\"w-3 h-3\"></i>
                            </button>
                            <input type=\"hidden\" name=\"field_types[]\" value=\"{$currentType}\" class=\"type-input\">
                        </div>
                        <div class=\"w-32 space-y-1 length-group\">
                            <label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Length / Enum</label>
                            <input type=\"text\" name=\"field_lengths[]\" value=\"{$length}\" placeholder=\"255 or A,B,C\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-700 dark:text-slate-300 outline-none\">
                        </div>
                        <div class=\"flex-shrink-0 pt-5\">
                            <button type=\"button\" onclick=\"removeField(this)\" class=\"p-2 text-slate-400 hover:text-red-500 transition-colors\"><i data-lucide=\"x\" class=\"w-4 h-4\"></i></button>
                        </div>
                    </div>
                    <div class=\"flex gap-3 items-end flex-wrap\">
                        <div class=\"flex-1 min-w-[140px] space-y-1\">
                            <label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Default</label>
                            <input type=\"text\" name=\"field_defaults[]\" value=\"{$default}\" placeholder=\"NULL\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-700 dark:text-slate-300 outline-none\">
                        </div>
                        <div class=\"w-48 space-y-1\">
                            <label class=\"text-[10px] font-bold text-indigo-400 uppercase tracking-widest\">Refs Table</label>
                            <select name=\"field_refs_table[]\" onchange=\"updateRefCols(this)\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-xs text-slate-700 dark:text-slate-300 outline-none\">
                                {$relTableOptions}
                            </select>
                        </div>
                        <div class=\"w-32 space-y-1\">
                            <label class=\"text-[10px] font-bold text-indigo-400 uppercase tracking-widest\">Refs Col</label>
                            <select name=\"field_refs_col[]\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-xs text-slate-700 dark:text-slate-300 outline-none\">
                                {$relColOptions}
                            </select>
                        </div>
                        <label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\">
                            <input type=\"checkbox\" name=\"field_notnull[{$i}]\" value=\"1\" {$notNull} class=\"accent-indigo-600 w-3.5 h-3.5\"> NOT NULL
                        </label>
                        <label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\">
                            <input type=\"checkbox\" name=\"field_unique[{$i}]\" value=\"1\" {$unique} class=\"accent-indigo-600 w-3.5 h-3.5\"> UNIQUE
                        </label>
                        <label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\">
                            <input type=\"checkbox\" name=\"field_primary[{$i}]\" value=\"1\" {$primary} class=\"accent-indigo-600 w-3.5 h-3.5\"> PRIMARY
                        </label>
                        <label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\">
                            <input type=\"checkbox\" name=\"field_autoinc[{$i}]\" value=\"1\" {$autoInc} class=\"accent-indigo-600 w-3.5 h-3.5\"> AUTO_INC
                        </label>
                        <button type=\"button\" onclick=\"toggleValidation(this)\" class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/20 transition-colors\"><i data-lucide=\"shield-check\" class=\"w-3 h-3\"></i> Validation</button>
                    </div>
                    <div class=\"validation-panel hidden mt-3 p-4 bg-amber-50 dark:bg-amber-950/10 border border-amber-200 dark:border-amber-800/30 rounded-lg space-y-3\"><div class=\"grid grid-cols-2 gap-3\"><div class=\"space-y-1\"><label class=\"text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest\">Min Value/Length</label><input type=\"text\" name=\"field_min[{$i}]\" placeholder=\"e.g. 0 or 5\" class=\"w-full bg-white dark:bg-slate-950 border border-amber-200 dark:border-amber-800 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-300 outline-none\"></div><div class=\"space-y-1\"><label class=\"text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest\">Max Value/Length</label><input type=\"text\" name=\"field_max[{$i}]\" placeholder=\"e.g. 100 or 255\" class=\"w-full bg-white dark:bg-slate-950 border border-amber-200 dark:border-amber-800 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-300 outline-none\"></div></div><div class=\"space-y-1\"><label class=\"text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest\">Regex Pattern</label><input type=\"text\" name=\"field_regex[{$i}]\" placeholder=\"e.g. ^[a-zA-Z0-9]+$\" class=\"w-full bg-white dark:bg-slate-950 border border-amber-200 dark:border-amber-800 rounded-lg p-2 text-xs font-mono text-slate-700 dark:text-slate-300 outline-none\"></div></div>
                </div>
            </div>";
        }

        $protected = ['users', 'job_workers'];
        $deleteBtn = ($isEdit && !in_array($table, $protected)) ? '<button hx-delete="/admin/schema/' . $table . '" hx-confirm="DELETE TABLE AND ALL DATA?" class="text-red-500 text-sm font-bold flex items-center gap-1 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i> Drop Table</button>' : '';
        $readonly = $isEdit ? 'readonly' : '';
        $syncText = $isEdit ? 'Alter Table &amp; Sync' : 'Create Table &amp; Sync';
        
        $fillableValue = htmlspecialchars(implode(',', $meta['fillable'] ?? []));
        $hiddenValue = htmlspecialchars(implode(',', $meta['hidden'] ?? []));
        $castsValue = htmlspecialchars(json_encode($meta['casts'] ?? new \stdClass(), JSON_UNESCAPED_SLASHES));

        $indexesHtml = '';
        foreach ($indexes as $idx => $index) {
            $indexName = htmlspecialchars($index['name'] ?? '');
            $indexCols = htmlspecialchars(implode(',', $index['columns'] ?? []));
            $indexUnique = !empty($index['unique']) ? 'checked' : '';
            $indexesHtml .= "<div class='index-row bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-lg p-3 flex gap-3 items-center'>
                <div class='flex-1 space-y-1'>
                    <label class='text-[10px] font-bold text-slate-400 uppercase tracking-widest'>Index Name</label>
                    <input type='text' name='index_names[]' value='{$indexName}' placeholder='idx_column_name' class='w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-sm text-slate-900 dark:text-white outline-none'>
                </div>
                <div class='flex-1 space-y-1'>
                    <label class='text-[10px] font-bold text-slate-400 uppercase tracking-widest'>Columns (comma-separated)</label>
                    <input type='text' name='index_columns[]' value='{$indexCols}' placeholder='user_id,status' class='w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-sm text-slate-900 dark:text-white outline-none'>
                </div>
                <label class='flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors'>
                    <input type='checkbox' name='index_unique[{$idx}]' value='1' {$indexUnique} class='accent-indigo-600 w-3.5 h-3.5'> UNIQUE
                </label>
                <button type='button' onclick='removeIndex(this)' class='p-2 text-slate-400 hover:text-red-500 transition-colors'><i data-lucide='x' class='w-4 h-4'></i></button>
            </div>";
        }

        return <<<HTML
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div><h3 class="text-2xl font-bold text-slate-900 dark:text-white">{$title}</h3><p class="text-slate-500 text-sm mt-1">Define columns, types, and constraints</p></div>
                {$deleteBtn}
            </div>
            <form hx-post="{$action}" hx-target="#content" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-2xl shadow-xl space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Table Name</label>
                        <input type="text" name="table" value="{$table}" placeholder="e.g. products" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-slate-900 dark:text-white outline-none focus:border-indigo-500 transition-all" required {$readonly}>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Engine</label>
                        <select name="engine" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-slate-700 dark:text-slate-300 outline-none">
                            <option value="default" selected>Default (SQLite)</option>
                        </select>
                    </div>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-500/20 rounded-lg text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-indigo-900 dark:text-indigo-300">API Field Control</h4>
                            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">Control which fields are fillable via API and which are hidden from responses</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-widest flex items-center gap-1.5">
                                <i data-lucide="edit-3" class="w-3 h-3"></i> Fillable Fields (comma-separated)
                            </label>
                            <input type="text" name="fillable" value="{$fillableValue}" placeholder="e.g. name,email,status" class="w-full bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-800 rounded-lg p-3 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500 transition-all">
                            <p class="text-[10px] text-indigo-600 dark:text-indigo-400">Only these fields can be set via API create/update. Empty = all non-guarded fields allowed.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-widest flex items-center gap-1.5">
                                <i data-lucide="eye-off" class="w-3 h-3"></i> Hidden Fields (comma-separated)
                            </label>
                            <input type="text" name="hidden" value="{$hiddenValue}" placeholder="e.g. password,secret_key" class="w-full bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-800 rounded-lg p-3 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500 transition-all">
                            <p class="text-[10px] text-indigo-600 dark:text-indigo-400">These fields are removed from all API JSON responses.</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-widest flex items-center gap-1.5">
                            <i data-lucide="code" class="w-3 h-3"></i> Type Casts (JSON format)
                        </label>
                        <textarea name="casts" rows="2" placeholder='{"is_active":"bool","price":"float","metadata":"json","created_at":"datetime"}' class="w-full bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-800 rounded-lg p-3 text-xs font-mono text-slate-900 dark:text-white outline-none focus:border-indigo-500 transition-all resize-none">{$castsValue}</textarea>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Schema Columns</label>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">id (PK, AI) is auto-created</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <input type="checkbox" name="with_timestamps" value="1" {$tsChecked} class="accent-indigo-600 w-4 h-4">
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Timestamps (created_at, updated_at)</span>
                        </label>
                    </div>
                    <div id="fields-container" class="space-y-3">
                        {$fieldsHtml}
                    </div>
                    <button type="button" onclick="addField()" class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 text-sm font-semibold hover:underline">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Column
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Indexes</label>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">Improve query performance</p>
                        </div>
                    </div>
                    <div id="indexes-container" class="space-y-3">
                        {$indexesHtml}
                    </div>
                    <button type="button" onclick="addIndex()" class="inline-flex items-center gap-2 text-emerald-600 dark:text-emerald-400 text-sm font-semibold hover:underline">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Index
                    </button>
                </div>
                <div class="pt-6 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center gap-3">
                    <button type="button" onclick="previewSQL()" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <i data-lucide="eye" class="w-4 h-4"></i> Preview SQL
                    </button>
                    <div class="flex gap-3">
                        <button type="button" hx-get="/admin" hx-target="#content" hx-push-url="true" class="px-6 py-3 text-sm font-medium text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all">Cancel</button>
                        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-500 hover:-translate-y-0.5 active:translate-y-0 shadow-lg shadow-indigo-500/20 transition-all">
                            {$syncText}
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <script>
            lucide.createIcons();
            var tableData = {$tableDataJson};
            var fieldIndex = document.querySelectorAll('.field-row').length;
            
            function reindexFields() {
                document.querySelectorAll('.field-row').forEach((row, i) => {
                    row.querySelectorAll('input[type=\"checkbox\"]').forEach(cb => {
                        cb.name = cb.name.replace(/\[\d+\]/, '[' + i + ']');
                    });
                });
            }

            function moveField(btn, dir) {
                const row = btn.closest('.field-row');
                if (dir === -1 && row.previousElementSibling) {
                    row.parentNode.insertBefore(row, row.previousElementSibling);
                } else if (dir === 1 && row.nextElementSibling) {
                    row.parentNode.insertBefore(row.nextElementSibling, row);
                }
                reindexFields();
            }

            function updateRefCols(select) {
                const row = select.closest('.field-row');
                const colSelect = row.querySelector('select[name=\"field_refs_col[]\"]');
                const tableName = select.value;
                colSelect.innerHTML = '<option value=\"\"> Select Col </option>';
                if (tableName && tableData[tableName]) {
                    tableData[tableName].forEach(col => {
                        const opt = document.createElement('option');
                        opt.value = col;
                        opt.textContent = col;
                        if (col === 'id') opt.selected = true;
                        colSelect.appendChild(opt);
                    });
                }
            }

            function addField() {
                const container = document.getElementById('fields-container');
                const idx = fieldIndex++;
                const types = ['TEXT','VARCHAR','CHAR','INTEGER','BIGINT','SMALLINT','TINYINT','REAL','FLOAT','DOUBLE','DECIMAL','BOOLEAN','DATE','DATETIME','TIMESTAMP','TIME','BLOB','JSON','ENUM','UUID'];
                const opts = types.map(t => '<option value=\"'+t+'\">'+t+'</option>').join('');
                const relOpts = `{$tableOptions}`;
                const div = document.createElement('div');
                div.className = 'field-row bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex gap-4';
                div.innerHTML = '<div class=\"flex flex-col gap-1 pt-6\"><button type=\"button\" onclick=\"moveField(this, -1)\" class=\"p-1 text-slate-400 hover:text-indigo-500 transition-colors\"><i data-lucide=\"chevron-up\" class=\"w-4 h-4\"></i></button><button type=\"button\" onclick=\"moveField(this, 1)\" class=\"p-1 text-slate-400 hover:text-indigo-500 transition-colors\"><i data-lucide=\"chevron-down\" class=\"w-4 h-4\"></i></button></div><div class=\"flex-1 space-y-3\"><div class=\"flex gap-3 items-center\"><div class=\"flex-1 space-y-1\"><label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Column Name</label><input type=\"text\" name=\"field_names[]\" placeholder=\"e.g. email\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500 transition-all\" required></div><div class=\"w-36 space-y-1 relative\"><label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Type</label><button type=\"button\" onclick=\"openTypePicker(this)\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-700 dark:text-slate-300 outline-none hover:border-indigo-500 transition-all text-left flex items-center justify-between\"><span class=\"type-display\">TEXT</span><i data-lucide=\"chevron-down\" class=\"w-3 h-3\"></i></button><input type=\"hidden\" name=\"field_types[]\" value=\"TEXT\" class=\"type-input\"></div><div class=\"w-20 space-y-1 length-group\"><label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Length</label><input type=\"text\" name=\"field_lengths[]\" placeholder=\"255\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-700 dark:text-slate-300 outline-none\"></div><div class=\"flex-shrink-0 pt-5\"><button type=\"button\" onclick=\"removeField(this)\" class=\"p-2 text-slate-400 hover:text-red-500 transition-colors\"><i data-lucide=\"x\" class=\"w-4 h-4\"></i></button></div></div><div class=\"flex gap-3 items-end flex-wrap\"><div class=\"flex-1 min-w-[140px] space-y-1\"><label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Default</label><input type=\"text\" name=\"field_defaults[]\" placeholder=\"NULL\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm text-slate-700 dark:text-slate-300 outline-none\"></div><div class=\"w-48 space-y-1\"><label class=\"text-[10px] font-bold text-indigo-400 uppercase tracking-widest\">Refs Table</label><select name=\"field_refs_table[]\" onchange=\"updateRefCols(this)\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-xs text-slate-700 dark:text-slate-300 outline-none\">'+relOpts+'</select></div><div class=\"w-32 space-y-1\"><label class=\"text-[10px] font-bold text-indigo-400 uppercase tracking-widest\">Refs Col</label><select name=\"field_refs_col[]\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-xs text-slate-700 dark:text-slate-300 outline-none\"></select></div><label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\"><input type=\"checkbox\" name=\"field_notnull['+idx+']\" value=\"1\" class=\"accent-indigo-600 w-3.5 h-3.5\"> NOT NULL</label><label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\"><input type=\"checkbox\" name=\"field_unique['+idx+']\" value=\"1\" class=\"accent-indigo-600 w-3.5 h-3.5\"> UNIQUE</label><label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\"><input type=\"checkbox\" name=\"field_primary['+idx+']\" value=\"1\" class=\"accent-indigo-600 w-3.5 h-3.5\"> PRIMARY</label><label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\"><input type=\"checkbox\" name=\"field_autoinc['+idx+']\" value=\"1\" class=\"accent-indigo-600 w-3.5 h-3.5\"> AUTO_INC</label><button type="button" onclick="toggleValidation(this)" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/20 transition-colors"><i data-lucide="shield-check" class="w-3 h-3"></i> Validation</button></div><div class="validation-panel hidden mt-3 p-4 bg-amber-50 dark:bg-amber-950/10 border border-amber-200 dark:border-amber-800/30 rounded-lg space-y-3"><div class="grid grid-cols-2 gap-3"><div class="space-y-1"><label class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest">Min Value/Length</label><input type="text" name="field_min[+idx+]" placeholder="e.g. 0 or 5" class="w-full bg-white dark:bg-slate-950 border border-amber-200 dark:border-amber-800 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-300 outline-none"></div><div class="space-y-1"><label class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest">Max Value/Length</label><input type="text" name="field_max[+idx+]" placeholder="e.g. 100 or 255" class="w-full bg-white dark:bg-slate-950 border border-amber-200 dark:border-amber-800 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-300 outline-none"></div></div><div class="space-y-1"><label class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest">Regex Pattern</label><input type="text" name="field_regex[+idx+]" placeholder="e.g. ^[a-zA-Z0-9]+$" class="w-full bg-white dark:bg-slate-950 border border-amber-200 dark:border-amber-800 rounded-lg p-2 text-xs font-mono text-slate-700 dark:text-slate-300 outline-none"></div></div></div>';
                container.appendChild(div);
                lucide.createIcons();
                reindexFields();
            }
            function removeField(btn) {
                const row = btn.closest('.field-row');
                if (document.querySelectorAll('.field-row').length > 1) {
                    row.remove();
                    reindexFields();
                }
            }
            function toggleLength(sel) {
                const group = sel.closest('.flex').querySelector('.length-group input');
                if (!group) return;
                const needsLength = ['VARCHAR','CHAR','DECIMAL','ENUM'].includes(sel.value);
                group.disabled = !needsLength;
                group.style.opacity = needsLength ? '1' : '0.3';
            }
            document.querySelectorAll(".type-input").forEach(input => { const lengthInput = input.closest(".flex-1").querySelector(".length-group input"); if (lengthInput) { const needsLength = ["VARCHAR", "CHAR", "DECIMAL", "ENUM"].includes(input.value); lengthInput.disabled = !needsLength; lengthInput.style.opacity = needsLength ? "1" : "0.3"; } });
            
            function addIndex() {
                const container = document.getElementById("indexes-container");
                const idx = document.querySelectorAll(".index-row").length;
                const div = document.createElement("div");
                div.className = "index-row bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-lg p-3 flex gap-3 items-center";
                div.innerHTML = "<div class=\"flex-1 space-y-1\"><label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Index Name</label><input type=\"text\" name=\"index_names[]\" placeholder=\"idx_column_name\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-sm text-slate-900 dark:text-white outline-none\"></div><div class=\"flex-1 space-y-1\"><label class=\"text-[10px] font-bold text-slate-400 uppercase tracking-widest\">Columns (comma-separated)</label><input type=\"text\" name=\"index_columns[]\" placeholder=\"user_id,status\" class=\"w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-sm text-slate-900 dark:text-white outline-none\"></div><label class=\"flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium cursor-pointer select-none text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors\"><input type=\"checkbox\" name=\"index_unique["+idx+"]\" value=\"1\" class=\"accent-indigo-600 w-3.5 h-3.5\"> UNIQUE</label><button type=\"button\" onclick=\"removeIndex(this)\" class=\"p-2 text-slate-400 hover:text-red-500 transition-colors\"><i data-lucide=\"x\" class=\"w-4 h-4\"></i></button>";
                container.appendChild(div);
                lucide.createIcons();
            }
            function removeIndex(btn) {
                const row = btn.closest(".index-row");
                row.remove();
            }
            
            // Type Picker
            const typePickerData = {
                'TEXT': { icon: 'align-left', desc: 'Long text', color: 'blue' },
                'VARCHAR': { icon: 'type', desc: 'Short text', color: 'blue' },
                'CHAR': { icon: 'text', desc: 'Fixed text', color: 'blue' },
                'INTEGER': { icon: 'hash', desc: 'Whole number', color: 'green' },
                'BIGINT': { icon: 'binary', desc: 'Large number', color: 'green' },
                'SMALLINT': { icon: 'minus', desc: 'Small number', color: 'green' },
                'TINYINT': { icon: 'minimize-2', desc: 'Tiny number', color: 'green' },
                'REAL': { icon: 'percent', desc: 'Decimal', color: 'emerald' },
                'FLOAT': { icon: 'activity', desc: 'Float', color: 'emerald' },
                'DOUBLE': { icon: 'trending-up', desc: 'Double', color: 'emerald' },
                'DECIMAL': { icon: 'dollar-sign', desc: 'Money', color: 'emerald' },
                'BOOLEAN': { icon: 'toggle-left', desc: 'True/False', color: 'purple' },
                'DATE': { icon: 'calendar', desc: 'Date only', color: 'orange' },
                'DATETIME': { icon: 'calendar-clock', desc: 'Date + Time', color: 'orange' },
                'TIMESTAMP': { icon: 'clock', desc: 'Timestamp', color: 'orange' },
                'TIME': { icon: 'watch', desc: 'Time only', color: 'orange' },
                'BLOB': { icon: 'file', desc: 'Binary file', color: 'red' },
                'JSON': { icon: 'braces', desc: 'JSON data', color: 'yellow' },
                'ENUM': { icon: 'list', desc: 'Enum', color: 'pink' },
                'UUID': { icon: 'fingerprint', desc: 'UUID', color: 'indigo' }
            };
            let activeTypePicker = null;
            
            window.openTypePicker = function(btn) {
                if (activeTypePicker) {
                    activeTypePicker.remove();
                    activeTypePicker = null;
                }
                const container = btn.closest('.relative');
                const currentType = container.querySelector('.type-input').value;
                const picker = document.createElement('div');
                picker.className = 'absolute z-50 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl p-4 w-[420px] left-0';
                picker.style.top = '100%';
                let html = '<div class="grid grid-cols-4 gap-2">';
                for (const [type, info] of Object.entries(typePickerData)) {
                    const isActive = type === currentType;
                    const activeClass = isActive ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-950/30' : '';
                    html += `<button type="button" onclick="selectType(this, '\${type}')" class="type-option flex flex-col items-center gap-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 transition-all \${activeClass}" data-type="\${type}"><i data-lucide="\${info.icon}" class="w-5 h-5 text-\${info.color}-500"></i><div class="text-center"><div class="text-[10px] font-bold text-slate-900 dark:text-white">\${type}</div><div class="text-[8px] text-slate-500">\${info.desc}</div></div></button>`;
                }
                html += '</div>';
                picker.innerHTML = html;
                container.appendChild(picker);
                activeTypePicker = picker;
                setTimeout(() => lucide.createIcons(), 10);
                document.addEventListener('click', closeTypePickerOutside);
            };
            
            window.selectType = function(btn, type) {
                const container = btn.closest('.relative');
                const display = container.querySelector('.type-display');
                const input = container.querySelector('.type-input');
                const lengthInput = container.closest('.flex-1').querySelector('.length-group input');
                display.textContent = type;
                input.value = type;
                if (lengthInput) {
                    const needsLength = ['VARCHAR', 'CHAR', 'DECIMAL', 'ENUM'].includes(type);
                    lengthInput.disabled = !needsLength;
                    lengthInput.style.opacity = needsLength ? '1' : '0.3';
                }
                if (activeTypePicker) {
                    activeTypePicker.remove();
                    activeTypePicker = null;
                }
                document.removeEventListener('click', closeTypePickerOutside);
            };
            
            function closeTypePickerOutside(e) {
                if (activeTypePicker && !activeTypePicker.contains(e.target) && !e.target.closest('button[onclick*="openTypePicker"]')) {
                    activeTypePicker.remove();
                    activeTypePicker = null;
                    document.removeEventListener('click', closeTypePickerOutside);
                }
            }
            
            window.toggleValidation = function(btn) {
                const panel = btn.closest('.flex-1').querySelector('.validation-panel');
                panel.classList.toggle('hidden');
                lucide.createIcons();
            };
            
            function previewSQL() {
                const tableName = document.querySelector('input[name="table"]').value;
                if (!tableName) { alert('Table name required'); return; }
                const fields = [];
                document.querySelectorAll('.field-row').forEach(row => {
                    const name = row.querySelector('input[name="field_names[]"]').value;
                    if (!name) return;
                    const type = row.querySelector('.type-input').value;
                    const length = row.querySelector('input[name="field_lengths[]"]').value;
                    const defaultVal = row.querySelector('input[name="field_defaults[]"]').value;
                    const notNull = row.querySelector('input[type="checkbox"][name*="field_notnull"]').checked;
                    const unique = row.querySelector('input[type="checkbox"][name*="field_unique"]').checked;
                    const primary = row.querySelector('input[type="checkbox"][name*="field_primary"]').checked;
                    const autoInc = row.querySelector('input[type="checkbox"][name*="field_autoinc"]').checked;
                    const refTable = row.querySelector('select[name="field_refs_table[]"]').value;
                    const refCol = row.querySelector('select[name="field_refs_col[]"]').value;
                    fields.push({name,type,length,defaultVal,notNull,unique,primary,autoInc,refTable,refCol});
                });
                const withTimestamps = document.querySelector('input[name="with_timestamps"]').checked;
                let sql = 'CREATE TABLE `' + tableName + '` (\\n  `id` INTEGER PRIMARY KEY AUTOINCREMENT';
                fields.forEach(f => {
                    let def = '\\n  `' + f.name + '` ' + f.type;
                    if (f.length && ['VARCHAR','CHAR','DECIMAL'].includes(f.type)) def += '(' + f.length + ')';
                    if (f.notNull) def += ' NOT NULL';
                    if (f.unique) def += ' UNIQUE';
                    if (f.defaultVal) def += ' DEFAULT ' + f.defaultVal;
                    if (f.refTable) def += ' REFERENCES `' + f.refTable + '`(' + (f.refCol || 'id') + ')';
                    sql += ',' + def;
                });
                if (withTimestamps) {
                    sql += ',\\n  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP';
                    sql += ',\\n  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP';
                }
                sql += '\\n);';
                const indexes = [];
                document.querySelectorAll('.index-row').forEach(row => {
                    const name = row.querySelector('input[name="index_names[]"]').value;
                    const cols = row.querySelector('input[name="index_columns[]"]').value;
                    const unique = row.querySelector('input[type="checkbox"][name*="index_unique"]').checked;
                    if (name && cols) {
                        sql += '\\n\\nCREATE ' + (unique ? 'UNIQUE ' : '') + 'INDEX `' + name + '` ON `' + tableName + '` (' + cols + ');';
                    }
                });
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4';
                modal.innerHTML = '<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] overflow-hidden"><div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800"><h3 class="text-lg font-bold text-slate-900 dark:text-white">SQL Preview</h3><button onclick="this.closest(\'.fixed\').remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button></div><div class="p-6 overflow-y-auto max-h-[60vh]"><pre class="text-sm text-slate-700 dark:text-slate-300 bg-slate-950 p-4 rounded-lg overflow-x-auto font-mono">' + sql.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre></div><div class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end"><button onclick="this.closest(\'.fixed\').remove()" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-all font-medium">Close</button></div></div>';
                document.body.appendChild(modal);
                lucide.createIcons();
            }
        </script>
HTML;
    }
}
