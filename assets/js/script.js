tailwind.config = {
    darkMode: 'class'
}


const toggleSidebar = document.getElementById('toggle-sidebar');
const sidebar = document.getElementById('sidebar');
const openIcon = toggleSidebar ? toggleSidebar.querySelector('.sidebar-open') : null;
const closeIcon = toggleSidebar ? toggleSidebar.querySelector('.sidebar-close') : null;
const sidebarItems = document.querySelectorAll('.sidebar-item');
const logoText = document.getElementById('logo-text');
const menuTitle = document.getElementById('menu-title');
const secondaryTitle = document.getElementById('secondary-title') ?? document.createElement('div');
const logo = document.getElementById('logo');
const footer_profile = document.getElementById('footer-profile');
const sidebarLinks = document.querySelectorAll('.sidebar-link');
const header = document.querySelector('.header-half');
const usersToggle = document.getElementById('users-toggle') ?? document.createElement('div');

const usersItems = document.getElementById('users-items') ?? document.createElement('div');
const collapsedIcon = document.getElementById('collapsed-icon') ?? document.createElement('div');
function toggleSidebarState() {
    sidebar.classList.toggle('sidebar-collapsed');
    sidebar.classList.toggle('sidebar-expanded');

    sidebarItems.forEach(item => {
        item.classList.toggle('hidden');
    });

    logoText.classList.toggle('hidden');
    secondaryTitle.classList.toggle('hidden');

    if (sidebar.classList.contains('sidebar-collapsed')) {
        logo.classList.remove('space-x-1');
        footer_profile.classList.remove('space-x-3');
        openIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
        header.classList.remove('header-half');
        header.classList.add('header-full');
        sidebarLinks.forEach(link => {
            link.classList.remove('space-x-2');
        });
        usersItems.classList.add('hidden');
        collapsedIcon.classList.add('hidden');

    } else {
        header.classList.add('header-half');
        header.classList.remove('header-full');
        openIcon.classList.add('hidden');
        closeIcon.classList.remove('hidden');
        logo.classList.add('space-x-1');
        footer_profile.classList.add('space-x-3');
        sidebarLinks.forEach(link => {
            link.classList.add('space-x-2');
        });
        collapsedIcon.classList.remove('hidden');
    }

    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('sidebar-collapsed'));
}

usersToggle.addEventListener('click', () => {
    if (!sidebar.classList.contains('sidebar-collapsed')) {
        usersItems.classList.toggle('hidden');
        const icon = usersToggle.querySelector('.users-icon');
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
        localStorage.setItem('usersCollapsed', usersItems.classList.contains('hidden'));
    }
});

if (localStorage.getItem('usersCollapsed') === 'true') {
    usersItems.classList.add('hidden');
}

if (toggleSidebar && sidebar) {
    toggleSidebar.addEventListener('click', toggleSidebarState);

    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        toggleSidebarState();
    }
}

const userMenuButton = document.getElementById('user-menu-button');
const userMenu = document.getElementById('user-menu');
const arrow = document.getElementById("userArrow");
if (userMenuButton && userMenu) {
    userMenuButton.addEventListener('click', () => {
        const isHidden = userMenu.classList.contains('hidden');

        if (arrow) arrow.classList.toggle("rotate-180");

        if (isHidden) {
            // Prepare to animate
            userMenu.classList.remove("hidden");

            // Slide down + fade in
            requestAnimationFrame(() => {
                userMenu.classList.remove("opacity-0", "-translate-y-2");
                userMenu.classList.add("opacity-100", "translate-y-0");
            });

        } else {
            // Slide up + fade out
            userMenu.classList.remove("opacity-100", "translate-y-0");
            userMenu.classList.add("opacity-0", "-translate-y-2");

            // After animation ends → hide
            setTimeout(() => {
                userMenu.classList.add("hidden");
            }, 300); // same as duration-300
        }
    });
}

const tabs = document.querySelectorAll(".tab-btn");
const sections = {
    "personal-info": document.getElementById("personal-info"),
    "password-settings": document.getElementById("password-settings")
};

