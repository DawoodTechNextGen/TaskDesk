<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
// Lets Admin see exactly what a candidate sees, without creating a real
// candidate login/attempt. Full control of the question bank is admin-only,
// so the preview is too.
requirePageAdmin();

$assessmentId = (int)($_GET['assessment_id'] ?? 0);
if ($assessmentId <= 0) {
    header('Location: assessments.php');
    exit;
}
$title = $_GET['title'] ?? '';
$durationMinutes = (int)($_GET['duration'] ?? 30);
$passingPercentage = (int)($_GET['passing'] ?? 60);
$technology = $_GET['tech'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Preview: ' . $title . ' - TaskDesk';
include_once "./include/headerLinks.php";
?>
<!-- Syntax highlighting for code examples inside questions (Locally Hosted) -->
<link href="<?= BASE_URL ?>assets/css/libs/highlight-atom-one-dark.min.css" rel="stylesheet">
<script src="<?= BASE_URL ?>assets/js/libs/highlight.min.js"></script>
<style>
    .option-row { transition: all .15s ease; }
    .option-row.selected { border-color: #4f46e5; background-color: rgba(79,70,229,0.08); }
    .option-row.correct-answer { border-color: #16a34a; background-color: rgba(22,163,74,0.1); }
    .option-row.wrong-answer { border-color: #dc2626; background-color: rgba(220,38,38,0.1); }

    /* Same read-only question rendering as my_assessment.php / assessments.php,
       kept in sync so the preview is pixel-accurate to what a candidate sees.
       highlight.js's theme CSS (loaded above) owns the code block's
       background/color once it adds its "hljs" class and per-token spans. */
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
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors min-h-screen">
    <div id="toast-container" class="fixed top-4 right-4 z-[9999] space-y-4"></div>

    <!-- Admin-only preview chrome - a candidate never sees this bar or banner. -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center sticky top-0 z-40">
        <div class="font-bold text-gray-800 dark:text-white">DawoodTech NextGen &mdash; Assessment</div>
        <a href="assessments.php" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Assessments</a>
    </div>
    <div class="bg-amber-500 text-white text-center text-xs font-semibold py-1.5 px-4 flex items-center justify-center gap-1.5">
        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.03974 12.336C1.96652 12.1176 1.96652 11.8824 2.03974 11.664C2.9424 8.98525 6.61452 3 12.0004 3C17.3862 3 21.0584 8.98525 21.961 11.664C22.0342 11.8824 22.0342 12.1176 21.961 12.336C21.0584 15.0147 17.3862 21 12.0004 21C6.61452 21 2.9424 15.0147 2.03974 12.336Z" stroke="currentColor" stroke-width="1.5"/><path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="1.5"/></svg>
        <span>ADMIN PREVIEW &mdash; this is exactly what the candidate will see below. Nothing here is saved, and no real attempt is created.</span>
    </div>

    <main class="max-w-3xl mx-auto px-4 py-10">
        <div id="loadingState" class="text-center text-gray-500 dark:text-gray-400 py-20">Loading questions...</div>

        <!-- Pending: instructions + Start (matches my_assessment.php's pending screen) -->
        <div id="pendingState" class="hidden bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-1"><?= htmlspecialchars($title) ?></h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6"><?= htmlspecialchars($technology) ?></p>
            <div class="grid grid-cols-3 gap-4 mb-6 text-center">
                <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" id="pendingQCount">-</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Questions</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?= $durationMinutes ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Minutes</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?= $passingPercentage ?>%</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Pass %</div>
                </div>
            </div>
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-xl p-5 mb-6 text-sm text-amber-800 dark:text-amber-300 space-y-1.5">
                <p class="font-bold">Before you start, please read:</p>
                <p>• You get <strong>ONE attempt only</strong>. The timer cannot be paused once started.</p>
                <p>• The assessment runs in <strong>fullscreen</strong>. Switching tabs/apps or exiting fullscreen is treated as a rule violation.</p>
                <p>• You will get <strong>one warning</strong> if you leave the assessment screen. A second time will <strong>fail your attempt immediately</strong>.</p>
                <p>• Copying question/answer text is disabled.</p>
                <p>• Make sure you're in a quiet place with a stable internet connection before starting.</p>
            </div>
            <button id="startBtn" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-lg shadow-md">Start Assessment</button>
            <p class="text-center text-xs text-gray-400 mt-3">(In preview mode this just reveals the questions below - no fullscreen lock, no timer penalty, nothing is saved.)</p>
        </div>

        <!-- In progress -->
        <div id="inProgressState" class="hidden">
            <div id="timerBar" class="sticky z-30 bg-indigo-600 text-white rounded-xl px-5 py-3 mb-6 flex justify-between items-center shadow-md" style="top: 105px;">
                <span class="font-semibold"><?= htmlspecialchars($title) ?></span>
                <span class="font-mono text-lg font-bold" id="timerDisplay">--:--</span>
            </div>
            <div id="questionsContainer" class="space-y-6"></div>
            <div class="flex justify-between items-center mt-6 gap-3">
                <button id="prevBtn" class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-xl font-bold shadow-md disabled:opacity-40 disabled:cursor-not-allowed">&larr; Previous</button>
                <span id="questionProgress" class="text-sm font-semibold text-gray-500 dark:text-gray-400"></span>
                <button id="nextOrSubmitBtn" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md">Next &rarr;</button>
            </div>
        </div>

        <!-- Result - preview-only bonus: shows which answers were right/wrong, since this
             is for the admin's own content verification, not a real candidate result. -->
        <div id="resultState" class="hidden bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 border border-gray-100 dark:border-gray-700">
            <div class="text-center mb-6">
                <div class="text-5xl mb-3">✅</div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Preview Complete</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">A real candidate only sees Pass/Fail here, not this answer breakdown - it's shown to you for content review.</p>
            </div>
            <div id="reviewContainer" class="space-y-4"></div>
        </div>
    </main>

    <?php include_once "./include/footerLinks.php"; ?>
    <script>
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

        const screens = ['loadingState', 'pendingState', 'inProgressState', 'resultState'];
        function showScreen(id) {
            screens.forEach(s => document.getElementById(s).classList.toggle('hidden', s !== id));
        }

        const ASSESSMENT_ID = <?= (int)$assessmentId ?>;
        const DURATION_MINUTES = <?= (int)$durationMinutes ?>;
        let questions = [];
        let currentQuestionIndex = 0;
        let remainingSeconds = 0;
        let timerInterval = null;
        let selectedAnswers = {};

        async function loadQuestions() {
            const res = await fetch(`controller/assessments.php?action=get_questions&assessment_id=${ASSESSMENT_ID}`);
            const result = await res.json();
            if (!result.success || !result.data.length) {
                showToast('error', result.message || 'This assessment has no questions yet.');
                return;
            }
            questions = result.data;
            document.getElementById('pendingQCount').textContent = questions.length;
            showScreen('pendingState');
        }

        function renderQuestions() {
            currentQuestionIndex = 0;
            renderCurrentQuestion();
        }

        function renderCurrentQuestion() {
            const q = questions[currentQuestionIndex];
            const container = document.getElementById('questionsContainer');
            container.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                    <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-2">Question ${currentQuestionIndex + 1} of ${questions.length} &middot; ${q.points} pt${q.points > 1 ? 's' : ''}</div>
                    <div class="rendered-question mb-4 text-gray-800 dark:text-gray-100">${q.question_html}</div>
                    <div class="space-y-2" data-question-id="${q.id}">
                        ${q.options.map(o => `
                            <label class="option-row flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-indigo-400 ${selectedAnswers[q.id] == o.id ? 'selected' : ''}">
                                <input type="radio" name="q_${q.id}" value="${o.id}" class="option-input w-4 h-4" style="accent-color:#4f46e5;" ${selectedAnswers[q.id] == o.id ? 'checked' : ''}>
                                <span class="text-sm text-gray-700 dark:text-gray-200">${escapeHtml(o.option_text)}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;

            container.querySelectorAll('pre.ql-syntax').forEach(block => {
                if (window.hljs) hljs.highlightElement(block);
            });

            container.querySelectorAll('.option-input').forEach(input => {
                input.addEventListener('change', (e) => {
                    const wrapper = e.target.closest('[data-question-id]');
                    selectedAnswers[wrapper.dataset.questionId] = e.target.value;
                    wrapper.querySelectorAll('.option-row').forEach(r => r.classList.remove('selected'));
                    e.target.closest('.option-row').classList.add('selected');
                });
            });

            document.getElementById('questionProgress').textContent = `Question ${currentQuestionIndex + 1} of ${questions.length}`;
            document.getElementById('prevBtn').disabled = currentQuestionIndex === 0;
            const isLast = currentQuestionIndex === questions.length - 1;
            const nextBtn = document.getElementById('nextOrSubmitBtn');
            nextBtn.textContent = isLast ? 'Submit Assessment' : 'Next →';
            nextBtn.className = 'px-6 py-3 text-white rounded-xl font-bold shadow-md ' +
                (isLast ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700');
        }

        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                renderCurrentQuestion();
            }
        });

        function startTimer() {
            remainingSeconds = DURATION_MINUTES * 60;
            updateTimerDisplay();
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                remainingSeconds--;
                updateTimerDisplay();
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    showToast('error', "Time's up (preview only - no penalty here, a real attempt would auto-submit now)");
                }
            }, 1000);
        }
        function updateTimerDisplay() {
            const m = Math.max(0, Math.floor(remainingSeconds / 60));
            const s = Math.max(0, remainingSeconds % 60);
            document.getElementById('timerDisplay').textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        document.getElementById('startBtn').addEventListener('click', () => {
            renderQuestions();
            showScreen('inProgressState');
            startTimer();
        });

        document.getElementById('nextOrSubmitBtn').addEventListener('click', () => {
            const isLast = currentQuestionIndex === questions.length - 1;
            if (!isLast) {
                currentQuestionIndex++;
                renderCurrentQuestion();
                return;
            }
            clearInterval(timerInterval);
            const reviewContainer = document.getElementById('reviewContainer');
            reviewContainer.innerHTML = questions.map((q, idx) => {
                const chosenId = selectedAnswers[q.id] ? Number(selectedAnswers[q.id]) : null;
                return `
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-2">Question ${idx + 1}</div>
                    <div class="rendered-question mb-3 text-gray-800 dark:text-gray-100 text-sm">${q.question_html}</div>
                    <div class="space-y-1.5">
                        ${q.options.map(o => {
                            let cls = '';
                            if (o.is_correct == 1) cls = 'correct-answer';
                            else if (chosenId === o.id) cls = 'wrong-answer';
                            return `<div class="option-row ${cls} flex items-center justify-between p-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                                <span>${escapeHtml(o.option_text)}</span>
                                ${o.is_correct == 1 ? '<span class="text-green-600 dark:text-green-400 text-xs font-bold">✓ Correct</span>' : (chosenId === o.id ? '<span class="text-red-600 dark:text-red-400 text-xs font-bold">Your pick</span>' : '')}
                            </div>`;
                        }).join('')}
                    </div>
                </div>`;
            }).join('');
            reviewContainer.querySelectorAll('pre.ql-syntax').forEach(block => {
                if (window.hljs) hljs.highlightElement(block);
            });
            showScreen('resultState');
        });

        loadQuestions();
    </script>
</body>
</html>
