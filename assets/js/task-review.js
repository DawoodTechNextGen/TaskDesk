// Task Review and Management Functions for Supervisors

// Review Task Modal
function showReviewModal(taskId, taskTitle) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-lg w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Review Task</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">${taskTitle}</p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Review Notes (Optional)</label>
                <textarea id="reviewNotes" rows="4" 
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
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
            showToast(result.message, 'success');
            document.querySelector('.fixed')?.remove();
            // Reload tasks
            location.reload();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Error reviewing task', 'error');
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
        showToast('Please select a new due date', 'error');
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
            showToast(result.message, 'success');
            document.querySelector('.fixed')?.remove();
            location.reload();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Error reactivating task', 'error');
    }
}

// Toast Notification
function showToast(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 3000);
}
