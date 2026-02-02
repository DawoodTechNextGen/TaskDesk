// Supervisor Charts
// COMPLETE FIXED Supervisor Charts
function initializeSupervisorCharts() {
  const { isDarkMode, textColor, gridColor, backgroundColor } =
    getThemeColors();

  // Team Performance
  fetch("controller/dashboard.php?action=supervisor_team_performance")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const ctx = document
          .getElementById("teamPerformanceChart")
          .getContext("2d");

        new Chart(ctx, {
          type: "radar",
          data: {
            labels: data.labels, // course names
            datasets: [
              {
                label: "Completion Rate",
                data: data.completion,
                borderColor: "#3B82F6",
                backgroundColor: "rgba(59, 130, 246, 0.2)",
                pointBackgroundColor: "#3B82F6",
                borderWidth: 2,
              },
              {
                label: "On-time Delivery",
                data: data.on_time,
                borderColor: "#10B981",
                backgroundColor: "rgba(16, 185, 129, 0.2)",
                pointBackgroundColor: "#10B981",
                borderWidth: 2,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: true, // Allows hiding datasets
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
              r: {
                min: 0,
                max: 100,
                grid: { color: gridColor },
                angleLines: { color: gridColor },
                pointLabels: {
                  color: textColor,
                  font: { size: 11 },
                },
                ticks: {
                  backdropColor: "transparent",
                  color: textColor,
                },
              },
            },
          },
        });
      }
    });

  // Task Status Overview
  fetch("controller/dashboard.php?action=supervisor_task_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const supervisorTaskCtx = document
          .getElementById("supervisorTaskChart")
          .getContext("2d");
        new Chart(supervisorTaskCtx, {
          type: "bar",
          data: {
            labels: data.labels,
            datasets: [
              {
                label: "Tasks",
                data: data.values,
                backgroundColor: ["#10B981", "#F59E0B", "#6B7280", "#EF4444"],
                borderWidth: 0,
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
}

// Load supervisor stats
async function loadSupervisorStats() {
  try {
    const response = await fetch(
      "controller/dashboard.php?action=supervisor_stats",
    );
    const data = await response.json();

    if (data.success) {
      if (document.getElementById("supervisorCompletionRate")) {
        document.getElementById("supervisorCompletionRate").textContent =
          data.completion_rate + "%";
      }
      if (document.getElementById("supervisorOnTimeRate")) {
        document.getElementById("supervisorOnTimeRate").textContent =
          data.on_time_rate + "%";
      }
    }
  } catch (error) {
    console.error("Error loading supervisor stats:", error);
  }
}

// Dedicated task logic moved to assets/js/tasks-management.js
