<?php
namespace Core\UI\Components;

class Table
{
    public static function render(array $data, array $fields, string $table, string $sort = '', string $order = 'asc', string $basePath = ''): string
    {
        $html = '<div class="bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden backdrop-blur-sm shadow-xl">';
        $html .= '<div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 px-6 py-3 flex items-center justify-between">';
        $html .= '<span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Columns</span>';
        $html .= '<button id="toggle-columns-btn" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 rounded-md border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all"><i data-lucide="eye" class="w-3 h-3"></i>Toggle</button>';
        $html .= '</div>';
        $html .= '<div id="column-toggles" class="hidden border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20 px-6 py-4 flex flex-wrap gap-3">';
        foreach ($fields as $idx => $field) {
            $fieldName = $field['name'];
            $html .= '<label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">';
            $html .= '<input type="checkbox" class="column-toggle w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" data-column="' . $idx . '" checked>';
            $html .= '<span>' . e($fieldName) . '</span></label>';
        }
        $html .= '</div>';
        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="w-full text-left border-collapse min-w-full">';
        $html .= '<thead><tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30">';
        $html .= "<th class='px-6 py-4 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-12'><input type='checkbox' id='select-all' class='w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500'></th>";
        foreach ($fields as $idx => $field) {
            $fieldName = $field['name'];
            $isSorted = ($sort === $fieldName);
            $nextOrder = ($isSorted && $order === 'asc') ? 'desc' : 'asc';
            $sortIcon = '';
            if ($isSorted) {
                $sortIcon = $order === 'asc' ? '<i data-lucide="chevron-up" class="w-3 h-3 inline"></i>' : '<i data-lucide="chevron-down" class="w-3 h-3 inline"></i>';
            }
            $sortUrl = !empty($basePath) ? "{$basePath}?sort={$fieldName}&order={$nextOrder}" : "#";
            $html .= "<th class='col-{$idx} px-6 py-4 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap'>";
            $html .= "<a href='#' hx-get='{$sortUrl}' hx-target='#content' class='flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer'>";
            $html .= e($fieldName) . $sortIcon;
            $html .= "</a></th>";
        }
        $html .= "<th class='px-6 py-4 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right whitespace-nowrap'>Actions</th>";
        $html .= '</tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800/50">';
        if (empty($data)) {
            $colCount = count($fields) + 2;
            $html .= "<tr><td colspan='{$colCount}' class='px-6 py-16 text-center'><div class='flex flex-col items-center gap-3 text-slate-400 dark:text-slate-600'><i data-lucide='inbox' class='w-12 h-12'></i><p class='text-sm font-medium'>No records found</p><p class='text-xs'>Create your first record to get started</p></div></td></tr>";
        }
        foreach ($data as $row) {
            $id = $row['id'] ?? '';
            $html .= '<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors">';
            $html .= "<td class='px-6 py-4'><input type='checkbox' class='row-select w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500' value='" . e($id) . "'></td>";
            foreach ($fields as $idx => $field) {
                $val = $row[$field['name']] ?? '';
                $displayVal = e($val);
                $truncated = false;
                if (strlen($val) > 50) {
                    $displayVal = e(substr($val, 0, 50)) . '...';
                    $truncated = true;
                }
                $cellClass = "col-{$idx} px-6 py-4 text-sm text-slate-700 dark:text-slate-300 whitespace-nowrap";
                if ($truncated) {
                    $cellClass .= " cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400";
                    $html .= "<td class='{$cellClass}' onclick='showModal(" . htmlspecialchars(json_encode($val), ENT_QUOTES) . ")' title='Click to view full content'>{$displayVal}</td>";
                } else {
                    $html .= "<td class='{$cellClass}'>{$displayVal}</td>";
                }
            }
            $html .= "<td class='px-6 py-4 text-right space-x-2 whitespace-nowrap'>";
            $html .= "<a href='/admin/" . e($table) . "/edit/" . e($id) . "' class='inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 rounded-md border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all'><i data-lucide='edit-2' class='w-3 h-3'></i>Edit</a>";
            $html .= "<form method='POST' action='/admin/" . e($table) . "/delete/" . e($id) . "' style='display:inline' onsubmit='return confirm(\"Delete this record?\")'>";
            $html .= "<button type='submit' class='inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-400/5 rounded-md border border-red-200 dark:border-red-400/20 hover:bg-red-400/10 transition-all'><i data-lucide='trash-2' class='w-3 h-3'></i>Delete</button></form>";
            $html .= "</td></tr>";
        }
        $html .= '</tbody></table></div>';
        $html .= "<div id='bulk-actions' class='hidden border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-between'>";
        $html .= "<span class='text-sm text-slate-600 dark:text-slate-400'><span id='selected-count'>0</span> selected</span>";
        $html .= "<button id='bulk-delete-btn' class='inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-500 transition-all'><i data-lucide='trash-2' class='w-4 h-4'></i>Delete Selected</button>";
        $html .= "</div></div>";
        $html .= "<div id='content-modal' class='hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4' onclick='closeModal(event)'>";
        $html .= "<div class='bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden' onclick='event.stopPropagation()'>";
        $html .= "<div class='flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800'>";
        $html .= "<h3 class='text-lg font-bold text-slate-900 dark:text-white'>Full Content</h3>";
        $html .= "<button onclick='closeModal()' class='text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors'><i data-lucide='x' class='w-5 h-5'></i></button>";
        $html .= "</div>";
        $html .= "<div class='p-6 overflow-y-auto max-h-[60vh]'><pre id='modal-content' class='text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap break-words'></pre></div>";
        $html .= "</div></div>";
        $html .= "<script>
            (function() {
                const selectAll = document.getElementById('select-all');
                const rowSelects = document.querySelectorAll('.row-select');
                const bulkActions = document.getElementById('bulk-actions');
                const selectedCount = document.getElementById('selected-count');
                const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
                const toggleBtn = document.getElementById('toggle-columns-btn');
                const columnToggles = document.getElementById('column-toggles');
                const columnCheckboxes = document.querySelectorAll('.column-toggle');
                const modal = document.getElementById('content-modal');
                const modalContent = document.getElementById('modal-content');
                window.showModal = function(content) {
                    modalContent.textContent = content;
                    modal.classList.remove('hidden');
                    lucide.createIcons();
                };
                window.closeModal = function(event) {
                    if (!event || event.target === modal) {
                        modal.classList.add('hidden');
                    }
                };
                function updateBulkActions() {
                    const checked = document.querySelectorAll('.row-select:checked');
                    selectedCount.textContent = checked.length;
                    bulkActions.classList.toggle('hidden', checked.length === 0);
                }
                selectAll.addEventListener('change', function() {
                    rowSelects.forEach(cb => cb.checked = this.checked);
                    updateBulkActions();
                });
                rowSelects.forEach(cb => {
                    cb.addEventListener('change', function() {
                        selectAll.checked = Array.from(rowSelects).every(c => c.checked);
                        updateBulkActions();
                    });
                });
                toggleBtn.addEventListener('click', function() {
                    columnToggles.classList.toggle('hidden');
                });
                columnCheckboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        const colIdx = this.dataset.column;
                        const cols = document.querySelectorAll('.col-' + colIdx);
                        cols.forEach(col => {
                            col.style.display = this.checked ? '' : 'none';
                        });
                    });
                });
                bulkDeleteBtn.addEventListener('click', function() {
                    const checked = Array.from(document.querySelectorAll('.row-select:checked'));
                    if (checked.length === 0) return;
                    if (!confirm('Delete ' + checked.length + ' record(s)?')) return;
                    const ids = checked.map(cb => cb.value);
                    fetch('/admin/" . e($table) . "/bulk-delete', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ids: ids})
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (data.error || 'Unknown error'));
                        }
                    });
                });
                lucide.createIcons();
            })();
        </script>";
        return $html;
    }

}
