// Task Review and Management Functions for Supervisors

// Use global showToast if available, otherwise define a simple one
const notify = (type, msg) => {
    if (typeof showToast === 'function') {
        showToast(type, msg);
    } else {
        alert(msg);
    }
};

// Review Task Modal
function showReviewModal(taskId, taskTitle) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-lg w-full mx-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Review Task</h3>
                <button type="button" onclick="copyTaskDetails(${taskId}, this)" title="Copy all task details"
                    class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-300 dark:bg-indigo-900/30 dark:hover:bg-gray-600 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <span>Copy Task Details</span>
                </button>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">${taskTitle}</p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Review Notes (Optional)</label>
                <textarea id="reviewNotes" rows="4" 
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-white"
                    placeholder="Add feedback or comments..."></textarea>
            </div>
            
            <div class="flex space-x-3">
                <button onclick="reviewTask(${taskId}, 'approved')" 
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    ✓ Approve
                </button>
                <button onclick="reviewTask(${taskId}, 'needs_improvement')" 
                    class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    ⚠ Request Improvements
                </button>
                <button onclick="reviewTask(${taskId}, 'rejected')" 
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    ✗ Reject
                </button>
            </div>
            
            <button onclick="this.closest('.fixed').remove()" 
                class="mt-4 w-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Cancel
            </button>
        </div>
    `;
    document.body.appendChild(modal);
}

// Rich-text (Quill) HTML -> readable plain text, keeping line breaks and list bullets.
function htmlToPlainText(html) {
    const box = document.createElement('div');
    box.style.cssText = 'position:fixed;left:-9999px;top:0;white-space:normal;';
    box.innerHTML = html || '';
    box.querySelectorAll('li').forEach(li => {
        const list = li.parentElement;
        const marker = list && list.tagName === 'OL' ? `${[...list.children].indexOf(li) + 1}. ` : '- ';
        li.prepend(document.createTextNode(marker));
    });
    document.body.appendChild(box);
    const text = box.innerText;
    box.remove();
    return text.replace(/\n{3,}/g, '\n\n').trim();
}

async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch (e) {
        // Fallback for browsers/contexts without the async clipboard API
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;left:-9999px;top:0;';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        ta.remove();
        return ok;
    }
}

// Copy everything about the task being reviewed (details, links, intern notes,
// description) as plain text, e.g. to paste into a chat or a review tool.
async function copyTaskDetails(taskId, btn) {
    const label = btn?.querySelector('span');
    const original = label?.textContent;
    try {
        const response = await fetch('controller/task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'get_task', task_id: taskId })
        });
        const result = await response.json();
        if (!result.success) {
            notify('error', result.message || 'Task not found');
            return;
        }

        const t = result.data;
        const line = (name, value) => (value !== null && value !== undefined && String(value).trim() !== '') ? `${name}: ${String(value).trim()}` : null;
        const text = [
            line('Task', t.title),
            t.week_number > 0 ? line('Week', t.week_number) : null,
            line('Assigned To', t.assign_to_name),
            line('Created By', t.created_by_name),
            line('Status', t.status),
            line('Created At', t.created_at),
            line('Due Date', t.due_date),
            line('Submitted At', t.completed_at),
            line('GitHub Repo', t.github_repo),
            line('Live URL', t.live_url),
            '',
            'Intern Notes:',
            htmlToPlainText(t.additional_notes) || '-',
            '',
            'Task Description:',
            htmlToPlainText(t.description) || '-',
        ].filter(l => l !== null).join('\n');

        if (await copyText(text)) {
            notify('success', 'Task details copied to clipboard');
            if (label) {
                label.textContent = 'Copied!';
                setTimeout(() => { label.textContent = original; }, 2000);
            }
        } else {
            notify('error', 'Could not copy to clipboard');
        }
    } catch (error) {
        notify('error', 'Error copying task details');
    }
}

// Review Task Function
async function reviewTask(taskId, action) {
    const notes = document.getElementById('reviewNotes')?.value || '';
    
    try {
        const response = await fetch('controller/task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'review_task',
                task_id: taskId,
                review_action: action,
                review_notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            notify('success', result.message);
            document.querySelector('.fixed')?.remove();
            // Reload tasks after a short delay to see the toast
            setTimeout(() => location.reload(), 1000);
        } else {
            notify('error', result.message);
        }
    } catch (error) {
        notify('error', 'Error reviewing task');
    }
}

// Reactivate Expired Task Modal
function showReactivateModal(taskId, taskTitle, currentDueDate) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Reactivate Expired Task</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">${taskTitle}</p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Due Date</label>
                <input type="text" value="${currentDueDate}" disabled
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Due Date</label>
                <input type="date" id="newDueDate" 
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div class="flex space-x-3">
                <button onclick="reactivateTask(${taskId})" 
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    Reactivate Task
                </button>
                <button onclick="this.closest('.fixed').remove()" 
                    class="flex-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Reactivate Task Function
async function reactivateTask(taskId) {
    const newDueDate = document.getElementById('newDueDate')?.value;
    
    if (!newDueDate) {
        notify('error', 'Please select a new due date');
        return;
    }
    
    try {
        const response = await fetch('controller/task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'reactivate_task',
                task_id: taskId,
                new_due_date: newDueDate
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            notify('success', result.message);
            document.querySelector('.fixed')?.remove();
            setTimeout(() => location.reload(), 1000);
        } else {
            notify('error', result.message);
        }
    } catch (error) {
        notify('error', 'Error reactivating task');
    }
}

