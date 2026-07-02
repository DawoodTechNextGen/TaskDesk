document.addEventListener('DOMContentLoaded', () => {
    const requestsBody = document.getElementById('requestsBody');
    const requestsMessage = document.getElementById('requestsMessage');

    const showMessage = (message, type = 'success') => {
        requestsMessage.textContent = message;
        requestsMessage.className = `mb-4 rounded-xl px-4 py-3 text-sm ${type === 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'}`;
        requestsMessage.classList.remove('hidden');
        setTimeout(() => {
            requestsMessage.classList.add('hidden');
        }, 5000);
    };

    const loadRequests = async () => {
        try {
            const response = await fetch('controller/chat_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_requests' }),
            });
            const result = await response.json();
            if (!result.success) {
                requestsBody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-red-500 dark:text-red-400">${result.message || 'Failed to load chat requests.'}</td></tr>`;
                return;
            }
            const pendingCount = result.data ? result.data.length : 0;
            const badgeElement = document.querySelector('#chat-requests-badge');
            if (badgeElement) {
                badgeElement.textContent = pendingCount > 0 ? pendingCount : '';
                badgeElement.style.display = pendingCount > 0 ? 'inline-flex' : 'none';
            }

            if (!result.data || !result.data.length) {
                requestsBody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No chat requests found.</td></tr>`;
                return;
            }

            requestsBody.innerHTML = result.data.map((request, index) => {
                const statusClasses = {
                    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200',
                    accepted: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
                    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
                };
                return `
                    <tr>
                        <td class="px-4 py-4">${index + 1}</td>
                        <td class="px-4 py-4">${request.sender_name} <span class="text-xs text-gray-400">(${request.sender_email})</span></td>
                        <td class="px-4 py-4">${request.receiver_name} <span class="text-xs text-gray-400">(${request.receiver_email})</span></td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${statusClasses[request.status] || 'bg-gray-100 text-gray-800'}">${request.status}</span>
                        </td>
                        <td class="px-4 py-4">${new Date(request.created_at).toLocaleString()}</td>
                        <td class="px-4 py-4">${new Date(request.updated_at).toLocaleString()}</td>
                        <td class="px-4 py-4 space-x-2">
                            <button data-id="${request.id}" data-action="approve" class="approve-btn inline-flex items-center px-3 py-1.5 rounded-lg bg-green-600 text-white text-xs font-semibold hover:bg-green-700 transition">Approve</button>
                            <button data-id="${request.id}" data-action="reject" class="reject-btn inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition">Reject</button>
                        </td>
                    </tr>
                `;
            }).join('');

            document.querySelectorAll('.approve-btn, .reject-btn').forEach((button) => {
                button.addEventListener('click', async (event) => {
                    const requestId = event.currentTarget.dataset.id;
                    const action = event.currentTarget.dataset.action;
                    await updateRequestStatus(requestId, action);
                });
            });
        } catch (error) {
            requestsBody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-red-500 dark:text-red-400">Unable to load requests. Please try again later.</td></tr>`;
            console.error('Error loading chat requests', error);
        }
    };

    const updateRequestStatus = async (requestId, action) => {
        try {
            const response = await fetch('controller/chat_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, request_id: requestId }),
            });
            const result = await response.json();
            if (result.success) {
                showMessage(result.message, 'success');
                await loadRequests();
            } else {
                showMessage(result.message || 'Failed to update request status.', 'error');
            }
        } catch (error) {
            showMessage('Failed to update request status. Please try again.', 'error');
            console.error('Error updating request status', error);
        }
    };

    loadRequests();
});
