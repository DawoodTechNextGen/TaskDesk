// Task Management Reusable Logic
let taskDataTable;
let createEditor, editEditor;
let liveTimerInterval;

// Format Date helpers
function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return 'N/A';
    const date = new Date(dateTimeStr);
    return date.toLocaleString();
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800">Pending</span>',
        'working': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800">Working</span>',
        'complete': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">Complete</span>',
        'pending_review': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">Review</span>',
        'approved': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Approved</span>',
        'rejected': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800">Rejected</span>',
        'needs_improvement': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 border border-orange-200 dark:border-orange-800">Improvement</span>',
        'expired': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">Expired</span>'
    };
    return badges[status.toLowerCase()] || `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">${status}</span>`;
}

// Initialize Task Management Page
async function initTaskManagement(statusFilter = 'all') {
    taskDataTable = $('#tasksTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 10,
        columnDefs: [
            { targets: -1, orderable: false }
        ]
    });

    await loadTasks(statusFilter);
    initializeSearchableSelects();
    setupEventListeners();
}

async function loadTasks(statusFilter) {
    const loader = document.getElementById('table-loader');
    if (loader) loader.classList.remove('hidden', 'opacity-0');

    try {
        const response = await fetch('controller/task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get', status: statusFilter === 'all' ? null : statusFilter })
        });
        const result = await response.json();

        if (result.success) {
            taskDataTable.clear();
            result.data.forEach((task, index) => {
                const actions = generateTaskActions(task);
                taskDataTable.row.add([
                    index + 1,
                    `#${task.id}`,
                    `<span class="font-medium text-gray-800 dark:text-white">${task.title}</span>`,
                    task.assign_to || 'Unassigned',
                    getStatusBadge(task.status),
                    formatDateTime(task.created_at),
                    actions
                ]);
            });
            taskDataTable.draw();
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
        showToast('error', 'Failed to load tasks');
    } finally {
        if (loader) {
            loader.classList.add('opacity-0');
            setTimeout(() => loader.classList.add('hidden'), 300);
        }
    }
}

function generateTaskActions(task) {
    const isOwner = true; // For now assuming creator/supervisor
    let buttons = `
        <div class="flex items-center space-x-2">
            <button onclick="viewTaskDetails(${task.id})" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="View Details">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            </button>
    `;

    if (task.status === 'pending_review') {
        buttons += `
            <button onclick="triggerReviewModal(${task.id})" class="px-3 py-1 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                Review
            </button>
        `;
    }

    if (task.status === 'expired') {
        buttons += `
            <button onclick="triggerReactivateModal(${task.id})" class="px-3 py-1 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 transition-colors shadow-sm">
                Reactivate
            </button>
        `;
    }

    if (['pending', 'needs_improvement'].includes(task.status)) {
        buttons += `
            <button onclick="editTaskModal(${JSON.stringify(task).replace(/"/g, '&quot;')})" class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors" title="Edit Task">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </button>
        `;
    }

    // Only show delete button for Admin (1), Manager (4), or Supervisor (3)
    if (typeof role !== 'undefined' && ['1', '4', '3'].includes(role.toString())) {
        buttons += `
            <button onclick="deleteTask(${task.id})" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete Task">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        `;
    }

    buttons += '</div>';
    return buttons;
}

