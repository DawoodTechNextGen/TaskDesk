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
            const ctx = document.getElementById("teamPerformanceChart").getContext("2d");

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
                        }
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
                backgroundColor: ["#10B981", "#F59E0B", "#EF4444", "#6B7280"],
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
      "controller/dashboard.php?action=supervisor_stats"
    );
    const data = await response.json();

    if (data.success) {
      document.getElementById("supervisorCompletionRate").textContent =
        data.completion_rate + "%";
    }
  } catch (error) {
    console.error("Error loading supervisor stats:", error);
  }
}

// Supervisor-specific task management functions
let createEditor, editEditor;
let dataTable;
let liveInterval;

document
  .getElementById("create-task")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    // Get form values
    const title = document.getElementById("title").value.trim();
    const description = createEditor?.getData?.().trim() || "";
    const user_id = document.getElementById("user_id").value;
    const due_date = document.getElementById("due_date").value;
    const technology_id = document.getElementById("technology_id").value;

    // Validation
    if (!title) {
      alert("Please enter a task title");
      return;
    }
    if (!description) {
      alert("Please enter a task description");
      return;
    }
    if (!technology_id) {
      alert("Please select a technology");
      return;
    }
    if (!user_id) {
      alert("Please select a user to assign");
      return;
    }
    if (!due_date) {
      alert("Please select a due date");
      return;
    }

    try {
      const response = await fetch("controller/task.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "create",
          title,
          description,
          user_id: parseInt(user_id), // send as integer
          due_date, // YYYY-MM-DD
        }),
      });

      const result = await response.json();

      if (result.success) {
        // Close modal
        document.querySelector("#create-task-modal .close-modal")?.click();

        // Refresh task list
        await getTasks();

        // Success toast
        showToast("success", result.message || "Task created successfully!");

        // Reset form
        this.reset();
        createEditor.setData(""); // Clear CKEditor

        // Reset user dropdown (because it depends on technology)
        const userSelect = document.getElementById("user_id");
        userSelect.innerHTML =
          '<option value="">First select technology</option>';
      } else {
        showToast("error", result.message || "Failed to create task");
      }
    } catch (error) {
      console.error("Create task error:", error);
      showToast("error", "Network error. Please try again.");
    }
  });

// Add this once, outside any function
// Fixed technology dropdown event listener
document.addEventListener('DOMContentLoaded', function() {
    // Technology dropdown change event - FIXED VERSION
    const technologySelect = document.getElementById('technology_id');
    if (technologySelect) {
        technologySelect.addEventListener('change', async function() {
            const techId = this.value;
            const userSelect = document.getElementById('user_id');

            // Reset user dropdown
            userSelect.innerHTML = '<option value="">Loading users...</option>';

            if (!techId) {
                userSelect.innerHTML = '<option value="">First select technology</option>';
                return;
            }

            try {
                console.log('Fetching users for technology:', techId); // Debug log
                
                const response = await fetch('controller/get_users_by_tech.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tech_id: techId
                    })
                });

                console.log('Response status:', response.status); // Debug log
                
                const result = await response.json();
                console.log('API Response:', result); // Debug log

                userSelect.innerHTML = '<option value="">Select User</option>';

                if (result.success && result.users && result.users.length > 0) {
                    result.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.name;
                        userSelect.appendChild(option);
                    });
                } else {
                    userSelect.innerHTML = '<option value="">No users found for this technology</option>';
                }
            } catch (error) {
                console.error('Error loading users:', error);
                userSelect.innerHTML = '<option value="">Error loading users</option>';
            }
        });
    } else {
        console.error('Technology select element not found');
    }
});

