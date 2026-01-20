<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Technologies Management - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Manage Technologies</h2>
                    <!-- Add New Technology Button -->
                    <button class="open-modal bg-indigo-600 text-white px-4 py-2 rounded-lg m-2"
                        data-modal="add-tech-modal">
                        Add New Technology
                    </button>
                </div>
                <!-- Technologies Table -->
                <div class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">All Technologies</h2>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table id="techTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                        ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                        Name
                                    </th>
                                    <th>
                                        <span class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                            Status
                                        </span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                        Created At
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <!-- Add Technology Modal -->
    <div id="add-tech-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-950 dark:text-gray-50">Add New Technology</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="add-tech-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Technology Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="e.g. Laravel">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Technology Modal -->
    <div id="edit-tech-modal" class="modal hidden fixed inset-0 z-50 bg-black  bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-950 dark:text-gray-50">Edit Technology</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="edit-tech-form">
                <input type="hidden" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Technology Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:outline-none bg-white focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 text-gray-900">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Status</label>
                    <select name="status" required class="w-full px-3 py-2 border rounded-lg focus:outline-none bg-white focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 text-gray-900">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        let dataTable;

        // Load technologies
        async function loadTechnologies() {
            const res = await fetch('controller/technology.php?action=get');
            const result = await res.json();

            if (result.success) {
                dataTable.clear();
                result.data.forEach((tech, index) => {
                    dataTable.row.add([
                        tech.id,
                        tech.name,
                        tech.status == 1 ?
                        `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>` :
                        `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Inactive</span>`,
                        new Date(tech.created_at).toLocaleString(),
                        `
                        <button class="edit-tech text-blue-600 mr-3" data-id="${tech.id}" data-name="${tech.name}" data-status="${tech.status}">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2 12C2 16.714 2 19.0711 3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12V10.5M13.5 2H12C7.28595 2 4.92893 2 3.46447 3.46447C2.49073 4.43821 2.16444 5.80655 2.0551 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M16.652 3.45506L17.3009 2.80624C18.3759 1.73125 20.1188 1.73125 21.1938 2.80624C22.2687 3.88124 22.2687 5.62415 21.1938 6.69914L20.5449 7.34795M16.652 3.45506C16.652 3.45506 16.7331 4.83379 17.9497 6.05032C19.1662 7.26685 20.5449 7.34795 20.5449 7.34795M16.652 3.45506L10.6872 9.41993C10.2832 9.82394 10.0812 10.0259 9.90743 10.2487C9.70249 10.5114 9.52679 10.7957 9.38344 11.0965C9.26191 11.3515 9.17157 11.6225 8.99089 12.1646L8.41242 13.9M20.5449 7.34795L17.5625 10.3304M14.5801 13.3128C14.1761 13.7168 13.9741 13.9188 13.7513 14.0926C13.4886 14.2975 13.2043 14.4732 12.9035 14.6166C12.6485 14.7381 12.3775 14.8284 11.8354 15.0091L10.1 15.5876M10.1 15.5876L8.97709 15.9619C8.71035 16.0508 8.41626 15.9814 8.21744 15.7826C8.01862 15.5837 7.9492 15.2897 8.03811 15.0229L8.41242 13.9M10.1 15.5876L8.41242 13.9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                        </button>
                        <button class="delete-tech text-red-600" data-id="${tech.id}">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M9.5 11L10 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M14.5 11L14 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path> <path d="M18.3735 15.3991C18.1965 18.054 18.108 19.3815 17.243 20.1907C16.378 21 15.0476 21 12.3868 21H11.6134C8.9526 21 7.6222 21 6.75719 20.1907C5.89218 19.3815 5.80368 18.054 5.62669 15.3991L5.16675 8.5M18.8334 8.5L18.6334 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                        `
                    ]);
                });
                dataTable.draw();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            dataTable = $('#techTable').DataTable({
                ordering: false,
                pageLength: 10
            });

            // Add Technology
            document.getElementById('add-tech-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                formData.append('action', 'create');

                const res = await fetch('controller/technology.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) {
                    e.target.reset();
                    document.querySelector('#add-tech-modal .close-modal').click();
                    loadTechnologies();
                }
            });

            // Edit Technology
            document.addEventListener('click', (e) => {
                if (e.target.closest('.edit-tech')) {
                    const btn = e.target.closest('.edit-tech');
                    document.querySelector('#edit-tech-form [name="id"]').value = btn.dataset.id;
                    document.querySelector('#edit-tech-form [name="name"]').value = btn.dataset.name;
                    document.querySelector('#edit-tech-form [name="status"]').value = btn.dataset.status;
                    
                    document.getElementById('edit-tech-modal').classList.remove('hidden');
                }
            });

            document.getElementById('edit-tech-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                formData.append('action', 'update');

                const res = await fetch('controller/technology.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) {
                    document.querySelector('#edit-tech-modal .close-modal').click();
                    loadTechnologies();
                }
            });

            // Delete Technology
            document.addEventListener('click', async (e) => {
                if (e.target.closest('.delete-tech')) {
                    if (!confirm('Are you sure you want to delete this technology?')) return;
                    const id = e.target.closest('.delete-tech').dataset.id;

                    const res = await fetch('controller/technology.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'delete',
                            id
                        })
                    });
                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);
                    if (json.success) loadTechnologies();
                }
            });

            // Modal close handlers
            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.modal').classList.add('hidden');
                });
            });

            // Open add modal
            document.querySelectorAll('.open-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    const modal = document.getElementById(btn.dataset.modal);
                    modal.classList.remove('hidden');
                });
            });

            loadTechnologies();
        });

        // Simple toast function (you probably already have this in your project)
        function showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `px-4 py-3 rounded-lg text-white ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.textContent = message;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>

</html>