// Task Creation Form Logic
async function initTaskCreation() {
    initializeSearchableSelects();
    await initTextEditor('#description-editor', 'create');

    const form = document.getElementById('create-task-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('create-task-btn');
            const btnText = document.getElementById('btn-text');
            const btnIcon = document.getElementById('btn-icon');
            const btnLoader = document.getElementById('btn-loader');

            const payload = {
                action: 'create',
                title: document.getElementById('title').value,
                due_date: document.getElementById('due_date').value,
                user_id: document.getElementById('user_id').value,
                description: createEditor ? createEditor.root.innerHTML : document.querySelector('#description-editor').innerHTML
            };

            if (!payload.user_id) {
                showToast('error', 'Please select an intern');
                return;
            }

            // Start Loading
            if (btn) btn.disabled = true;
            if (btnText) btnText.textContent = 'Creating...';
            if (btnIcon) btnIcon.classList.add('hidden');
            if (btnLoader) btnLoader.classList.remove('hidden');

            try {
                const response = await fetch('controller/task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.success) {
                    showToast('success', 'Task created successfully!');
                    form.reset();
                    if (createEditor) createEditor.setContents([]);
                    setTimeout(() => window.location.href = 'tasks.php?status=pending', 1500);
                } else {
                    showToast('error', result.message);
                    // Reset Loading on Error
                    if (btn) btn.disabled = false;
                    if (btnText) btnText.textContent = 'Create Task';
                    if (btnIcon) btnIcon.classList.remove('hidden');
                    if (btnLoader) btnLoader.classList.add('hidden');
                }
            } catch (error) {
                showToast('error', 'Failed to create task');
                // Reset Loading on Error
                if (btn) btn.disabled = false;
                if (btnText) btnText.textContent = 'Create Task';
                if (btnIcon) btnIcon.classList.remove('hidden');
                if (btnLoader) btnLoader.classList.add('hidden');
            }
        });
    }
}

// Shared Searchable Select Logic
function initializeSearchableSelects() {
    document.querySelectorAll(".searchable-wrapper").forEach((wrapper) => {
        const select = wrapper.querySelector(".searchable-select");
        const input = wrapper.querySelector(".searchable-input");
        const dropdown = wrapper.querySelector(".searchable-dropdown");

        if (!select || !input || !dropdown) return;

        function populateDropdown(showAll = false) {
            dropdown.innerHTML = "";
            const options = Array.from(select.options);
            const searchTerm = showAll ? "" : input.value.toLowerCase().trim();
            let hasResults = false;

            options.forEach((option) => {
                if (!option.value) return;
                const optionText = option.text.toLowerCase();
                if (!searchTerm || optionText.includes(searchTerm)) {
                    hasResults = true;
                    const li = document.createElement("li");
                    li.className = "px-4 py-2.5 cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 border-b border-gray-50 dark:border-gray-600 last:border-0";
                    li.textContent = option.text;
                    li.addEventListener("click", () => {
                        input.value = option.text;
                        select.value = option.value;
                        dropdown.classList.add("hidden");
                    });
                    dropdown.appendChild(li);
                }
            });

            if (!hasResults) {
                const li = document.createElement("li");
                li.className = "px-4 py-3 text-gray-500 dark:text-gray-400 text-center text-sm";
                li.textContent = "No interns found";
                dropdown.appendChild(li);
            }
        }

        input.addEventListener("focus", () => {
            populateDropdown(true);
            dropdown.classList.remove("hidden");
        });

        input.addEventListener("input", () => {
            populateDropdown();
            dropdown.classList.remove("hidden");
        });

        document.addEventListener("click", (e) => {
            if (!wrapper.contains(e.target)) dropdown.classList.add("hidden");
        });
    });
}

// Quill.js Initialization (Replacing TinyMCE)
async function initTextEditor(selector, mode) {
    const container = document.querySelector(selector);
    if (!container) return;

    // Clear previous if any (though Quill doesn't have a direct 'remove' for instance like TinyMCE, 
    // we just ensure we don't double init if not needed)
    if (mode === 'create' && createEditor) return;
    if (mode === 'edit' && editEditor) return;

    const toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
        ['blockquote', 'code-block'],
        [{ 'header': 1 }, { 'header': 2 }],               // custom button values
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'script': 'sub' }, { 'script': 'super' }],      // superscript/subscript
        [{ 'indent': '-1' }, { 'indent': '+1' }],          // outdent/indent
        [{ 'direction': 'rtl' }],                         // text direction
        [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
        [{ 'font': [] }],
        [{ 'align': [] }],
        ['link', 'image', 'video'],
        ['clean']                                         // remove formatting button
    ];

    try {
        const quill = new Quill(selector, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
            },
            placeholder: 'Start writing description...'
        });

        if (mode === 'create') createEditor = quill;
        else editEditor = quill;

        // Custom styling for dark mode
        const isDark = document.documentElement.classList.contains('dark');
        applyQuillTheme(isDark);

    } catch (error) {
        console.error('Quill Init Error:', error);
    }
}

