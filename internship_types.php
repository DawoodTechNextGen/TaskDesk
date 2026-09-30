<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
require_once './include/internship_type_helper.php';
requirePageAdmin();
$types = getInternshipTypes($conn);
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Internship Types - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-10 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Internship Types</h2>
                    <button id="add-type-btn" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium">
                        Add New Type
                    </button>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-3xl">
                    Choose which internship types students can pick on the registration form, and what each one includes.
                    If every type is turned off, the form hides the Internship Type field, new registrations are saved without a type,
                    and the Internship Type column is hidden from the registration pages.
                    A new type applies to registrations only: when a student is hired, the chosen duration still decides
                    whether they become a Task Base (4 weeks) or Learning Base (8/12 weeks) intern.
                </p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- New type (shown by "Add New Type") -->
                    <form id="new-type-form" class="type-form hidden bg-white dark:bg-gray-800 rounded-xl shadow-md border-2 border-dashed border-indigo-300 dark:border-indigo-700 p-6 space-y-4" data-action="add">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Internship Type</h3>
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="is_enabled" value="1" class="w-4 h-4 accent-indigo-600" checked>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Show on form</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Label on form</label>
                            <input type="text" name="label" maxlength="100" required placeholder="e.g. Hybrid Internship"
                                class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">"Includes" heading</label>
                            <input type="text" name="includes_title" maxlength="150" placeholder="e.g. Hybrid Internship Includes:"
                                class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">
                                What's included <span class="text-xs text-gray-500">(one item per line)</span>
                            </label>
                            <textarea name="includes_items" rows="9"
                                class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" id="cancel-new-type" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Type</button>
                        </div>
                    </form>

                    <?php foreach ($types as $type): ?>
                        <form class="type-form bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                            <input type="hidden" name="type_value" value="<?= (int)$type['type_value'] ?>">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars(internshipTypeLabel($type['type_value'])) ?></h3>
                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" name="is_enabled" value="1" class="w-4 h-4 accent-indigo-600" <?= $type['is_enabled'] ? 'checked' : '' ?>>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Show on form</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Label on form</label>
                                <input type="text" name="label" maxlength="100" required value="<?= htmlspecialchars($type['label']) ?>"
                                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">"Includes" heading</label>
                                <input type="text" name="includes_title" maxlength="150" value="<?= htmlspecialchars($type['includes_title']) ?>"
                                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">
                                    What's included <span class="text-xs text-gray-500">(one item per line)</span>
                                </label>
                                <textarea name="includes_items" rows="9"
                                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"><?= htmlspecialchars(implode("\n", $type['includes_items'])) ?></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const newTypeForm = document.getElementById('new-type-form');
        document.getElementById('add-type-btn').addEventListener('click', () => {
            newTypeForm.reset();
            newTypeForm.classList.remove('hidden');
            newTypeForm.elements.label.focus();
        });
        document.getElementById('cancel-new-type').addEventListener('click', () => newTypeForm.classList.add('hidden'));

        document.querySelectorAll('.type-form').forEach(form => {
            form.addEventListener('submit', async e => {
                e.preventDefault();
                const isNew = form.dataset.action === 'add';
                const fd = new FormData(form);
                fd.append('action', isNew ? 'add' : 'save');
                if (!form.elements.is_enabled.checked) fd.set('is_enabled', '0');
                const res = await fetch('controller/internship_types.php', { method: 'POST', body: fd });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success && isNew) {
                    setTimeout(() => location.reload(), 600);
                }
            });
        });
    </script>
</body>

</html>
