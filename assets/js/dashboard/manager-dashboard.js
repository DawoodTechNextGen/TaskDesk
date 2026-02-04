// assets/js/dashboard/manager-dashboard.js
// Modern Manager Charts with Enhanced Styling
function initializeManagerCharts() {
    const { isDarkMode, textColor, gridColor, backgroundColor } = getThemeColors();

    // Load overview stats first
    loadManagerOverviewStats();

    // Registration Status Distribution - Modern Donut Chart
    fetch("controller/dashboard.php?action=manager_registration_stats")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const registrationStatusCtx = document.getElementById("registrationStatusChart").getContext("2d");
                const registrationStatusChart = new Chart(registrationStatusCtx, {
                    type: "doughnut",
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values.map(Number),
                            backgroundColor: data.labels.map(label => {
                                const regColors = {
                                    'New': '#3B82F6',        // Blue
                                    'Contacted': '#F59E0B',  // Yellow
                                    'Hired': '#10B981',      // Green
                                    'Rejected': '#EF4444'    // Red
                                };
                                return regColors[label] || '#6B7280';
                            }),
                            borderWidth: 0,
                            borderRadius: 8,
                            spacing: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "70%",
                        plugins: {
                            legend: {
                                display: false
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
                                        const ratio = data.ratios ? data.ratios[context.dataIndex] : (total > 0 ? ((value / total) * 100).toFixed(1) : 0);
                                        return `${label}: ${value} (${ratio}%)`;
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
                            ctx.font = "bold 24px sans-serif";
                            ctx.fillStyle = textColor;

                            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            ctx.fillText(total, width / 2, height / 2);

                            ctx.font = "12px sans-serif";
                            ctx.fillStyle = isDarkMode ? "#9CA3AF" : "#6B7280";
                            ctx.fillText("Total", width / 2, height / 2 + 24);
                            ctx.restore();
                        }
                    }]
                });

                // Update legend
                const legendContainer = document.getElementById("registrationStatusLegend");
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
                                <div class="w-3 h-3 rounded mr-2" style="background-color: ${registrationStatusChart.data.datasets[0].backgroundColor[index]}"></div>
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

    // Monthly Registration Trends - Modern Line Chart
    fetch("controller/dashboard.php?action=manager_monthly_registrations")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const monthlyRegistrationsCtx = document.getElementById("monthlyRegistrationsChart").getContext("2d");

                // Create gradient
                const gradient = monthlyRegistrationsCtx.createLinearGradient(0, 0, 0, 400);
                if (isDarkMode) {
                    gradient.addColorStop(0, "rgba(139, 92, 246, 0.3)");
                    gradient.addColorStop(1, "rgba(139, 92, 246, 0.05)");
                } else {
                    gradient.addColorStop(0, "rgba(139, 92, 246, 0.2)");
                    gradient.addColorStop(1, "rgba(139, 92, 246, 0.02)");
                }

                new Chart(monthlyRegistrationsCtx, {
                    type: "line",
                    data: {
                        labels: data.months,
                        datasets: [{
                            label: "Registrations",
                            data: data.registrations,
                            borderColor: "#8B5CF6",
                            backgroundColor: gradient,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: "#8B5CF6",
                            pointBorderColor: backgroundColor,
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
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
                                        let label = `Registrations: ${context.parsed.y}`;
                                        if (data.percentages && context.dataIndex > 0) {
                                            const percentage = data.percentages[context.dataIndex];
                                            const arrow = percentage >= 0 ? "↑" : "↓";
                                            const color = percentage >= 0 ? "🟢" : "🔴";
                                            label += ` ${color} ${arrow} ${Math.abs(percentage)}% MoM`;
                                        }
                                        return label;
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
                                    color: textColor,
                                    padding: 10
                                }
                            },
                            x: {
                                grid: {
                                    color: gridColor,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: textColor,
                                    padding: 10
                                }
                            }
                        },
                        elements: {
                            line: {
                                tension: 0.4
                            }
                        }
                    }
                });
            }
        });

    // Technology-wise Registrations
    fetch("controller/dashboard.php?action=manager_tech_registrations")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const techRegistrationCtx = document.getElementById("techRegistrationChart").getContext("2d");
                const techRegistrationChart = new Chart(techRegistrationCtx, {
                    type: "pie",
                    data: {
                        labels: data.technologies,
                        datasets: [{
                            data: data.registrations,
                            backgroundColor: [
                                "#3B82F6",
                                "#10B981",
                                "#8B5CF6",
                                "#F59E0B",
                                "#EF4444"
                            ],
                            borderWidth: 0,
                            borderRadius: 8,
                            spacing: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
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
                                        const ratio = data.ratios ? data.ratios[context.dataIndex] : 0;
                                        return `${label}: ${value} (${ratio}%)`;
                                    }
                                }
                            }
                        }
                    }
                });

                // Update legend
                const legendContainer = document.getElementById("techRegistrationLegend");
                if (legendContainer) {
                    legendContainer.innerHTML = "";
                    data.technologies.forEach((tech, index) => {
                        const value = Number(data.registrations[index]);
                        const total = data.registrations.map(Number).reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

                        const legendItem = document.createElement("div");
                        legendItem.className = "flex flex-col items-center";
                        legendItem.innerHTML = `
                            <div class="flex items-center mb-2">
                                <div class="w-3 h-3 rounded mr-2" style="background-color: ${techRegistrationChart.data.datasets[0].backgroundColor[index]}"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">${tech}</span>
                            </div>
                            <div class="text-lg font-bold text-gray-800 dark:text-white">${value}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${percentage}%</div>
                        `;
                        legendContainer.appendChild(legendItem);
                    });
                }
            }
        });

    // Internship Type Distribution
    fetch("controller/dashboard.php?action=manager_internship_type_stats")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const internshipTypeCtx = document.getElementById("internshipTypeChart").getContext("2d");
                new Chart(internshipTypeCtx, {
                    type: "bar",
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: "Registrations",
                            data: data.values,
                            backgroundColor: [
                                "rgba(59, 130, 246, 0.8)",
                                "rgba(16, 185, 129, 0.8)"
                            ],
                            borderColor: [
                                "rgb(59, 130, 246)",
                                "rgb(16, 185, 129)"
                            ],
                            borderWidth: 2,
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: backgroundColor,
                                titleColor: textColor,
                                bodyColor: textColor,
                                borderColor: gridColor,
                                borderWidth: 1,
                                callbacks: {
                                    label: function (context) {
                                        const label = context.dataset.label || "";
                                        const value = context.raw || 0;
                                        const ratio = data.ratios ? data.ratios[context.dataIndex] : 0;
                                        return `${label}: ${value} (${ratio}%)`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: textColor
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: textColor
                                }
                            }
                        }
                    }
                });
            }
        });

    // Load recent registrations
    loadRecentRegistrations();
}