function applyQuillTheme(isDark) {
    const editors = document.querySelectorAll('.ql-container, .ql-toolbar');
    editors.forEach(el => {
        if (isDark) {
            el.style.borderColor = '#4b5563'; // gray-600
            el.style.backgroundColor = '#1f2937'; // gray-800
        } else {
            el.style.borderColor = '#e5e7eb'; // gray-200
            el.style.backgroundColor = '#f9fafb'; // gray-50
        }
    });
}

// Observe theme changes to re-init editors
const themeObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            const editors = [
                { id: '#description-editor', mode: 'create' },
                { id: '#edit-description-editor', mode: 'edit' }
            ];

            editors.forEach(ed => {
                if (document.querySelector(ed.id)) {
                    applyQuillTheme(document.documentElement.classList.contains('dark'));
                }
            });
        }
    });
});

themeObserver.observe(document.documentElement, { attributes: true });

// Toast Helper (Reusing project's toast system if exists, or simple alert)
function showToast(type, message) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-500' : 'bg-red-500';
    toast.className = `flex items-center p-4 mb-4 text-white rounded-xl shadow-lg animate-fadeIn ${bgColor}`;
    toast.innerHTML = `
        <div class="ml-3 text-sm font-medium">${message}</div>
        <button class="ml-auto -mx-1.5 -my-1.5 p-1.5 inline-flex items-center justify-center h-8 w-8 text-white hover:bg-white/20 rounded-lg">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// Setup Event Listeners
function setupEventListeners() {
    // Close modals
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
            if (liveTimerInterval) {
                clearInterval(liveTimerInterval);
                liveTimerInterval = null;
            }
        });
    });

    // Edit Form Submit
    const editForm = document.getElementById('edit-task-form');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('update-task-btn');
            const btnText = document.getElementById('update-btn-text');
            const btnLoader = document.getElementById('update-btn-loader');

            const id = document.getElementById('edit_task_id').value;
            const payload = {
                action: 'update',
                id: id,
                title: document.getElementById('edit_title').value,
                due_date: document.getElementById('edit_due_date').value,
                user_id: document.getElementById('edit_user_id').value,
                description: editEditor ? editEditor.root.innerHTML : document.querySelector('#edit-description-editor').innerHTML
            };

            // Start Loading
            if (btn) btn.disabled = true;
            if (btnText) btnText.textContent = 'Updating...';
            if (btnLoader) btnLoader.classList.remove('hidden');

            try {
                const response = await fetch('controller/task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    showToast('success', 'Task updated successfully');
                    document.getElementById('edit-task-modal').classList.add('hidden');
                    loadTasks(new URLSearchParams(window.location.search).get('status') || 'all');
                } else {
                    showToast('error', result.message);
                }
            } catch (error) {
                showToast('error', 'Update failed');
            } finally {
                // Reset Loading
                if (btn) btn.disabled = false;
                if (btnText) btnText.textContent = 'Update Task';
                if (btnLoader) btnLoader.classList.add('hidden');
            }
        });
    }
}

// Placeholder functions for modals (to be implemented more deeply)
async function viewTaskDetails(taskId) {
    const modal = document.getElementById('view-task-modal');
    if (!modal) return;

    try {
        // Fetch full task details
        const taskResponse = await fetch('controller/task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_task', task_id: taskId })
        });
        const taskResult = await taskResponse.json();

        if (!taskResult.success) {
            showToast('error', taskResult.message || 'Task not found');
            return;
        }

        const task = taskResult.data;

        // Clear any existing timer
        if (liveTimerInterval) {
            clearInterval(liveTimerInterval);
            liveTimerInterval = null;
        }

        // Populate basic details
        const contentArea = document.getElementById('task-view-content');
        if (contentArea) {
            contentArea.innerHTML = `
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Task Title</label>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">${task.title}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Status</label>
                    <div>${getStatusBadge(task.status)}</div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Assigned To</label>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">${task.assign_to_name || 'N/A'}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Created By</label>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">${task.created_by_name || 'N/A'}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Due Date</label>
                    <p class="text-sm font-bold text-red-600 dark:text-red-400">${task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No Limit'}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Timeline</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Created: ${formatDateTime(task.created_at)}</p>
                    ${task.completed_at ? `<p class="text-xs text-green-600 dark:text-green-400">Completed: ${formatDateTime(task.completed_at)}</p>` : ''}
                </div>
            `;
        }

        const descArea = document.getElementById('task-view-description');
        if (descArea) {
            descArea.innerHTML = task.description || '<p class="text-gray-400 italic">No description provided</p>';
        }

        // Fetch and populate logs
        const logResponse = await fetch('controller/timeLog.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get', task_id: taskId })
        });
        const logResult = await logResponse.json();

        const logsBody = document.getElementById('view-logs-body');
        const totalTimeEl = document.getElementById('view-total-time');
        const logsSection = document.getElementById('time-logs-section');

        if (logsBody) logsBody.innerHTML = '';
        let totalSeconds = 0;

        if (logResult.success && logResult.logs.length > 0) {
            if (logsSection) logsSection.classList.remove('hidden');
            logResult.logs.forEach(log => {
                const duration = parseInt(log.duration) || 0;
                totalSeconds += duration;

                if (logsBody) {
                    logsBody.innerHTML += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="py-3 px-4 text-xs text-gray-700 dark:text-gray-300">${formatDateTime(log.started_at)}</td>
                            <td class="py-3 px-4 text-xs text-gray-700 dark:text-gray-300">${log.stopped_at ? formatDateTime(log.stopped_at) : '<span class="text-blue-500 animate-pulse font-bold">In Progress...</span>'}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-600 dark:text-blue-400 font-bold">${formatDuration(duration || 0)}</td>
                        </tr>
                    `;
                }
            });
            if (totalTimeEl) totalTimeEl.textContent = `Total Time: ${formatDuration(totalSeconds)}`;

            // Live Timer Logic (moved here to access totalSeconds)
            const banner = document.getElementById('live-timer-banner');
            if (banner && task.status === 'working' && task.started_at) {
                banner.classList.remove('hidden');
                const startTime = new Date(task.started_at).getTime();
                const sessionCounterEl = document.getElementById('live-counter');
                const baseTotalSeconds = totalSeconds;

                const updateLiveCounters = () => {
                    const now = new Date().getTime();
                    const sessionDiff = Math.max(0, Math.floor((now - startTime) / 1000));

                    // Update Session Counter
                    if (sessionCounterEl) sessionCounterEl.textContent = formatDuration(sessionDiff);

                    // Update Total Time Live
                    if (totalTimeEl) totalTimeEl.textContent = `Total Time: ${formatDuration(baseTotalSeconds + sessionDiff)}`;
                };

                updateLiveCounters();
                liveTimerInterval = setInterval(updateLiveCounters, 1000);
            } else if (banner) {
                banner.classList.add('hidden');
            }

        } else {
            if (logsSection) logsSection.classList.add('hidden');
        }

        // Attendance Summary
        try {
            const attResponse = await fetch('controller/timeLog.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_task_attendance', task_id: taskId })
            });
            const attResult = await attResponse.json();

            const attSection = document.getElementById('attendance-summary-section');
            const attBody = document.getElementById('attendance-summary-body');

            if (attResult.success && attResult.attendance.length > 0) {
                if (attSection) attSection.classList.remove('hidden');
                if (attBody) {
                    attBody.innerHTML = `
                        <div class="grid grid-cols-3 gap-4 font-bold text-xs text-gray-400 uppercase tracking-widest pb-2 border-b border-gray-100 dark:border-gray-700">
                            <div>Date</div>
                            <div>Work Time</div>
                            <div>Status</div>
                        </div>
                    `;
                    attResult.attendance.forEach(record => {
                        const statusColors = {
                            'present': 'text-green-600 bg-green-50 dark:bg-green-900/20',
                            'absent': 'text-red-600 bg-red-50 dark:bg-red-900/20',
                            'half_day': 'text-amber-600 bg-amber-50 dark:bg-amber-900/20'
                        };
                        const colorClass = statusColors[record.status] || 'text-gray-600 bg-gray-50';

                        attBody.innerHTML += `
                            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-50 dark:border-gray-800 items-center text-xs">
                                <div class="font-medium text-gray-700 dark:text-gray-300">${record.date}</div>
                                <div class="font-mono text-blue-600 dark:text-blue-400 font-bold">${record.formatted_time}</div>
                                <div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${colorClass}">
                                        ${record.status}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                }
            } else {
                if (attSection) attSection.classList.add('hidden');
            }
        } catch (e) {
            console.error('Attendance fetch error:', e);
        }

        // External Links (Github/Live)
        const linksArea = document.getElementById('task-links');
        if (linksArea) {
            linksArea.innerHTML = '';
            if (task.github_repo) {
                linksArea.innerHTML += `<a href="${task.github_repo}" target="_blank" class="px-4 py-2 bg-gray-800 text-white text-xs font-bold rounded-xl hover:bg-gray-900 transition-all flex items-center"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.041-1.416-4.041-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg> GitHub</a>`;
            }
            if (task.live_url) {
                linksArea.innerHTML += `<a href="${task.live_url}" target="_blank" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition-all flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg> Live Demo</a>`;
            }
        }

        modal.classList.remove('hidden');
    } catch (error) {
        console.error('Error fetching task details:', error);
        showToast('error', 'Failed to load task details');
    }
}

function formatDuration(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return [h, m, s].map(v => v < 10 ? "0" + v : v).join(":");
}

function triggerReviewModal(taskId) {
    if (typeof showReviewModal === 'function') {
        const rowData = taskDataTable.rows().data().toArray().find(r => r[1].includes(taskId));
        // Strip HTML from title if it exists
        const titleSpan = document.createElement('div');
        titleSpan.innerHTML = rowData ? rowData[2] : 'Task Review';
        const titleText = titleSpan.textContent || titleSpan.innerText || 'Task Review';
        showReviewModal(taskId, titleText);
    }
}

function triggerReactivateModal(taskId) {
    if (typeof showReactivateModal === 'function') {
        const rowData = taskDataTable.rows().data().toArray().find(r => r[1].includes(taskId));
        const titleSpan = document.createElement('div');
        titleSpan.innerHTML = rowData ? rowData[2] : 'Reactivate Task';
        const titleText = titleSpan.textContent || titleSpan.innerText || 'Reactivate Task';
        showReactivateModal(taskId, titleText, 'N/A');
    }
}

async function editTaskModal(task) {
    const modal = document.getElementById('edit-task-modal');
    if (!modal) return;

    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_title').value = task.title;
    document.getElementById('edit_due_date').value = task.due_date || '';

    // Set searchable select
    const select = document.getElementById('edit_user_id');
    const input = modal.querySelector('.searchable-input');
    if (select && input) {
        select.value = task.assign_id;
        const option = Array.from(select.options).find(o => o.value == task.assign_id);
        input.value = option ? option.text : '';
    }

    if (!editEditor) {
        await initTextEditor('#edit-description-editor', 'edit');
    }
    if (editEditor) {
        editEditor.root.innerHTML = task.description || '';
    }

    modal.classList.remove('hidden');
}
async function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task? This will also remove all associated time logs and attendance records for this task.')) {
        return;
    }

    try {
        const response = await fetch('controller/task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', task_id: taskId })
        });
        const result = await response.json();

        if (result.success) {
            showToast('success', 'Task deleted successfully');
            // Reload the current view
            const urlParams = new URLSearchParams(window.location.search);
            const currentStatus = urlParams.get('status') || 'all';
            loadTasks(currentStatus);
        } else {
            showToast('error', result.message || 'Failed to delete task');
        }
    } catch (error) {
        console.error('Delete Task Error:', error);
        showToast('error', 'An error occurred while deleting the task');
    }
}
