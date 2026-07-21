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
$page_title = 'Curriculum Management - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Internship Curriculum Roadmap</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure week-wise curriculum tasks for each technology and duration.</p>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-end">
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Select Technology</label>
                            <select id="techSelect" class="w-full px-4 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">-- Choose Technology --</option>
                                <?php
                                $tech_stmt = $conn->query("SELECT id, name FROM technologies WHERE status = 1 ORDER BY name ASC");
                                while ($t = $tech_stmt->fetch_assoc()) {
                                    echo "<option value='{$t['id']}'>" . htmlspecialchars($t['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Select Duration</label>
                            <select id="durationSelect" class="w-full px-4 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="4 weeks">4 Weeks Internship</option>
                                <option value="8 weeks" selected>8 Weeks Internship</option>
                                <option value="12 weeks">12 Weeks Internship</option>
                            </select>
                        </div>
                        <div class="border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-700 pt-4 lg:pt-0 lg:pl-6">
                            <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Import Curriculum (JSON)</label>
                            <form id="importJsonForm" class="flex items-center gap-2">
                                <input type="file" id="jsonFileInput" accept=".json" required class="text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300 focus:outline-none w-full">
                                <button type="submit" class="px-4 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm shrink-0">Import</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Roadmap Visualization Area -->
                <div id="roadmapArea" class="hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white" id="roadmapTitle">Roadmap Timeline</h3>
                    </div>

                    <!-- Roadmap Tree Timeline -->
                    <div class="relative pl-6 md:pl-10 border-l-2 border-indigo-200 dark:border-indigo-900 ml-4 md:ml-8 my-10 space-y-12" id="timelineContainer">
                        <!-- Weeks generated dynamically by JS -->
                    </div>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 shadow-sm text-center">
                    <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-950/55 flex items-center justify-center mb-4 text-indigo-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-2">No Technology Selected</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">Select a technology and duration from the dropdown filters above to view and manage its curriculum roadmap.</p>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <!-- Add/Edit Task Modal -->
    <div id="taskModal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-11/12 max-w-lg p-6 animate-fadeIn">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-950 dark:text-gray-50" id="modalTitle">Configure Task</h3>
                <button type="button" class="close-modal text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="taskForm">
                <input type="hidden" name="tech_id" id="modalTechId">
                <input type="hidden" name="duration" id="modalDuration">
                <input type="hidden" name="week_number" id="modalWeekNumber">

                <div class="mb-2 text-sm text-indigo-600 dark:text-indigo-400 font-semibold" id="modalSubtitle">
                    Week 1 Configuration
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Task Title</label>
                    <input type="text" name="title" id="taskTitle" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none border-gray-200 dark:border-gray-600" placeholder="e.g. Setting up Environment and Basic CRUD">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Task Description</label>
                    <div id="editor-container" class="h-48 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-b-lg border border-gray-200 dark:border-gray-600"></div>
                    <textarea name="description" id="taskDescription" class="hidden"></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        let quill;
        let curriculumTasks = [];

        document.addEventListener('DOMContentLoaded', () => {
            // Initialize Quill editor
            quill = new Quill('#editor-container', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });

            // Handle changes to selects
            document.getElementById('techSelect').addEventListener('change', fetchCurriculum);
            document.getElementById('durationSelect').addEventListener('change', fetchCurriculum);

            // Handle JSON import form submit
            document.getElementById('importJsonForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const techId = document.getElementById('techSelect').value;
                const duration = document.getElementById('durationSelect').value;

                if (!techId || !duration) {
                    showToast('error', 'Please select a Technology and Duration first.');
                    return;
                }

                const fileInput = document.getElementById('jsonFileInput');
                if (fileInput.files.length === 0) {
                    showToast('error', 'Please select a JSON file to import.');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'import_json');
                formData.append('tech_id', techId);
                formData.append('duration', duration);
                formData.append('json_file', fileInput.files[0]);

                try {
                    const res = await fetch('controller/curriculum.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();
                    showToast(result.success ? 'success' : 'error', result.message);
                    if (result.success) {
                        e.target.reset();
                        fetchCurriculum();
                    }
                } catch (err) {
                    console.error(err);
                    showToast('error', 'Failed to import curriculum JSON.');
                }
            });

            // Modal close
            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('taskModal').classList.add('hidden');
                });
            });

            // Form Submit
            document.getElementById('taskForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                document.getElementById('taskDescription').value = quill.root.innerHTML;

                const formData = new FormData(e.target);
                formData.append('action', 'save');

                const res = await fetch('controller/curriculum.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                showToast(result.success ? 'success' : 'error', result.message);
                if (result.success) {
                    document.getElementById('taskModal').classList.add('hidden');
                    fetchCurriculum();
                }
            });
        });

        async function fetchCurriculum() {
            const techId = document.getElementById('techSelect').value;
            const duration = document.getElementById('durationSelect').value;

            if (!techId || !duration) {
                document.getElementById('roadmapArea').classList.add('hidden');
                document.getElementById('emptyState').classList.remove('hidden');
                return;
            }

            document.getElementById('roadmapArea').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');

            const techName = document.getElementById('techSelect').options[document.getElementById('techSelect').selectedIndex].text;
            document.getElementById('roadmapTitle').textContent = `${techName} (${duration}) - Curriculum Roadmap`;

            try {
                const res = await fetch(`controller/curriculum.php?action=get&tech_id=${techId}&duration=${encodeURIComponent(duration)}`);
                const result = await res.json();

                if (result.success) {
                    curriculumTasks = result.data;
                    renderRoadmap(duration);
                } else {
                    showToast('error', result.message);
                }
            } catch (err) {
                console.error(err);
                showToast('error', 'Failed to fetch curriculum data');
            }
        }

        function renderRoadmap(duration) {
            const container = document.getElementById('timelineContainer');
            container.innerHTML = '';

            const numWeeks = (duration === '4 weeks') ? 4 : ((duration === '8 weeks') ? 8 : 12);

            for (let w = 1; w <= numWeeks; w++) {
                const task = curriculumTasks.find(t => t.week_number === w);

                const item = document.createElement('div');
                item.className = 'relative flex flex-col md:flex-row items-start gap-6 group my-8';

                // Week Dot Indicator
                const isConfigured = !!task;
                const dotColorClass = isConfigured 
                    ? 'bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-lg shadow-indigo-200 dark:shadow-none ring-4 ring-indigo-100 dark:ring-indigo-950/50' 
                    : 'bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-400';

                // Build HTML for week node
                let nodeContent = '';
                if (isConfigured) {
                    // Modern premium populated card
                    nodeContent = `
                        <div class="flex-1 w-full p-6 bg-white dark:bg-gray-800/90 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group-hover:border-indigo-400/50 dark:group-hover:border-indigo-500/40">
                            <!-- Background gradient flare -->
                            <div class="absolute -right-20 -top-20 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all duration-300"></div>
                            
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4 gap-2 pb-3 border-b border-gray-100 dark:border-gray-700/50">
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg sm:text-xl flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                    ${escapeHtml(task.title)}
                                </h4>
                                <span class="self-start sm:self-auto bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-950/80 dark:to-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold px-3 py-1 rounded-full border border-indigo-200/50 dark:border-indigo-900/60 shadow-sm">Week ${w}</span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-6 prose dark:prose-invert max-w-none">
                                ${task.description || '<p class="italic text-gray-400">No description provided</p>'}
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t border-gray-50 dark:border-gray-700/30">
                                <button onclick="openEditModal(${w}, ${task.id}, '${escapeQuote(task.title)}', '${escapeQuote(task.description)}')" class="px-4 py-2 bg-blue-50 hover:bg-blue-100/80 dark:bg-blue-950/30 dark:hover:bg-blue-950/60 text-blue-600 dark:text-blue-400 hover:text-blue-700 text-xs font-semibold rounded-xl transition-all flex items-center gap-1.5 shadow-sm border border-blue-100/50 dark:border-blue-900/40">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit Task
                                </button>
                                <button onclick="deleteTask(${task.id})" class="px-4 py-2 bg-red-50 hover:bg-red-100/80 dark:bg-red-950/30 dark:hover:bg-red-950/60 text-red-600 dark:text-red-400 hover:text-red-700 text-xs font-semibold rounded-xl transition-all flex items-center gap-1.5 shadow-sm border border-red-100/50 dark:border-red-900/40">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    // Modern dashed add card
                    nodeContent = `
                        <div onclick="openAddModal(${w})" class="cursor-pointer flex-1 w-full p-8 bg-white/30 dark:bg-gray-800/20 rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-700/80 hover:bg-gradient-to-r hover:from-indigo-50/10 hover:to-violet-50/10 dark:hover:from-indigo-950/5 dark:hover:to-violet-950/5 hover:border-indigo-400 dark:hover:border-indigo-500/60 transition-all duration-300 flex flex-col justify-center items-center py-10 text-center shadow-sm">
                            <span class="bg-gray-100/80 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 text-xs font-bold px-3 py-1 rounded-full border border-gray-200/50 dark:border-gray-700/50 mb-3 shadow-inner">Week ${w}</span>
                            <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-3 group-hover:scale-110 group-hover:rotate-90 transition-all duration-300 shadow-sm border border-indigo-100/50 dark:border-indigo-900/40">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <span class="text-base font-bold text-gray-700 dark:text-gray-300">Configure Week ${w} Curriculum</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 mt-1 max-w-xs">Initialize the learning metrics and deliverables for this timeline node.</span>
                        </div>
                    `;
                }

                item.innerHTML = `
                    <!-- Timeline Dot -->
                    <div class="absolute -left-[41px] md:-left-[49px] top-6 w-10 h-10 rounded-full flex items-center justify-center ${dotColorClass} z-10 font-bold text-sm ring-4 ring-gray-50 dark:ring-gray-900/50 transition-all duration-300 group-hover:scale-110">
                        ${w}
                    </div>
                    ${nodeContent}
                `;
                container.appendChild(item);
            }
        }

        function openAddModal(week) {
            const techId = document.getElementById('techSelect').value;
            const duration = document.getElementById('durationSelect').value;

            document.getElementById('modalTechId').value = techId;
            document.getElementById('modalDuration').value = duration;
            document.getElementById('modalWeekNumber').value = week;

            document.getElementById('modalTitle').textContent = `Add Task`;
            document.getElementById('modalSubtitle').textContent = `Week ${week} Configuration`;
            document.getElementById('taskTitle').value = '';
            quill.root.innerHTML = '';

            document.getElementById('taskModal').classList.remove('hidden');
        }

        function openEditModal(week, taskId, title, description) {
            const techId = document.getElementById('techSelect').value;
            const duration = document.getElementById('durationSelect').value;

            document.getElementById('modalTechId').value = techId;
            document.getElementById('modalDuration').value = duration;
            document.getElementById('modalWeekNumber').value = week;

            document.getElementById('modalTitle').textContent = `Edit Task`;
            document.getElementById('modalSubtitle').textContent = `Week ${week} Configuration`;
            document.getElementById('taskTitle').value = title;
            quill.root.innerHTML = description;

            document.getElementById('taskModal').classList.remove('hidden');
        }

        async function deleteTask(id) {
            if (!confirm('Are you sure you want to delete this task from the curriculum?')) return;

            const res = await fetch('controller/curriculum.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'delete',
                    id: id
                })
            });
            const result = await res.json();

            showToast(result.success ? 'success' : 'error', result.message);
            if (result.success) {
                fetchCurriculum();
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function escapeQuote(text) {
            if (!text) return '';
            return text.replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, ' ');
        }
    </script>
</body>
</html>
