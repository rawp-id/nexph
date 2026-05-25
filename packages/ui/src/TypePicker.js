// Visual Type Picker for Schema Form
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

function openTypePicker(btn) {
    if (activeTypePicker) {
        activeTypePicker.remove();
        activeTypePicker = null;
    }

    const container = btn.closest('.w-36');
    const currentType = btn.querySelector('.type-input').value;
    
    const picker = document.createElement('div');
    picker.className = 'absolute z-50 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl p-4 w-[420px] left-0';
    picker.style.top = '100%';
    
    let html = '<div class="grid grid-cols-4 gap-2">';
    for (const [type, info] of Object.entries(typePickerData)) {
        const isActive = type === currentType;
        const activeClass = isActive ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-950/30' : '';
        html += `
            <button type="button" onclick="selectType(this, '${type}')" 
                class="type-option flex flex-col items-center gap-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-${info.color}-500 hover:bg-${info.color}-50 dark:hover:bg-${info.color}-950/20 transition-all ${activeClass}" 
                data-type="${type}">
                <i data-lucide="${info.icon}" class="w-5 h-5 text-${info.color}-500"></i>
                <div class="text-center">
                    <div class="text-[10px] font-bold text-slate-900 dark:text-white">${type}</div>
                    <div class="text-[8px] text-slate-500">${info.desc}</div>
                </div>
            </button>
        `;
    }
    html += '</div>';
    
    picker.innerHTML = html;
    container.appendChild(picker);
    activeTypePicker = picker;
    
    setTimeout(() => lucide.createIcons(), 10);
    
    document.addEventListener('click', closeTypePickerOutside);
}

function selectType(btn, type) {
    const container = btn.closest('.relative');
    const display = container.querySelector('.type-display');
    const input = container.querySelector('.type-input');
    const lengthInput = container.closest('.flex-1').querySelector('.length-group input');
    
    display.textContent = type;
    input.value = type;
    
    if (lengthInput) {
        const needsLength = ['VARCHAR', 'CHAR', 'DECIMAL'].includes(type);
        lengthInput.disabled = !needsLength;
        lengthInput.style.opacity = needsLength ? '1' : '0.3';
    }
    
    if (activeTypePicker) {
        activeTypePicker.remove();
        activeTypePicker = null;
    }
    
    document.removeEventListener('click', closeTypePickerOutside);
}

function closeTypePickerOutside(e) {
    if (activeTypePicker && !activeTypePicker.contains(e.target) && !e.target.closest('button[onclick*="openTypePicker"]')) {
        activeTypePicker.remove();
        activeTypePicker = null;
        document.removeEventListener('click', closeTypePickerOutside);
    }
}
