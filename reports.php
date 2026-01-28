<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], [1, 3, 4])) {
    header('location:' . BASE_URL . 'login.php');
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Dynamic Reports - TaskDesk';
include_once "./include/headerLinks.php";
?>
<!-- External Libraries for Charts and Exports -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php" ?>
            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-12 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="max-w-7xl mx-auto">
                    <!-- Header Actions -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dynamic Reports</h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Comprehensive analytics and historical data for the last 6 months.</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button onclick="exportToExcel()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all">
                                <i class="fas fa-file-excel mr-2"></i> Export Excel
                            </button>
                            <button onclick="exportToPDF()" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all">
                                <i class="fas fa-file-pdf mr-2"></i> Export PDF
                            </button>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div id="chartsContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Shimmer loading state -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 h-80 animate-pulse"></div>
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 h-80 animate-pulse"></div>
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 h-80 animate-pulse"></div>
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 h-80 animate-pulse"></div>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>
    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        let reportData = null;
        let charts = {};

        document.addEventListener('DOMContentLoaded', function() {
            fetchReportData();
        });

        async function fetchReportData() {
            try {
                const response = await fetch('controller/reports.php?action=get_report_data');
                const result = await response.json();
                if (result.success) {
                    reportData = result;
                    renderReports();
                } else {
                    console.error('Error:', result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        }

        function renderReports() {
            const container = document.getElementById('chartsContainer');
            container.innerHTML = ''; // Clear loading state

            // Add Summary Cards for Admin/Manager
            if (reportData.charts.registrations && reportData.charts.hiring_trends) {
                const regData = reportData.charts.registrations.data;
                const hireData = reportData.charts.hiring_trends.data;
                
                const currentReg = regData[regData.length - 1];
                const prevReg = regData[regData.length - 2] || 0;
                const regGrowth = prevReg > 0 ? Math.round(((currentReg - prevReg) / prevReg) * 100) : (currentReg * 100);

                const currentHire = hireData[hireData.length - 1];
                const prevHire = hireData[hireData.length - 2] || 0;
                const hireGrowth = prevHire > 0 ? Math.round(((currentHire - prevHire) / prevHire) * 100) : (currentHire * 100);

                const totalReg = regData.reduce((a, b) => a + b, 0);
                const totalHired = hireData.reduce((a, b) => a + b, 0);
                const avgRatio = totalReg > 0 ? ((totalHired / totalReg) * 100).toFixed(1) : 0;

                const summaryHtml = `
                    <div class="col-span-full grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-2xl border border-blue-100 dark:border-blue-800 relative overflow-hidden">
                            <p class="text-blue-600 dark:text-blue-400 text-sm font-medium uppercase tracking-wider">Registrations</p>
                            <div class="flex items-end justify-between mt-1">
                                <h4 class="text-3xl font-bold text-blue-900 dark:text-white">${totalReg}</h4>
                                <span class="px-2 py-1 rounded text-xs font-bold ${regGrowth >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                    ${regGrowth >= 0 ? '↑' : '↓'} ${Math.abs(regGrowth)}% MoM
                                </span>
                            </div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-2xl border border-green-100 dark:border-green-800 relative overflow-hidden">
                            <p class="text-green-600 dark:text-green-400 text-sm font-medium uppercase tracking-wider">Total Hires</p>
                            <div class="flex items-end justify-between mt-1">
                                <h4 class="text-3xl font-bold text-green-900 dark:text-white">${totalHired}</h4>
                                <span class="px-2 py-1 rounded text-xs font-bold ${hireGrowth >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                    ${hireGrowth >= 0 ? '↑' : '↓'} ${Math.abs(hireGrowth)}% MoM
                                </span>
                            </div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 p-6 rounded-2xl border border-orange-100 dark:border-orange-800">
                            <p class="text-orange-600 dark:text-orange-400 text-sm font-medium uppercase tracking-wider">Avg. Conversion Rate</p>
                            <h4 class="text-3xl font-bold text-orange-900 dark:text-white mt-1">${avgRatio}%</h4>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', summaryHtml);
            }

            const chartKeys = Object.keys(reportData.charts);
            chartKeys.forEach(key => {
                const chartInfo = reportData.charts[key];
                const card = createChartCard(key, chartInfo.label || key.replace('_', ' ').toUpperCase());
                container.appendChild(card);
                
                const ctx = document.getElementById(`chart-${key}`).getContext('2d');
                
                // Color palette for charts
                const colors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', 
                    '#EC4899', '#06B6D4', '#6366F1', '#84CC16', '#F97316'
                ];

                const isRatio = key.includes('ratio') || key.includes('ratio');
                const isMixed = chartInfo.type === 'mixed';
                
                const config = {
                    type: isMixed ? 'bar' : chartInfo.type, // Chart.js mixed charts start with a base type
                    data: {
                        labels: chartInfo.labels || reportData.months,
                        datasets: isMixed ? chartInfo.datasets.map(ds => ({
                            ...ds,
                            borderWidth: 2,
                            tension: 0.4
                        })) : [{
                            label: chartInfo.label || '',
                            data: chartInfo.data,
                            backgroundColor: chartInfo.type === 'pie' || chartInfo.type === 'doughnut' ? colors : chartInfo.backgroundColor,
                            borderColor: chartInfo.borderColor || '#fff',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: chartInfo.type === 'line'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#4B5563'
                                }
                            },
                            tooltip: {
                                backgroundColor: document.documentElement.classList.contains('dark') ? '#1F2937' : '#FFFFFF',
                                titleColor: document.documentElement.classList.contains('dark') ? '#E5E7EB' : '#111827',
                                bodyColor: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#4B5563',
                                borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB',
                                borderWidth: 1,
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || context.label || '';
                                        if (label) label += ': ';
                                        const val = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                                        
                                        // Specific handling for Task Status ratios
                                        if (key === 'task_status' && chartInfo.ratios) {
                                            const ratio = chartInfo.ratios[context.dataIndex];
                                            return `${label}${val} (${ratio}%)`;
                                        }

                                        const unit = (label.toLowerCase().includes('ratio') || isRatio) ? '%' : '';
                                        return label + val + unit;
                                    }
                                }
                            }
                        },
                        scales: chartInfo.type === 'pie' || chartInfo.type === 'doughnut' ? {} : {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                grid: {
                                    color: document.documentElement.classList.contains('dark') ? '#374151' : '#F3F4F6'
                                },
                                ticks: {
                                    color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#4B5563'
                                },
                                title: {
                                    display: isMixed,
                                    text: 'Quantity',
                                    color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#4B5563'
                                }
                            },
                            y1: isMixed ? {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false // only want the grid lines for one axis
                                },
                                ticks: {
                                    color: '#F59E0B',
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Ratio (%)',
                                    color: '#F59E0B'
                                }
                            } : undefined,
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#4B5563'
                                }
                            }
                        }
                    }
                };

                charts[key] = new Chart(ctx, config);
            });
        }

        function createChartCard(id, title) {
            const div = document.createElement('div');
            div.className = "bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 transition-all hover:shadow-md";
            div.innerHTML = `
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">${title}</h3>
                <div class="h-64 relative">
                    <canvas id="chart-${id}"></canvas>
                </div>
            `;
            return div;
        }

        function exportToExcel() {
            if (!reportData) return;
            
            const wb = XLSX.utils.book_new();
            
            Object.keys(reportData.charts).forEach(key => {
                const chart = reportData.charts[key];
                const labels = chart.labels || reportData.months;
                
                let rows;
                if (chart.datasets) {
                    // Handle multi-dataset charts
                    rows = labels.map((label, index) => {
                        const row = { 'Month/Category': label };
                        chart.datasets.forEach(ds => {
                            row[ds.label] = ds.data[index] + (ds.label.toLowerCase().includes('ratio') ? '%' : '');
                        });
                        return row;
                    });
                } else {
                    // Handle single dataset charts (including Task Status with ratios)
                    rows = labels.map((label, index) => {
                        const row = { [key.toUpperCase()]: label, 'Value': chart.data[index] };
                        if (key === 'task_status' && chart.ratios) {
                            row['Ratio (%)'] = chart.ratios[index] + '%';
                        }
                        return row;
                    });
                }
                
                const ws = XLSX.utils.json_to_sheet(rows);
                XLSX.utils.book_append_sheet(wb, ws, key.substring(0, 31));
            });
            
            XLSX.writeFile(wb, `TaskDesk_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
        }

        async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.setFontSize(20);
            doc.text('TaskDesk Analytical Report', 14, 22);
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 30);
            
            let currentY = 40;

            Object.keys(reportData.charts).forEach((key, index) => {
                const chart = reportData.charts[key];
                const labels = chart.labels || reportData.months;
                
                if (currentY > 240) {
                    doc.addPage();
                    currentY = 20;
                }

                doc.setFontSize(14);
                doc.setTextColor(0);
                doc.text(key.replace('_', ' ').toUpperCase(), 14, currentY);
                currentY += 5;

                let head, body;
                if (chart.datasets) {
                    head = [['Category', ...chart.datasets.map(ds => ds.label)]];
                    body = labels.map((label, i) => [
                        label, 
                        ...chart.datasets.map(ds => ds.data[i] + (ds.label.toLowerCase().includes('ratio') ? '%' : ''))
                    ]);
                } else {
                    let h = ['Category', 'Value'];
                    if (key === 'task_status' && chart.ratios) h.push('Ratio (%)');
                    head = [h];
                    
                    body = labels.map((label, i) => {
                        let r = [label, chart.data[i]];
                        if (key === 'task_status' && chart.ratios) r.push(chart.ratios[i] + '%');
                        return r;
                    });
                }
                
                doc.autoTable({
                    startY: currentY,
                    head: head,
                    body: body,
                    theme: 'grid',
                    headStyles: { fillColor: [59, 130, 246] }
                });

                currentY = doc.lastAutoTable.finalY + 15;
            });

            doc.save(`TaskDesk_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        }
    </script>
</body>
</html>