document.addEventListener("DOMContentLoaded", async function () {
  dataTable = $("#tasksTable").DataTable({
    responsive: true,
    pageLength: 10,
    ordering: false,
  });

  // Initialize CKEditor for create modal
  try {
    createEditor = await ClassicEditor.create(
      document.querySelector("#create-description-editor"),
      {
        toolbar: [
          "heading",
          "|",
          "bold",
          "italic",
          "link",
          "bulletedList",
          "numberedList",
          "|",
          "undo",
          "redo",
        ],
        height: "150px",
        minHeight: "150px",
      }
    );
  } catch (error) {
    console.error("Error initializing create editor:", error);
    // Fallback to textarea if CKEditor fails
    document.querySelector("#create-description-editor").innerHTML =
      '<textarea id="description-fallback" rows="4" class="text-sm w-full p-2 rounded-lg border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Task description"></textarea>';
  }

  // Initialize CKEditor for edit modal (empty initially)
  try {
    editEditor = await ClassicEditor.create(
      document.querySelector("#edit-description-editor"),
      {
        toolbar: [
          "heading",
          "|",
          "bold",
          "italic",
          "link",
          "bulletedList",
          "numberedList",
          "|",
          "undo",
          "redo",
        ],
        height: "200px",
        minHeight: "200px",
      }
    );
  } catch (error) {
    console.error("Error initializing edit editor:", error);
    // Fallback to textarea if CKEditor fails
    document.querySelector("#edit-description-editor").innerHTML =
      '<textarea id="edit-description-fallback" rows="6" class="text-sm w-full p-2 rounded-lg border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Task description"></textarea>';
  }

  await getTasks();
  await viewTask();
  await editTask();
  // Removed admin-specific functions: allTasks(), completeTasks(), workingTasks(), pendingTasks()
});

async function getTasks() {
  try {
    const response = await fetch("controller/task.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "get",
      }),
    });

    const result = await response.json();

    if (result.success) {
      const statusOrder = {
        working: 1,
        pending: 2,
        complete: 3,
      };
      result.data.sort((a, b) => {
        if (statusOrder[a.status] !== statusOrder[b.status]) {
          return statusOrder[a.status] - statusOrder[b.status];
        }
        return new Date(b.created_at) - new Date(a.created_at);
      });

      dataTable.clear();
      let count = 1;
      const currentUserName = "<?php echo $_SESSION['user_name'] ?>";

      result.data.forEach((task) => {
        dataTable.row.add([
          count,
          task.id,
          task.title,
          (task.assign_to =
            task.assign_to == currentUserName ? "Me" : task.assign_to),
          getStatusBadge(
            task.status === "complete"
              ? task.approval_status === 2
                ? "Declined"
                : task.status
              : task.status
          ),
          formatDateTime(task.created_at),
          `
                        ${
                          task.status == "complete" || task.status == "working"
                            ? ""
                            : `
                            <button class="open-modal text-blue-600 me-2"
                            data-modal="edit-task-modal"
                            data-id="${task.id}"
                            data-title="${task.title}"
                            data-description="${task.description}"
                            data-assign-id="${task.assign_id}"
                            data-tech-id="${task.tech_id || ""}"
                            data-due-date="${task.due_date || ""}">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M4 22H8M20 22H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M13.8881 3.66293L14.6296 2.92142C15.8581 1.69286 17.85 1.69286 19.0786 2.92142C20.3071 4.14999 20.3071 6.14188 19.0786 7.37044L18.3371 8.11195M13.8881 3.66293C13.8881 3.66293 13.9807 5.23862 15.3711 6.62894C16.7614 8.01926 18.3371 8.11195 18.3371 8.11195M13.8881 3.66293L7.07106 10.4799C6.60933 10.9416 6.37846 11.1725 6.17992 11.4271C5.94571 11.7273 5.74491 12.0522 5.58107 12.396C5.44219 12.6874 5.33894 12.9972 5.13245 13.6167L4.25745 16.2417M18.3371 8.11195L14.9286 11.5204M11.5201 14.9289C11.0584 15.3907 10.8275 15.6215 10.5729 15.8201C10.2727 16.0543 9.94775 16.2551 9.60398 16.4189C9.31256 16.5578 9.00282 16.6611 8.38334 16.8675L5.75834 17.7426M5.75834 17.7426L5.11667 17.9564C4.81182 18.0581 4.47573 17.9787 4.2485 17.7515C4.02128 17.5243 3.94194 17.1882 4.04356 16.8833L4.25745 16.2417M5.75834 17.7426L4.25745 16.2417" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                            </button>
                            `
                        }
                            <button class="open-modal text-amber-600" 
                            data-modal="view-task-modal" id="view-task" 
                                data-id="${task.id}" 
                                data-title="${task.title}" 
                                data-description="${task.description}" 
                                data-assign="${
                                  task.assign_to == currentUserName
                                    ? "Me"
                                    : task.assign_to
                                }" 
                                data-status="${task.status}" 
                                data-created="${formatDateTime(
                                  task.created_at
                                )}"
                                data-started="${
                                  task.started_at == null
                                    ? "Not started Yet"
                                    : formatDateTime(task.started_at)
                                }"
                                data-completed="${
                                  task.completed_at == null
                                    ? null
                                    : formatDateTime(task.completed_at)
                                }"
                                data-gitrepo="${task.github_repo ?? ""}"
                                data-liveurl="${task.live_url ?? ""}"
                                data-additionalmsg="${
                                  task.additional_notes ?? ""
                                }">
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 4.45962C9.91153 4.16968 10.9104 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C3.75612 8.07914 4.32973 7.43025 5 6.82137" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="1.5"></path> </g></svg>
                            </button>`,
        ]);
        count++;
      });
      dataTable.draw();
    } else {
      showToast("error", result.message);
    }
  } catch (error) {
    console.error("Error:", error);
  }
}

