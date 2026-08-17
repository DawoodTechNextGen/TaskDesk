<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only admin/manager access
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4)) {
    header('Location: index.php');
    exit;
}

include_once './include/connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Email Settings - TaskDesk';
include_once "./include/headerLinks.php";
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-10 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Registration Email Settings</h2>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 max-w-3xl">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">"Yes, I am Interested" Email</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            This is the message sent to new candidates. It includes a WhatsApp button, a
                            <strong>"Yes, I am Interested"</strong> button (goes to the link below) and a
                            <strong>"Not Interested"</strong> button (automatically marks the candidate as Rejected).
                            Task Base and Learning Base interns get separate text and links.
                        </p>
                    </div>

                    <div class="px-6 pt-4 flex space-x-2 border-b border-gray-200 dark:border-gray-700">
                        <button type="button" id="tabTaskBase" data-type="0"
                            class="type-tab px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400">
                            Task Base Internship
                        </button>
                        <button type="button" id="tabLearningBase" data-type="1"
                            class="type-tab px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-transparent text-gray-500 dark:text-gray-400">
                            Learning Base Internship
                        </button>
                    </div>

                    <form id="emailSettingsForm" class="p-6 space-y-5">
                        <input type="hidden" id="internshipType" value="0">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Subject</label>
                            <input type="text" id="emailSubject" name="email_subject"
                                class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                                placeholder="Application Update - DawoodTech NextGen">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Message</label>
                            <div id="emailBodyEditor" class="bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-b-lg border border-gray-200 dark:border-gray-600" style="min-height: 220px;"></div>
                            <textarea id="emailBody" name="email_body" class="hidden"></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use Bold, lists and the link button to format the email. Select text and click the link icon to make it clickable.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                "Yes, I am Interested" Link (Payment / Registration Form URL)
                            </label>
                            <input type="url" id="interestedLink" name="interested_link"
                                class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                                placeholder="https://forms.google.com/... or https://yourpaymentpage.com/...">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Candidates who click "Yes, I am Interested" are sent directly to this link. Leave empty to just show a thank-you message instead.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" id="saveBtn"
                                class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
    <script>
        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white shadow-lg ${
                type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-yellow-500'
            }`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        const quill = new Quill('#emailBodyEditor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        async function loadSettings(type) {
            try {
                const res = await fetch('controller/email_settings.php?action=get&type=' + type);
                const json = await res.json();
                if (json.success) {
                    document.getElementById('emailSubject').value = json.email_subject || '';
                    quill.root.innerHTML = json.email_body || '';
                    document.getElementById('interestedLink').value = json.interested_link || '';
                } else {
                    showToast('error', json.message || 'Failed to load settings');
                }
            } catch (e) {
                showToast('error', 'Failed to load settings: ' + e.message);
            }
        }

        function switchTab(type) {
            document.getElementById('internshipType').value = type;

            document.querySelectorAll('.type-tab').forEach(btn => {
                const active = btn.dataset.type === String(type);
                btn.classList.toggle('border-indigo-600', active);
                btn.classList.toggle('text-indigo-600', active);
                btn.classList.toggle('dark:text-indigo-400', active);
                btn.classList.toggle('border-transparent', !active);
                btn.classList.toggle('text-gray-500', !active);
                btn.classList.toggle('dark:text-gray-400', !active);
            });

            loadSettings(type);
        }

        document.getElementById('tabTaskBase').addEventListener('click', () => switchTab('0'));
        document.getElementById('tabLearningBase').addEventListener('click', () => switchTab('1'));

        document.getElementById('emailSettingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            document.getElementById('emailBody').value = quill.root.innerHTML;

            try {
                const res = await fetch('controller/email_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'save',
                        type: document.getElementById('internshipType').value,
                        email_subject: document.getElementById('emailSubject').value,
                        email_body: document.getElementById('emailBody').value,
                        interested_link: document.getElementById('interestedLink').value
                    })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
            } catch (e) {
                showToast('error', 'Save failed: ' + e.message);
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Settings';
            }
        });

        loadSettings('0');
    </script>
</body>
</html>