tabs.forEach(tab => {
    tab.addEventListener("click", (e) => {
        e.preventDefault();

        const target = tab.dataset.target;

        // Hide all sections
        Object.values(sections).forEach(s => s.classList.add("hidden"));

        // Show only target section
        sections[target].classList.remove("hidden");

        // Remove active styling from all tabs
        tabs.forEach(t => {
            t.classList.remove("border-indigo-500", "text-indigo-600");
            t.classList.add("border-transparent", "text-gray-500");
        });

        // Add active styles to clicked tab
        tab.classList.add("border-indigo-500", "text-indigo-600");
        tab.classList.remove("border-transparent", "text-gray-500");
    });
});


document.addEventListener('click', (e) => {
    if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
        userMenu.classList.add('hidden');
    }
});

sidebarLinks.forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        sidebarLinks.forEach(l => l.classList.remove('active-sidebar-link'));
        this.classList.add('active-sidebar-link');
    });
});

function setupModal(modalId) {
    const modal = document.getElementById(modalId);
    const closeButtons = modal.querySelectorAll('.close-modal, .close-btn');

    document.addEventListener('click', (e) => {
        const btn = e.target.closest(`.open-modal[data-modal="${modalId}"]`);
        if (btn) {
            modal.classList.remove('hidden');
        }
    });

    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });
}

document.querySelectorAll('.modal').forEach(modal => {
    setupModal(modal.id);
});

const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-upload');
const uploadProgress = document.getElementById('upload-progress');
const progressBar = document.getElementById('progress-bar');
const progressPercent = document.getElementById('progress-percent');
const uploadedFiles = document.getElementById('uploaded-files');
const filesList = document.getElementById('files-list');

if (dropZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    dropZone.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', handleFiles);

}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight() {
    dropZone.classList.add('highlight');
}

function unhighlight() {
    dropZone.classList.remove('highlight');
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles({ target: { files } });
}

function handleFiles(e) {
    const files = e.target.files;
    if (files.length === 0) return;
    uploadProgress.classList.remove('hidden');

    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);

            setTimeout(() => {
                uploadProgress.classList.add('hidden');
                showUploadedFiles(files);
            }, 500);
        }

        progressBar.style.width = `${progress}%`;
        progressPercent.textContent = `${Math.round(progress)}%`;
    }, 200);
}

function showUploadedFiles(files) {
    dropZone.classList.add('hidden');
    filesList.innerHTML = '';

    Array.from(files).forEach(file => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';

        const fileType = getFileType(file.name);
        const fileSize = formatFileSize(file.size);

        fileItem.innerHTML = `
            <div class="file-icon">
                <i class="fas ${getFileIcon(fileType)}"></i>
            </div>
            <div class="file-info">
                <div class="file-name">${file.name}</div>
                <div class="file-size">${fileSize}</div>
            </div>
            <div class="file-remove" title="Remove file">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-red-500 hover:text-red-600 cursor-pointer">
                    <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </div>
        `;
        const removeBtn = fileItem.querySelector('.file-remove');
        removeBtn.addEventListener('click', () => {
            dropZone.classList.remove('hidden');
            fileItem.remove();
            if (filesList.children.length === 0) {
                uploadedFiles.classList.add('hidden');
            }
        });

        filesList.appendChild(fileItem);
    });

    uploadedFiles.classList.remove('hidden');
}

function getFileType(filename) {
    const extension = filename.split('.').pop().toLowerCase();
    const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const docTypes = ['doc', 'docx', 'txt', 'rtf'];
    const pdfTypes = ['pdf'];
    const spreadsheetTypes = ['xls', 'xlsx', 'csv'];

    if (imageTypes.includes(extension)) return 'image';
    if (docTypes.includes(extension)) return 'document';
    if (pdfTypes.includes(extension)) return 'pdf';
    if (spreadsheetTypes.includes(extension)) return 'spreadsheet';
    return 'file';
}

function getFileIcon(type) {
    const icons = {
        'image': 'fa-file-image',
        'document': 'fa-file-word',
        'pdf': 'fa-file-pdf',
        'spreadsheet': 'fa-file-excel',
        'file': 'fa-file-alt'
    };
    return icons[type] || 'fa-file-alt';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    html.classList.toggle('dark');

    localStorage.setItem('darkMode', isDark ? 'disabled' : 'enabled');
    updateDarkModeIcons(!isDark);
}