function loadManagerOverviewStats() {
    fetch("controller/dashboard.php?action=manager_overview_stats")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                document.getElementById("todayRegistrations").textContent = data.today;
                document.getElementById("weekRegistrations").textContent = data.week;
                document.getElementById("monthRegistrations").textContent = data.month;
                document.getElementById("hiringRate").textContent = data.hiring_rate + "%";
            }
        });

    fetch("controller/dashboard.php?action=manager_registration_counts")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                document.getElementById("totalRegistrations").textContent = data.counts.total;
                document.getElementById("newRegistrations").textContent = data.counts.new;
                document.getElementById("contactedRegistrations").textContent = data.counts.contact;
                document.getElementById("hiredRegistrations").textContent = data.counts.hire;

                document.getElementById("totalRegistrationsCount").textContent = data.counts.total;
                document.getElementById("newRegistrationsCount").textContent = data.counts.new;
                document.getElementById("contactedCount").textContent = data.counts.contact;
                document.getElementById("rejectedCount").textContent = data.counts.rejected;
            }
        });
}

function loadRecentRegistrations() {
    fetch("controller/dashboard.php?action=manager_recent_registrations")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const tableBody = document.getElementById("recentRegistrationsTable");
                tableBody.innerHTML = "";

                data.registrations.forEach(reg => {
                    const statusColors = {
                        'new': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        'contact': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        'hire': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'rejected': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                    };

                    const statusText = {
                        'new': 'New',
                        'contact': 'Contacted',
                        'hire': 'Hired',
                        'rejected': 'Rejected'
                    };

                    const row = document.createElement("tr");
                    row.className = "border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700";
                    row.innerHTML = `
                        <td class="py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 text-sm font-bold mr-3">
                                    ${reg.name.charAt(0).toUpperCase()}
                                </div>
                                <span class="text-sm font-medium text-gray-800 dark:text-white">${reg.name}</span>
                            </div>
                        </td>
                        <td class="py-4 text-sm text-gray-600 dark:text-gray-300">${reg.email}</td>
                        <td class="py-4 text-sm text-gray-600 dark:text-gray-300">${reg.technology || 'N/A'}</td>
                        <td class="py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[reg.status]}">
                                ${statusText[reg.status]}
                            </span>
                        </td>
                        <td class="py-4 text-sm text-gray-500 dark:text-gray-400">${formatDate(reg.created_at)}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Helper function to get theme colors
function getThemeColors() {
    const isDarkMode = document.documentElement.classList.contains('dark');
    return {
        isDarkMode,
        textColor: isDarkMode ? '#E5E7EB' : '#374151',
        gridColor: isDarkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(209, 213, 219, 0.8)',
        backgroundColor: isDarkMode ? '#1F2937' : '#FFFFFF'
    };
}