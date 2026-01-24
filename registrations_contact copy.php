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
$page_title = 'Contact List - TaskDesk';
include_once "./include/headerLinks.php";
?>

<style>
    /* ... (Same styles as registrations.php) ... */
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
</style>
<style>
    /* Add these styles for conflict highlighting */
    .time-slot-conflict {
        border-color: #f87171 !important;
        background-color: #fef2f2 !important;
        color: #dc2626 !important;
    }
    
    .time-slot-conflict:focus {
        border-color: #dc2626 !important;
        ring-color: #fca5a5 !important;
    }
    
    /* Alert animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    #timeSlotAlert > div {
        animation: fadeIn 0.3s ease-out;
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

                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Contact List</h2>
                </div>

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="table-container">
                        <div id="tableLoader" class="table-loader p-8">
                            <div class="flex justify-center items-center space-x-4">
                                <div class="loader"></div>
                                <span class="text-gray-600 dark:text-gray-300">Loading contacts...</span>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="contactTable" class="min-w-full">
                                <thead class="text-sm text-gray-800 dark:text-gray-50"></thead>
                                <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Schedule Interview Modal -->
            <!-- Schedule Interview Modal -->
            <div id="interviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Schedule Interview - Step 1 of 3</h3>
                        <button type="button" id="closeIntModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Progress Steps -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">1</div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Basic Info</span>
                            </div>
                            <div class="h-1 flex-1 mx-4 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex items-center space-x-2 opacity-50">
                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-semibold">2</div>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Date & Time</span>
                            </div>
                            <div class="h-1 flex-1 mx-4 bg-gray-200 dark:bg-gray-700 opacity-50"></div>
                            <div class="flex items-center space-x-2 opacity-50">
                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-semibold">3</div>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirm</span>
                            </div>
                        </div>
                    </div>

                    <form id="interviewForm" class="space-y-6">
                        <input type="hidden" id="intId" name="id">

                        <!-- Step 1: Basic Information -->
                        <div id="step1" class="space-y-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Candidate Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Name</label>
                                        <input type="text" id="intName" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-800 dark:text-gray-200" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Email</label>
                                        <input type="text" id="intEmail" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-800 dark:text-gray-200" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact</label>
                                        <input type="text" id="intContact" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-800 dark:text-gray-200" readonly>
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
                                    <select id="intTech" name="technology_id" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <option value="">-- Select Technology --</option>
                                        <?php
                                        $techQ = "SELECT id, name FROM technologies ORDER BY name";
                                        $techR = mysqli_query($conn, $techQ);
                                        while ($row = mysqli_fetch_assoc($techR)) {
                                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <div id="techError" class="text-red-500 text-xs mt-1 hidden">Please select a technology</div>
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
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 text-center">Confirm Interview Details</h4>

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
                                            Please review all details before scheduling the interview.
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
                                <button type="button" id="cancelIntModal" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
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
                                    Schedule Interview
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            <style>
                /* Calendar styling */
                #calendarDays button {
                    min-height: 2.5rem;
                }

                #calendarDays button:hover:not(:disabled) {
                    transform: scale(1.05);
                    transition: transform 0.2s ease;
                }

                #calendarDays button:disabled {
                    cursor: not-allowed;
                }

                /* Modal scrollbar styling */
                #interviewModal div[class*="max-h-"]::-webkit-scrollbar {
                    width: 6px;
                }

                #interviewModal div[class*="max-h-"]::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 3px;
                }

                #interviewModal div[class*="max-h-"]::-webkit-scrollbar-thumb {
                    background: #888;
                    border-radius: 3px;
                }

                #interviewModal div[class*="max-h-"]::-webkit-scrollbar-thumb:hover {
                    background: #555;
                }

                .dark #interviewModal div[class*="max-h-"]::-webkit-scrollbar-track {
                    background: #374151;
                }

                .dark #interviewModal div[class*="max-h-"]::-webkit-scrollbar-thumb {
                    background: #6b7280;
                }

                .dark #interviewModal div[class*="max-h-"]::-webkit-scrollbar-thumb:hover {
                    background: #9ca3af;
                }
            </style>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
