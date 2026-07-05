document.addEventListener('DOMContentLoaded', () => {
    const rulesForm = document.getElementById('rulesForm');
    const rulesMessage = document.getElementById('rulesMessage');

    const showRulesMessage = (message, type = 'success') => {
        rulesMessage.textContent = message;
        rulesMessage.className = `mb-4 rounded-xl px-4 py-3 text-sm ${type === 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-200' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-200'}`;
        rulesMessage.classList.remove('hidden');
        setTimeout(() => {
            rulesMessage.classList.add('hidden');
        }, 5000);
    };

    const loadChatRules = async () => {
        try {
            const response = await fetch('controller/chat_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_chat_rules' }),
            });
            const result = await response.json();
            if (result.success && result.data) {
                document.getElementById('rule_admin_to_all').checked = result.data.admin_to_all === 1;
                document.getElementById('rule_supervisor_to_supervisor').checked = result.data.supervisor_to_supervisor === 1;
                document.getElementById('rule_intern_to_intern').checked = result.data.intern_to_intern === 1;
                document.getElementById('rule_intern_to_supervisor').checked = result.data.intern_to_supervisor === 1;
                document.getElementById('rule_supervisor_to_intern').checked = result.data.supervisor_to_intern === 1;
            }
        } catch (error) {
            console.error('Error loading chat rules', error);
        }
    };

    rulesForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const rules = {
                admin_to_all: document.getElementById('rule_admin_to_all').checked ? 1 : 0,
                supervisor_to_supervisor: document.getElementById('rule_supervisor_to_supervisor').checked ? 1 : 0,
                intern_to_intern: document.getElementById('rule_intern_to_intern').checked ? 1 : 0,
                intern_to_supervisor: document.getElementById('rule_intern_to_supervisor').checked ? 1 : 0,
                supervisor_to_intern: document.getElementById('rule_supervisor_to_intern').checked ? 1 : 0,
            };

            const response = await fetch('controller/chat_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_chat_rules', rules }),
            });
            const result = await response.json();
            if (result.success) {
                showRulesMessage(result.message || 'Chat rules updated successfully.', 'success');
            } else {
                showRulesMessage(result.message || 'Failed to update chat rules.', 'error');
            }
        } catch (error) {
            showRulesMessage('An error occurred. Please try again.', 'error');
            console.error('Error saving chat rules', error);
        }
    });



    // Modal and table elements for monitoring
    const logsModal = document.getElementById('logsModal');
    const logsContainer = document.getElementById('logsContainer');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const closeModalFooterBtn = document.getElementById('closeModalFooterBtn');
    const modalTitle = document.getElementById('modalTitle');
    const monitoredBody = document.getElementById('monitoredBody');

    const loadMonitoredChats = async () => {
        try {
            const response = await fetch('controller/chat_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_monitored_chats' }),
            });
            const result = await response.json();
            if (!result.success) {
                monitoredBody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-red-500 dark:text-red-400">${result.message || 'Failed to load monitored chats.'}</td></tr>`;
                return;
            }

            if (!result.data || !result.data.length) {
                monitoredBody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No active intern chats found.</td></tr>`;
                return;
            }

            monitoredBody.innerHTML = result.data.map((chat, index) => {
                return `
                    <tr>
                        <td class="px-4 py-4">${index + 1}</td>
                        <td class="px-4 py-4 font-semibold text-gray-805 dark:text-white">${chat.sender_name} <br/><span class="text-xs text-gray-400 font-normal">(${chat.sender_email})</span></td>
                        <td class="px-4 py-4 font-semibold text-gray-850 dark:text-white">${chat.receiver_name} <br/><span class="text-xs text-gray-400 font-normal">(${chat.receiver_email})</span></td>
                        <td class="px-4 py-4 text-xs text-gray-550 dark:text-gray-400">${new Date(chat.created_at).toLocaleString()}</td>
                        <td class="px-4 py-4 text-xs text-gray-550 dark:text-gray-400">${new Date(chat.updated_at).toLocaleString()}</td>
                        <td class="px-4 py-4">
                            <button data-sender-id="${chat.sender_id}" data-receiver-id="${chat.receiver_id}" data-sender-name="${chat.sender_name}" data-receiver-name="${chat.receiver_name}" class="view-logs-btn inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition">View Logs</button>
                        </td>
                    </tr>
                `;
            }).join('');

            document.querySelectorAll('.view-logs-btn').forEach((button) => {
                button.addEventListener('click', (event) => {
                    const btn = event.currentTarget;
                    const senderId = btn.dataset.senderId;
                    const receiverId = btn.dataset.receiverId;
                    const senderName = btn.dataset.senderName;
                    const receiverName = btn.dataset.receiverName;
                    openLogsModal(senderId, receiverId, senderName, receiverName);
                });
            });
        } catch (error) {
            monitoredBody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-red-500 dark:text-red-400">Unable to load monitored chats.</td></tr>`;
            console.error('Error loading monitored chats', error);
        }
    };

    const openLogsModal = async (senderId, receiverId, senderName, receiverName) => {
        modalTitle.textContent = `Chat Logs: ${senderName} & ${receiverName}`;
        logsContainer.innerHTML = '<div class="text-center py-8 text-sm text-gray-550 dark:text-gray-400">Loading conversation logs...</div>';
        logsModal.classList.remove('hidden');

        try {
            const response = await fetch('controller/chat_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_chat_logs', sender_id: senderId, receiver_id: receiverId }),
            });
            const result = await response.json();
            if (!result.success) {
                logsContainer.innerHTML = `<div class="text-center py-8 text-sm text-red-500 dark:text-red-400">${result.message || 'Failed to load logs.'}</div>`;
                return;
            }

            if (!result.data || !result.data.length) {
                logsContainer.innerHTML = '<div class="text-center py-8 text-sm text-gray-550 dark:text-gray-400">No messages exchanged yet between these interns.</div>';
                return;
            }

            logsContainer.innerHTML = result.data.map((msg) => {
                const isSenderA = Number(msg.sender_id) === Number(senderId);
                const bubbleBg = isSenderA 
                    ? 'bg-indigo-600 text-white rounded-tr-none' 
                    : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-tl-none';
                const flexAlign = isSenderA ? 'justify-end' : 'justify-start';
                const alignItem = isSenderA ? 'items-end' : 'items-start';
                const textColor = isSenderA ? 'text-white/70' : 'text-gray-400 dark:text-gray-500';
                
                let contentHtml = '';
                if (msg.message_type === 'image') {
                    contentHtml = `
                        <div class="mb-1.5 relative rounded-lg overflow-hidden border border-black/5 dark:border-white/5 max-w-[240px]">
                            <img src="${msg.file_path}" class="max-w-full max-h-48 object-cover block" alt="Sent Image" />
                        </div>
                    `;
                    if (msg.message && msg.message !== 'image.png' && msg.message !== '') {
                        contentHtml += `<p class="text-sm leading-normal whitespace-pre-wrap">${msg.message}</p>`;
                    }
                } else if (msg.message_type === 'file') {
                    contentHtml = `
                        <a href="${msg.file_path}" target="_blank" download class="flex items-center gap-2 p-2.5 rounded-lg border text-sm transition mb-1 ${
                            isSenderA 
                                ? 'bg-indigo-700/30 border-indigo-500/20 text-white hover:bg-indigo-700/50' 
                                : 'bg-gray-50 dark:bg-gray-900 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/60'
                        }">
                            <svg class="w-6 h-6 shrink-0 ${isSenderA ? 'text-indigo-200' : 'text-blue-500'}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                            <div class="text-left min-w-0 flex-1">
                                <p class="font-semibold truncate max-w-[150px]">${msg.message}</p>
                                <span class="text-[10px] opacity-75">Click to view/download</span>
                            </div>
                        </a>
                    `;
                } else {
                    contentHtml = `<p class="text-sm leading-normal whitespace-pre-wrap">${msg.message}</p>`;
                }

                return `
                    <div class="flex w-full ${flexAlign} mb-2">
                        <div class="flex flex-col ${alignItem} max-w-[70%]">
                            <span class="text-[10px] text-gray-400 dark:text-gray-500 mb-0.5 px-1">${msg.sender_name}</span>
                            <div class="rounded-2xl px-4 py-2.5 shadow-sm ${bubbleBg}">
                                ${contentHtml}
                                <span class="text-[9px] ${textColor} text-right mt-1.5 opacity-80 block select-none">
                                    ${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Scroll to bottom
            logsContainer.scrollTop = logsContainer.scrollHeight;
        } catch (error) {
            logsContainer.innerHTML = '<div class="text-center py-8 text-sm text-red-500 dark:text-red-400">Failed to load logs due to an error.</div>';
            console.error('Error loading chat logs', error);
        }
    };

    const closeLogsModal = () => {
        logsModal.classList.add('hidden');
        logsContainer.innerHTML = '';
    };

    closeModalBtn.addEventListener('click', closeLogsModal);
    closeModalFooterBtn.addEventListener('click', closeLogsModal);

    loadMonitoredChats();
    loadChatRules();
});
