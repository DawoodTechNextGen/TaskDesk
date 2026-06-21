let unifiedChartInstance = null;
let currentTrendType = 'commission'; // default type
let commissionData = null;
let taskData = null;

// Global theme coloring helper (matches the theme of the app)
function getThemeColors() {
  const isDarkMode = document.documentElement.classList.contains('dark');
  return {
    isDarkMode: isDarkMode,
    textColor: isDarkMode ? '#F3F4F6' : '#1F2937',
    gridColor: isDarkMode ? 'rgba(75, 85, 99, 0.2)' : 'rgba(243, 244, 246, 1)',
    backgroundColor: isDarkMode ? '#1F2937' : '#FFFFFF'
  };
}

function initializeAdminCharts() {
  // 1. Load and initialize Task Status Distribution Donut Chart
  initializeTaskStatusChart();

  // 2. Load and initialize the Unified Trends Chart
  loadUnifiedTrendsData();
}

function initializeTaskStatusChart() {
  const { textColor, gridColor, backgroundColor } = getThemeColors();

  fetch("controller/dashboard.php?action=admin_task_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const ctx = document.getElementById("taskStatusChart").getContext("2d");
        const taskStatusChart = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: data.labels,
            datasets: [{
              data: data.values.map(Number),
              backgroundColor: data.labels.map(label => {
                const statusColors = {
                  'Complete': '#10B981',
                  'Working': '#F59E0B',
                  'Inprogress': '#F59E0B',
                  'In progress': '#F59E0B',
                  'Pending': '#6366F1',
                  'Expired': '#EF4444',
                  'Rejected': '#DC2626',
                  'Approved': '#059669',
                  'Needs improvement': '#D97706',
                  'Pending review': '#8B5CF6'
                };
                return statusColors[label] || '#6B7280';
              }),
              borderWidth: 0,
              borderRadius: 6,
              spacing: 3,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "75%",
            plugins: {
              legend: { display: false },
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
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const ratio = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return ` ${label}: ${value} (${ratio}%)`;
                  }
                }
              }
            }
          },
          plugins: [{
            afterDraw: function (chart) {
              const ctx = chart.ctx;
              const width = chart.width;
              const height = chart.height;
              ctx.save();
              ctx.textAlign = "center";
              ctx.textBaseline = "middle";
              ctx.font = "bold 20px sans-serif";
              ctx.fillStyle = textColor;

              const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
              ctx.fillText(total, width / 2, height / 2 - 10);

              ctx.font = "11px sans-serif";
              ctx.fillStyle = '#6B7280';
              ctx.fillText("Total Tasks", width / 2, height / 2 + 15);
              ctx.restore();
            }
          }]
        });

        // Populate custom legend
        const legendContainer = document.getElementById("taskStatusLegend");
        if (legendContainer) {
          legendContainer.innerHTML = "";
          data.labels.forEach((label, index) => {
            const value = Number(data.values[index]);
            const total = data.values.map(Number).reduce((a, b) => a + b, 0);
            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

            const legendItem = document.createElement("div");
            legendItem.className = "flex items-center justify-between p-1.5 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800";
            legendItem.innerHTML = `
              <div class="flex items-center truncate">
                <div class="w-2.5 h-2.5 rounded-full mr-2 flex-shrink-0" style="background-color: ${taskStatusChart.data.datasets[0].backgroundColor[index]}"></div>
                <span class="text-[11px] font-medium text-gray-600 dark:text-gray-400 truncate">${label}</span>
              </div>
              <span class="text-[11px] font-semibold text-gray-800 dark:text-white pl-2">${value} (${percentage}%)</span>
            `;
            legendContainer.appendChild(legendItem);
          });
        }
      }
    });
}

function loadUnifiedTrendsData() {
  // Parallel fetch both datasets
  Promise.all([
    fetch("controller/dashboard.php?action=admin_commission_trends").then(r => r.json()),
    fetch("controller/dashboard.php?action=admin_monthly_trends").then(r => r.json())
  ]).then(([commJson, taskJson]) => {
    if (commJson.success) commissionData = commJson;
    if (taskJson.success) taskData = taskJson;

    // Render the default view
    renderUnifiedChart();
  }).catch(err => {
    console.error("Error loading dashboard trend datasets:", err);
  });
}

