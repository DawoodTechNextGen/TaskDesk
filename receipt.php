<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';

$interns_query = $conn->query("
    SELECT u.id, u.name, u.email, t.name AS tech_name
    FROM users u
    LEFT JOIN technologies t ON u.tech_id = t.id
    WHERE u.user_role = 2
    ORDER BY u.name ASC
");
$interns = $interns_query ? $interns_query->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Receipt Generator - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Receipt Generator</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Select an intern, enter the fee amount, and generate an official PDF receipt.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Generate Form -->
                    <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6 h-fit">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">New Receipt</h3>
                        <form id="receipt-form">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Search Intern</label>
                                <input type="text" id="intern-search" placeholder="Type a name to filter..."
                                    class="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100 mb-2">
                                <select name="intern_id" id="intern-select" required
                                    class="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">-- Select Intern --</option>
                                    <?php foreach ($interns as $intern): ?>
                                        <option value="<?= (int)$intern['id'] ?>"
                                            data-email="<?= htmlspecialchars($intern['email']) ?>"
                                            data-tech="<?= htmlspecialchars($intern['tech_name'] ?: 'N/A') ?>">
                                            <?= htmlspecialchars($intern['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="intern-preview" class="mb-4 text-xs text-gray-500 dark:text-gray-400 hidden">
                                <div><span class="font-medium">Email:</span> <span id="preview-email"></span></div>
                                <div><span class="font-medium">Track:</span> <span id="preview-tech"></span></div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Fee Amount (PKR)</label>
                                <input type="number" name="amount" min="1" step="0.01" required placeholder="e.g. 5000"
                                    class="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Notes (Optional)</label>
                                <textarea name="notes" rows="2" placeholder="e.g. Registration fee, 1st installment"
                                    class="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"></textarea>
                            </div>

                            <button type="submit" id="generate-btn"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Generate Receipt
                            </button>
                        </form>
                    </div>

                    <!-- History -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Receipt History</h2>
                        </div>
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="receiptsTable" class="min-w-full">
                                <thead class="bg-indigo-200 dark:bg-indigo-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Receipt No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Intern</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        let receiptsTable;

        function formatDisplayDate(dateString) {
            if (!dateString) return 'N/A';
            const d = new Date(dateString);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        async function loadReceipts() {
            const res = await fetch('controller/receipt.php?action=list');
            const json = await res.json();
            if (!json.success) return;

            receiptsTable.clear();
            json.data.forEach(r => {
                receiptsTable.row.add([
                    r.receipt_no,
                    `${r.intern_name}<br><span class="text-gray-400">${r.intern_email}</span>`,
                    `PKR ${parseFloat(r.amount).toLocaleString()}`,
                    formatDisplayDate(r.issued_at),
                    `<button class="download-receipt text-indigo-600 dark:text-indigo-400 hover:underline font-semibold" data-id="${r.id}">Download</button>`
                ]);
            });
            receiptsTable.draw();
        }

        function downloadBlobResponse(blob, fallbackName) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fallbackName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        document.addEventListener('DOMContentLoaded', () => {
            receiptsTable = $('#receiptsTable').DataTable({
                ordering: false,
                pageLength: 10
            });
            loadReceipts();

            const internSelect = document.getElementById('intern-select');
            const internSearch = document.getElementById('intern-search');
            const preview = document.getElementById('intern-preview');

            internSearch.addEventListener('input', () => {
                const q = internSearch.value.trim().toLowerCase();
                Array.from(internSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = q.length > 0 && !opt.textContent.toLowerCase().includes(q);
                });
            });

            internSelect.addEventListener('change', () => {
                const opt = internSelect.selectedOptions[0];
                if (opt && opt.value) {
                    document.getElementById('preview-email').textContent = opt.dataset.email || 'N/A';
                    document.getElementById('preview-tech').textContent = opt.dataset.tech || 'N/A';
                    preview.classList.remove('hidden');
                } else {
                    preview.classList.add('hidden');
                }
            });

            document.getElementById('receipt-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('generate-btn');
                const formData = new FormData(e.target);
                formData.append('action', 'generate');

                btn.disabled = true;
                btn.classList.add('opacity-60', 'cursor-not-allowed');

                try {
                    const res = await fetch('controller/receipt.php', {
                        method: 'POST',
                        body: formData
                    });

                    const contentType = res.headers.get('Content-Type') || '';
                    if (contentType.includes('application/pdf')) {
                        const blob = await res.blob();
                        const disposition = res.headers.get('Content-Disposition') || '';
                        const match = disposition.match(/filename="(.+)"/);
                        downloadBlobResponse(blob, match ? match[1] : 'Receipt.pdf');
                        showToast('success', 'Receipt generated successfully.');
                        e.target.reset();
                        preview.classList.add('hidden');
                        loadReceipts();
                    } else {
                        const json = await res.json();
                        showToast('error', json.message || 'Failed to generate receipt.');
                    }
                } catch (err) {
                    console.error(err);
                    showToast('error', 'Network error. Please try again.');
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });

            document.addEventListener('click', async (e) => {
                const btn = e.target.closest('.download-receipt');
                if (!btn) return;

                const id = btn.dataset.id;
                btn.textContent = 'Downloading...';

                try {
                    const res = await fetch(`controller/receipt.php?action=download&id=${id}`);
                    const contentType = res.headers.get('Content-Type') || '';
                    if (contentType.includes('application/pdf')) {
                        const blob = await res.blob();
                        const disposition = res.headers.get('Content-Disposition') || '';
                        const match = disposition.match(/filename="(.+)"/);
                        downloadBlobResponse(blob, match ? match[1] : 'Receipt.pdf');
                    } else {
                        const json = await res.json();
                        showToast('error', json.message || 'Failed to download receipt.');
                    }
                } catch (err) {
                    console.error(err);
                    showToast('error', 'Network error. Please try again.');
                } finally {
                    btn.textContent = 'Download';
                }
            });
        });
    </script>
</body>

</html>
