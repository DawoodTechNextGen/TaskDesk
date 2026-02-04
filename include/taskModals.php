<!-- Reusable Task Modals -->
<!-- Edit Task Modal -->
<div id="edit-task-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[3%] p-6 rounded-lg w-11/12 max-w-4xl relative max-h-[90vh] flex flex-col">
        <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded-lg close-modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h2 class="text-2xl font-bold mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">Edit Task Details</h2>

        <div class="modal-body flex-1 overflow-y-auto custom-scrollbar px-2">
            <form id="edit-task-form" class="space-y-6">
                <input type="hidden" id="edit_task_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Task Title</label>
                        <input type="text" id="edit_title" required class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Due Date</label>
                        <input type="date" id="edit_due_date" required class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Assign To Intern</label>
                    <div class="searchable-wrapper relative w-full">
                        <select id="edit_user_id" class="searchable-select hidden">
                            <option value="">Select Intern</option>
                            <?php
                            $userQuery = "SELECT id, name FROM users WHERE user_role = 2 AND freeze_status = 'active' ORDER BY name ASC";
                            $userResult = mysqli_query($conn, $userQuery);
                            while ($user = mysqli_fetch_assoc($userResult)) {
                                echo "<option value=\"{$user['id']}\">{$user['name']}</option>";
                            }
                            ?>
                        </select>
                        <div class="relative">
                            <input type="text" class="searchable-input w-full p-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-white cursor-pointer transition-all outline-none focus:ring-2 focus:ring-blue-500" placeholder="Change intern" autocomplete="off">
                        </div>
                        <ul class="searchable-dropdown hidden absolute z-50 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl mt-1 shadow-xl max-h-48 overflow-y-auto"></ul>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Description</label>
                    <div id="edit-description-editor"></div>
                </div>
            </form>
        </div>

        <div class="modal-footer flex justify-end gap-3 pt-6 border-t mt-6">
            <button type="button" class="close-modal px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-semibold">Cancel</button>
            <button type="submit" form="edit-task-form" id="update-task-btn" class="px-8 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all shadow-md hover:shadow-blue-500/20 font-bold flex items-center space-x-2 disabled:opacity-70 disabled:cursor-not-allowed">
                <span id="update-btn-text">Update Task</span>
                <div id="update-btn-loader" class="hidden">
                    <svg class="w-5 h-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- View Task Modal -->
<div id="view-task-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[3%] p-6 rounded-2xl w-11/12 max-w-4xl relative max-h-[90vh] flex flex-col shadow-2xl">
        <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded-lg close-modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h2 class="text-2xl font-bold mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">Task Details</h2>

        <!-- Live Timer Banner (Hidden by default) -->
        <div id="live-timer-banner" class="hidden mb-6 p-4 bg-blue-600/10 dark:bg-blue-600/20 border border-blue-200 dark:border-blue-800 rounded-2xl flex items-center justify-between">
            <div class="flex items-center">
                <div class="relative flex h-3 w-3 mr-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                </div>
                <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Live Session Active</p>
            </div>
            <div id="live-counter" class="text-xl font-mono font-bold text-blue-700 dark:text-blue-400">00:00:00</div>
        </div>

        <div class="modal-body flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-6">
            <div id="task-view-content" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data populated by JS -->
            </div>
            
            <div class="space-y-3">
                <h4 class="font-bold text-gray-700 dark:text-gray-300 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    Task Description
                </h4>
                <div id="task-view-description" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600 text-sm leading-relaxed min-h-[100px]"></div>
            </div>

            <div id="time-logs-section" class="hidden space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-gray-800 dark:text-white flex items-center text-lg">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Time Tracking Logs
                    </h4>
                    <span id="view-total-time" class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm font-bold"></span>
                </div>
                <div class="overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <table class="min-w-full bg-white dark:bg-gray-800 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold text-gray-600 dark:text-gray-300">Started</th>
                                <th class="py-3 px-4 text-left font-bold text-gray-600 dark:text-gray-300">Stopped</th>
                                <th class="py-3 px-4 text-right font-bold text-gray-600 dark:text-gray-300">Duration</th>
                            </tr>
                        </thead>
                        <tbody id="view-logs-body" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                    </table>
                </div>
            </div>

            <!-- Attendance Summary (Optional, populated by JS) -->
            <div id="attendance-summary-section" class="hidden space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                <h4 class="font-bold text-gray-800 dark:text-white flex items-center text-lg">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Daily Attendance Breakdown
                </h4>
                <div id="attendance-summary-body" class="text-sm space-y-2">
                    <!-- Data populated by JS -->
                </div>
            </div>
        </div>

        <div class="modal-footer flex justify-between items-center pt-6 border-t mt-6">
            <div id="task-links" class="flex gap-3">
                <!-- Dynamic external links (Github/Live) -->
            </div>
            <button type="button" class="close-modal px-8 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-bold">Close Window</button>
        </div>
    </div>
