// Intern Charts
function initializeInternCharts() {
  const { isDarkMode, textColor, gridColor, backgroundColor } =
    getThemeColors();

  // Task Progress Chart
  fetch("controller/dashboard.php?action=intern_monthly_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const internTaskCtx = document
          .getElementById("internTaskChart")
          .getContext("2d");
        new Chart(internTaskCtx, {
          type: "bar",
          data: {
            labels: data.months,
            datasets: [
              {
                label: "Tasks Completed",
                data: data.tasks,
                backgroundColor: "#3B82F6",
                borderColor: "#2563EB",
                borderWidth: 2,
                borderRadius: 4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                backgroundColor: backgroundColor,
                titleColor: textColor,
                bodyColor: textColor,
                borderColor: gridColor,
                borderWidth: 1,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: gridColor,
                },
                ticks: {
                  color: textColor,
                },
              },
              x: {
                grid: {
                  color: gridColor,
                },
                ticks: {
                  color: textColor,
                },
              },
            },
          },
        });
      }
    });

  // Weekly Hours Chart
  fetch("controller/dashboard.php?action=intern_weekly_hours")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const weeklyHoursCtx = document
          .getElementById("weeklyHoursChart")
          .getContext("2d");

        // Create gradient
        const gradient = weeklyHoursCtx.createLinearGradient(0, 0, 0, 300);
        if (isDarkMode) {
          gradient.addColorStop(0, "rgba(16, 185, 129, 0.3)");
          gradient.addColorStop(1, "rgba(16, 185, 129, 0.05)");
        } else {
          gradient.addColorStop(0, "rgba(16, 185, 129, 0.2)");
          gradient.addColorStop(1, "rgba(16, 185, 129, 0.02)");
        }

        new Chart(weeklyHoursCtx, {
          type: "line",
          data: {
            labels: data.days,
            datasets: [
              {
                label: "Hours Worked",
                data: data.hours,
                borderColor: "#10B981",
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: "#10B981",
                pointBorderColor: backgroundColor,
                pointBorderWidth: 2,
                pointRadius: 5,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                backgroundColor: backgroundColor,
                titleColor: textColor,
                bodyColor: textColor,
                borderColor: gridColor,
                borderWidth: 1,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: gridColor,
                },
                ticks: {
                  color: textColor,
                },
              },
              x: {
                grid: {
                  color: gridColor,
                },
                ticks: {
                  color: textColor,
                },
              },
            },
          },
        });
      }
    });
}

