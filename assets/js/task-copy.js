// Copy a task's full details as plain text (used by the Review and View Details modals).

function copyToast(type, msg) {
    if (typeof showToast === 'function') showToast(type, msg);
    else if (type === 'error') alert(msg);
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
            copyToast('error', result.message || 'Task not found');
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
            copyToast('success', 'Task details copied to clipboard');
            if (label) {
                label.textContent = 'Copied!';
                setTimeout(() => { label.textContent = original; }, 2000);
            }
        } else {
            copyToast('error', 'Could not copy to clipboard');
        }
    } catch (error) {
        copyToast('error', 'Error copying task details');
    }
}