function updateDarkModeIcons(isDark) {
    const moonIcons = document.querySelectorAll('.moon');
    const sunIcons = document.querySelectorAll('.sun');

    moonIcons.forEach(icon => {
        if (isDark) {
            icon.classList.add('hidden');
        } else {
            icon.classList.remove('hidden');
        }
    });

    sunIcons.forEach(icon => {
        if (isDark) {
            icon.classList.remove('hidden');
        } else {
            icon.classList.add('hidden');
        }
    });
}

function initDarkMode() {
    const html = document.documentElement;
    const darkModePref = localStorage.getItem('darkMode');

    if (darkModePref === 'enabled' ||
        (!darkModePref && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        html.classList.add('dark');
        updateDarkModeIcons(true);
    } else {
        html.classList.remove('dark');
        updateDarkModeIcons(false);
    }
}

initDarkMode();

const darkModeToggle = document.getElementById('dark-mode-toggle');
if (darkModeToggle) {
    darkModeToggle.addEventListener('click', toggleDarkMode);
}

const sidebarThemeToggle = document.getElementById('sidebar-theme-toggle');
if (sidebarThemeToggle) {
    sidebarThemeToggle.addEventListener('click', toggleDarkMode);
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toast-container');
    const toastId = Date.now();
    const toast = document.createElement('div');
    toast.id = `toast-${toastId}`;
    toast.className = `p-4 rounded-lg shadow-lg flex items-center justify-between transition-all duration-300 max-w-sm opacity-0 transform translate-x-full`;

    const styles = {
        success: 'bg-emerald-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-black',
        info: 'bg-blue-500 text-white'
    };

    toast.className += ` ${styles[type]}`;
    toast.innerHTML = `
        <span>${message}</span>
        <button onclick="dismissToast(${toastId})" class="ml-4 text-white hover:text-gray-200">✕</button>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('opacity-0', 'translate-x-full');
    }, 100);

    setTimeout(() => {
        dismissToast(toastId);
    }, 5000);
}

function dismissToast(toastId) {
    const toast = document.getElementById(`toast-${toastId}`);
    if (toast) {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}
function formatDateTime(datetime) {
    const dateObj = new Date(datetime);

    const day = String(dateObj.getDate()).padStart(2, '0');
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const month = monthNames[dateObj.getMonth()];
    const year = String(dateObj.getFullYear()).slice(-2);

    let hours = dateObj.getHours();
    const minutes = String(dateObj.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;

    return `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
}
function getThemeColors() {
    const isDarkMode = localStorage.getItem('darkMode');
    return {
        isDarkMode,
        textColor: (isDarkMode === 'disabled') ? 'oklch(70.7% 0.022 261.325)' : 'oklch(70.7% 0.022 261.325)',
        gridColor: (isDarkMode === 'disabled') ? 'oklch(70.7% 0.022 261.325)' : 'oklch(70.7% 0.022 261.325)',
        backgroundColor: (isDarkMode === 'disabled') ? '#fff' : '#1F2937'
    };
}

function getStatusBadge(status) {
    let classes = "";

    switch (status) {
        case "complete":
            classes = "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200";
            break;
        case "pending":
            classes = "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200";
            break;
        case "working":
            classes = "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200";
            break;
        case "pending_review":
            classes = "bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200";
            break;
        case "approved":
            classes = "bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200";
            break;
        case "rejected":
            classes = "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200";
            break;
        case "needs_improvement":
            classes = "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200";
            break;
        case "expired":
        case "Expired":
            classes = "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200";
            break;
        default:
            classes = "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200";
    }

    return `<span id="status" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${classes}">
                ${status}
            </span>`;
}
function formatDuration(seconds) {
    let h = String(Math.floor(seconds / 3600)).padStart(2, '0');
    let m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    let s = String(seconds % 60).padStart(2, '0');
    return `${h}h: ${m}m: ${s}s`;
}
