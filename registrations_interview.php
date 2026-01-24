<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only admin access
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4)) {
    header('Location: index.php');
    exit;
}

include_once './include/connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Interview List - TaskDesk';
include_once "./include/headerLinks.php";
?>

<style>
    .expand-icon {
        width: 10px;
        height: 10px;
        display: inline-block;
        position: relative;
        cursor: pointer;
        transition: transform 300ms ease;
    }

    .expand-icon .bar {
        position: absolute;
        background-color: currentColor;
        border-radius: 2px;
    }

    .expand-icon .horizontal {
        width: 100%;
        height: 1.5px;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
    }

    .expand-icon .vertical {
        height: 100%;
        width: 1.5px;
        left: 50%;
        top: 0;
        transform: translateX(-50%);
    }

    tr.shown .expand-icon {
        transform: rotate(45deg);
    }

    .loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.3s ease;
    }

    .loader {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .table-loader {
        display: none;
        text-align: center;
        padding: 20px;
    }

    .table-loader.active {
        display: block;
    }

    .table-container {
        position: relative;
        min-height: 200px;
    }

    /* Status badges */
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-interview {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-hired {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Interview time styling */
    .interview-time {
        font-size: 12px;
        padding: 4px 8px;
        background: #f3f4f6;
        border-radius: 6px;
        margin: 2px 0;
    }

    .dark .interview-time {
        background: #374151;
        color: #d1d5db;
    }

    /* Upcoming interview highlight */
    .upcoming-interview {
        border-left: 4px solid #3b82f6 !important;
        background-color: #f0f9ff !important;
    }

    .dark .upcoming-interview {
        background-color: #1e3a8a1a !important;
        border-left-color: #60a5fa !important;
    }

    /* Action buttons */
    .action-btn {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Modal scrollbar styling */
    #rescheduleModal div[class*="max-h-"]::-webkit-scrollbar {
        width: 6px;
    }

    #rescheduleModal div[class*="max-h-"]::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    #rescheduleModal div[class*="max-h-"]::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    #rescheduleModal div[class*="max-h-"]::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .dark #rescheduleModal div[class*="max-h-"]::-webkit-scrollbar-track {
        background: #374151;
    }

    .dark #rescheduleModal div[class*="max-h-"]::-webkit-scrollbar-thumb {
        background: #6b7280;
    }

    .dark #rescheduleModal div[class*="max-h-"]::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }

    /* Conflict highlighting */
    .time-slot-conflict {
        border-color: #f87171 !important;
        background-color: #fef2f2 !important;
        color: #dc2626 !important;
    }

    .time-slot-conflict:focus {
        border-color: #dc2626 !important;
        ring-color: #fca5a5 !important;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #timeSlotAlert>div {
        animation: fadeIn 0.3s ease-out;
    }

    .details-wrapper {
        display: none;
        overflow: hidden;
    }
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="globalLoader" class="loader-overlay hidden">
        <div class="loader"></div>
    </div>
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">

                <!-- Title only -->
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Interview List</h2>

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="table-container">
                        <div id="tableLoader" class="table-loader p-8">
                            <div class="flex justify-center items-center space-x-4">
                                <div class="loader"></div>
                                <span class="text-gray-600 dark:text-gray-300">Loading interviews...</span>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="interviewTable" class="min-w-full">
                                <thead class="text-sm text-gray-800 dark:text-gray-50"></thead>
                                <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <!-- View Interview Details Modal -->
            <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Interview Details</h3>
                        <button type="button" id="closeViewModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Candidate Info -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Candidate Information</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Name</label>
                                    <div id="viewName" class="font-medium text-gray-800 dark:text-white"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact</label>
                                    <div id="viewContact" class="font-medium text-gray-800 dark:text-white"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Email</label>
                                    <div id="viewEmail" class="font-medium text-gray-800 dark:text-white"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Technology</label>
                                    <div id="viewTechnology" class="font-medium text-gray-800 dark:text-white"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Interview Details -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Interview Details</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date & Time</label>
                                    <div id="viewDateTime" class="font-medium text-gray-800 dark:text-white"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Platform</label>
                                    <div id="viewPlatform" class="font-medium text-gray-800 dark:text-white"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Duration</label>
                                    <div id="viewDuration" class="font-medium text-gray-800 dark:text-white"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                                    <div id="viewStatus" class="font-medium"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Interview Notes -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Interview Notes</h4>
                            <textarea id="viewNotes" class="w-full px-3 py-2 border rounded text-sm bg-white dark:bg-gray-800 dark:border-gray-600" rows="3" placeholder="Add interview notes..."></textarea>
                            <button id="saveNotesBtn" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Save Notes</button>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" id="viewRescheduleBtn" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                                Reschedule
                            </button>
                            <button type="button" id="viewHireBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Hire
                            </button>
                            <button type="button" class="action-btn reject-btn px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reschedule Modal (Multi-step) -->
            <div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Reschedule Interview - Step 1 of 3</h3>
                        <button type="button" id="closeReschModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Progress Steps -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold" id="step1-indicator">1</div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Basic Info</span>
                            </div>
                            <div class="h-1 flex-1 mx-4 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex items-center space-x-2 opacity-50" id="step2-wrapper">
                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-semibold">2</div>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Date & Time</span>
                            </div>
                            <div class="h-1 flex-1 mx-4 bg-gray-200 dark:bg-gray-700 opacity-50"></div>
                            <div class="flex items-center space-x-2 opacity-50" id="step3-wrapper">
                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-semibold">3</div>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirm</span>
                            </div>
                        </div>
                    </div>

                    <form id="rescheduleForm" class="space-y-6">
                        <input type="hidden" id="intId" name="id">

                        <!-- Step 1: Basic Information -->
                        <div id="step1" class="space-y-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Candidate Information</h4>
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Name</label>
                                            <input type="text" id="intName" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-800 dark:text-gray-200" readonly>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact</label>
                                            <input type="text" id="intContact" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-800 dark:text-gray-200" readonly>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Email</label>
                                        <input type="text" id="intEmail" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-800 dark:text-gray-200" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Interview Platform <span class="text-red-500">*</span></label>
                                    <select id="intPlatform" name="platform" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <option value="">-- Select Platform --</option>
                                        <option value="Google Meet">Google Meet</option>
                                        <option value="Zoom">Zoom</option>
                                        <option value="Microsoft Teams">Microsoft Teams</option>
                                        <option value="In Person">In Person</option>
                                        <option value="Phone Call">Phone Call</option>
                                    </select>
                                    <div id="platformError" class="text-red-500 text-xs mt-1 hidden">Please select a platform</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Technology <span class="text-red-500">*</span></label>
                                    <select id="intTech" name="technology_id" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required disabled>
                                        <option value="">-- Select Technology --</option>
                                        <?php
                                        $techQ = "SELECT id, name FROM technologies ORDER BY name";
                                        $techR = mysqli_query($conn, $techQ);
                                        while ($row = mysqli_fetch_assoc($techR)) {
                                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Technology cannot be changed during rescheduling.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Date & Time (Initially hidden) -->
                        <div id="step2" class="space-y-6 hidden">
                            <!-- Calendar Container -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Select Date & Time <span class="text-red-500">*</span></h4>

                                <!-- Calendar Navigation -->
                                <div class="flex items-center justify-between mb-4">
                                    <button type="button" id="prevMonth" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <h4 id="currentMonth" class="text-base font-semibold text-gray-800 dark:text-white">January 2024</h4>
                                    <button type="button" id="nextMonth" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Days of Week -->
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Sun</div>
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Mon</div>
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Tue</div>
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Wed</div>
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Thu</div>
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Fri</div>
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Sat</div>
                                </div>

                                <!-- Calendar Days -->
                                <div id="calendarDays" class="grid grid-cols-7 gap-1"></div>
                                <div id="dateError" class="text-red-500 text-xs mt-2 hidden">Please select a date</div>
                            </div>

                            <!-- Time Selection -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Time <span class="text-red-500">*</span></label>
                                    <input type="time" id="intFromTime" name="from_time" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <div id="timeError" class="text-red-500 text-xs mt-1 hidden">Start time must be before end time</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Time <span class="text-red-500">*</span></label>
                                    <input type="time" id="intToTime" name="to_time" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                </div>
                            </div>

                            <!-- Duration Display -->
                            <div id="durationDisplay" class="text-center py-3 px-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg hidden">
                                <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                                    Duration: <span id="durationText" class="font-bold">0 minutes</span>
                                </span>
                            </div>

                            <!-- Selected Date Display -->
                            <div id="selectedDateDisplay" class="text-center py-3 px-4 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-lg">
                                <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                                    Selected Date: <span id="displayDate" class="font-bold">No date selected</span>
                                </span>
                            </div>

                            <!-- Booked Slots Display -->
                            <div id="bookedSlotsDisplay" class="hidden">
                                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Booked Time Slots (Today)
                                </h5>
                                <div id="slotsList" class="space-y-2 text-sm"></div>
                            </div>
                        </div>

                        <!-- Step 3: Confirmation (Initially hidden) -->
                        <div id="step3" class="space-y-6 hidden">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 text-center">Confirm Reschedule Details</h4>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Candidate</label>
                                            <div id="confirmName" class="font-medium text-gray-800 dark:text-white"></div>
                                        </div>
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Contact</label>
                                            <div id="confirmContact" class="font-medium text-gray-800 dark:text-white"></div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Platform</label>
                                            <div id="confirmPlatform" class="font-medium text-gray-800 dark:text-white"></div>
                                        </div>
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Technology</label>
                                            <div id="confirmTech" class="font-medium text-gray-800 dark:text-white"></div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date & Time</label>
                                            <div id="confirmDateTime" class="font-medium text-gray-800 dark:text-white"></div>
                                        </div>
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Duration</label>
                                            <div id="confirmDuration" class="font-medium text-gray-800 dark:text-white"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        <span class="text-sm text-yellow-700 dark:text-yellow-300">
                                            Please review all details before confirming rescheduling.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <button type="button" id="prevStepBtn" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors hidden">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    Previous
                                </button>
                            </div>

                            <div class="flex space-x-3">
                                <button type="button" id="cancelReschModalBtn" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Cancel
                                </button>
                                <button type="button" id="nextStepBtn" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors font-medium">
                                    Next Step
                                    <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors font-medium hidden">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Confirm Reschedule
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Hire Modal -->
            <div id="hireModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Hire Candidate</h3>
                        <button type="button" id="hireCancelBtn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form id="hireForm" class="space-y-6">
                        <input type="hidden" id="hireId" name="id">

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Candidate Name</label>
                                <input type="text" id="hireName" class="w-full px-3 py-2.5 border rounded-lg text-gray-800 dark:text-gray-200" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Technology</label>
                                <input type="text" id="hireTechnology" class="w-full px-3 py-2.5 border rounded-lg text-gray-800 dark:text-gray-200" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Supervisor</label>
                                <div class="searchable-wrapper relative w-full">
                                    <select id="hireTrainer" class="searchable-select hidden" name="hireTrainer" required>
                                        <option value="">Select Supervisor</option>
                                        <?php
                                        $userQuery = "SELECT id, name FROM users WHERE user_role = 3 ORDER BY name ASC";
                                        $userResult = mysqli_query($conn, $userQuery);
                                        while ($user = mysqli_fetch_assoc($userResult)) {
                                            echo "<option value=\"{$user['id']}\">{$user['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="relative">
                                        <input type="text" class="searchable-input w-full px-3 py-2.5 pr-10 border rounded-lg bg-white dark:bg-gray-800 dark:text-gray-200 cursor-pointer" placeholder="Select Supervisor" autocomplete="off" required>
                                    </div>
                                    <ul class="searchable-dropdown hidden absolute z-50 w-full bg-white dark:bg-gray-800 border rounded-lg mt-1 max-h-60 overflow-y-auto shadow-lg"></ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" id="cancelHireBtn" class="px-4 py-2.5 border rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Hire Candidate
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
<script>
    // Enhanced utilities
    const LoaderManager = {
        showGlobal: function() {
            document.getElementById('globalLoader').classList.remove('hidden');
        },
        hideGlobal: function() {
            document.getElementById('globalLoader').classList.add('hidden');
        }
    };

    function showToast(type, msg) {
        const toast = document.createElement('div');
        toast.className = `px-5 py-3 rounded-lg text-white shadow-lg ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} transform transition-all duration-300`;
        toast.textContent = msg;
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Get Pakistan time (Asia/Karachi)
    function getPakistanTime() {
        return new Date().toLocaleString("en-US", {timeZone: "Asia/Karachi"});
    }

    // Get today's date in Pakistan timezone
    function getTodayPakistanDate() {
        const now = new Date();
        const pakistanTime = new Date(now.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
        return new Date(pakistanTime.getFullYear(), pakistanTime.getMonth(), pakistanTime.getDate());
    }

    // Format date to YYYY-MM-DD in Pakistan timezone
    function formatPakistanDate(date) {
        const pakistanDate = new Date(date.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
        const year = pakistanDate.getFullYear();
        const month = String(pakistanDate.getMonth() + 1).padStart(2, '0');
        const day = String(pakistanDate.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Format date and time in Pakistan timezone
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return 'Not scheduled';

        const date = new Date(dateTimeStr);
        if (isNaN(date)) return dateTimeStr;

        // Convert to Pakistan timezone for display
        const options = {
            timeZone: 'Asia/Karachi',
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };

        const pakistanDate = new Date(date.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
        
        return {
            date: pakistanDate.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                timeZone: 'Asia/Karachi'
            }),
            time: pakistanDate.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'Asia/Karachi'
            }),
            full: pakistanDate.toLocaleString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'Asia/Karachi'
            }),
            iso: formatPakistanDate(pakistanDate) + ' ' + pakistanDate.toTimeString().slice(0, 5)
        };
    }

    // Calculate time status in Pakistan timezone
    function getTimeStatus(interviewStart) {
        if (!interviewStart) return 'unknown';

        const now = new Date();
        const nowPakistan = new Date(now.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
        
        const interviewTime = new Date(interviewStart);
        const interviewPakistan = new Date(interviewTime.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
        
        const diffHours = (interviewPakistan - nowPakistan) / (1000 * 60 * 60);

        if (diffHours < 0) return 'past';
        if (diffHours < 24) return 'today';
        if (diffHours < 72) return 'soon';
        return 'upcoming';
    }

    // Get status badge
    function getStatusBadge(status) {
        const badges = {
            'interview': '<span class="status-badge status-interview">Scheduled</span>',
            'hired': '<span class="status-badge status-hired">Hired</span>',
            'rejected': '<span class="status-badge status-rejected">Rejected</span>',
            'no_show': '<span class="status-badge status-rejected">No Show</span>'
        };
        return badges[status] || `<span class="status-badge bg-gray-100 text-gray-800">${status}</span>`;
    }

    // Calculate duration between two dates
    function calculateDuration(start, end) {
        if (!start || !end) return 'N/A';

        const startTime = new Date(start);
        const endTime = new Date(end);
        const diffMs = endTime - startTime;
        const diffMins = Math.floor(diffMs / 60000);
        const hours = Math.floor(diffMins / 60);
        const minutes = diffMins % 60;

        return `${hours}h ${minutes}m`;
    }

    /* Searchable Select Logic */
    function initSearchableSelect() {
        const $wrapper = $('.searchable-wrapper');
        const $originalSelect = $wrapper.find('.searchable-select');
        const $searchInput = $wrapper.find('.searchable-input');
        const $dropdown = $wrapper.find('.searchable-dropdown');

        function populateDropdown() {
            $dropdown.empty();
            $originalSelect.find('option').each(function() {
                if (this.value === '') return;
                const $li = $('<li>').addClass('px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200')
                    .on('click', () => {
                        $originalSelect.val(this.value);
                        $searchInput.val(this.textContent);
                        $dropdown.addClass('hidden');
                    });
                $li.text(this.textContent);
                $dropdown.append($li);
            });
        }

        $searchInput.on('click input', function() {
            populateDropdown();
            const term = this.value.toLowerCase();
            $dropdown.find('li').each(function() {
                const $li = $(this);
                const text = $li.text().toLowerCase();
                $li.toggle(text.indexOf(term) > -1);
            });
            $dropdown.removeClass('hidden');
        });

        $(document).on('click', function(e) {
            if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
                $dropdown.addClass('hidden');
            }
        });
    }

    const expandableColumns = ['email', 'cnic', 'city', 'country', 'platform'];
    const headerMap = {
        id: 'ID',
        name: 'Name',
        email: 'Email',
        mbl_number: 'Contact',
        technology: 'Technology',
        platform: 'Platform',
        cnic: 'CNIC',
        city: 'City',
        country: 'Country',
        interview_start: 'Interview Time',
        interview_end: 'Interview End',
        status: 'Status'
    };

    function formatDetails(row) {
        const details = expandableColumns.map(k => {
            let value = row[k] ?? '-';

            // Format special fields
            if (k === 'interview_start' && row.interview_start) {
                const dt = formatDateTime(row.interview_start);
                value = dt.full;
            } else if (k === 'interview_end' && row.interview_end) {
                const dt = formatDateTime(row.interview_end);
                value = dt.time;
            } else if (k === 'scheduled_at' && row.scheduled_at) {
                value = formatDateTime(row.scheduled_at).full;
            }

            return `<div><span class="font-semibold">${headerMap[k]}:</span> <span class="text-gray-600 dark:text-gray-300">${escapeHTML(value)}</span></div>`;
        }).join('');

        return `<div class="details-wrapper"><div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg grid grid-cols-2 gap-4 text-sm">${details}</div></div>`;
    }

    // --- Reschedule Modal Functionality ---
    let currentStep = 1;
    let selectedDate = null;
    let currentDate = getTodayPakistanDate();
    let formData = {};
    let currentCandidateId = null;

    function initializeModal() {
        currentStep = 1;
        selectedDate = null;
        currentDate = getTodayPakistanDate();
        updateProgress();
        updateStepDisplay();
        initCalendar();
        setDefaultTimes();

        // Reset errors
        $('#platformError, #techError, #dateError, #timeError').addClass('hidden');
        $('#intPlatform, #intTech, #intFromTime, #intToTime').removeClass('border-red-500 time-slot-conflict');

        // Clear displays
        $('#bookedSlotsDisplay, #durationDisplay').addClass('hidden');
        $('#slotsList').empty();
    }

    function updateProgress() {
        $('#rescheduleModal h3').first().text(`Reschedule Interview - Step ${currentStep} of 3`);

        const steps = ['step1-indicator', 'step2-wrapper', 'step3-wrapper'];
        steps.forEach((id, index) => {
            const stepNum = index + 1;
            const $el = $(`#${id.replace('-indicator', '').replace('-wrapper', '')}-indicator`);
            const $wrapper = $(`#${id}`);

            // Easier: just query the steps indicators directly
            const $steps = $('#rescheduleModal .flex.items-center.space-x-2');
            $steps.each(function(idx) {
                const $num = $(this).find('div');
                const $text = $(this).find('span');
                const sNum = idx + 1;

                if (sNum === currentStep) {
                    $num.attr('class', 'w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold');
                    $text.attr('class', 'text-sm font-medium text-gray-700 dark:text-gray-300');
                } else if (sNum < currentStep) {
                    $num.attr('class', 'w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-semibold');
                    $text.attr('class', 'text-sm font-medium text-gray-700 dark:text-gray-300');
                } else {
                    $num.attr('class', 'w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-semibold');
                    $text.attr('class', 'text-sm font-medium text-gray-500 dark:text-gray-400');
                }
            });
        });
    }

    function updateStepDisplay() {
        $('#step1, #step2, #step3').addClass('hidden');
        $(`#step${currentStep}`).removeClass('hidden');

        const $prev = $('#prevStepBtn');
        const $next = $('#nextStepBtn');
        const $submit = $('#submitBtn');

        if (currentStep === 1) {
            $prev.addClass('hidden');
            $next.removeClass('hidden').text('Next Step');
            $submit.addClass('hidden');
        } else if (currentStep === 2) {
            $prev.removeClass('hidden');
            $next.removeClass('hidden').text('Review & Confirm');
            $submit.addClass('hidden');
        } else if (currentStep === 3) {
            $prev.removeClass('hidden');
            $next.addClass('hidden');
            $submit.removeClass('hidden');
            updateConfirmationDetails();
        }
    }

    function validateStep1() {
        const platform = $('#intPlatform').val();
        let isValid = true;

        if (!platform) {
            $('#platformError').removeClass('hidden');
            $('#intPlatform').addClass('border-red-500');
            isValid = false;
        } else {
            $('#platformError').addClass('hidden');
            $('#intPlatform').removeClass('border-red-500');
        }

        if (isValid) {
            formData.platform = platform;
        }
        return isValid;
    }

    function validateStep2() {
        const startTime = $('#intFromTime').val();
        const endTime = $('#intToTime').val();
        let isValid = true;

        $('#dateError, #timeError').addClass('hidden');

        if (!selectedDate) {
            $('#dateError').removeClass('hidden');
            isValid = false;
        }

        if (!startTime || !endTime) {
            $('#timeError').removeClass('hidden').text('Please select both start and end time');
            isValid = false;
        } else if (startTime >= endTime) {
            $('#timeError').removeClass('hidden').text('End time must be after start time');
            isValid = false;
        }

        if (isValid) {
            formData.date = selectedDate;
            formData.startTime = startTime;
            formData.endTime = endTime;

            const start = new Date(`2000-01-01T${startTime}:00`);
            const end = new Date(`2000-01-01T${endTime}:00`);
            formData.duration = (end - start) / (1000 * 60);
        }
        return isValid;
    }

    function updateConfirmationDetails() {
        $('#confirmName').text($('#intName').val());
        $('#confirmContact').text($('#intContact').val());
        $('#confirmPlatform').text(formData.platform);
        $('#confirmTech').text($('#intTech option:selected').text());

        const dateStr = selectedDate.toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            timeZone: 'Asia/Karachi'
        });
        $('#confirmDateTime').text(`${dateStr} | ${formData.startTime} - ${formData.endTime}`);

        const h = Math.floor(formData.duration / 60);
        const m = formData.duration % 60;
        $('#confirmDuration').text(`${h}h ${m}m`);
    }

    function initCalendar() {
        renderCalendar(currentDate);
    }

    function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        $('#currentMonth').text(`${monthNames[month]} ${year}`);

        const firstDay = new Date(year, month, 1).getDay();
        const totalDays = new Date(year, month + 1, 0).getDate();
        const today = getTodayPakistanDate();

        const $days = $('#calendarDays').empty();

        for (let i = 0; i < firstDay; i++) $days.append('<div class="h-10"></div>');

        for (let day = 1; day <= totalDays; day++) {
            const cellDate = new Date(year, month, day);
            cellDate.setHours(0, 0, 0, 0); // Normalize time
            
            const $btn = $('<button type="button" class="h-10 flex items-center justify-center rounded-lg text-sm font-medium transition-colors"></button>');

            if (cellDate.getTime() === today.getTime()) {
                $btn.addClass('bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300');
            }

            if (selectedDate && selectedDate.getTime() === cellDate.getTime()) {
                $btn.removeClass('hover:bg-gray-200 dark:hover:bg-gray-600').addClass('bg-indigo-600 text-white hover:bg-indigo-700');
            } else {
                $btn.addClass('hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200');
            }

            if (cellDate < today) {
                $btn.addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
            } else {
                $btn.on('click', () => selectDate(cellDate));
            }

            $btn.text(day);
            $days.append($btn);
        }
    }

    async function selectDate(date) {
        selectedDate = date;
        renderCalendar(currentDate); // Re-render to highlight

        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            timeZone: 'Asia/Karachi'
        };
        
        const pakistanDate = new Date(date.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
        $('#displayDate').text(pakistanDate.toLocaleDateString('en-US', options));
        $('#dateError').addClass('hidden');

        updateTimeSlotDisplay(formatPakistanDate(date));
        validateTimeInput();
    }

    function setDefaultTimes() {
        // Get current Pakistan time
        const now = new Date();
        const pakistanTime = new Date(now.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
        
        // Set default start time to next hour (rounded to nearest 15 minutes)
        pakistanTime.setHours(pakistanTime.getHours() + 1);
        pakistanTime.setMinutes(Math.ceil(pakistanTime.getMinutes() / 15) * 15);
        pakistanTime.setSeconds(0);
        
        const startTime = pakistanTime.toTimeString().slice(0, 5);
        $('#intFromTime').val(startTime);
        
        // Set default end time to 1 hour after start time
        const endTimeObj = new Date(pakistanTime.getTime() + (60 * 60 * 1000)); // Add 1 hour
        const endTime = endTimeObj.toTimeString().slice(0, 5);
        $('#intToTime').val(endTime);

        updateDurationDisplay();
    }

    function updateDurationDisplay() {
        const start = $('#intFromTime').val();
        const end = $('#intToTime').val();
        if (start && end) {
            const s = new Date(`2000-01-01T${start}:00`);
            const e = new Date(`2000-01-01T${end}:00`);
            const diff = (e - s) / 60000;
            if (diff > 0) {
                $('#durationDisplay').removeClass('hidden');
                const h = Math.floor(diff / 60);
                const m = diff % 60;
                $('#durationText').text(`${h}h ${m}m`);
            }
        }
    }

    async function validateTimeInput() {
        const start = $('#intFromTime').val();
        const end = $('#intToTime').val();
        if (!start || !end || !selectedDate) return;

        if (start >= end) {
            $('#timeError').removeClass('hidden').text('End time must be after start time');
            $('#intFromTime, #intToTime').addClass('border-red-500');
            return false;
        }

        $('#timeError').addClass('hidden');
        $('#intFromTime, #intToTime').removeClass('border-red-500');

        // Check conflicts
        const dateStr = formatPakistanDate(selectedDate);
        const res = await checkTimeSlotConflict(dateStr, start, end, currentCandidateId);

        if (res.hasConflict) {
            $('#timeError').removeClass('hidden').text(res.message);
            $('#intFromTime, #intToTime').addClass('time-slot-conflict');
            return false;
        }

        $('#intFromTime, #intToTime').removeClass('time-slot-conflict');
        return true;
    }

    async function fetchBookedSlots(date) {
        try {
            const res = await fetch(`controller/registrations.php?action=get_booked_slots&date=${date}`);
            return await res.json();
        } catch (e) {
            return {
                success: false,
                slots: []
            };
        }
    }

    async function updateTimeSlotDisplay(date) {
        const res = await fetchBookedSlots(date);
        if (res.success && res.slots.length > 0) {
            $('#bookedSlotsDisplay').removeClass('hidden');
            $('#slotsList').html(res.slots.map(s =>
                `<div class="flex justify-between p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600 mb-1">
                    <span class="text-xs text-gray-700 dark:text-gray-300 font-mono">${s.formatted_start} - ${s.formatted_end}</span>
                    <span class="text-xs text-gray-500">${s.name}</span>
                </div>`
            ).join(''));
        } else {
            $('#bookedSlotsDisplay').addClass('hidden');
        }
    }

    async function checkTimeSlotConflict(date, start, end, id) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('interview_start', `${date} ${start}:00`);
            formData.append('interview_end', `${date} ${end}:00`);
            formData.append('action', 'check_conflict');

            const res = await fetch('controller/registrations.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            return {
                hasConflict: !json.success && json.type === 'conflict',
                message: json.message
            };
        } catch (e) {
            return {
                hasConflict: false
            };
        }
    }

    function showAlert(title, msg) {
        alert(`${title}\n${msg}`);
    }

    // Reject candidate function
    async function rejectCandidate(id, name, table) {
        if (!confirm(`Are you sure you want to reject ${name}?`)) return;

        LoaderManager.showGlobal();
        try {
            const formData = new FormData();
            formData.append('action', 'reject_candidate');
            formData.append('id', id);
            formData.append('status', 'rejected');

            const res = await fetch('controller/registrations.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.success) {
                showToast('success', 'Candidate rejected');
                if (table) table.ajax.reload();
            } else {
                showToast('error', json.message);
            }
        } catch (e) {
            showToast('error', e.message);
        } finally {
            LoaderManager.hideGlobal();
        }
    }

    $(document).ready(function() {
        initSearchableSelect();

        // Build table header
        $('#interviewTable thead').html(`
            <tr>
                <th></th>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Technology</th>
                <th>Internship Type</th>
                <th>Experience</th>
                <th>Actions</th>
            </tr>
        `);

        const table = $('#interviewTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: 'controller/registrations.php',
                type: 'GET',
                data: function(d) {
                    d.action = 'interview';
                },
                dataSrc: function(json) {
                    return json.data || [];
                }
            },
            columns: [{
                    class: 'details-control cursor-pointer text-center font-bold',
                    orderable: false,
                    data: null,
                    defaultContent: '<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>'
                },
                {
                    data: 'id',
                    className: 'font-mono'
                },
                {
                    data: 'name',
                    render: function(data, type, row) {
                        const timeStatus = getTimeStatus(row.interview_start);
                        let timeClass = '';

                        if (timeStatus === 'soon') timeClass = 'time-soon';
                        if (timeStatus === 'today') timeClass = 'time-today';

                        const formattedTime = row.interview_start ? formatDateTime(row.interview_start).time : '';
                        const formattedDate = row.interview_start ? formatDateTime(row.interview_start).date : '';

                        return `
                            <div>
                                <div class="font-medium text-gray-800 dark:text-white">${escapeHTML(data)}</div>
                                <div class="text-xs ${timeClass}">${formattedDate} ${formattedTime}</div>
                            </div>
                        `;
                    }
                },
                {
                    data: 'mbl_number',
                    className: 'font-mono'
                },
                {
                    data: 'technology',
                    render: function(data) {
                        return `<span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">${escapeHTML(data)}</span>`;
                    }
                },
                {
                    data: 'internship_type_text'
                },
                {
                    data: 'experience_text'
                },
                {
                    data: null,
                    orderable: false,
                    className: 'space-x-1',
                    render: function(data, type, row) {
                        const timeStatus = getTimeStatus(row.interview_start);
                        const isPast = timeStatus === 'past';

                        return `
                        <div class="flex flex-wrap gap-1">
                            <button class="action-btn bg-blue-500 hover:bg-blue-600 text-white view-btn" 
                                    data-id="${row.id}" title="View Details">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                            <button class="action-btn bg-yellow-500 hover:bg-yellow-600 text-white reschedule-btn" 
                                    data-id="${row.id}" title="Reschedule" ${isPast ? 'disabled' : ''}>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>
                            <button class="action-btn bg-green-500 hover:bg-green-600 text-white hire-btn" 
                                    data-id="${row.id}" title="Hire">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <button class="action-btn bg-red-500 hover:bg-red-600 text-white reject-table-btn" 
                                    data-id="${row.id}" title="Reject">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>`;
                    }
                }
            ],
            order: [
                [2, 'asc']
            ],
            language: {
                processing: 'Processing...',
                emptyTable: 'No interviews scheduled',
                zeroRecords: 'No interviews match your filter'
            },
            createdRow: function(row, data, dataIndex) {
                const timeStatus = getTimeStatus(data.interview_start);
                if (timeStatus === 'today') {
                    $(row).addClass('upcoming-interview');
                }
            }
        });

        // Row details expansion
        $('#interviewTable tbody').on('click', 'td.details-control', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            if (row.child.isShown()) {
                $(row.child()).find('.details-wrapper').slideUp(300, function() {
                    row.child.hide();
                    tr.removeClass('shown');
                });
            } else {
                row.child(formatDetails(row.data())).show();
                tr.addClass('shown');
                $(row.child()).find('.details-wrapper').slideDown(300);
            }
        });

        // View Details
        $(document).on('click', '.view-btn', function() {
            const row = table.row($(this).closest('tr')).data();
            loadViewModal(row);
        });

        function loadViewModal(row) {
            const dtStart = formatDateTime(row.interview_start);
            const dtEnd = formatDateTime(row.interview_end);

            $('#viewName').text(row.name);
            $('#viewContact').text(row.mbl_number);
            $('#viewEmail').text(row.email || 'N/A');
            $('#viewTechnology').text(row.technology);
            $('#viewDateTime').text(`${dtStart.full} - ${dtEnd.time}`);
            $('#viewPlatform').text(row.platform || 'N/A');
            $('#viewDuration').text(calculateDuration(row.interview_start, row.interview_end));
            $('#viewStatus').html(getStatusBadge(row.status));
            $('#viewNotes').val(row.remarks || '');

            // Store current row data
            $('#viewModal').data('row', row);

            // Clear existing handlers before adding new ones
            $('#viewRescheduleBtn').off('click');
            $('#viewHireBtn').off('click');
            $('#viewModal .reject-btn').off('click');

            // Set button actions
            $('#viewRescheduleBtn').on('click', function() {
                $('#viewModal').addClass('hidden');
                openRescheduleModal(row);
            });

            $('#viewHireBtn').on('click', function() {
                $('#viewModal').addClass('hidden');
                openHireModal(row);
            });

            $('#viewModal .reject-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#viewModal').addClass('hidden');
                rejectCandidate(row.id, row.name, table);
            });

            $('#viewModal').removeClass('hidden');
        }

        // Save notes
        $('#saveNotesBtn').on('click', async function() {
            const row = $('#viewModal').data('row');
            const notes = $('#viewNotes').val();

            LoaderManager.showGlobal();
            try {
                const formData = new FormData();
                formData.append('action', 'update_interview_notes');
                formData.append('id', row.id);
                formData.append('notes', notes);

                const res = await fetch('controller/registrations.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (json.success) {
                    showToast('success', 'Notes saved successfully');
                    table.ajax.reload();
                } else {
                    showToast('error', json.message);
                }
            } catch (e) {
                showToast('error', e.message);
            } finally {
                LoaderManager.hideGlobal();
            }
        });

        // Close view modal
        $('#closeViewModal').on('click', () => $('#viewModal').addClass('hidden'));

        // Event Listeners for Multi-step Modal
        $('#nextStepBtn').on('click', async function() {
            if (currentStep === 1) {
                if (validateStep1()) {
                    currentStep++;
                    updateProgress();
                    updateStepDisplay();
                }
            } else if (currentStep === 2) {
                if (validateStep2()) {
                    const btn = $(this);
                    const originalText = btn.text();
                    btn.text('Checking...').prop('disabled', true);

                    const dateStr = formatPakistanDate(selectedDate);
                    const start = $('#intFromTime').val();
                    const end = $('#intToTime').val();

                    const res = await checkTimeSlotConflict(dateStr, start, end, currentCandidateId);
                    btn.text(originalText).prop('disabled', false);

                    if (res.hasConflict) {
                        showAlert('Time Slot Conflict', res.message || 'This time slot is already booked.');
                        return;
                    }

                    currentStep++;
                    updateProgress();
                    updateStepDisplay();
                }
            }
        });

        $('#prevStepBtn').on('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateProgress();
                updateStepDisplay();
            }
        });

        $('#prevMonth').on('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar(currentDate);
        });
        $('#nextMonth').on('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar(currentDate);
        });

        $('#intFromTime, #intToTime').on('change', async function() {
            updateDurationDisplay();
            await validateTimeInput();
        });

        $('#intPlatform').on('change', function() {
            if (this.value) {
                $('#platformError').addClass('hidden');
                $(this).removeClass('border-red-500');
            }
        });

        // Reschedule Function
        function openRescheduleModal(row) {
            currentCandidateId = row.id;
            $('#intId').val(row.id);
            $('#intName').val(row.name);
            $('#intEmail').val(row.email);
            $('#intContact').val(row.mbl_number);

            // Set Technology
            $("#intTech option").filter(function() {
                return $(this).text() === row.technology;
            }).prop('selected', true);

            // Set Platform
            if (row.platform) $('#intPlatform').val(row.platform);

            initializeModal();

            // Pre-fill existing interview time
            if (row.interview_start && row.interview_end) {
                const startDate = new Date(row.interview_start);
                const pakistanStartDate = new Date(startDate.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));
                const endDate = new Date(row.interview_end);
                const pakistanEndDate = new Date(endDate.toLocaleString("en-US", {timeZone: "Asia/Karachi"}));

                // Select date (sets selectedDate and resets calendar)
                selectDate(pakistanStartDate);

                // Set times
                const startStr = pakistanStartDate.toTimeString().slice(0, 5);
                const endStr = pakistanEndDate.toTimeString().slice(0, 5);

                $('#intFromTime').val(startStr);
                $('#intToTime').val(endStr);

                // Trigger change to update duration logic
                updateDurationDisplay();
            }

            $('#rescheduleModal').removeClass('hidden');
        }

        $(document).on('click', '.reschedule-btn:not(:disabled)', function() {
            const row = table.row($(this).closest('tr')).data();
            openRescheduleModal(row);
        });

        $('#closeReschModal, #cancelReschModalBtn').on('click', () => $('#rescheduleModal').addClass('hidden'));

        $('#rescheduleForm').on('submit', async function(e) {
            e.preventDefault();

            if (currentStep !== 3) return;
            LoaderManager.showGlobal();
            try {
                const formDataObj = new FormData();
                formDataObj.append('action', 'reschedule_interview');
                formDataObj.append('id', currentCandidateId);
                formDataObj.append('platform', formData.platform);
                formDataObj.append('interview_start', `${formatPakistanDate(formData.date)} ${formData.startTime}:00`);
                formDataObj.append('interview_end', `${formatPakistanDate(formData.date)} ${formData.endTime}:00`);
                
                const res = await fetch('controller/registrations.php', {
                    method: 'POST',
                    body: formDataObj
                });
                const json = await res.json();

                if (json.success) {
                    showToast('success', 'Interview rescheduled successfully');
                    $('#rescheduleModal').addClass('hidden');
                    table.ajax.reload();
                } else {
                    if (json.type === 'conflict') {
                        showAlert('Time Slot Conflict', json.message);
                    } else {
                        showToast('error', json.message);
                    }
                }
            } catch (e) {
                showToast('error', e.message);
            } finally {
                LoaderManager.hideGlobal();
            }
        });

        // Hire
        function openHireModal(row) {
            $('#hireId').val(row.id);
            $('#hireName').val(row.name);
            $('#hireTechnology').val(row.technology);
            $('#hireModal').removeClass('hidden');
        }

        $(document).on('click', '.hire-btn', function() {
            const row = table.row($(this).closest('tr')).data();
            openHireModal(row);
        });

        $('#hireCancelBtn, #cancelHireBtn').on('click', () => $('#hireModal').addClass('hidden'));

        $('#hireForm').on('submit', async function(e) {
            e.preventDefault();

            if (!$('#hireTrainer').val()) {
                showToast('error', 'Please select a supervisor');
                return;
            }

            if (!confirm('Are you sure you want to hire this candidate?')) return;

            LoaderManager.showGlobal();
            try {
                const formData = new FormData(this);
                formData.append('action', 'update_hire_status');

                const res = await fetch('controller/registrations.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (json.success) {
                    showToast('success', 'Candidate hired successfully');
                    $('#hireModal').addClass('hidden');
                    table.ajax.reload();
                } else {
                    showToast('error', json.message);
                }
            } catch (e) {
                showToast('error', e.message);
            } finally {
                LoaderManager.hideGlobal();
            }
        });

        // Reject from table (using unique class)
        $(document).on('click', '.reject-table-btn', function() {
            const row = table.row($(this).closest('tr')).data();
            rejectCandidate(row.id, row.name, table);
        });

        // Initialize table - load all interviews
        table.ajax.reload();
    });
</script>
</body>

</html>