<script>
    // Step-by-step modal functionality
    let currentStep = 1;
    let selectedDate = null;
    let currentDate = new Date();
    let formData = {};
    let table; // DataTable instance
    let currentCandidateId = null; // Store current candidate ID

    // Initialize when modal opens
    function initializeModal() {
        currentStep = 1;
        selectedDate = null;
        currentDate = new Date();
        updateProgress();
        updateStepDisplay();
        initCalendar();
        setDefaultTimes();
        
        // Reset all errors
        document.getElementById('platformError').classList.add('hidden');
        document.getElementById('techError').classList.add('hidden');
        document.getElementById('dateError').classList.add('hidden');
        document.getElementById('timeError').classList.add('hidden');
        
        // Reset border colors
        document.getElementById('intPlatform').classList.remove('border-red-500');
        document.getElementById('intTech').classList.remove('border-red-500');
        
        // Clear booked slots display
        document.getElementById('bookedSlotsDisplay').classList.add('hidden');
        document.getElementById('slotsList').innerHTML = '';
        
        // Hide duration display
        document.getElementById('durationDisplay').classList.add('hidden');
        
        // Clear conflict indicators
        document.getElementById('intFromTime').classList.remove('time-slot-conflict');
        document.getElementById('intToTime').classList.remove('time-slot-conflict');
    }

    // Update progress indicators
    function updateProgress() {
        // Update title
        document.querySelector('#interviewModal h3').textContent = `Schedule Interview - Step ${currentStep} of 3`;
        
        // Update progress steps
        const steps = document.querySelectorAll('#interviewModal .flex.items-center.space-x-2');
        steps.forEach((step, index) => {
            const stepNumber = step.querySelector('div');
            const stepText = step.querySelector('span');
            
            if (index + 1 === currentStep) {
                // Current step
                stepNumber.className = 'w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold';
                stepText.className = 'text-sm font-medium text-gray-700 dark:text-gray-300';
            } else if (index + 1 < currentStep) {
                // Completed step
                stepNumber.className = 'w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-semibold';
                stepText.className = 'text-sm font-medium text-gray-700 dark:text-gray-300';
            } else {
                // Upcoming step
                stepNumber.className = 'w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-semibold';
                stepText.className = 'text-sm font-medium text-gray-500 dark:text-gray-400';
            }
        });
    }

    // Update step display
    function updateStepDisplay() {
        // Hide all steps
        document.getElementById('step1').classList.add('hidden');
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('step3').classList.add('hidden');
        
        // Show current step
        document.getElementById(`step${currentStep}`).classList.remove('hidden');
        
        // Update buttons
        const prevBtn = document.getElementById('prevStepBtn');
        const nextBtn = document.getElementById('nextStepBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        if (currentStep === 1) {
            prevBtn.classList.add('hidden');
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
            nextBtn.textContent = 'Next Step';
        } else if (currentStep === 2) {
            prevBtn.classList.remove('hidden');
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
            nextBtn.textContent = 'Review & Schedule';
        } else if (currentStep === 3) {
            prevBtn.classList.remove('hidden');
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
            updateConfirmationDetails();
        }
    }

    // Validate step 1
    function validateStep1() {
        const platform = document.getElementById('intPlatform').value;
        const tech = document.getElementById('intTech').value;
        let isValid = true;
        
        // Reset errors
        document.getElementById('platformError').classList.add('hidden');
        document.getElementById('techError').classList.add('hidden');
        
        // Validate platform
        if (!platform) {
            document.getElementById('platformError').classList.remove('hidden');
            document.getElementById('intPlatform').classList.add('border-red-500');
            isValid = false;
        } else {
            document.getElementById('intPlatform').classList.remove('border-red-500');
        }
        
        // Validate technology
        if (!tech) {
            document.getElementById('techError').classList.remove('hidden');
            document.getElementById('intTech').classList.add('border-red-500');
            isValid = false;
        } else {
            document.getElementById('intTech').classList.remove('border-red-500');
        }
        
        if (isValid) {
            formData.platform = platform;
            formData.technology = tech;
            formData.technologyText = document.getElementById('intTech').selectedOptions[0].text;
        }
        
        return isValid;
    }

    // Validate step 2
    function validateStep2() {
        const startTime = document.getElementById('intFromTime').value;
        const endTime = document.getElementById('intToTime').value;
        let isValid = true;
        
        // Reset errors
        document.getElementById('dateError').classList.add('hidden');
        document.getElementById('timeError').classList.add('hidden');
        
        // Validate date
        if (!selectedDate) {
            document.getElementById('dateError').classList.remove('hidden');
            isValid = false;
        }
        
        // Validate time
        if (!startTime || !endTime) {
            document.getElementById('timeError').classList.remove('hidden');
            document.getElementById('timeError').textContent = 'Please select both start and end time';
            isValid = false;
        } else if (startTime >= endTime) {
            document.getElementById('timeError').classList.remove('hidden');
            document.getElementById('timeError').textContent = 'End time must be after start time';
            isValid = false;
        }
        
        // Validate duration (minimum 15 minutes)
        if (startTime && endTime) {
            const start = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);
            const duration = (end - start) / (1000 * 60);
            
            if (duration < 15) {
                document.getElementById('timeError').classList.remove('hidden');
                document.getElementById('timeError').textContent = 'Minimum duration is 15 minutes';
                isValid = false;
            }
            
            // Update duration display
            if (duration > 0) {
                document.getElementById('durationDisplay').classList.remove('hidden');
                const hours = Math.floor(duration / 60);
                const minutes = duration % 60;
                let durationText = '';
                if (hours > 0) {
                    durationText += `${hours} hour${hours > 1 ? 's' : ''} `;
                }
                if (minutes > 0) {
                    durationText += `${minutes} minute${minutes > 1 ? 's' : ''}`;
                }
                document.getElementById('durationText').textContent = durationText.trim();
            }
        }
        
        if (isValid) {
            formData.date = selectedDate;
            formData.startTime = startTime;
            formData.endTime = endTime;
            formData.dateText = document.getElementById('displayDate').textContent;
            
            // Calculate duration for display
            const start = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);
            const duration = (end - start) / (1000 * 60);
            formData.duration = duration;
        }
        
        return isValid;
    }

    // Update confirmation details
    function updateConfirmationDetails() {
        document.getElementById('confirmName').textContent = formData.name;
        document.getElementById('confirmContact').textContent = formData.contact;
        document.getElementById('confirmPlatform').textContent = formData.platform;
        document.getElementById('confirmTech').textContent = formData.technologyText;
        
        // Format date and time
        const date = new Date(selectedDate);
        const options = { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        };
        const dateStr = date.toLocaleDateString('en-US', options);
        document.getElementById('confirmDateTime').textContent = `${dateStr} | ${formData.startTime} - ${formData.endTime}`;
        
        // Format duration
        const hours = Math.floor(formData.duration / 60);
        const minutes = formData.duration % 60;
        let durationText = '';
        if (hours > 0) {
            durationText += `${hours} hour${hours > 1 ? 's' : ''} `;
        }
        if (minutes > 0) {
            durationText += `${minutes} minute${minutes > 1 ? 's' : ''}`;
        }
        document.getElementById('confirmDuration').textContent = durationText.trim();
    }

    // Calendar functionality
    function initCalendar() {
        renderCalendar(currentDate);
    }

    function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();

        // Update current month display
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;

        // Get first day of month and total days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const totalDays = lastDay.getDate();
        const startingDay = firstDay.getDay();

        // Clear previous calendar
        const calendarDays = document.getElementById('calendarDays');
        calendarDays.innerHTML = '';

        // Add empty cells for days before first day of month
        for (let i = 0; i < startingDay; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'h-10 flex items-center justify-center';
            calendarDays.appendChild(emptyCell);
        }

        // Add days of the month
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let day = 1; day <= totalDays; day++) {
            const dayCell = document.createElement('button');
            dayCell.type = 'button';
            dayCell.className = 'h-10 flex items-center justify-center rounded-lg text-sm font-medium transition-colors';

            const cellDate = new Date(year, month, day);

            // Check if it's today
            if (cellDate.getTime() === today.getTime()) {
                dayCell.classList.add('bg-indigo-100', 'dark:bg-indigo-900', 'text-indigo-700', 'dark:text-indigo-300');
            }

            // Check if it's selected
            if (selectedDate && selectedDate.getTime() === cellDate.getTime()) {
                dayCell.classList.add('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            } else {
                dayCell.classList.add('hover:bg-gray-200', 'dark:hover:bg-gray-600', 'text-gray-800', 'dark:text-gray-200');
            }

            // Disable past dates
            if (cellDate < today) {
                dayCell.classList.add('opacity-50', 'cursor-not-allowed');
                dayCell.disabled = true;
            }

            dayCell.textContent = day;
            dayCell.dataset.date = cellDate.toISOString().split('T')[0];

            dayCell.addEventListener('click', () => selectDate(cellDate));
            calendarDays.appendChild(dayCell);
        }
    }

    async function selectDate(date) {
        selectedDate = date;
        const dateStr = date.toISOString().split('T')[0];
        
        // Update display
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        document.getElementById('displayDate').textContent = date.toLocaleDateString('en-US', options);
        
        // Hide date error if shown
        document.getElementById('dateError').classList.add('hidden');
        
        // Fetch and display booked slots
        await updateTimeSlotDisplay(dateStr);
        
        // Clear any time conflict indicators
        document.getElementById('intFromTime').classList.remove('time-slot-conflict');
        document.getElementById('intToTime').classList.remove('time-slot-conflict');
        document.getElementById('timeError').classList.add('hidden');
        
        // Re-render calendar to update selection
        renderCalendar(currentDate);
    }

    // Set default times
    function setDefaultTimes() {
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        document.getElementById('intFromTime').value = now.toTimeString().slice(0, 5);

        now.setHours(0, 0, 0, 0);
        document.getElementById('intToTime').value = now.toTimeString().slice(0, 5);
        
        // Update duration display
        updateDurationDisplay();
    }

    // Update duration display
    function updateDurationDisplay() {
        const startTime = document.getElementById('intFromTime').value;
        const endTime = document.getElementById('intToTime').value;
        
        if (startTime && endTime) {
            const start = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);
            const duration = (end - start) / (1000 * 60);
            
            if (duration > 0) {
                document.getElementById('durationDisplay').classList.remove('hidden');
                const hours = Math.floor(duration / 60);
                const minutes = duration % 60;
                let durationText = '';
                if (hours > 0) {
                    durationText += `${hours} hour${hours > 1 ? 's' : ''} `;
                }
                if (minutes > 0) {
                    durationText += `${minutes} minute${minutes > 1 ? 's' : ''}`;
                }
                document.getElementById('durationText').textContent = durationText.trim();
            }
        }
    }

    // Validate time input in real-time
    async function validateTimeInput() {
        const startTime = document.getElementById('intFromTime').value;
        const endTime = document.getElementById('intToTime').value;
        
        if (!startTime || !endTime || !selectedDate) {
            return false;
        }
        
        // Check basic time validation
        if (startTime >= endTime) {
            document.getElementById('timeError').classList.remove('hidden');
            document.getElementById('timeError').textContent = 'End time must be after start time';
            document.getElementById('intFromTime').classList.add('border-red-500');
            document.getElementById('intToTime').classList.add('border-red-500');
            return false;
        }
        
        // Check duration (minimum 15 minutes)
        const start = new Date(`2000-01-01 ${startTime}`);
        const end = new Date(`2000-01-01 ${endTime}`);
        const duration = (end - start) / (1000 * 60);
        
        if (duration < 15) {
            document.getElementById('timeError').classList.remove('hidden');
            document.getElementById('timeError').textContent = 'Minimum duration is 15 minutes';
            document.getElementById('intFromTime').classList.add('border-red-500');
            document.getElementById('intToTime').classList.add('border-red-500');
            return false;
        }
        
        // Clear basic errors
        document.getElementById('timeError').classList.add('hidden');
        document.getElementById('intFromTime').classList.remove('border-red-500');
        document.getElementById('intToTime').classList.remove('border-red-500');
        
        // Check for time slot conflicts
        const selectedDateStr = selectedDate.toISOString().split('T')[0];
        const conflictCheck = await checkTimeSlotConflict(selectedDateStr, startTime, endTime, currentCandidateId);
        
        if (conflictCheck.hasConflict) {
            // Show time slot conflict error
            document.getElementById('timeError').classList.remove('hidden');
            document.getElementById('timeError').textContent = conflictCheck.message || 'Time slot is already booked';
            document.getElementById('intFromTime').classList.add('time-slot-conflict');
            document.getElementById('intToTime').classList.add('time-slot-conflict');
            
            // Show alert for conflict
            showAlert('Time Slot Conflict', conflictCheck.message || 'This time slot is already booked. Please choose a different time.');
            return false;
        }
        
        // Clear conflict indicators
        document.getElementById('intFromTime').classList.remove('time-slot-conflict');
        document.getElementById('intToTime').classList.remove('time-slot-conflict');
        return true;
    }

    // Function to fetch booked slots for a date
    async function fetchBookedSlots(date) {
        try {
            const params = new URLSearchParams({
                action: 'get_booked_slots',
                date: date
            });

            const res = await fetch(`controller/registrations.php?${params}`);
            return await res.json();
        } catch (error) {
            console.error('Error fetching booked slots:', error);
            return {
                success: false,
                slots: []
            };
        }
    }

    // Update time slot display
    async function updateTimeSlotDisplay(date) {
        const bookedSlots = await fetchBookedSlots(date);
        const slotsContainer = document.getElementById('bookedSlotsDisplay');
        const slotsList = document.getElementById('slotsList');

        if (bookedSlots.success && bookedSlots.slots.length > 0) {
            slotsContainer.classList.remove('hidden');
            
            const slotsHTML = bookedSlots.slots.map(slot =>
                `<div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600 mb-1">
                    <span class="text-gray-700 dark:text-gray-300 text-xs">${slot.formatted_start} - ${slot.formatted_end}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">${slot.name}</span>
                </div>`
            ).join('');
            
            slotsList.innerHTML = slotsHTML;
        } else {
            slotsContainer.classList.add('hidden');
        }
    }

    // Function to check time slot conflict
    async function checkTimeSlotConflict(selectedDate, startTime, endTime, candidateId) {
        try {
            const interviewStart = `${selectedDate} ${startTime}:00`;
            const interviewEnd = `${selectedDate} ${endTime}:00`;

            const formDataObj = new FormData();
            formDataObj.append('id', candidateId);
            formDataObj.append('interview_start', interviewStart);
            formDataObj.append('interview_end', interviewEnd);
            formDataObj.append('action', 'check_conflict');

            const res = await fetch('controller/registrations.php', {
                method: 'POST',
                body: formDataObj
            });

            const result = await res.json();
            
            // Return a simplified object
            return {
                hasConflict: !result.success && result.type === 'conflict',
                message: result.message,
                conflicts: result.conflicts || []
            };
        } catch (error) {
            console.error('Error checking conflict:', error);
            return {
                hasConflict: false,
                message: 'Error checking time slot'
            };
        }
    }

    // Function to show alert (replaces conflict modal)
    function showAlert(title, message) {
        // Create alert element
        const alertHTML = `
            <div id="timeSlotAlert" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
                    <div class="flex items-center mb-4">
                        <div class="mr-3 p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white">${title}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Time slot unavailable</p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <div class="text-sm text-gray-700 dark:text-gray-300 mb-2">${message}</div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded font-medium text-blue-700 dark:text-blue-300 text-sm">
                            Please select a different time slot.
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="closeAlert()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            OK, I'll choose another time
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Remove existing alert if any
        const existingAlert = document.getElementById('timeSlotAlert');
        if (existingAlert) existingAlert.remove();

        // Add new alert
        document.body.insertAdjacentHTML('beforeend', alertHTML);
    }

    function closeAlert() {
        const alert = document.getElementById('timeSlotAlert');
        if (alert) alert.remove();
    }

    // Main form submission
    async function submitInterviewForm() {
        const selectedDateStr = selectedDate.toISOString().split('T')[0];
        const startTime = document.getElementById('intFromTime').value;
        const endTime = document.getElementById('intToTime').value;
        const candidateId = document.getElementById('intId').value;
        
        // Final validation
        if (!validateStep2()) {
            return;
        }
        
        // Check for conflicts one more time
        const conflictCheck = await checkTimeSlotConflict(selectedDateStr, startTime, endTime, candidateId);
        if (conflictCheck.hasConflict) {
            showAlert('Time Slot Conflict', conflictCheck.message || 'This time slot is already booked. Please choose a different time.');
            return;
        }
        
        LoaderManager.showGlobal();
        try {
            const formDataObj = new FormData();
            formDataObj.append('id', candidateId);
            formDataObj.append('platform', document.getElementById('intPlatform').value);
            formDataObj.append('technology_id', document.getElementById('intTech').value);
            formDataObj.append('interview_start', `${selectedDateStr} ${startTime}:00`);
            formDataObj.append('interview_end', `${selectedDateStr} ${endTime}:00`);
            formDataObj.append('action', 'schedule_interview');

            const res = await fetch('controller/registrations.php', {
                method: 'POST',
                body: formDataObj
            });
            const json = await res.json();

            if (json.success) {
                showToast('success', 'Interview scheduled successfully');
                closeModal();
                if (table) table.ajax.reload();
            } else {
                if (json.type === 'conflict') {
                    showAlert('Time Slot Conflict', json.message || 'This time slot is already booked. Please choose a different time.');
                } else {
                    showToast('error', json.message || 'Scheduling failed');
                }
            }
        } catch (e) {
            showToast('error', 'Error: ' + e.message);
        } finally {
            LoaderManager.hideGlobal();
        }
    }

    // Open modal function (called from table)
    function openInterviewModal(candidateId, candidateName, candidateEmail, candidateContact) {
        currentCandidateId = candidateId;
        
        // Set form values
        document.getElementById('intId').value = candidateId;
        document.getElementById('intName').value = candidateName;
        document.getElementById('intEmail').value = candidateEmail;
        document.getElementById('intContact').value = candidateContact;
        
        // Store in formData for confirmation
        formData.name = candidateName;
        formData.contact = candidateContact;
        formData.email = candidateEmail;
        
        // Initialize modal (DO NOT reset form values)
        currentStep = 1;
        selectedDate = null;
        currentDate = new Date();
        updateProgress();
        updateStepDisplay();
        initCalendar();
        setDefaultTimes();
        
        // Reset all errors
        document.getElementById('platformError').classList.add('hidden');
        document.getElementById('techError').classList.add('hidden');
        document.getElementById('dateError').classList.add('hidden');
        document.getElementById('timeError').classList.add('hidden');
        
        // Reset border colors
        document.getElementById('intPlatform').classList.remove('border-red-500');
        document.getElementById('intTech').classList.remove('border-red-500');
        
        // Clear booked slots display
        document.getElementById('bookedSlotsDisplay').classList.add('hidden');
        document.getElementById('slotsList').innerHTML = '';
        
        // Hide duration display
        document.getElementById('durationDisplay').classList.add('hidden');
        
        // Clear conflict indicators
        document.getElementById('intFromTime').classList.remove('time-slot-conflict');
        document.getElementById('intToTime').classList.remove('time-slot-conflict');
        
        // Show modal
        document.getElementById('interviewModal').classList.remove('hidden');
        
        // Set today's date
        selectedDate = new Date();
        selectDate(selectedDate);
    }

    // Close modal
    function closeModal() {
        document.getElementById('interviewModal').classList.add('hidden');
        // Don't reset form values - they'll be set when modal opens
    }

    // Utility functions (from your existing code)
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
        toast.className = `px-5 py-3 rounded-lg text-white shadow-lg ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        toast.textContent = msg;
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Step navigation
        document.getElementById('nextStepBtn').addEventListener('click', function() {
            if (currentStep === 1) {
                if (validateStep1()) {
                    currentStep++;
                    updateProgress();
                    updateStepDisplay();
                }
            } else if (currentStep === 2) {
                if (validateStep2()) {
                    // Check for time slot conflicts before proceeding
                    const selectedDateStr = selectedDate ? selectedDate.toISOString().split('T')[0] : '';
                    const startTime = document.getElementById('intFromTime').value;
                    const endTime = document.getElementById('intToTime').value;
                    
                    if (selectedDateStr && startTime && endTime) {
                        // Show loading
                        const originalText = this.textContent;
                        this.textContent = 'Checking availability...';
                        this.disabled = true;
                        
                        setTimeout(async () => {
                            const conflictCheck = await checkTimeSlotConflict(selectedDateStr, startTime, endTime, currentCandidateId);
                            this.textContent = originalText;
                            this.disabled = false;
                            
                            if (conflictCheck.hasConflict) {
                                showAlert('Time Slot Conflict', conflictCheck.message || 'This time slot is already booked. Please choose a different time.');
                                return;
                            }
                            
                            currentStep++;
                            updateProgress();
                            updateStepDisplay();
                        }, 500);
                    } else {
                        currentStep++;
                        updateProgress();
                        updateStepDisplay();
                    }
                }
            }
        });

        document.getElementById('prevStepBtn').addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateProgress();
                updateStepDisplay();
            }
        });

        // Calendar navigation
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar(currentDate);
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar(currentDate);
        });

        // Time input change - validate on change
        document.getElementById('intFromTime').addEventListener('change', async function() {
            updateDurationDisplay();
            await validateTimeInput();
        });

        document.getElementById('intToTime').addEventListener('change', async function() {
            updateDurationDisplay();
            await validateTimeInput();
        });

        // Real-time validation for select fields
        document.getElementById('intPlatform').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('platformError').classList.add('hidden');
                this.classList.remove('border-red-500');
            }
        });

        document.getElementById('intTech').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('techError').classList.add('hidden');
                this.classList.remove('border-red-500');
            }
        });

        // Modal close
        document.getElementById('closeIntModal').addEventListener('click', closeModal);
        document.getElementById('cancelIntModal').addEventListener('click', closeModal);
        document.getElementById('interviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Form submission
        document.getElementById('interviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Final validation
            if (currentStep !== 3) return;
            
            await submitInterviewForm();
        });

        // DataTable initialization
        const expandableColumns = ['email', 'cnic', 'city', 'country', 'created_at'];
        const headerMap = {
            id: 'ID',
            name: 'Name',
            email: 'Email',
            mbl_number: 'Contact',
            technology: 'Technology',
            internship_type: 'Internship Type',
            experience: 'Experience',
            cnic: 'CNIC',
            city: 'City',
            country: 'Country',
            created_at: 'Created At'
        };

        function formatDetails(row) {
            return `<div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg grid grid-cols-2 gap-4 text-sm">${expandableColumns.map(k => `<div><span class="font-semibold">${headerMap[k]}:</span> <span>${escapeHTML(row[k] ?? '-')}</span></div>`).join('')}</div>`;
        }

        // Build table header
        $('#contactTable thead').html(`<tr><th></th><th>ID</th><th>Name</th><th>Contact</th><th>Technology</th><th>Internship Type</th><th>Experience</th><th>Actions</th></tr>`);

        table = $('#contactTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: 'controller/registrations.php',
                type: 'GET',
                data: function(d) {
                    d.action = 'contact';
                }
            },
            columns: [{
                    class: 'details-control cursor-pointer text-center font-bold',
                    orderable: false,
                    data: null,
                    defaultContent: '<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>'
                },
                {
                    data: 'id'
                },
                {
                    data: 'name'
                },
                {
                    data: 'mbl_number'
                },
                {
                    data: 'technology'
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
                    render: function(data, type, row) {
                        return `
                            <div>
                                <button class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs schedule-btn" data-id="${row.id}">Schedule Interview</button>
                            </div>`;
                    }
                }
            ],
            order: [
                [1, 'desc']
            ],
            language: {
                processing: 'Processing...',
                emptyTable: 'No contacts found',
                zeroRecords: 'No contacts found'
            }
        });

        // Row details expansion
        $('#contactTable tbody').on('click', 'td.details-control', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
            } else {
                row.child(formatDetails(row.data())).show();
                tr.addClass('shown');
            }
        });

        // Open Schedule Modal from table
        $(document).on('click', '.schedule-btn', function() {
            const tr = $(this).closest('tr');
            const row = table.row(tr).data();
            
            // Get the selected row's technology name
            const rowTechnology = row.technology;
            
            // Find the matching option in the select
            const techSelect = document.getElementById('intTech');
            for (let i = 0; i < techSelect.options.length; i++) {
                if (techSelect.options[i].text === rowTechnology) {
                    techSelect.selectedIndex = i;
                    break;
                }
            }
            
            openInterviewModal(row.id, row.name, row.email, row.mbl_number);
        });
    });
</script>
</body>

</html>