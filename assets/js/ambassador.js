// Shared by the Campus Ambassador pages (ambassador_dashboard.php and
// ambassador_registrations.php): badges, score text and the referral fetch.

function ambEsc(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const AMB_STATUS = {
    new: ['Applied', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
    contact: ['Contacted', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
    assessment: ['Assessment', 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'],
    interview: ['Interview', 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200'],
    hire: ['Hired', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
    rejected: ['Rejected', 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
};

const AMB_ASSESSMENT = {
    pending: ['Not started', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
    in_progress: ['In progress', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
    pass: ['Passed', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
    fail: ['Failed', 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
};

function ambBadge(map, key) {
    if (!key || !map[key]) return '-';
    return `<span class="px-2 py-1 rounded-full text-[10px] font-semibold whitespace-nowrap ${map[key][1]}">${map[key][0]}</span>`;
}

// "85% (17/20)" once the assessment is finished, "-" otherwise.
function ambScore(r) {
    if ((r.assessment_status !== 'pass' && r.assessment_status !== 'fail') || r.percentage === null) return '-';
    return `${parseFloat(r.percentage).toFixed(0)}% <span class="text-gray-400">(${ambEsc(r.score)}/${ambEsc(r.total_marks)})</span>`;
}

function ambNameCell(r) {
    return `<div style="min-width: 190px;"><div class="font-medium">${ambEsc(r.name)}</div><div class="text-gray-500 dark:text-gray-400">${ambEsc(r.university) || ambEsc(r.city)}</div></div>`;
}

function ambContactCell(r) {
    return `<div>${ambEsc(r.email)}</div><div class="text-gray-500 dark:text-gray-400">${ambEsc(r.phone)}</div>`;
}

// The logged-in ambassador's referrals, newest first. Returns [] and shows a toast on failure.
async function ambFetchReferrals() {
    try {
        const res = await fetch('controller/ambassador.php?action=my_referrals');
        const json = await res.json();
        if (json.success) return json.data;
        showToast('error', json.message || 'Failed to load your referrals');
    } catch (err) {
        showToast('error', 'Failed to load your referrals');
    }
    return [];
}