function renderUnifiedChart() {
  const { isDarkMode, textColor, gridColor, backgroundColor } = getThemeColors();
  const ctx = document.getElementById("unifiedTrendsChart");
  if (!ctx) return;

  if (unifiedChartInstance) {
    unifiedChartInstance.destroy();
  }

  const dataObj = currentTrendType === 'commission' ? commissionData : taskData;
  if (!dataObj) {
    ctx.parentNode.innerHTML = `<div class="flex items-center justify-center h-full text-red-500">Failed to load chart data</div>`;
    return;
  }

  const gradient = ctx.getContext("2d").createLinearGradient(0, 0, 0, 320);
  const primaryColor = currentTrendType === 'commission' ? '#10B981' : '#3B82F6';

  if (isDarkMode) {
    gradient.addColorStop(0, currentTrendType === 'commission' ? "rgba(16, 185, 129, 0.3)" : "rgba(59, 130, 246, 0.3)");
    gradient.addColorStop(1, "rgba(0, 0, 0, 0)");
  } else {
    gradient.addColorStop(0, currentTrendType === 'commission' ? "rgba(16, 185, 129, 0.15)" : "rgba(59, 130, 246, 0.15)");
    gradient.addColorStop(1, "rgba(255, 255, 255, 0)");
  }

  const datasets = [{
    label: currentTrendType === 'commission' ? "Payout (PKR)" : "Tasks Created",
    data: currentTrendType === 'commission' ? dataObj.payouts : dataObj.tasks,
    borderColor: primaryColor,
    backgroundColor: gradient,
    borderWidth: 3,
    tension: 0.38,
    fill: true,
    pointBackgroundColor: primaryColor,
    pointBorderColor: backgroundColor,
    pointBorderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 7,
    pointHoverBackgroundColor: primaryColor,
    pointHoverBorderColor: backgroundColor,
    pointHoverBorderWidth: 2
  }];

  unifiedChartInstance = new Chart(ctx.getContext("2d"), {
    type: "line",
    data: {
      labels: dataObj.months,
      datasets: datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: "index",
      },
      plugins: {
        legend: { display: false },
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
              if (currentTrendType === 'commission') {
                return `Payout: ${context.parsed.y.toLocaleString()} PKR`;
              } else {
                let label = `Tasks Created: ${context.parsed.y}`;
                if (dataObj.percentages && context.dataIndex > 0) {
                  const percentage = dataObj.percentages[context.dataIndex];
                  const arrow = percentage >= 0 ? "↑" : "↓";
                  label += ` (${percentage >= 0 ? "+" : ""}${percentage}% MoM)`;
                }
                return label;
              }
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: gridColor,
            drawBorder: false
          },
          ticks: {
            color: isDarkMode ? '#9CA3AF' : '#4B5563',
            font: { size: 11 },
            padding: 8,
            callback: function (value) {
              if (currentTrendType === 'commission') {
                return value >= 1000 ? (value / 1000) + 'k PKR' : value + ' PKR';
              }
              return value;
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: isDarkMode ? '#9CA3AF' : '#4B5563',
            font: { size: 11 },
            padding: 8
          }
        }
      }
    }
  });

  // Update stats container below chart if tasks is active
  const statsContainer = document.getElementById("monthlyTrendsStatsContainer");
  if (statsContainer) {
    if (currentTrendType === 'tasks') {
      statsContainer.classList.remove('hidden');
      updateMonthlyTrendsStats(dataObj.tasks, dataObj.months);
    } else {
      statsContainer.classList.add('hidden');
    }
  }
}

function switchTrendChart(type) {
  if (currentTrendType === type) return;
  currentTrendType = type;

  // Toggle button styles
  const btnComm = document.getElementById("btnCommissionTrends");
  const btnTask = document.getElementById("btnTaskTrends");
  const title = document.getElementById("trendChartTitle");
  const subtitle = document.getElementById("trendChartSubtitle");

  if (type === 'commission') {
    btnComm.className = "px-4 py-1.5 rounded-md text-xs font-semibold transition-all bg-indigo-600 text-white shadow-sm";
    btnTask.className = "px-4 py-1.5 rounded-md text-xs font-semibold transition-all text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-white";
    title.textContent = "Commission Payout Trends";
    subtitle.textContent = "Monthly aggregate commission payouts for the last 6 months";
  } else {
    btnTask.className = "px-4 py-1.5 rounded-md text-xs font-semibold transition-all bg-indigo-600 text-white shadow-sm";
    btnComm.className = "px-4 py-1.5 rounded-md text-xs font-semibold transition-all text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-white";
    title.textContent = "Monthly Task Trends";
    subtitle.textContent = "Trend of tasks assigned across all technologies over the last 6 months";
  }

  renderUnifiedChart();
}

function updateMonthlyTrendsStats(tasks, months) {
  const statsContainer = document.getElementById("monthlyTrendsStatsContainer");
  if (!statsContainer) return;

  tasks = tasks.map(Number);
  const totalTasks = tasks.reduce((sum, value) => sum + value, 0);
  const averageTasks = Math.round(totalTasks / tasks.length);
  const peakValue = Math.max(...tasks);
  const peakIndex = tasks.indexOf(peakValue);
  const peakMonth = months[peakIndex];

  const currentMonth = tasks[tasks.length - 1];
  const prevMonth = tasks[tasks.length - 2] || 0;
  const growthRate = prevMonth > 0
    ? Math.round(((currentMonth - prevMonth) / prevMonth) * 100)
    : (currentMonth * 100);

  statsContainer.innerHTML = `
    <div class="text-center p-2.5 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-xl border border-indigo-100/50 dark:border-indigo-950/20">
      <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">${totalTasks}</div>
      <div class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 dark:text-gray-500">Total Tasks</div>
    </div>
    <div class="text-center p-2.5 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100/50 dark:border-emerald-950/20">
      <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${averageTasks}</div>
      <div class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 dark:text-gray-500">Avg/Month</div>
    </div>
    <div class="text-center p-2.5 bg-purple-50/50 dark:bg-purple-900/10 rounded-xl border border-purple-100/50 dark:border-purple-950/20">
      <div class="text-lg font-bold text-purple-600 dark:text-purple-400">${peakValue}</div>
      <div class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 dark:text-gray-500">Peak (${peakMonth})</div>
    </div>
    <div class="text-center p-2.5 bg-orange-50/50 dark:bg-orange-900/10 rounded-xl border border-orange-100/50 dark:border-orange-950/20">
      <div class="text-lg font-bold ${growthRate >= 0 ? "text-emerald-600 dark:text-emerald-400" : "text-rose-600 dark:text-rose-400"}">
        ${growthRate >= 0 ? "+" : ""}${growthRate}%
      </div>
      <div class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 dark:text-gray-500">Growth (MoM)</div>
    </div>
  `;
}
