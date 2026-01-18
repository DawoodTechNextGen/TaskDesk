 <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 px-6">
     <div class="flex flex-col md:flex-row items-center justify-between">
         <p class="text-sm text-gray-500 dark:text-gray-400">Developed with <span style="color: red;">&#10084;</span> by <a href="https://logicblaze.co/" style="text-decoration:none;"><strong>LogicBlaze Technologies</strong></a></p>
         <div class="flex space-x-6 mt-4 md:mt-0 ">
             <a href="https://dawoodtechnextgen.org/terms-and-conditions/" target="_blank"
                 class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Terms</a>
             <a href="https://dawoodtechnextgen.org/privacy-policy/" target="_blank"
                 class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Privacy</a>
             <a href="https://dawoodtechnextgen.org/contact/" target="_blank"
                 class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Contact</a>
         </div>
     </div>
 </footer>
 <!-- Add this script to your main dashboard or admin page -->
 <script>
     // Function to process email queue
     async function processEmailQueue() {
         try {
             const response = await fetch('queue/email_queue.php');
             const text = await response.text();
             console.log('Email queue processed:', text);

             // Optional: Show notification
             if (text.includes('emails sent')) {
                 //  showQueueNotification(text);
                //  console.log('Email queue processor stopped.');
             }
         } catch (error) {
             console.error('Failed to process email queue:', error);
         }
     }

     // Function to show notification
     function showQueueNotification(message) {
         const notification = document.createElement('div');
         notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
         notification.textContent = message;
         notification.id = 'queue-notification';

         document.body.appendChild(notification);

         // Remove notification after 5 seconds
         setTimeout(() => {
             notification.remove();
         }, 5000);
     }

     // Start processing every 2 minutes (120,000 milliseconds)
     let queueInterval;

     // Function to start the queue processor
     function startQueueProcessor() {
         // Process immediately on start
         processEmailQueue();

         // Then process every 2 minutes
         queueInterval = setInterval(processEmailQueue, 120000);

        //  console.log('Email queue processor started. Will run every 2 minutes.');
     }

     // Function to stop the queue processor
     function stopQueueProcessor() {
         if (queueInterval) {
             clearInterval(queueInterval);
            //  console.log('Email queue processor stopped.');
         }
     }

     // Start when page loads (for admin pages)
     document.addEventListener('DOMContentLoaded', () => {
         // Only start if user is admin/supervisor (optional)
         const userRole = <?php echo $_SESSION['user_role'] ?? 0; ?>;
         const isAdmin = userRole == 1 || userRole == 4;

         if (isAdmin) {
             startQueueProcessor();

             // Optional: Add controls to start/stop
             // addQueueControls();
         }
     });

     // Optional: Add UI controls
     function addQueueControls() {
         const controls = document.createElement('div');
         controls.className = 'fixed bottom-20 right-4 bg-gray-800 text-white p-3 rounded-lg shadow-lg z-40';
         controls.innerHTML = `
        <div class="text-sm mb-2">Email Queue Processor</div>
        <div class="flex gap-2">
            <button id="process-now" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm">
                Process Now
            </button>
            <button id="stop-queue" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                Stop
            </button>
        </div>
        <div class="mt-2 text-xs text-gray-400" id="last-process">Last run: Never</div>
    `;

         document.body.appendChild(controls);

         // Add event listeners
         document.getElementById('process-now').addEventListener('click', () => {
             processEmailQueue();
             updateLastProcessTime();
         });

         document.getElementById('stop-queue').addEventListener('click', stopQueueProcessor);

         // Update last process time
         function updateLastProcessTime() {
             const now = new Date();
             document.getElementById('last-process').textContent =
                 `Last run: ${now.toLocaleTimeString()}`;
         }
     }
 </script>