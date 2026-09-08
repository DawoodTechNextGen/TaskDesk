<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
if ((int)($_SESSION['user_role'] ?? 0) !== ROLE_CANDIDATE) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'My Assessment - TaskDesk';
include_once "./include/headerLinks.php";
?>
<!-- Syntax highlighting for code examples inside questions (Locally Hosted) -->
<link href="<?= BASE_URL ?>assets/css/libs/highlight-atom-one-dark.min.css" rel="stylesheet">
<script src="<?= BASE_URL ?>assets/js/libs/highlight.min.js"></script>
<style>
    .exam-locked-select { user-select: none; -webkit-user-select: none; }
    #timerBar.low-time { background-color: #dc2626 !important; }
    .option-row { transition: all .15s ease; }
    .option-row.selected { border-color: #4f46e5; background-color: rgba(79,70,229,0.08); }

    /* Plain, self-contained rendering of admin-authored question HTML - deliberately
       NOT using Quill's own .ql-editor/.ql-container classes here, since those assume
       an interactive editor (height:100%, overflow-y:auto) that blows up the layout
       when reused for static display. */
    .rendered-question :is(p, ol, ul, blockquote, h1, h2, h3, h4) { margin: 0 0 8px 0; }
    .rendered-question > :last-child { margin-bottom: 0; }
    .rendered-question ol, .rendered-question ul { padding-left: 1.4em; }
    /* highlight.js's theme CSS (loaded above) owns background/color once it
       adds its "hljs" class and per-token spans - this just handles layout. */
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

    <!-- Minimal top bar - intentionally has no sidebar/dashboard links -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center sticky top-0 z-40">
        <div class="font-bold text-gray-800 dark:text-white">DawoodTech NextGen &mdash; Assessment</div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
            <a href="logout.php" class="text-sm text-red-600 dark:text-red-400 hover:underline">Logout</a>
        </div>
    </div>

    <main class="max-w-3xl mx-auto px-4 py-10">
        <div id="loadingState" class="text-center text-gray-500 dark:text-gray-400 py-20">Loading your assessment...</div>

        <div id="noneState" class="hidden text-center bg-white dark:bg-gray-800 rounded-xl shadow-md p-10 border border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">No Assessment Assigned</h2>
            <p class="text-gray-500 dark:text-gray-400">There is currently no assessment on your account. Please contact the DawoodTech NextGen team.</p>
        </div>

        <!-- Pending: instructions + Start -->
        <div id="pendingState" class="hidden bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-1" id="pendingTitle"></h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6" id="pendingTech"></p>
            <div class="grid grid-cols-3 gap-4 mb-6 text-center">
                <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" id="pendingQCount"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Questions</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" id="pendingDuration"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Minutes</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" id="pendingPassing"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Pass %</div>
                </div>
            </div>
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-xl p-5 mb-6 text-sm text-amber-800 dark:text-amber-300 space-y-1.5">
                <p class="font-bold">Before you start, please read:</p>
                <p>• You get <strong>ONE attempt only</strong>. The timer cannot be paused once started.</p>
                <p>• The assessment runs in <strong>fullscreen</strong>. Switching tabs/apps or exiting fullscreen is treated as a rule violation.</p>
                <p>• You will get <strong>one warning</strong> if you leave the assessment screen. A second time will <strong>fail your attempt immediately</strong>.</p>
                <p>• Copying question/answer text is disabled.</p>
                <p>• <strong>Camera access is required.</strong> Periodic snapshots are captured during the assessment for verification purposes.</p>
                <p>• Make sure you're in a quiet place with a stable internet connection before starting.</p>
            </div>
            <button id="startBtn" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-lg shadow-md">Start Assessment</button>
        </div>

        <!-- In progress -->
        <div id="inProgressState" class="hidden">
            <!-- Resume/Enter gate - required so Fullscreen API has a fresh user gesture -->
            <div id="resumeGate" class="hidden text-center bg-white dark:bg-gray-800 rounded-2xl shadow-md p-10 border border-gray-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Resume Assessment</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Click below to re-enter fullscreen assessment mode and continue. Your timer keeps running.</p>
                <button id="resumeBtn" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md">Resume Assessment</button>
            </div>

            <div id="examContent" class="hidden exam-locked-select">
                <div id="timerBar" class="sticky z-30 bg-indigo-600 text-white rounded-xl px-5 py-3 mb-6 flex justify-between items-center shadow-md" style="top: 65px;">
                    <span class="font-semibold flex items-center gap-2">
                        <span id="examTitleLabel"></span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-normal bg-red-600/80 px-2 py-0.5 rounded-full" title="Periodic webcam snapshots are being captured">
                            <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span> REC
                        </span>
                    </span>
                    <span class="font-mono text-lg font-bold" id="timerDisplay">--:--</span>
                </div>
                <div id="questionsContainer" class="space-y-6"></div>
                <div class="flex justify-between items-center mt-6 gap-3">
                    <span id="questionProgress" class="text-sm font-semibold text-gray-500 dark:text-gray-400"></span>
                    <button id="nextOrSubmitBtn" disabled class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-indigo-600">Next &rarr;</button>
                </div>
                <p class="text-center text-xs text-gray-400 mt-2">Select an answer to continue. Once you move to the next question, you can't come back to change this one.</p>
            </div>

            <!-- Warning overlay (1st violation) -->
            <div id="warningOverlay" class="hidden fixed inset-0 bg-black/80 z-[9998] flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md text-center border-4 border-amber-500">
                    <div class="text-4xl mb-3">⚠️</div>
                    <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mb-2">Warning: You Left the Assessment</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">Switching tabs, apps, or exiting fullscreen is not allowed. <strong>One more time and your attempt will be marked failed.</strong></p>
                    <button id="warningResumeBtn" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold">Resume Assessment</button>
                </div>
            </div>
        </div>

        <!-- Result -->
        <div id="resultState" class="hidden text-center bg-white dark:bg-gray-800 rounded-2xl shadow-md p-10 border border-gray-100 dark:border-gray-700">
            <div class="text-5xl mb-4" id="resultIcon"></div>
            <h2 class="text-2xl font-bold mb-2" id="resultTitle"></h2>
            <p class="text-gray-500 dark:text-gray-400" id="resultMessage"></p>
        </div>

        <!-- Locked-out screen after 2nd violation -->
        <div id="violationFailState" class="hidden text-center bg-white dark:bg-gray-800 rounded-2xl shadow-md p-10 border-4 border-red-500">
            <div class="text-5xl mb-4">🚫</div>
            <h2 class="text-2xl font-bold text-red-600 dark:text-red-400 mb-2">Attempt Ended</h2>
            <p class="text-gray-500 dark:text-gray-400">Your attempt was ended for leaving the assessment screen after a warning. This has been recorded as a failed attempt.</p>
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

        const screens = ['loadingState', 'noneState', 'pendingState', 'inProgressState', 'resultState', 'violationFailState'];
        function showScreen(id) {
            screens.forEach(s => document.getElementById(s).classList.toggle('hidden', s !== id));
        }

        let timerInterval = null;
        let examActive = false; // proctoring + timer only live while true
        let violationReported = false;
        let examLocked = false;
        let currentQuestions = [];
        let currentQuestionIndex = 0;
        let currentAnswers = {};

        async function api(action, body) {
            const formData = body instanceof FormData ? body : new URLSearchParams(body || {});
            formData.append('action', action);
            const res = await fetch('controller/candidate_assessment.php', { method: 'POST', body: formData });
            return res.json();
        }

        function requestFullscreenSafe() {
            const el = document.documentElement;
            const req = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
            if (req) { try { req.call(el).catch(() => {}); } catch (e) {} }
        }
        function exitFullscreenSafe() {
            const exit = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
            if ((document.fullscreenElement || document.webkitFullscreenElement) && exit) {
                try { exit.call(document).catch(() => {}); } catch (e) {}
            }
        }

        // ---------------- Proctoring ----------------
        function onLeaveAssessment() {
            if (!examActive || examLocked || violationReported) return;
            violationReported = true;
            reportViolation();
        }
        async function reportViolation() {
            const result = await api('report_violation');
            if (result.failed) {
                lockExamAsFailed();
            } else {
                document.getElementById('warningOverlay').classList.remove('hidden');
            }
        }
        function lockExamAsFailed() {
            examLocked = true;
            examActive = false;
            clearInterval(timerInterval);
            stopCameraCapture();
            exitFullscreenSafe();
            document.getElementById('warningOverlay').classList.add('hidden');
            showScreen('violationFailState');
        }

        document.addEventListener('visibilitychange', () => { if (document.hidden) onLeaveAssessment(); });
        window.addEventListener('blur', onLeaveAssessment);
        document.addEventListener('fullscreenchange', () => {
            if (examActive && !document.fullscreenElement && !document.webkitFullscreenElement) onLeaveAssessment();
        });
        document.addEventListener('webkitfullscreenchange', () => {
            if (examActive && !document.fullscreenElement && !document.webkitFullscreenElement) onLeaveAssessment();
        });

        document.addEventListener('copy', e => { if (examActive) e.preventDefault(); });
        document.addEventListener('cut', e => { if (examActive) e.preventDefault(); });
        document.addEventListener('contextmenu', e => { if (examActive) e.preventDefault(); });
        document.addEventListener('keydown', e => {
            if (!examActive) return;
            const k = e.key ? e.key.toLowerCase() : '';
            const ctrlOrCmd = e.ctrlKey || e.metaKey;
            const blockedCombo = ctrlOrCmd && ['c', 'p', 's', 'u'].includes(k);
            const blockedDevtools = e.key === 'F12' || (ctrlOrCmd && e.shiftKey && ['i', 'j', 'c'].includes(k));
            if (blockedCombo || blockedDevtools) e.preventDefault();
        });
        window.addEventListener('beforeunload', e => {
            if (examActive) { e.preventDefault(); e.returnValue = ''; }
        });

        document.getElementById('warningResumeBtn').addEventListener('click', () => {
            requestFullscreenSafe();
            violationReported = false;
            document.getElementById('warningOverlay').classList.add('hidden');
        });
        document.getElementById('resumeBtn').addEventListener('click', async () => {
            requestFullscreenSafe(); // must be first: preserves the user-gesture for Fullscreen API
            const cameraOk = await requestCameraAccess();
            if (!cameraOk) {
                exitFullscreenSafe();
                showToast('error', 'Camera access is required to continue this assessment. Please allow camera permission and try again.');
                return;
            }
            document.getElementById('resumeGate').classList.add('hidden');
            document.getElementById('examContent').classList.remove('hidden');
            armExam();
        });

        // ---------------- Rendering (one question at a time, forward-only) ----------------
        function renderQuestions(questions, answers) {
            currentQuestions = questions;
            currentAnswers = answers || {};
            // Forward-only: once a question is answered you can't return to it, so on
            // resume (e.g. page reload mid-attempt) pick up at the first question that
            // doesn't have a saved answer yet, not question 1.
            const firstUnanswered = currentQuestions.findIndex(q => currentAnswers[q.id] === undefined);
            currentQuestionIndex = firstUnanswered === -1 ? currentQuestions.length - 1 : firstUnanswered;
            renderCurrentQuestion();
        }

        function renderCurrentQuestion() {
            const q = currentQuestions[currentQuestionIndex];
            const alreadyAnswered = currentAnswers[q.id] !== undefined;
            const container = document.getElementById('questionsContainer');
            container.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                    <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-2">Question ${currentQuestionIndex + 1} of ${currentQuestions.length} &middot; ${q.points} pt${q.points > 1 ? 's' : ''}</div>
                    <div class="rendered-question mb-4 text-gray-800 dark:text-gray-100">${q.question_html}</div>
                    <div class="space-y-2" data-question-id="${q.id}">
                        ${q.options.map(o => `
                            <label class="option-row flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-indigo-400 ${currentAnswers[q.id] == o.id ? 'selected' : ''}">
                                <input type="radio" name="q_${q.id}" value="${o.id}" class="option-input w-4 h-4" style="accent-color:#4f46e5;" ${currentAnswers[q.id] == o.id ? 'checked' : ''}>
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
                input.addEventListener('change', async (e) => {
                    const wrapper = e.target.closest('[data-question-id]');
                    const questionId = wrapper.dataset.questionId;
                    currentAnswers[questionId] = e.target.value;
                    wrapper.querySelectorAll('.option-row').forEach(r => r.classList.remove('selected'));
                    e.target.closest('.option-row').classList.add('selected');
                    document.getElementById('nextOrSubmitBtn').disabled = false;
                    await api('save_answer', { question_id: questionId, selected_option_id: e.target.value });
                });
            });

            document.getElementById('questionProgress').textContent = `Question ${currentQuestionIndex + 1} of ${currentQuestions.length}`;

            const isLast = currentQuestionIndex === currentQuestions.length - 1;
            const nextBtn = document.getElementById('nextOrSubmitBtn');
            nextBtn.textContent = isLast ? 'Submit Assessment' : 'Next →';
            nextBtn.disabled = !alreadyAnswered;
            nextBtn.className = 'px-6 py-3 text-white rounded-xl font-bold shadow-md disabled:opacity-40 disabled:cursor-not-allowed ' +
                (isLast ? 'bg-green-600 hover:bg-green-700 disabled:hover:bg-green-600' : 'bg-indigo-600 hover:bg-indigo-700 disabled:hover:bg-indigo-600');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // ---------------- Webcam Proctoring ----------------
        // Camera access is mandatory: the exam does not start/resume without it.
        // Snapshots are taken on a fixed interval for as long as examActive is true.
        let cameraStream = null;
        let cameraVideoEl = null;
        let captureInterval = null;
        const CAPTURE_INTERVAL_MS = 60000; // 60s

        async function requestCameraAccess() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return false;
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 320 }, height: { ideal: 240 } },
                    audio: false
                });
                return true;
            } catch (e) {
                cameraStream = null;
                return false;
            }
        }

        function startCaptureLoop() {
            if (!cameraStream) return;
            cameraVideoEl = document.createElement('video');
            cameraVideoEl.srcObject = cameraStream;
            cameraVideoEl.muted = true;
            cameraVideoEl.playsInline = true;
            cameraVideoEl.play().catch(() => {});

            const canvas = document.createElement('canvas');
            const captureFrame = () => {
                if (!examActive || !cameraVideoEl || !cameraVideoEl.videoWidth) return;
                canvas.width = cameraVideoEl.videoWidth;
                canvas.height = cameraVideoEl.videoHeight;
                canvas.getContext('2d').drawImage(cameraVideoEl, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.6);
                api('capture_snapshot', { image: dataUrl }).catch(() => {});
            };

            cameraVideoEl.addEventListener('loadeddata', () => {
                captureFrame();
                clearInterval(captureInterval);
                captureInterval = setInterval(captureFrame, CAPTURE_INTERVAL_MS);
            }, { once: true });
        }

        function stopCameraCapture() {
            clearInterval(captureInterval);
            captureInterval = null;
            if (cameraVideoEl) { cameraVideoEl.pause(); cameraVideoEl.srcObject = null; cameraVideoEl = null; }
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }
        }

        // ---------------- Timer ----------------
        let remainingSeconds = 0;
        function startTimer() {
            clearInterval(timerInterval);
            updateTimerDisplay();
            timerInterval = setInterval(() => {
                remainingSeconds--;
                updateTimerDisplay();
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    finishAttempt(true);
                }
            }, 1000);
        }
        function updateTimerDisplay() {
            const m = Math.max(0, Math.floor(remainingSeconds / 60));
            const s = Math.max(0, remainingSeconds % 60);
            document.getElementById('timerDisplay').textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            document.getElementById('timerBar').classList.toggle('low-time', remainingSeconds <= 60);
        }

        // ---------------- Exam lifecycle ----------------
        function armExam() {
            examActive = true;
            violationReported = false;
            startTimer();
            startCaptureLoop();
        }

        async function finishAttempt(auto) {
            examActive = false;
            clearInterval(timerInterval);
            stopCameraCapture();
            exitFullscreenSafe();
            const result = await api('submit');
            if (result.success) {
                showResult(result.status, result.fail_reason);
            } else {
                await loadState();
            }
        }
        document.getElementById('nextOrSubmitBtn').addEventListener('click', () => {
            const isLast = currentQuestionIndex === currentQuestions.length - 1;
            if (!isLast) {
                currentQuestionIndex++;
                renderCurrentQuestion();
                return;
            }
            if (!confirm('Submit your assessment? You cannot change answers after this.')) return;
            finishAttempt(false);
        });

        function showResult(status, failReason) {
            showScreen('resultState');
            const pass = status === 'pass';
            document.getElementById('resultIcon').textContent = pass ? '🎉' : '😞';
            document.getElementById('resultTitle').textContent = pass ? 'You Passed!' : 'Not This Time';
            document.getElementById('resultTitle').className = 'text-2xl font-bold mb-2 ' + (pass ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400');
            document.getElementById('resultMessage').textContent = 'Thank you for completing the assessment. Your result has been recorded and our team will be in touch.';
        }

        // ---------------- Boot ----------------
        document.getElementById('startBtn').addEventListener('click', async () => {
            requestFullscreenSafe(); // must be first: preserves the user-gesture for Fullscreen API
            const cameraOk = await requestCameraAccess();
            if (!cameraOk) {
                exitFullscreenSafe();
                showToast('error', 'Camera access is required to start this assessment. Please allow camera permission in your browser and try again.');
                return;
            }
            const result = await api('start');
            if (!result.success) {
                showToast('error', result.message || 'Could not start assessment');
                stopCameraCapture();
                return;
            }
            enterInProgress(result, true);
        });

        function enterInProgress(data, freshStart) {
            remainingSeconds = data.remaining_seconds;
            document.getElementById('examTitleLabel').textContent = data.assessment.title;
            renderQuestions(data.questions, data.answers);
            showScreen('inProgressState');

            if (freshStart) {
                document.getElementById('resumeGate').classList.add('hidden');
                document.getElementById('examContent').classList.remove('hidden');
                armExam();
            } else {
                // Page was (re)loaded mid-attempt - browsers require a fresh click to
                // re-enter fullscreen, so gate behind one.
                document.getElementById('examContent').classList.add('hidden');
                document.getElementById('resumeGate').classList.remove('hidden');
            }
        }

        async function loadState() {
            const result = await api('get_state');
            if (!result.success) {
                showScreen('noneState');
                return;
            }
            if (result.state === 'none') {
                showScreen('noneState');
            } else if (result.state === 'pending') {
                document.getElementById('pendingTitle').textContent = result.assessment.title;
                document.getElementById('pendingTech').textContent = result.assessment.technology || '';
                document.getElementById('pendingQCount').textContent = result.assessment.question_count;
                document.getElementById('pendingDuration').textContent = result.assessment.duration_minutes;
                document.getElementById('pendingPassing').textContent = result.assessment.passing_percentage + '%';
                showScreen('pendingState');
            } else if (result.state === 'in_progress') {
                enterInProgress(result, false);
            } else {
                showResult(result.state, result.fail_reason);
            }
        }

        loadState();
    </script>
</body>
</html>
