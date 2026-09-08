<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
// Full control of the question bank (build/edit/delete assessments & questions)
// is Admin-only, per spec - unlike the rest of the registrations pipeline this
// isn't shared with Manager/Collaborator.
requirePageAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Assessments - TaskDesk';
include_once "./include/headerLinks.php"; ?>
<!-- Syntax highlighting for code examples inside questions (Locally Hosted) -->
<link href="<?= BASE_URL ?>assets/css/libs/highlight-atom-one-dark.min.css" rel="stylesheet">
<script src="<?= BASE_URL ?>assets/js/libs/highlight.min.js"></script>
<style>
    /* Plain, self-contained rendering of question HTML in the list preview below -
       deliberately NOT using Quill's .ql-editor/.ql-container classes (those assume
       an interactive editor with height:100%/overflow-y:auto, which blows up a static
       preview's layout). highlight.js's theme CSS (loaded above) owns the code
       block's background/color once it adds its "hljs" class and per-token spans. */
    .rendered-question :is(p, ol, ul, blockquote, h1, h2, h3, h4) { margin: 0 0 8px 0; }
    .rendered-question > :last-child { margin-bottom: 0; }
    .rendered-question ol, .rendered-question ul { padding-left: 1.4em; }
    .rendered-question pre.ql-syntax {
        padding: 12px 14px;
        border-radius: 8px;
        overflow-x: auto;
        font-family: 'Courier New', Consolas, Monaco, monospace;
        font-size: 13px;
        line-height: 1.5;
        white-space: pre;
        margin: 8px 0;
    }
    .rendered-question code {
        background: #eef2ff;
        color: #4338ca;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: 'Courier New', Consolas, Monaco, monospace;
        font-size: 0.9em;
    }
    html.dark .rendered-question code {
        background: #312e81;
        color: #c7d2fe;
    }

    /* The live Quill editor (while the admin is authoring a question) has the same
       inline-code contrast problem in dark mode - Quill's own stylesheet hardcodes a
       light-gray background for inline code with no dark-mode variant, which turns
       invisible against light text. Override it to match the theme. */
    #question-editor-container .ql-editor code {
        background: #eef2ff;
        color: #4338ca;
    }
    html.dark #question-editor-container .ql-editor code {
        background: #312e81;
        color: #c7d2fe;
    }
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Assessment Question Bank</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Build field-specific assessments (MCQs, code snippets, difficulty, pass %) that can be sent to candidates from the Contact list.</p>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row gap-6 items-end">
                        <div class="w-full sm:w-80">
                            <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Select Technology / Field</label>
                            <select id="techSelect" class="w-full px-4 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">-- Choose Technology --</option>
                                <?php
                                $tech_result = $conn->query("SELECT id, name FROM technologies WHERE status = 1 ORDER BY name ASC");
                                while ($t = $tech_result->fetch_assoc()) {
                                    echo "<option value='{$t['id']}'>" . htmlspecialchars($t['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button id="newAssessmentBtn" class="hidden px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-md">
                            + New Assessment
                        </button>
                    </div>
                </div>

                <!-- Assessments Grid -->
                <div id="assessmentsGrid" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>

                <!-- Empty State -->
                <div id="emptyState" class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 shadow-sm text-center">
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-2">No Technology Selected</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">Select a technology/field above to view and manage its assessments.</p>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <!-- Assessment (meta) Modal -->
    <div id="assessmentModal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-11/12 max-w-lg p-6 animate-fadeIn">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-950 dark:text-gray-50" id="assessmentModalTitle">New Assessment</h3>
                <button type="button" class="close-assessment-modal text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="assessmentForm">
                <input type="hidden" name="id" id="assessmentId">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Title</label>
                    <input type="text" name="title" id="assessmentTitle" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600" placeholder="e.g. React Fundamentals Screening">
                </div>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Difficulty</label>
                        <select name="difficulty" id="assessmentDifficulty" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Duration (min)</label>
                        <input type="number" name="duration_minutes" id="assessmentDuration" min="1" value="30" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Passing %</label>
                        <input type="number" name="passing_percentage" id="assessmentPassing" min="1" max="100" value="60" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" class="close-assessment-modal px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Assessment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Questions Modal -->
    <div id="questionsModal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6 animate-fadeIn">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-950 dark:text-gray-50">Questions - <span id="questionsAssessmentTitle"></span></h3>
                <button type="button" class="close-questions-modal text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- List view -->
            <div id="questionsListView">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <button id="addQuestionBtn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">+ Add Question</button>
                    <form id="importJsonForm" class="flex items-center gap-2">
                        <input type="file" id="jsonFileInput" accept=".json" required class="text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300 focus:outline-none">
                        <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-semibold shrink-0">Import JSON</button>
                    </form>
                    <details class="text-xs text-gray-500 dark:text-gray-400">
                        <summary class="cursor-pointer text-indigo-600 dark:text-indigo-400 font-semibold">JSON format?</summary>
                        <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-900 rounded-lg overflow-x-auto text-[11px] leading-relaxed">[
  {
    "question": "&lt;p&gt;What does &lt;code&gt;useState&lt;/code&gt; return?&lt;/p&gt;",
    "points": 1,
    "options": [
      { "text": "An array with value and setter", "is_correct": true },
      { "text": "A single object", "is_correct": false },
      { "text": "Nothing", "is_correct": false }
    ]
  }
]</pre>
                        <p class="mt-1">"question" can include HTML (e.g. a &lt;pre&gt; code block). Exactly one option per question needs "is_correct": true. Imported questions are added to the existing list, nothing is overwritten.</p>
                    </details>
                </div>
                <div id="questionsList" class="space-y-3"></div>
            </div>

            <!-- Editor view -->
            <div id="questionEditorView" class="hidden">
                <input type="hidden" id="questionId">
                <input type="hidden" id="questionAssessmentId">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Question (use the code-block toolbar button for code examples)</label>
                    <div id="question-editor-container" class="h-40 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-b-lg border border-gray-200 dark:border-gray-600"></div>
                </div>
                <div class="mb-4 w-32">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Points</label>
                    <input type="number" id="questionPoints" min="1" value="1" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600">
                </div>
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100">Options (mark the correct one)</label>
                        <button type="button" id="addOptionBtn" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">+ Add option</button>
                    </div>
                    <div id="optionsList" class="space-y-2"></div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="cancelQuestionBtn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Back to list</button>
                    <button type="button" id="saveQuestionBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Question</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        // Sidebar-style line icons (same stroke-based SVG convention used in
        // include/sideBar.php) for the assessment card meta row.
        const ICONS = {
            clock: `<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5"/></svg>`,
            check: `<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5"/></svg>`,
            question: `<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.09 9C9.3251 8.33167 9.78915 7.76811 10.4 7.40913C11.0108 7.05016 11.7289 6.91894 12.4272 7.03871C13.1255 7.15848 13.7588 7.52149 14.2151 8.06349C14.6713 8.60548 14.9211 9.29152 14.92 10C14.92 12 11.92 13 11.92 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17H12.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5"/></svg>`,
            users: `<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M15 8C16.6569 8 18 6.88071 18 5.5C18 4.11929 16.6569 3 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M2.5 18C2.5 15.2386 5.35786 13 9 13C12.6421 13 15.5 15.2386 15.5 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M17 13.5C19.4853 13.9251 21.5 15.6127 21.5 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>`
        };

        let assessments = [];
        let currentAssessment = null;
        let questionEditor;
        let optionRowCount = 0;

        document.addEventListener('DOMContentLoaded', () => {
            questionEditor = new Quill('#question-editor-container', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['code-block'],
                        ['clean']
                    ]
                }
            });

            document.getElementById('techSelect').addEventListener('change', fetchAssessments);
            document.getElementById('newAssessmentBtn').addEventListener('click', () => openAssessmentModal());
            document.querySelectorAll('.close-assessment-modal').forEach(b => b.addEventListener('click', () => document.getElementById('assessmentModal').classList.add('hidden')));
            document.querySelectorAll('.close-questions-modal').forEach(b => b.addEventListener('click', () => document.getElementById('questionsModal').classList.add('hidden')));

            document.getElementById('assessmentForm').addEventListener('submit', saveAssessment);
            document.getElementById('addQuestionBtn').addEventListener('click', () => openQuestionEditor());
            document.getElementById('importJsonForm').addEventListener('submit', importQuestionsJson);
            document.getElementById('cancelQuestionBtn').addEventListener('click', showQuestionsList);
            document.getElementById('saveQuestionBtn').addEventListener('click', saveQuestion);
            document.getElementById('addOptionBtn').addEventListener('click', () => addOptionRow('', false));
        });

        async function fetchAssessments() {
            const techId = document.getElementById('techSelect').value;
            if (!techId) {
                document.getElementById('assessmentsGrid').classList.add('hidden');
                document.getElementById('newAssessmentBtn').classList.add('hidden');
                document.getElementById('emptyState').classList.remove('hidden');
                return;
            }
            document.getElementById('newAssessmentBtn').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');

            const res = await fetch(`controller/assessments.php?action=list_assessments&technology_id=${techId}`);
            const result = await res.json();
            if (!result.success) {
                showToast('error', result.message);
                return;
            }
            assessments = result.data;
            renderAssessments();
        }

        function renderAssessments() {
            const grid = document.getElementById('assessmentsGrid');
            grid.classList.remove('hidden');

            if (assessments.length === 0) {
                grid.innerHTML = `<div class="col-span-full text-center text-gray-500 dark:text-gray-400 py-10">No assessments yet for this technology. Click "New Assessment" to create one.</div>`;
                return;
            }

            const difficultyColor = { easy: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', hard: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' };

            grid.innerHTML = assessments.map(a => `
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 flex flex-col gap-3 ${a.status == 0 ? 'opacity-50' : ''}">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-gray-900 dark:text-white">${escapeHtml(a.title)}</h4>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full ${difficultyColor[a.difficulty] || ''}">${a.difficulty}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap gap-x-4 gap-y-1">
                        <span class="inline-flex items-center gap-1">${ICONS.clock} ${a.duration_minutes} min</span>
                        <span class="inline-flex items-center gap-1">${ICONS.check} Pass at ${a.passing_percentage}%</span>
                        <span class="inline-flex items-center gap-1">${ICONS.question} ${a.question_count} questions</span>
                        <span class="inline-flex items-center gap-1">${ICONS.users} ${a.attempt_count} sent</span>
                        ${a.status == 0 ? '<span class="text-red-500 font-semibold">Deactivated</span>' : ''}
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <button class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold" onclick="openQuestionsModal(${a.id})">Manage Questions</button>
                        <button class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 rounded-lg text-xs font-semibold" onclick='viewAsIntern(${JSON.stringify(a)})' ${a.question_count == 0 ? 'disabled title="Add questions first"' : ''}>View as Intern</button>
                        <button class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-semibold" onclick='openAssessmentModal(${JSON.stringify(a)})'>Edit</button>
                        <button class="px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 rounded-lg text-xs font-semibold" onclick="deleteAssessment(${a.id})">Delete</button>
                    </div>
                </div>
            `).join('');
        }

        function openAssessmentModal(a) {
            document.getElementById('assessmentModalTitle').textContent = a ? 'Edit Assessment' : 'New Assessment';
            document.getElementById('assessmentId').value = a ? a.id : '';
            document.getElementById('assessmentTitle').value = a ? a.title : '';
            document.getElementById('assessmentDifficulty').value = a ? a.difficulty : 'medium';
            document.getElementById('assessmentDuration').value = a ? a.duration_minutes : 30;
            document.getElementById('assessmentPassing').value = a ? a.passing_percentage : 60;
            document.getElementById('assessmentModal').classList.remove('hidden');
        }

        async function saveAssessment(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'save_assessment');
            formData.append('technology_id', document.getElementById('techSelect').value);

            const res = await fetch('controller/assessments.php', { method: 'POST', body: formData });
            const result = await res.json();
            showToast(result.success ? 'success' : 'error', result.message);
            if (result.success) {
                document.getElementById('assessmentModal').classList.add('hidden');
                fetchAssessments();
            }
        }

        function viewAsIntern(a) {
            const techName = document.getElementById('techSelect').selectedOptions[0]?.text || '';
            const params = new URLSearchParams({
                assessment_id: a.id,
                title: a.title,
                duration: a.duration_minutes,
                passing: a.passing_percentage,
                tech: techName
            });
            window.open(`preview_assessment.php?${params.toString()}`, '_blank');
        }

        async function deleteAssessment(id) {
            if (!confirm('Delete this assessment? If candidates have already attempted it, it will be deactivated instead.')) return;
            const res = await fetch('controller/assessments.php', { method: 'POST', body: new URLSearchParams({ action: 'delete_assessment', id }) });
            const result = await res.json();
            showToast(result.success ? 'success' : 'error', result.message);
            if (result.success) fetchAssessments();
        }

        async function openQuestionsModal(assessmentId) {
            currentAssessment = assessments.find(a => a.id == assessmentId);
            document.getElementById('questionsAssessmentTitle').textContent = currentAssessment.title;
            document.getElementById('questionsModal').classList.remove('hidden');
            showQuestionsList();
            await loadQuestions();
        }

        let currentQuestions = [];
        async function loadQuestions() {
            const res = await fetch(`controller/assessments.php?action=get_questions&assessment_id=${currentAssessment.id}`);
            const result = await res.json();
            if (!result.success) {
                showToast('error', result.message);
                return;
            }
            currentQuestions = result.data;
            renderQuestionsList();
        }

        async function importQuestionsJson(e) {
            e.preventDefault();
            const fileInput = document.getElementById('jsonFileInput');
            if (!fileInput.files.length) {
                showToast('error', 'Please choose a JSON file first');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'import_questions');
            formData.append('assessment_id', currentAssessment.id);
            formData.append('json_file', fileInput.files[0]);

            const res = await fetch('controller/assessments.php', { method: 'POST', body: formData });
            const result = await res.json();
            showToast(result.success ? 'success' : 'error', result.message);
            if (result.success) {
                e.target.reset();
                await loadQuestions();
                fetchAssessments();
            }
        }

        function renderQuestionsList() {
            const list = document.getElementById('questionsList');
            if (currentQuestions.length === 0) {
                list.innerHTML = `<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">No questions yet. Click "Add Question" to create the first one.</p>`;
                return;
            }
            list.innerHTML = currentQuestions.map((q, idx) => `
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex justify-between items-start gap-3">
                        <div class="text-sm text-gray-800 dark:text-gray-200 flex-1">
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Q${idx + 1} (${q.points} pt${q.points > 1 ? 's' : ''})</span>
                            <div class="rendered-question">${q.question_html}</div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button class="px-2 py-1 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 rounded text-xs font-semibold" onclick='openQuestionEditor(${JSON.stringify(q)})'>Edit</button>
                            <button class="px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 rounded text-xs font-semibold" onclick="deleteQuestion(${q.id})">Delete</button>
                        </div>
                    </div>
                    <ul class="mt-2 text-xs text-gray-500 dark:text-gray-400 list-disc list-inside">
                        ${q.options.map(o => `<li class="${o.is_correct == 1 ? 'text-green-600 dark:text-green-400 font-semibold' : ''}">${escapeHtml(o.option_text)}${o.is_correct == 1 ? ' ✓' : ''}</li>`).join('')}
                    </ul>
                </div>
            `).join('');
            list.querySelectorAll('pre.ql-syntax').forEach(block => {
                if (window.hljs) hljs.highlightElement(block);
            });
        }

        function showQuestionsList() {
            document.getElementById('questionsListView').classList.remove('hidden');
            document.getElementById('questionEditorView').classList.add('hidden');
        }

        function openQuestionEditor(q) {
            document.getElementById('questionsListView').classList.add('hidden');
            document.getElementById('questionEditorView').classList.remove('hidden');

            document.getElementById('questionId').value = q ? q.id : '';
            document.getElementById('questionAssessmentId').value = currentAssessment.id;
            document.getElementById('questionPoints').value = q ? q.points : 1;
            questionEditor.root.innerHTML = q ? q.question_html : '';

            document.getElementById('optionsList').innerHTML = '';
            optionRowCount = 0;
            if (q && q.options.length) {
                q.options.forEach(o => addOptionRow(o.option_text, o.is_correct == 1));
            } else {
                addOptionRow('', false);
                addOptionRow('', false);
            }
        }

        function addOptionRow(text, isCorrect) {
            optionRowCount++;
            // The whole row is a <label> (not just the radio) so clicking anywhere on
            // it - not just the tiny circle - marks it correct, and it visibly
            // highlights so it's obvious which option is currently marked correct.
            const row = document.createElement('label');
            row.className = 'flex items-center gap-2 p-2 border rounded-lg cursor-pointer transition-colors ' +
                (isCorrect
                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                    : 'border-gray-200 dark:border-gray-600 hover:border-indigo-300 dark:hover:border-indigo-700');
            row.innerHTML = `
                <input type="radio" name="correctOption" ${isCorrect ? 'checked' : ''} class="option-correct w-4 h-4 shrink-0" style="accent-color:#4f46e5;">
                <input type="text" value="${escapeAttr(text)}" placeholder="Option text" class="option-text flex-1 px-3 py-1.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-600 text-sm" onclick="event.stopPropagation()">
                <button type="button" onclick="event.stopPropagation(); this.closest('label').remove()" class="text-red-500 hover:text-red-700 text-sm shrink-0">✕</button>
            `;
            row.querySelector('.option-correct').addEventListener('change', () => {
                document.querySelectorAll('#optionsList > label').forEach(r => {
                    r.classList.remove('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/30');
                    r.classList.add('border-gray-200', 'dark:border-gray-600', 'hover:border-indigo-300', 'dark:hover:border-indigo-700');
                });
                row.classList.remove('border-gray-200', 'dark:border-gray-600', 'hover:border-indigo-300', 'dark:hover:border-indigo-700');
                row.classList.add('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/30');
            });
            document.getElementById('optionsList').appendChild(row);
        }

        async function saveQuestion() {
            const options = [...document.querySelectorAll('#optionsList > label')].map(row => ({
                text: row.querySelector('.option-text').value.trim(),
                is_correct: row.querySelector('.option-correct').checked ? 1 : 0
            })).filter(o => o.text !== '');

            const questionHtml = questionEditor.root.innerHTML;
            if (questionEditor.getText().trim() === '') {
                showToast('error', 'Question text is required');
                return;
            }
            if (options.length < 2) {
                showToast('error', 'At least 2 options are required');
                return;
            }
            if (!options.some(o => o.is_correct)) {
                showToast('error', 'Select the correct option');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_question');
            formData.append('id', document.getElementById('questionId').value);
            formData.append('assessment_id', document.getElementById('questionAssessmentId').value);
            formData.append('question_html', questionHtml);
            formData.append('points', document.getElementById('questionPoints').value);
            formData.append('options_json', JSON.stringify(options));

            const res = await fetch('controller/assessments.php', { method: 'POST', body: formData });
            const result = await res.json();
            showToast(result.success ? 'success' : 'error', result.message);
            if (result.success) {
                showQuestionsList();
                await loadQuestions();
                fetchAssessments();
            }
        }

        async function deleteQuestion(id) {
            if (!confirm('Delete this question?')) return;
            const res = await fetch('controller/assessments.php', { method: 'POST', body: new URLSearchParams({ action: 'delete_question', id }) });
            const result = await res.json();
            showToast(result.success ? 'success' : 'error', result.message);
            if (result.success) {
                await loadQuestions();
                fetchAssessments();
            }
        }

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white shadow-lg ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
        function escapeAttr(text) {
            return escapeHtml(text);
        }
    </script>
</body>
</html>