// Load performance stats
async function loadInternPerformance() {
  try {
    const response = await fetch(
      "controller/dashboard.php?action=intern_stats"
    );
    const data = await response.json();

    if (data.success) {
      document.getElementById("completionRate").textContent =
        data.completion_rate + "%";
      document.getElementById("onTimeRate").textContent =
        data.on_time_rate + "%";
      document.getElementById("avgCompletionTime").textContent =
        data.avg_completion_time + "d";
      document.getElementById("totalHours").textContent =
        data.total_hours + "h";
    }
  } catch (error) {
    console.error("Error loading intern performance:", error);
  }
}








    document.addEventListener('DOMContentLoaded', async function() {
        await newTasks();
        // await newApprovals();
        setInterval(newTasks, 500);
        // setInterval(newApprovals, 500);
    });

    async function newTasks() {
        const newTasks = document.getElementById('new-tasks');
        const span = newTasks.getElementsByTagName('span')[0]; // first <span>
        const notificationNumber = span ? span.textContent.trim() : 0;
        try {
            const response = await fetch('controller/notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get'
                })
            });

            const result = await response.json();

            if (result.success && result.data > 0) {
                if (notificationNumber != result.data) {
                    playNotificationSound();
                }
                newTasks.innerHTML = `
                 <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                        <g id="SVGRepo_iconCarrier">
                                            <path d="M21 16.0002C21 18.8286 21 20.2429 20.1213 21.1215C19.2426 22.0002 17.8284 22.0002 15 22.0002H9C6.17157 22.0002 4.75736 22.0002 3.87868 21.1215C3 20.2429 3 18.8286 3 16.0002V13.0002M16 4.00195C18.175 4.01406 19.3529 4.11051 20.1213 4.87889C21 5.75757 21 7.17179 21 10.0002V12.0002M8 4.00195C5.82497 4.01406 4.64706 4.11051 3.87868 4.87889C3.11032 5.64725 3.01385 6.82511 3.00174 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M9 17.5H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M8 3.5C8 2.67157 8.67157 2 9.5 2H14.5C15.3284 2 16 2.67157 16 3.5V4.5C16 5.32843 15.3284 6 14.5 6H9.5C8.67157 6 8 5.32843 8 4.5V3.5Z" stroke="currentColor" stroke-width="1.5"></path>
                                            <path d="M8 14H9M16 14H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M17 10.5H15M12 10.5H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        </g>
                                    </svg>
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-semibold 
                        w-4 h-4 flex items-center justify-center rounded-full shadow">
                        ${result.data}
                    </span>
                `;
            } else {
                // newTasks.innerHTML = "";
                // console.log('No new tasks or error:');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    // async function newApprovals() {
    //     const newApprovals = document.getElementById('new-approvals');
    //      const span = newApprovals.getElementsByTagName('span')[0]; // first <span>
    //     const notificationNumber = span ? span.textContent.trim() : 0;
    //     try {
    //         const response = await fetch('controller/notification.php', {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json'
    //             },
    //             body: JSON.stringify({
    //                 action: 'getApprovals'
    //             })
    //         });

    //         const result = await response.json();

    //         if (result.success && result.data > 0) {
    //               if (notificationNumber != result.data) {
    //                 playNotificationSound();
    //             }
    //             newApprovals.innerHTML = `
    //              <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    //                                     <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
    //                                     <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
    //                                     <g id="SVGRepo_iconCarrier">
    //                                         <path d="M8.5 12.5L10.5 14.5L15.5 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
    //                                         <path d="M3.02907 13.0776C2.7032 12.3958 2.7032 11.6032 3.02907 10.9214C3.16997 10.6266 3.41023 10.3447 3.89076 9.78084C4.08201 9.55642 4.17764 9.44421 4.25796 9.32437C4.44209 9.04965 4.56988 8.74114 4.63393 8.41669C4.66188 8.27515 4.6736 8.12819 4.69706 7.83426C4.75599 7.09576 4.78546 6.72651 4.89427 6.41844C5.14594 5.70591 5.7064 5.14546 6.41893 4.89378C6.72699 4.78497 7.09625 4.7555 7.83475 4.69657C8.12868 4.67312 8.27564 4.66139 8.41718 4.63344C8.74163 4.56939 9.05014 4.4416 9.32485 4.25747C9.4447 4.17715 9.55691 4.08152 9.78133 3.89027C10.3452 3.40974 10.6271 3.16948 10.9219 3.02859C11.6037 2.70271 12.3963 2.70271 13.0781 3.02859C13.3729 3.16948 13.6548 3.40974 14.2187 3.89027C14.4431 4.08152 14.5553 4.17715 14.6752 4.25747C14.9499 4.4416 15.2584 4.56939 15.5828 4.63344C15.7244 4.66139 15.8713 4.67312 16.1653 4.69657C16.9038 4.7555 17.273 4.78497 17.5811 4.89378C18.2936 5.14546 18.8541 5.70591 19.1058 6.41844M4.89427 17.5806C5.14594 18.2931 5.7064 18.8536 6.41893 19.1053C6.72699 19.2141 7.09625 19.2435 7.83475 19.3025C8.12868 19.3259 8.27564 19.3377 8.41718 19.3656C8.74163 19.4297 9.05014 19.5574 9.32485 19.7416C9.44469 19.8219 9.55691 19.9175 9.78133 20.1088C10.3452 20.5893 10.6271 20.8296 10.9219 20.9705C11.6037 21.2963 12.3963 21.2963 13.0781 20.9705C13.3729 20.8296 13.6548 20.5893 14.2187 20.1088C14.4431 19.9175 14.5553 19.8219 14.6752 19.7416C14.9499 19.5574 15.2584 19.4297 15.5828 19.3656C15.7244 19.3377 15.8713 19.3259 16.1653 19.3025C16.9038 19.2435 17.273 19.2141 17.5811 19.1053C18.2936 18.8536 18.8541 18.2931 19.1058 17.5806C19.2146 17.2725 19.244 16.9033 19.303 16.1648C19.3264 15.8709 19.3381 15.7239 19.3661 15.5824C19.4301 15.2579 19.5579 14.9494 19.7421 14.6747C19.8224 14.5548 19.918 14.4426 20.1093 14.2182C20.5898 13.6543 20.8301 13.3724 20.971 13.0776C21.2968 12.3958 21.2968 11.6032 20.971 10.9214" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
    //                                     </g>
    //                                 </svg>
    //                 <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-semibold 
    //                     w-4 h-4 flex items-center justify-center rounded-full shadow">
    //                     ${result.data}
    //                 </span>
    //             `;
    //         } else {
    //             // newTasks.innerHTML = "";
    //             // console.log('No approvals tasks or error:', result.message);
    //         }
    //     } catch (error) {
    //         console.error('Error:', error);
    //     }
    // }

    document.getElementById('update-new-tasks').addEventListener('click', async function() {
        const newTasks = document.getElementById('new-tasks');
        try {
            const response = await fetch('controller/notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update'
                })
            });

            const result = await response.json();

            if (result.success) {
                newTasks.innerHTML = `
                 <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                        <g id="SVGRepo_iconCarrier">
                                            <path d="M21 16.0002C21 18.8286 21 20.2429 20.1213 21.1215C19.2426 22.0002 17.8284 22.0002 15 22.0002H9C6.17157 22.0002 4.75736 22.0002 3.87868 21.1215C3 20.2429 3 18.8286 3 16.0002V13.0002M16 4.00195C18.175 4.01406 19.3529 4.11051 20.1213 4.87889C21 5.75757 21 7.17179 21 10.0002V12.0002M8 4.00195C5.82497 4.01406 4.64706 4.11051 3.87868 4.87889C3.11032 5.64725 3.01385 6.82511 3.00174 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M9 17.5H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M8 3.5C8 2.67157 8.67157 2 9.5 2H14.5C15.3284 2 16 2.67157 16 3.5V4.5C16 5.32843 15.3284 6 14.5 6H9.5C8.67157 6 8 5.32843 8 4.5V3.5Z" stroke="currentColor" stroke-width="1.5"></path>
                                            <path d="M8 14H9M16 14H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M17 10.5H15M12 10.5H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        </g>
                                    </svg>
                `;
            } else {
                // newTasks.innerHTML = "";
                console.log('No new tasks or error:', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // document.getElementById('update-new-approvals').addEventListener('click', async function() {
    //     const newApprovals = document.getElementById('new-approvals');
    //     try {
    //         const response = await fetch('controller/notification.php', {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json'
    //             },
    //             body: JSON.stringify({
    //                 action: 'updateApprovals'
    //             })
    //         });

    //         const result = await response.json();

    //         if (result.success) {
    //             newApprovals.innerHTML = `
    //              <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    //                                     <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
    //                                     <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
    //                                     <g id="SVGRepo_iconCarrier">
    //                                         <path d="M8.5 12.5L10.5 14.5L15.5 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
    //                                         <path d="M3.02907 13.0776C2.7032 12.3958 2.7032 11.6032 3.02907 10.9214C3.16997 10.6266 3.41023 10.3447 3.89076 9.78084C4.08201 9.55642 4.17764 9.44421 4.25796 9.32437C4.44209 9.04965 4.56988 8.74114 4.63393 8.41669C4.66188 8.27515 4.6736 8.12819 4.69706 7.83426C4.75599 7.09576 4.78546 6.72651 4.89427 6.41844C5.14594 5.70591 5.7064 5.14546 6.41893 4.89378C6.72699 4.78497 7.09625 4.7555 7.83475 4.69657C8.12868 4.67312 8.27564 4.66139 8.41718 4.63344C8.74163 4.56939 9.05014 4.4416 9.32485 4.25747C9.4447 4.17715 9.55691 4.08152 9.78133 3.89027C10.3452 3.40974 10.6271 3.16948 10.9219 3.02859C11.6037 2.70271 12.3963 2.70271 13.0781 3.02859C13.3729 3.16948 13.6548 3.40974 14.2187 3.89027C14.4431 4.08152 14.5553 4.17715 14.6752 4.25747C14.9499 4.4416 15.2584 4.56939 15.5828 4.63344C15.7244 4.66139 15.8713 4.67312 16.1653 4.69657C16.9038 4.7555 17.273 4.78497 17.5811 4.89378C18.2936 5.14546 18.8541 5.70591 19.1058 6.41844M4.89427 17.5806C5.14594 18.2931 5.7064 18.8536 6.41893 19.1053C6.72699 19.2141 7.09625 19.2435 7.83475 19.3025C8.12868 19.3259 8.27564 19.3377 8.41718 19.3656C8.74163 19.4297 9.05014 19.5574 9.32485 19.7416C9.44469 19.8219 9.55691 19.9175 9.78133 20.1088C10.3452 20.5893 10.6271 20.8296 10.9219 20.9705C11.6037 21.2963 12.3963 21.2963 13.0781 20.9705C13.3729 20.8296 13.6548 20.5893 14.2187 20.1088C14.4431 19.9175 14.5553 19.8219 14.6752 19.7416C14.9499 19.5574 15.2584 19.4297 15.5828 19.3656C15.7244 19.3377 15.8713 19.3259 16.1653 19.3025C16.9038 19.2435 17.273 19.2141 17.5811 19.1053C18.2936 18.8536 18.8541 18.2931 19.1058 17.5806C19.2146 17.2725 19.244 16.9033 19.303 16.1648C19.3264 15.8709 19.3381 15.7239 19.3661 15.5824C19.4301 15.2579 19.5579 14.9494 19.7421 14.6747C19.8224 14.5548 19.918 14.4426 20.1093 14.2182C20.5898 13.6543 20.8301 13.3724 20.971 13.0776C21.2968 12.3958 21.2968 11.6032 20.971 10.9214" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
    //                                     </g>
    //                                 </svg>
    //             `;
    //         } else {
    //             // newTasks.innerHTML = "";
    //             console.log('No new tasks or error:', result.message);
    //         }
    //     } catch (error) {
    //         console.error('Error:', error);
    //     }
    // });
    // Play sound on new event (e.g., new message, new task, etc.)
    function playNotificationSound() {
        const sound = new Audio("notification.mp3");
        sound.play().catch(err => {
            // console.log("Sound blocked until user interacts:", err);
        });
    }