function viewTask() {
  document
    .getElementById("tasksTable")
    .addEventListener("click", async function (e) {
      const button = e.target.closest(
        ".open-modal[data-modal='view-task-modal']"
      );
      if (!button) return;

      const modal = document.getElementById("view-task-modal");
      const tbody = document.getElementById("logs-body");
      const totalTimeEl = document.getElementById("total-time");

      // Clear previous data
      tbody.innerHTML = "";
      totalTimeEl.innerText = "Total Time: 00:00:00";
      document.getElementById("time-logs").classList.add("hidden");

      let totalTime = 0;
      clearInterval(liveInterval);

      const task_id = button.dataset.id;
      const title = button.dataset.title;
      const description = button.dataset.description;
      const assignTo = button.dataset.assign;
      const status = button.dataset.status;
      const created = button.dataset.created;
      const started_at = button.dataset.started;
      const completed_at = button.dataset.completed;
      const github_repo = button.dataset.gitrepo;
      const live_url = button.dataset.liveurl;
      const additional_msg = button.dataset.additionalmsg;

      const viewUrls = document.querySelector(".view-urls");
      if (github_repo !== "" || github_repo !== null) {
        viewUrls.innerHTML = `
                                    <button type="button" class="close-modal px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-500">Close</button>
                                    ${
                                      github_repo
                                        ? `<a href="${github_repo}" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">View Github Repo</a>`
                                        : ""
                                    }
                                    ${
                                      live_url
                                        ? `<a href="${live_url}" target="_blank" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-500">View Live Web</a>`
                                        : ""
                                    }
                                    `;
      }
      // Update the view data
      modal.querySelector(".view-data").innerHTML = `
            <div class="grid grid-cols-2">
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Title:</label>
                    <span class="ms-2">${title}</span>
                </div>
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Assigned To:</label>
                    <span class="ms-2">${
                      assignTo == "<?php echo $_SESSION['user_name'] ?>"
                        ? "me"
                        : assignTo
                    }</span>
                </div>
            </div>
            <div class="grid grid-cols-2">
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Status:</label>
                    <span class="ms-2">${getStatusBadge(status)}</span>
                </div>
                <div class="mb-3 flex items-center col-span-2">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Created At:</label>
                    <span class="ms-2">${created}</span>
                </div>
            </div>
            <div class="">
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Start Time:</label>
                    <span class="ms-2">${started_at}</span>
                </div>
            ${
              status == "complete"
                ? `<div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Complete Time:</label>
                    <span class="ms-2">${completed_at}</span>
                </div>`
                : ""
            }
            </div>
            ${
              additional_msg && additional_msg.trim() !== ""
                ? `<div  class="mb-3">
            <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Student Message:</label>
            <span class="block p-2 bg-gray-50 dark:bg-gray-600 text-black dark:text-gray-200">${additional_msg}</span>
            </div>`
                : ""
            }
            <div class="mb-3">
                <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Description:</label>
                <span class="block p-2 border-l-4 border-indigo-400 dark:border-indigo-300 bg-indigo-100 dark:bg-indigo-500 text-indigo-600 dark:text-indigo-200">${description}</span>
            </div>
        `;

      try {
        const response = await fetch("controller/timeLog.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            action: "get",
            task_id: task_id,
          }),
        });

        const result = await response.json();

        if (result.success) {
          if (result.logs.length > 0) {
            document.getElementById("time-logs").classList.remove("hidden");

            result.logs.forEach((log) => {
              let row = document.createElement("tr");
              row.className =
                "hover:bg-gray-50 dark:bg-gray-600 bg-white transition-colors";

              let startTd = `<td class="py-3 px-6 text-sm">${formatDateTime(
                log.started_at
              )}</td>`;

              if (!log.stopped_at) {
                let startTime = new Date(log.started_at).getTime();
                let stopTd = `<td class="py-3 px-6 text-sm">--</td>`;
                let liveTd = document.createElement("td");
                liveTd.className = "py-3 px-6 text-sm live-counter";
                row.innerHTML = startTd + stopTd;
                row.appendChild(liveTd);
                tbody.appendChild(row);

                liveInterval = setInterval(() => {
                  let now = Date.now();
                  let diffSec = Math.floor((now - startTime) / 1000);

                  liveTd.innerText = formatDuration(diffSec);
                  totalTimeEl.innerText = `Total Time: ${formatDuration(
                    totalTime + diffSec
                  )}`;
                }, 1000);
              } else {
                row.innerHTML = `
                                ${startTd}
                                <td class="py-3 px-6 text-sm">${formatDateTime(
                                  log.stopped_at
                                )}</td>
                                <td class="py-3 px-6 text-sm">${formatDuration(
                                  log.duration
                                )}</td>
                            `;
                totalTime += parseInt(log.duration, 10);
                tbody.appendChild(row);
              }
            });

            totalTimeEl.innerText = `Total Time: ${formatDuration(totalTime)}`;
          }
        }
      } catch (error) {
        console.error("Error:", error);
      }

      modal.classList.remove("hidden");
      modal.querySelectorAll(".close-btn, .close-modal").forEach((closeBtn) => {
        closeBtn.addEventListener("click", () => {
          clearInterval(liveInterval);
          modal.classList.add("hidden");
        });
      });
    });
}

function editTask() {
  document
    .getElementById("edit_technology_id")
    .addEventListener("change", async function () {
      const techId = this.value;
      const userSelect = document.getElementById("edit_user_id");

      if (!techId) {
        userSelect.innerHTML =
          '<option value="">First select technology</option>';
        return;
      }

      userSelect.innerHTML = '<option value="">Loading...</option>';
      try {
        const res = await fetch("controller/get_users_by_tech.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            tech_id: techId,
          }),
        });
        const result = await res.json();

        userSelect.innerHTML = '<option value="">Select User</option>';
        if (result.success) {
          result.users.forEach((u) => userSelect.add(new Option(u.name, u.id)));
        } else {
          userSelect.innerHTML = '<option value="">No users found</option>';
        }
      } catch (err) {
        userSelect.innerHTML = '<option value="">Error loading users</option>';
      }
    });
}

// Handle edit button click to populate the modal
document.addEventListener("click", async function (e) {
  const editButton = e.target.closest(
    '.open-modal[data-modal="edit-task-modal"]'
  );
  if (!editButton) return;

  const taskId = editButton.dataset.id;
  const taskTitle = editButton.dataset.title;
  const taskDescription = editButton.dataset.description;
  const taskAssignId = editButton.dataset.assignId;
  const taskTechId = editButton.dataset.techId;
  const taskDueDate = editButton.dataset.dueDate;

  // Populate basic fields
  document.getElementById("edit_task_id").value = taskId;
  document.getElementById("edit_title").value = taskTitle;
  document.getElementById("edit_due_date").value = taskDueDate;

  // Set technology and trigger change to load users
  const techSelect = document.getElementById("edit_technology_id");
  techSelect.value = taskTechId || "";

  // Trigger change event to load users for this technology
  if (taskTechId) {
    const event = new Event("change");
    techSelect.dispatchEvent(event);

    // Wait a bit for users to load, then set the assigned user
    setTimeout(() => {
      document.getElementById("edit_user_id").value = taskAssignId;
    }, 500);
  }

  // Set description in editor
  if (editEditor && editEditor.setData) {
    editEditor.setData(taskDescription);
  } else {
    // Fallback for textarea
    const fallbackTextarea = document.getElementById(
      "edit-description-fallback"
    );
    if (fallbackTextarea) {
      fallbackTextarea.value = taskDescription;
    }
  }
});

// Handle technology change in edit modal
// Edit modal technology dropdown - FIXED VERSION
document.addEventListener('DOMContentLoaded', function() {
    const editTechnologySelect = document.getElementById('edit_technology_id');
    if (editTechnologySelect) {
        editTechnologySelect.addEventListener('change', async function() {
            const techId = this.value;
            const userSelect = document.getElementById('edit_user_id');

            if (!techId) {
                userSelect.innerHTML = '<option value="">First select technology</option>';
                return;
            }

            userSelect.innerHTML = '<option value="">Loading users...</option>';
            
            try {
                const response = await fetch('controller/get_users_by_tech.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tech_id: techId
                    })
                });

                const result = await response.json();

                userSelect.innerHTML = '<option value="">Select User</option>';
                
                if (result.success && result.users && result.users.length > 0) {
                    result.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.name;
                        userSelect.appendChild(option);
                    });
                } else {
                    userSelect.innerHTML = '<option value="">No users found</option>';
                }
            } catch (error) {
                console.error('Error loading users for edit modal:', error);
                userSelect.innerHTML = '<option value="">Error loading users</option>';
            }
        });
    }
});
// Submit edit form
document
  .getElementById("edit-task")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    const id = document.getElementById("edit_task_id").value;
    const title = document.getElementById("edit_title").value.trim();
    const description = editEditor.getData().trim();
    const user_id = document.getElementById("edit_user_id").value;
    const due_date = document.getElementById("edit_due_date").value;

    if (!title || !description || !user_id) {
      alert("Please fill all required fields");
      return;
    }

    try {
      const res = await fetch("controller/task.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "update",
          id,
          title,
          description,
          user_id,
          due_date: due_date || null,
        }),
      });

      const result = await res.json();

      if (result.success) {
        document.querySelector("#edit-task-modal .close-modal").click();
        await getTasks();
        showToast("success", "Task updated successfully");
      } else {
        showToast("error", result.message);
      }
    } catch (err) {
      console.error(err);
      alert("Update failed");
    }
  });

// Add this CSS function to fix modal scrolling
function addModalScrollStyles() {
  const style = document.createElement("style");
  style.textContent = `
            .modal-content {
                display: flex;
                flex-direction: column;
                max-height: 85vh;
            }
            .modal-body {
                flex: 1;
                overflow-y: hidden;
                max-height: calc(85vh - 120px);
            }
            .modal-footer {
                flex-shrink: 0;
                padding-top: 1rem;
                border-top: 1px solid #e5e7eb;
                background: white;
                position: sticky;
                bottom: 0;
            }
            .dark .modal-footer {
                border-top-color: #4b5563;
                background: #1f2937;
            }
            .ck-editor__editable {
                max-height: 200px !important;
                overflow-y: auto !important;
            }
        `;
  document.head.appendChild(style);
}

// Call this function when the page loads
document.addEventListener("DOMContentLoaded", function () {
  addModalScrollStyles();
});
