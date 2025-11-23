// Modern Admin Charts with Enhanced Styling
function initializeAdminCharts() {
  const { isDarkMode, textColor, gridColor, backgroundColor } =
    getThemeColors();
  // Task Status Distribution - Modern Donut Chart
  fetch("controller/dashboard.php?action=admin_task_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const taskStatusCtx = document
          .getElementById("taskStatusChart")
          .getContext("2d");
        const taskStatusChart = new Chart(taskStatusCtx, {
          type: "doughnut",
          data: {
            labels: data.labels,
            datasets: [
              {
                data: data.values.map(Number),
                backgroundColor: [
                  "#10B981", // Completed - Green
                  "#F59E0B", // Working - Yellow
                  "#EF4444", // Pending - Red
                  "#6B7280", // Overdue - Gray
                ],
                borderWidth: 0,
                borderRadius: 8,
                spacing: 4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "70%",
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
                    const label = context.label || "";
                    const value = context.raw || 0;
                    const total = context.dataset.data.reduce(
                      (a, b) => a + b,
                      0
                    );
                    const percentage = Math.round((value / total) * 100);
                    return `${label}: ${value} (${percentage}%)`;
                  },
                },
              },
            },
          },
          plugins: [
            {
              afterDraw: function (chart) {
                const ctx = chart.ctx;
                const width = chart.width;
                const height = chart.height;
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.font = "bold 24px sans-serif";
                ctx.fillStyle = textColor;

                const total = chart.data.datasets[0].data.reduce(
                  (a, b) => a + b,
                  0
                );
                ctx.fillText(total, width / 2, height / 2);

                ctx.font = "12px sans-serif";
                ctx.fillStyle = isDarkMode ? "#9CA3AF" : "#6B7280";
                ctx.fillText("Total Tasks", width / 2, height / 2 + 24);
                ctx.restore();
              },
            },
          ],
        });

        // Update legend
        const legendContainer = document.getElementById("taskStatusLegend");
        if (legendContainer) {
          legendContainer.innerHTML = "";
          data.labels.forEach((label, index) => {
            const value = Number(data.values[index]);
            const total = data.values.map(Number).reduce((a, b) => a + b, 0);
            const percentage = Math.round((value / total) * 100);

            const legendItem = document.createElement("div");
            legendItem.className = "flex items-center justify-between";
            legendItem.innerHTML = `
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded mr-2" style="background-color: ${taskStatusChart.data.datasets[0].backgroundColor[index]}"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">${label}</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-800 dark:text-white">${value}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">${percentage}%</div>
                            </div>
                        `;
            legendContainer.appendChild(legendItem);
          });
        }
      }
    });

  // Monthly Task Trends - Modern Line Chart with Enhanced Features
  fetch("controller/dashboard.php?action=admin_monthly_trends")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const monthlyTrendsCtx = document
          .getElementById("monthlyTrendsChart")
          .getContext("2d");

        // Create gradient for the chart
        const gradient = monthlyTrendsCtx.createLinearGradient(0, 0, 0, 400);
        if (isDarkMode) {
          gradient.addColorStop(0, "rgba(59, 130, 246, 0.3)");
          gradient.addColorStop(1, "rgba(59, 130, 246, 0.05)");
        } else {
          gradient.addColorStop(0, "rgba(59, 130, 246, 0.2)");
          gradient.addColorStop(1, "rgba(59, 130, 246, 0.02)");
        }

        const chart = new Chart(monthlyTrendsCtx, {
          type: "line",
          data: {
            labels: data.months,
            datasets: [
              {
                label: "Tasks Created",
                data: data.tasks,
                borderColor: "#3B82F6",
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: "#3B82F6",
                pointBorderColor: backgroundColor,
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: "#3B82F6",
                pointHoverBorderColor: backgroundColor,
                pointHoverBorderWidth: 3,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              intersect: false,
              mode: "index",
            },
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
                padding: 12,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                  label: function (context) {
                    return `Tasks: ${context.parsed.y}`;
                  },
                  title: function (context) {
                    return `Month: ${context[0].label}`;
                  },
                },
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: gridColor,
                  drawBorder: false,
                },
                ticks: {
                  color: textColor,
                  padding: 10,
                  callback: function (value) {
                    return value;
                  },
                },
                // title: {
                //     display: true,
                //     text: 'Number of Tasks',
                //     color: textColor,
                //     font: {
                //         weight: 'normal'
                //     }
                // }
              },
              x: {
                grid: {
                  color: gridColor,
                  drawBorder: false,
                },
                ticks: {
                  color: textColor,
                  padding: 10,
                },
                // title: {
                //     display: true,
                //     text: 'Months',
                //     color: textColor,
                //     font: {
                //         weight: 'normal'
                //     }
                // }
              },
            },
            elements: {
              line: {
                tension: 0.4,
              },
            },
          },
        });

        // Add custom statistics below the chart
        updateMonthlyTrendsStats(data.tasks, data.months);
      }
    })
    .catch((error) => {
      console.error("Error loading monthly trends:", error);
      const chartContainer = document.getElementById("monthlyTrendsChart");
      if (chartContainer) {
        chartContainer.innerHTML = `
                    <div class="flex items-center justify-center h-full text-red-500">
                        <svg class="w-8 h-8 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Failed to load chart data</span>
                    </div>
                `;
      }
    });

  // User Role Distribution - Modern Pie Chart
  fetch("controller/dashboard.php?action=admin_role_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const userRoleCtx = document
          .getElementById("userRoleChart")
          .getContext("2d");
        const userRoleChart = new Chart(userRoleCtx, {
          type: "pie",
          data: {
            labels: data.labels,
            datasets: [
              {
                data: data.values,
                backgroundColor: ["#EF4444", "#10B981", "#3B82F6"], // Admin, Intern, Supervisor
                borderWidth: 0,
                borderRadius: 8,
                spacing: 4,
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
          },
        });

        // Update legend
        const legendContainer = document.getElementById("userRoleLegend");
        if (legendContainer) {
          legendContainer.innerHTML = "";
          data.labels.forEach((label, index) => {
           const value = data.values[index];

            const total = data.values.map(Number).reduce((a, b) => a + b, 0);

            const percentage = Math.round((value / total) * 100);

            const legendItem = document.createElement("div");
            legendItem.className = "flex flex-col items-center";
            legendItem.innerHTML = `
                            <div class="flex items-center mb-2">
                                <div class="w-3 h-3 rounded mr-2" style="background-color: ${userRoleChart.data.datasets[0].backgroundColor[index]}"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">${label}</span>
                            </div>
                            <div class="text-lg font-bold text-gray-800 dark:text-white">${value}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${percentage}%</div>
                        `;
            legendContainer.appendChild(legendItem);
          });
        }
      }
    });

  // Technology-wise Task Distribution
  fetch("controller/dashboard.php?action=admin_tech_tasks")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const techTaskCtx = document
          .getElementById("techTaskChart")
          .getContext("2d");
        new Chart(techTaskCtx, {
          type: "bar",
          data: {
            labels: data.technologies,
            datasets: [
              {
                label: "Tasks",
                data: data.tasks,
                backgroundColor: [
                  "rgba(59, 130, 246, 0.8)",
                  "rgba(16, 185, 129, 0.8)",
                  "rgba(139, 92, 246, 0.8)",
                  "rgba(245, 158, 11, 0.8)",
                  "rgba(239, 68, 68, 0.8)",
                ],
                borderColor: [
                  "rgb(59, 130, 246)",
                  "rgb(16, 185, 129)",
                  "rgb(139, 92, 246)",
                  "rgb(245, 158, 11)",
                  "rgb(239, 68, 68)",
                ],
                borderWidth: 2,
                borderRadius: 8,
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
                  display: false,
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

function updateMonthlyTrendsStats(tasks, months) {
  const statsContainer = document.getElementById("monthlyTrendsStats");

  if (!statsContainer) return;

  // FIX: Convert strings to numbers
  tasks = tasks.map(Number);

  const totalTasks = tasks.reduce((sum, value) => sum + value, 0);
  const averageTasks = Math.round(totalTasks / tasks.length);
  const peakValue = Math.max(...tasks);
  const peakIndex = tasks.indexOf(peakValue);
  const peakMonth = months[peakIndex];

  const currentMonth = tasks[tasks.length - 1];
  const prevMonth = tasks[tasks.length - 2] || 0;
  const growthRate =
    prevMonth > 0
      ? Math.round(((currentMonth - prevMonth) / prevMonth) * 100)
      : 0;

  statsContainer.innerHTML = `
        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">${totalTasks}</div>
            <div class="text-sm text-blue-600 dark:text-blue-300">Total Tasks</div>
        </div>
        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">${averageTasks}</div>
            <div class="text-sm text-green-600 dark:text-green-300">Average/Month</div>
        </div>
        <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">${peakValue}</div>
            <div class="text-sm text-purple-600 dark:text-purple-300">Peak (${peakMonth})</div>
        </div>
        <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <div class="text-2xl font-bold ${
              growthRate >= 0
                ? "text-green-600 dark:text-green-400"
                : "text-red-600 dark:text-red-400"
            }">
                ${growthRate >= 0 ? "+" : ""}${growthRate}%
            </div>
            <div class="text-sm text-orange-600 dark:text-orange-300">Growth Rate</div>
        </div>
        `;
}