</div>

<!-- Task Submission Modal (Intern) -->
<div id="submission-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[5%] p-6 rounded-2xl w-11/12 max-w-2xl relative shadow-2xl">
        <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded-lg close-submission-modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h2 class="text-2xl font-bold mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">Submit Completed Task</h2>
        
        <form id="submission-form" class="space-y-4">
            <input type="hidden" id="submission-task-id">
            
            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">GitHub Repository URL</label>
                <input type="url" id="github-repo" placeholder="https://github.com/username/repo" required
                    class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none">
                <p id="github-error" class="text-xs text-red-500 hidden">Please enter a valid GitHub repository URL.</p>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Live Project URL</label>
                <input type="url" id="live-view" placeholder="https://your-live-demo.com" required
                    class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none">
                <p id="live-view-error" class="text-xs text-red-500 hidden">Please enter a valid live URL.</p>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Additional Notes (Optional)</label>
                <textarea id="additional-notes" rows="3" placeholder="Any specific instructions or details for the reviewer..."
                    class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none"></textarea>
            </div>
            
            <div class="modal-footer flex justify-end gap-3 pt-4 border-t mt-6">
                <button type="button" class="close-submission-modal px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-semibold">Cancel</button>
                <button type="button" id="submit-task-btn" class="px-8 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all shadow-md hover:shadow-green-500/20 font-bold">Submit Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Task Review Modal (Supervisor) -->
<div id="review-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[5%] p-6 rounded-2xl w-11/12 max-w-2xl relative shadow-2xl">
        <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded-lg close-review-modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h2 class="text-2xl font-bold mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">Review Task Submission</h2>
        
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <a id="review-github" href="#" target="_blank" class="flex items-center justify-center p-3 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.041-1.416-4.041-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    GitHub Repo
                </a>
                <a id="review-live" href="#" target="_blank" class="flex items-center justify-center p-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Live Demo
                </a>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Intern's Notes</label>
                <div id="review-intern-notes" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600 text-sm leading-relaxed min-h-[60px]"></div>
            </div>

            <form id="review-form" class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <input type="hidden" id="review-task-id">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Review Result</label>
                    <select id="review-action" required class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none">
                        <option value="approved">Approve Task</option>
                        <option value="needs_improvement">Request Improvements</option>
                        <option value="rejected">Reject Submission</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Feedback Notes</label>
                    <textarea id="review-notes" rows="3" placeholder="Add feedback for the intern..." class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none"></textarea>
                </div>

                <div class="modal-footer flex justify-end gap-3 pt-4">
                    <button type="button" class="close-review-modal px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-semibold">Cancel</button>
                    <button type="submit" class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all shadow-md hover:shadow-indigo-500/20 font-bold">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Task Reactivate Modal (Supervisor) -->
<div id="reactivate-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[10%] p-6 rounded-2xl w-11/12 max-w-md relative shadow-2xl">
        <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded-lg close-reactivate-modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h2 class="text-2xl font-bold mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">Reactivate Task</h2>
        
        <form id="reactivate-form" class="space-y-4">
            <input type="hidden" id="reactivate-task-id">
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Set New Due Date</label>
                <input type="date" id="reactivate-date" required
                    class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none">
            </div>

            <div class="modal-footer flex justify-end gap-3 pt-6 border-t mt-6">
                <button type="button" class="close-reactivate-modal px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-semibold">Cancel</button>
                <button type="submit" class="px-8 py-2.5 bg-orange-600 text-white rounded-xl hover:bg-orange-700 transition-all shadow-md hover:shadow-orange-500/20 font-bold">Reactivate</button>
            </div>
        </form>
    </div>
</div>
