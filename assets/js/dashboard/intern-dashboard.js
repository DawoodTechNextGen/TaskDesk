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
                callbacks: {
                  label: function (context) {
                    let label = `Tasks Completed: ${context.parsed.y}`;
                    if (data.percentages && context.dataIndex > 0) {
                      const percentage = data.percentages[context.dataIndex];
                      const arrow = percentage >= 0 ? "↑" : "↓";
                      const color = percentage >= 0 ? "🟢" : "🔴";
                      label += ` ${color} ${arrow} ${Math.abs(percentage)}% MoM`;
                    }
                    return label;
                  },
                },
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








document.addEventListener('DOMContentLoaded', async function () {
  await newTasks();
  setInterval(newTasks, 500);
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

document.getElementById('update-new-tasks').addEventListener('click', async function () {
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

// Play sound on new event (e.g., new message, new task, etc.)
function playNotificationSound() {
  const sound = new Audio("notification.mp3");
  sound.play().catch(err => {
    // console.log("Sound blocked until user interacts:", err);
  });
}