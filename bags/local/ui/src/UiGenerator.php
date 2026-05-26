<?php
namespace Core\UI;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;
use Core\Http\Middleware;
use Core\Database\DB;

class UiGenerator {
    public static function register(Router $router, array $meta, array $allMeta): void {
        $table = $meta['table'];
        $basePath = "/admin/{$table}";

        // Admin Table View
        $router->add('GET', $basePath, function(Request $req, Response $res) use ($table, $meta, $basePath, $allMeta) {
            try {
                $isSynced = \Core\Database\Migration::isSynced($table, $meta);
                $syncAlert = "";
                if (!$isSynced) {
                    $syncAlert = "<div class='bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 rounded-xl mb-6 flex items-center justify-between'>
                        <div class='flex items-center gap-3'>
                            <div class='w-8 h-8 bg-amber-100 dark:bg-amber-500/20 rounded-lg flex items-center justify-center text-amber-600 dark:text-amber-400'><i data-lucide='alert-circle' class='w-4 h-4'></i></div>
                            <div>
                                <p class='text-amber-800 dark:text-amber-400 font-bold text-sm'>Schema Mismatch Detected</p>
                                <p class='text-amber-600/80 dark:text-amber-400/80 text-xs'>Your database table structure doesn't match the current JSON schema metadata.</p>
                            </div>
                        </div>
                        <button hx-post='/admin/schema/sync/{$table}' hx-target='#content' class='bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition-all shadow-lg shadow-amber-500/20'>Sync Now</button>
                    </div>";
                }

                $search = $req->query('search') ?? '';
                $page = max(1, (int)($req->query('page') ?? 1));
                $perPage = max(10, min(100, (int)($req->query('per_page') ?? 25)));
                $offset = ($page - 1) * $perPage;
                $sort = $req->query('sort') ?? '';
                $order = $req->query('order') ?? 'asc';
                
                // Advanced filters
                $filterField = $req->query('filter_field') ?? '';
                $filterType = $req->query('filter_type') ?? '';
                $filterValue = $req->query('filter_value') ?? '';
                $filterValueEnd = $req->query('filter_value_end') ?? '';
                
                $whereClause = '';
                $params = [];
                $whereClauses = [];
                
                if (!empty($search)) {
                    $searchFields = array_map(fn($f) => "`{$f['name']}` LIKE ?", $meta['fields']);
                    $whereClauses[] = '(' . implode(' OR ', $searchFields) . ')';
                    $params = array_merge($params, array_fill(0, count($meta['fields']), "%{$search}%"));
                }
                
                // Apply advanced filter
                if (!empty($filterField) && !empty($filterType) && $filterValue !== '') {
                    $validField = false;
                    $fieldType = 'TEXT';
                    foreach ($meta['fields'] as $f) {
                        if ($f['name'] === $filterField) {
                            $validField = true;
                            $fieldType = strtoupper($f['type'] ?? 'TEXT');
                            break;
                        }
                    }
                    
                    if ($validField) {
                        switch ($filterType) {
                            case 'equals':
                                $whereClauses[] = "`{$filterField}` = ?";
                                $params[] = $filterValue;
                                break;
                            case 'not_equals':
                                $whereClauses[] = "`{$filterField}` != ?";
                                $params[] = $filterValue;
                                break;
                            case 'contains':
                                $whereClauses[] = "`{$filterField}` LIKE ?";
                                $params[] = "%{$filterValue}%";
                                break;
                            case 'starts_with':
                                $whereClauses[] = "`{$filterField}` LIKE ?";
                                $params[] = "{$filterValue}%";
                                break;
                            case 'ends_with':
                                $whereClauses[] = "`{$filterField}` LIKE ?";
                                $params[] = "%{$filterValue}";
                                break;
                            case 'greater_than':
                                $whereClauses[] = "`{$filterField}` > ?";
                                $params[] = $filterValue;
                                break;
                            case 'less_than':
                                $whereClauses[] = "`{$filterField}` < ?";
                                $params[] = $filterValue;
                                break;
                            case 'greater_equal':
                                $whereClauses[] = "`{$filterField}` >= ?";
                                $params[] = $filterValue;
                                break;
                            case 'less_equal':
                                $whereClauses[] = "`{$filterField}` <= ?";
                                $params[] = $filterValue;
                                break;
                            case 'between':
                                if ($filterValueEnd !== '') {
                                    $whereClauses[] = "`{$filterField}` BETWEEN ? AND ?";
                                    $params[] = $filterValue;
                                    $params[] = $filterValueEnd;
                                }
                                break;
                            case 'is_null':
                                $whereClauses[] = "`{$filterField}` IS NULL";
                                break;
                            case 'is_not_null':
                                $whereClauses[] = "`{$filterField}` IS NOT NULL";
                                break;
                        }
                    }
                }
                
                if (!empty($whereClauses)) {
                    $whereClause = ' WHERE ' . implode(' AND ', $whereClauses);
                }
                
                $orderClause = '';
                if (!empty($sort)) {
                    $validSort = false;
                    foreach ($meta['fields'] as $f) {
                        if ($f['name'] === $sort) {
                            $validSort = true;
                            break;
                        }
                    }
                    if ($validSort) {
                        $orderClause = " ORDER BY `{$sort}` " . (strtoupper($order) === 'DESC' ? 'DESC' : 'ASC');
                    }
                }
                
                $totalCount = DB::query("SELECT COUNT(*) as cnt FROM {$table}{$whereClause}", $params)[0]['cnt'] ?? 0;
                $totalPages = ceil($totalCount / $perPage);
                
                $fields = array_column($meta['fields'], 'name');
                $fields[] = 'id';
                $selectFields = array_filter($fields, fn($f) => $f !== 'password');
                $selectCols = implode(', ', array_map(fn($f) => "`{$f}`", $selectFields));
                $data = DB::query("SELECT {$selectCols} FROM {$table}{$whereClause}{$orderClause} LIMIT {$perPage} OFFSET {$offset}", $params);
                $content = "<div class='max-w-6xl mx-auto space-y-6'>";
                $content .= $syncAlert;
                $content .= "<div class='flex justify-between items-center mb-6'>
                    <div><h3 class='text-2xl font-bold dark:text-white capitalize'>{$table}</h3><p class='text-slate-500 text-sm'>{$totalCount} total records</p></div>
                    <div class='flex gap-3'>
                        <button onclick='exportData(\"{$table}\", \"csv\")' class='bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-xl text-sm font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-300 dark:hover:bg-slate-700 transition-all'><i data-lucide=\"download\" class=\"w-4 h-4 inline mr-1\"></i>Export CSV</button>
                        <button onclick='exportData(\"{$table}\", \"json\")' class='bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-xl text-sm font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-300 dark:hover:bg-slate-700 transition-all'><i data-lucide=\"download\" class=\"w-4 h-4 inline mr-1\"></i>Export JSON</button>
                        <button hx-get='/admin/schema/edit/{$table}' hx-target='#content' hx-push-url='true' class='bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-xl text-sm font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-300 dark:hover:bg-slate-700 transition-all'>Edit Schema</button>
                        <button hx-get='{$basePath}/create' hx-target='#content' hx-push-url='true' class='bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/20 hover:bg-indigo-500 transition-all'>+ New Record</button>
                    </div>
                </div>";
                
                // Build field options for filter dropdown
                $fieldOptions = '';
                foreach ($meta['fields'] as $f) {
                    $fname = htmlspecialchars($f['name']);
                    $ftype = strtoupper($f['type'] ?? 'TEXT');
                    $selected = ($filterField === $f['name']) ? 'selected' : '';
                    $fieldOptions .= "<option value='{$fname}' data-type='{$ftype}' {$selected}>{$fname}</option>";
                }
                
                $content .= "<div class='bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 mb-4 space-y-4'>
                    <div class='flex gap-3 items-center'>
                        <div class='flex-1'>
                            <input type='text' id='search-input' value='" . htmlspecialchars($search) . "' placeholder='Search records...' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500'>
                        </div>
                        <button id='toggle-filter-btn' class='bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 transition-all flex items-center gap-2'>
                            <i data-lucide='filter' class='w-4 h-4'></i>
                            <span>Filters</span>
                        </button>
                        <select id='per-page-select' class='bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-700 dark:text-slate-300'>
                            <option value='10' " . ($perPage == 10 ? 'selected' : '') . ">10 / page</option>
                            <option value='25' " . ($perPage == 25 ? 'selected' : '') . ">25 / page</option>
                            <option value='50' " . ($perPage == 50 ? 'selected' : '') . ">50 / page</option>
                            <option value='100' " . ($perPage == 100 ? 'selected' : '') . ">100 / page</option>
                        </select>
                    </div>
                    <div id='filter-panel' class='hidden border-t border-slate-200 dark:border-slate-800 pt-4'>
                        <div class='grid grid-cols-12 gap-3 items-end'>
                            <div class='col-span-3'>
                                <label class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 block'>Field</label>
                                <select id='filter-field' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-700 dark:text-slate-300'>
                                    <option value=''>Select field...</option>
                                    {$fieldOptions}
                                </select>
                            </div>
                            <div class='col-span-2'>
                                <label class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 block'>Operator</label>
                                <select id='filter-type' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-700 dark:text-slate-300'>
                                    <option value='equals'>Equals</option>
                                    <option value='not_equals'>Not Equals</option>
                                    <option value='contains'>Contains</option>
                                    <option value='starts_with'>Starts With</option>
                                    <option value='ends_with'>Ends With</option>
                                    <option value='greater_than'>Greater Than</option>
                                    <option value='less_than'>Less Than</option>
                                    <option value='greater_equal'>Greater or Equal</option>
                                    <option value='less_equal'>Less or Equal</option>
                                    <option value='between'>Between</option>
                                    <option value='is_null'>Is NULL</option>
                                    <option value='is_not_null'>Is Not NULL</option>
                                </select>
                            </div>
                            <div class='col-span-3'>
                                <label class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 block'>Value</label>
                                <input type='text' id='filter-value' value='" . htmlspecialchars($filterValue) . "' placeholder='Enter value...' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white'>
                            </div>
                            <div class='col-span-2 hidden' id='filter-value-end-container'>
                                <label class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 block'>End Value</label>
                                <input type='text' id='filter-value-end' value='" . htmlspecialchars($filterValueEnd) . "' placeholder='End value...' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white'>
                            </div>
                            <div class='col-span-2'>
                                <button id='apply-filter-btn' class='w-full bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-500 transition-all'>Apply</button>
                            </div>
                            <div class='col-span-2'>
                                <button id='clear-filter-btn' class='w-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-300 dark:hover:bg-slate-700 transition-all'>Clear</button>
                            </div>
                        </div>
                    </div>
                </div>";
                
                $content .= View::table($data, $meta['fields'], $table, $sort, $order, $basePath);
                
                if ($totalPages > 1) {
                    $content .= "<div class='flex justify-center items-center gap-2 mt-6'>";
                    if ($page > 1) {
                        $content .= "<button hx-get='{$basePath}?page=" . ($page - 1) . "&per_page={$perPage}&search=" . urlencode($search) . "' hx-target='#content' class='px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all'>Previous</button>";
                    }
                    $content .= "<span class='px-4 py-2 text-sm text-slate-500'>Page {$page} of {$totalPages}</span>";
                    if ($page < $totalPages) {
                        $content .= "<button hx-get='{$basePath}?page=" . ($page + 1) . "&per_page={$perPage}&search=" . urlencode($search) . "' hx-target='#content' class='px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all'>Next</button>";
                    }
                    $content .= "</div>";
                }
                
                $content .= "<script>
                    (function() {
                        let searchTimeout;
                        const filterPanel = document.getElementById('filter-panel');
                        const toggleFilterBtn = document.getElementById('toggle-filter-btn');
                        const filterField = document.getElementById('filter-field');
                        const filterType = document.getElementById('filter-type');
                        const filterValue = document.getElementById('filter-value');
                        const filterValueEnd = document.getElementById('filter-value-end');
                        const filterValueEndContainer = document.getElementById('filter-value-end-container');
                        const applyFilterBtn = document.getElementById('apply-filter-btn');
                        const clearFilterBtn = document.getElementById('clear-filter-btn');
                        
                        toggleFilterBtn.addEventListener('click', function() {
                            filterPanel.classList.toggle('hidden');
                        });
                        
                        filterType.addEventListener('change', function() {
                            const needsEndValue = this.value === 'between';
                            const needsValue = !['is_null', 'is_not_null'].includes(this.value);
                            filterValueEndContainer.classList.toggle('hidden', !needsEndValue);
                            filterValue.disabled = !needsValue;
                            if (!needsValue) filterValue.value = '';
                        });
                        
                        filterField.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const fieldType = selectedOption.dataset.type || 'TEXT';
                            const isNumeric = ['INTEGER', 'BIGINT', 'SMALLINT', 'TINYINT', 'REAL', 'FLOAT', 'DOUBLE', 'DECIMAL'].includes(fieldType);
                            const isDate = ['DATE', 'DATETIME', 'TIMESTAMP'].includes(fieldType);
                            
                            if (isDate) {
                                filterValue.type = 'date';
                                filterValueEnd.type = 'date';
                            } else if (isNumeric) {
                                filterValue.type = 'number';
                                filterValueEnd.type = 'number';
                            } else {
                                filterValue.type = 'text';
                                filterValueEnd.type = 'text';
                            }
                        });
                        
                        applyFilterBtn.addEventListener('click', function() {
                            const search = document.getElementById('search-input').value;
                            const perPage = document.getElementById('per-page-select').value;
                            let url = '{$basePath}?search=' + encodeURIComponent(search) + '&per_page=' + perPage;
                            
                            if (filterField.value) {
                                url += '&filter_field=' + encodeURIComponent(filterField.value);
                                url += '&filter_type=' + encodeURIComponent(filterType.value);
                                url += '&filter_value=' + encodeURIComponent(filterValue.value);
                                if (filterType.value === 'between' && filterValueEnd.value) {
                                    url += '&filter_value_end=' + encodeURIComponent(filterValueEnd.value);
                                }
                            }
                            
                            htmx.ajax('GET', url, {target: '#content'});
                        });
                        
                        clearFilterBtn.addEventListener('click', function() {
                            filterField.value = '';
                            filterType.value = 'equals';
                            filterValue.value = '';
                            filterValueEnd.value = '';
                            filterValueEndContainer.classList.add('hidden');
                            const search = document.getElementById('search-input').value;
                            const perPage = document.getElementById('per-page-select').value;
                            htmx.ajax('GET', '{$basePath}?search=' + encodeURIComponent(search) + '&per_page=' + perPage, {target: '#content'});
                        });
                        
                        document.getElementById('search-input').addEventListener('input', function(e) {
                            clearTimeout(searchTimeout);
                            searchTimeout = setTimeout(() => {
                                const perPage = document.getElementById('per-page-select').value;
                                htmx.ajax('GET', '{$basePath}?search=' + encodeURIComponent(e.target.value) + '&per_page=' + perPage, {target: '#content'});
                            }, 300);
                        });
                        document.getElementById('per-page-select').addEventListener('change', function(e) {
                            const search = document.getElementById('search-input').value;
                            htmx.ajax('GET', '{$basePath}?per_page=' + e.target.value + '&search=' + encodeURIComponent(search), {target: '#content'});
                        });
                        // Set initial filter values if present
                        if ('{$filterField}') {
                            filterField.value = '{$filterField}';
                            filterType.value = '{$filterType}';
                            filterPanel.classList.remove('hidden');
                            if (filterType.value === 'between') {
                                filterValueEndContainer.classList.remove('hidden');
                            }
                        }
                        
                        window.exportData = function(table, format) {
                            fetch('/admin/' + table + '/export?format=' + format)
                                .then(r => r.blob())
                                .then(blob => {
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = table + '_' + new Date().toISOString().split('T')[0] + '.' + format;
                                    document.body.appendChild(a);
                                    a.click();
                                    a.remove();
                                    window.URL.revokeObjectURL(url);
                                });
                        };
                        lucide.createIcons();
                    })();
                </script>";
                
                $content .= "</div>";
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), 'no such table')) {
                    $content = "<div class='bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-6 rounded-2xl mb-8'>
                        <div class='flex items-start gap-4'>
                            <div class='w-10 h-10 bg-red-100 dark:bg-red-500/20 rounded-xl flex items-center justify-center flex-shrink-0 text-red-600 dark:text-red-400'>
                                <i data-lucide='alert-triangle' class='w-5 h-5'></i>
                            </div>
                            <div>
                                <h3 class='text-red-800 dark:text-red-400 font-bold text-lg'>Table Missing in Database</h3>
                                <p class='text-red-600/80 dark:text-red-400/80 text-sm mt-1 mb-4'>The JSON schema exists, but the table <b>{$table}</b> has not been created or synced to the database yet. Review the schema below and click Sync to migrate.</p>
                            </div>
                        </div>
                    </div>";
                    $content .= View::schemaForm($meta);
                } else {
                    throw $e;
                }
            }
            
            if ($req->header('HX-Request')) { echo $content; exit; }
            echo View::layout($table, $content, $allMeta);
            exit;
        }, [Middleware::class . '::session']);

        // Admin Create Form
        $router->add('GET', "{$basePath}/create", function(Request $req, Response $res) use ($table, $meta, $allMeta) {
            $content = "<h3 class='text-xl font-bold mb-6'>Add New " . ucfirst($table) . "</h3>";
            $content .= View::form($meta['fields'], "/admin/{$table}/store");
            
            if ($req->header('HX-Request')) { echo $content; exit; }
            echo View::layout("New {$table}", $content, $allMeta);
            exit;
        }, [Middleware::class . '::session']);

        // Admin Store Record
        $router->add('POST', "{$basePath}/store", function(Request $req, Response $res) use ($table, $meta) {
            $input = $req->input();
            $fields = [];
            $placeholders = [];
            $values = [];
            
            $hasIdField = false;
            $idField = null;
            foreach ($meta['fields'] as $field) {
                if ($field['name'] === 'id') {
                    $hasIdField = true;
                    $idField = $field;
                    break;
                }
            }
            
            if ($hasIdField && empty($input['id'])) {
                if (strtoupper($idField['type'] ?? '') === 'UUID') {
                    $input['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
            }
            
            foreach ($meta['fields'] as $field) {
                if ($field['name'] === 'id' && !empty($idField['autoincrement'])) {
                    continue;
                }
                if ($field['name'] === 'created_at' || $field['name'] === 'updated_at') {
                    continue;
                }
                
                // Handle BLOB file uploads
                $fType = strtoupper($field['type'] ?? 'TEXT');
                if ($fType === 'BLOB' && isset($input[$field['name'] . '_base64'])) {
                    $base64Data = $input[$field['name'] . '_base64'];
                    // Remove data:image/png;base64, prefix
                    if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64Data, $matches)) {
                        $binaryData = base64_decode($matches[2]);
                        $fields[] = "`{$field['name']}`";
                        $placeholders[] = '?';
                        $values[] = $binaryData;
                    }
                } elseif (isset($input[$field['name']])) {
                    $fields[] = "`{$field['name']}`";
                    $placeholders[] = '?';
                    $values[] = $input[$field['name']];
                }
            }
            DB::query("INSERT INTO `{$table}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")", $values);
            header('Location: /admin/' . $table);
            exit;
        }, [Middleware::class . '::session']);

        // Admin Edit Form
        $router->add('GET', "{$basePath}/edit/{id}", function(Request $req, Response $res, array $params) use ($table, $meta, $allMeta) {
            $fields = array_column($meta['fields'], 'name');
            $fields[] = 'id';
            $selectCols = implode(', ', array_map(fn($f) => "`{$f}`", $fields));
            $data = DB::query("SELECT {$selectCols} FROM `{$table}` WHERE `id` = ?", [$params['id']]);
            if (empty($data)) {
                $content = "<div class='text-red-500'>Record not found</div>";
            } else {
                $content = "<h3 class='text-xl font-bold mb-6'>Edit " . ucfirst($table) . "</h3>";
                $content .= View::form($meta['fields'], "/admin/{$table}/update/{$params['id']}", $data[0], 'POST');
            }
            
            if ($req->header('HX-Request')) { echo $content; exit; }
            echo View::layout("Edit {$table}", $content, $allMeta);
            exit;
        }, [Middleware::class . '::session']);

        // Admin Update Record
        $router->add('POST', "{$basePath}/update/{id}", function(Request $req, Response $res, array $params) use ($table, $meta) {
            $input = $req->input();
            $fields = [];
            $values = [];
            foreach ($meta['fields'] as $field) {
                if ($field['name'] === 'id') continue;
                if ($field['name'] === 'created_at') continue;
                if ($field['name'] === 'updated_at') {
                    $fields[] = "`updated_at` = CURRENT_TIMESTAMP";
                    continue;
                }
                
                // Handle BLOB file uploads
                $fType = strtoupper($field['type'] ?? 'TEXT');
                if ($fType === 'BLOB' && isset($input[$field['name'] . '_base64'])) {
                    $base64Data = $input[$field['name'] . '_base64'];
                    if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64Data, $matches)) {
                        $binaryData = base64_decode($matches[2]);
                        $fields[] = "`{$field['name']}` = ?";
                        $values[] = $binaryData;
                    }
                } elseif (isset($input[$field['name']])) {
                    $fields[] = "`{$field['name']}` = ?";
                    $values[] = $input[$field['name']];
                }
            }
            $values[] = $params['id'];
            DB::query("UPDATE `{$table}` SET " . implode(', ', $fields) . " WHERE `id` = ?", $values);
            header('Location: /admin/' . $table);
            exit;
        }, [Middleware::class . '::session']);

        // Admin Delete Record
        $router->add('POST', "{$basePath}/delete/{id}", function(Request $req, Response $res, array $params) use ($table) {
            DB::query("DELETE FROM `{$table}` WHERE `id` = ?", [$params['id']]);
            header('Location: /admin/' . $table);
            exit;
        }, [Middleware::class . '::session']);
    }
}
