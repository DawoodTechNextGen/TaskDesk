<?php
session_start();
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>

<?php require_once('./include/config.php') ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TaskDesk - Manage your tasks and boost your productivity.</title>
  <link rel="icon" type="image/png" sizes="32x32" href="./assets/images/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="./assets/images/favicon-16x16.png">

  <!-- Apply the saved theme before first paint so there is no light/dark flash -->
  <script>
    (function() {
      try {
        const darkModePref = localStorage.getItem('darkMode');
        if (darkModePref === 'enabled' ||
          (!darkModePref && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
      } catch (e) {
        console.warn('Login dark-mode init error', e);
      }
    })();
  </script>
  <script src="./assets/js/tailwind.js"></script>
  <link rel="stylesheet" href="./assets/css/libs/animation.css">
  <style>
    @font-face {
      font-family: 'Montserrat';
      font-weight: 400;
      font-display: swap;
      src: url('./assets/fonts/static/Montserrat-Regular.ttf') format('truetype');
    }

    @font-face {
      font-family: 'Montserrat';
      font-weight: 600;
      font-display: swap;
      src: url('./assets/fonts/static/Montserrat-SemiBold.ttf') format('truetype');
    }

    body {
      font-family: 'Montserrat', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
      background: #f4f6fb;
    }

    html.dark body {
      background: #0b1120;
      color: #e5e7eb;
    }

    /* Brand panel */
    .brand-panel {
      background: radial-gradient(circle at 15% 20%, rgba(59, 130, 246, .45), transparent 45%),
        radial-gradient(circle at 85% 85%, rgba(37, 99, 235, .40), transparent 45%),
        linear-gradient(145deg, #0f1b3d 0%, #0b1226 100%);
    }

    .brand-grid {
      background-image: linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
      background-size: 36px 36px;
      mask-image: radial-gradient(ellipse at center, #000 40%, transparent 80%);
      -webkit-mask-image: radial-gradient(ellipse at center, #000 40%, transparent 80%);
    }

    .float-card {
      animation: floaty 6s ease-in-out infinite;
    }

    .float-card.delay {
      animation-delay: -3s;
    }

    @keyframes floaty {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-10px);
      }
    }

    .fade-up {
      animation: fadeUp .6s cubic-bezier(.16, 1, .3, 1) both;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(14px);
      }

      to {
        opacity: 1;
        transform: none;
      }
    }

    /* Button loader */
    .loader {
      width: 18px;
      height: 18px;
      border: 2px solid #ffffff;
      border-bottom-color: transparent;
      border-radius: 50%;
      display: inline-block;
      box-sizing: border-box;
      animation: rotation 1s linear infinite;
    }

    @keyframes rotation {
      to {
        transform: rotate(360deg);
      }
    }

    @media (prefers-reduced-motion: reduce) {

      .float-card,
      .fade-up,
      .loader {
        animation: none;
      }
    }
  </style>
</head>

<body class="min-h-screen">
  <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

  <div class="min-h-screen flex">

    <!-- Brand panel (desktop) -->
    <aside class="brand-panel relative hidden lg:flex lg:w-1/2 xl:w-[55%] overflow-hidden text-white">
      <div class="brand-grid absolute inset-0"></div>

      <div class="relative z-10 flex flex-col justify-between w-full p-12 xl:p-16">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-white/10 backdrop-blur flex items-center justify-center ring-1 ring-white/15">
            <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 246.43 217" aria-hidden="true">
              <polygon points="75.83 92.24 75.83 217 49.69 217 49.69 117 0 117 0 92.24 75.83 92.24" fill="#60A5FA" />
              <path d="M509.55,301.42A107.07,107.07,0,0,1,402.48,408.5H343.37V283.74h75.38V308.5H369.51v70.29h27.9a80.81,80.81,0,1,0,0-161.61H318.58V251.5H418.75v27.77H263.12V251.5h31v-60H399.63A109.92,109.92,0,0,1,509.55,301.42Z" transform="translate(-263.12 -191.5)" fill="#60A5FA" />
            </svg>
          </div>
          <div>
            <div class="text-xl font-semibold tracking-tight">TaskDesk</div>
            <div class="text-xs text-blue-200/80">by DawoodTech NextGen</div>
          </div>
        </div>

        <!-- Headline + preview cards -->
        <div class="max-w-xl">
          <h1 class="text-4xl xl:text-[2.75rem] font-semibold leading-tight tracking-tight">
            Learn, build and grow<br><span class="text-blue-300">one task at a time.</span>
          </h1>
          <p class="mt-5 text-blue-100/80 text-base leading-relaxed">
            Your internship workspace for tasks, weekly roadmaps, reviews and attendance, all in one place.
          </p>

          <div class="mt-10 relative h-56">
            <!-- Task card -->
            <div class="float-card absolute left-0 top-0 w-72 rounded-2xl bg-white/10 backdrop-blur-md ring-1 ring-white/15 p-5 shadow-2xl">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-blue-200">Week 3 · Task</span>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-full bg-emerald-400/20 text-emerald-300">Approved</span>
              </div>
              <div class="mt-3 font-semibold">Build a REST API with auth</div>
              <div class="mt-4 h-2 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full w-4/5 rounded-full bg-gradient-to-r from-blue-400 to-blue-300"></div>
              </div>
              <div class="mt-2 text-xs text-blue-100/70">80% of this week's roadmap done</div>
            </div>
            <!-- Stats card -->
            <div class="float-card delay absolute right-0 top-24 w-60 rounded-2xl bg-white text-gray-800 p-5 shadow-2xl">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <div class="text-2xl font-semibold leading-none">24</div>
                  <div class="text-xs text-gray-500 mt-1">Tasks completed</div>
                </div>
              </div>
              <div class="mt-4 flex items-end gap-1.5 h-10" aria-hidden="true">
                <div class="flex-1 rounded bg-blue-100 h-3"></div>
                <div class="flex-1 rounded bg-blue-200 h-5"></div>
                <div class="flex-1 rounded bg-blue-300 h-4"></div>
                <div class="flex-1 rounded bg-blue-400 h-7"></div>
                <div class="flex-1 rounded bg-blue-500 h-6"></div>
                <div class="flex-1 rounded bg-blue-600 h-10"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="text-xs text-blue-200/60">&copy; <?= date('Y') ?> DawoodTech NextGen. All rights reserved.</div>
      </div>
    </aside>

    <!-- Sign-in form -->
    <main class="flex-1 flex items-center justify-center px-4 py-10 sm:px-6">
      <div class="w-full max-w-md fade-up">
        <!-- Logo (mobile / tablet) -->
        <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
          <svg class="w-9 h-9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 246.43 217" aria-hidden="true">
            <polygon points="75.83 92.24 75.83 217 49.69 217 49.69 117 0 117 0 92.24 75.83 92.24" fill="#3B82F6" />
            <path d="M509.55,301.42A107.07,107.07,0,0,1,402.48,408.5H343.37V283.74h75.38V308.5H369.51v70.29h27.9a80.81,80.81,0,1,0,0-161.61H318.58V251.5H418.75v27.77H263.12V251.5h31v-60H399.63A109.92,109.92,0,0,1,509.55,301.42Z" transform="translate(-263.12 -191.5)" fill="#3B82F6" />
          </svg>
          <span class="text-2xl font-semibold text-gray-900 dark:text-white">TaskDesk</span>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-blue-900/5 ring-1 ring-gray-100 dark:ring-gray-800 p-8 sm:p-10">
          <h2 class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-white tracking-tight">Welcome back</h2>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sign in to continue to your workspace.</p>

          <form id="login-form" method="POST" action="<?= BASE_URL ?>controller/auth.php" autocomplete="on" class="mt-8 space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="login-email">Email address</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.24a2.25 2.25 0 01-1.07 1.92l-7.5 4.61a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.92V6.75" />
                  </svg>
                </span>
                <input
                  id="login-email"
                  type="email"
                  name="email"
                  required
                  autocomplete="email"
                  class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50/60 text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:bg-gray-800"
                  placeholder="you@example.com">
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="login-password">Password</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                  </svg>
                </span>
                <input
                  id="login-password"
                  type="password"
                  name="password"
                  required
                  autocomplete="current-password"
                  class="w-full pl-11 pr-12 py-3 rounded-xl border border-gray-200 bg-gray-50/60 text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:bg-gray-800"
                  placeholder="Enter your password">
                <button type="button" id="toggle-password" aria-label="Show password"
                  class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"></button>
              </div>
            </div>

            <button type="submit" id="signin-btn"
              class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 shadow-lg shadow-blue-600/25 focus:outline-none focus:ring-4 focus:ring-blue-500/30 transition disabled:opacity-80 disabled:cursor-not-allowed">
              <span id="signin-text">Sign in</span>
              <span id="signin-loader" class="loader hidden" aria-hidden="true"></span>
              <svg id="signin-arrow" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </button>
          </form>

          <div class="mt-8 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.04A11.96 11.96 0 013.6 6 12 12 0 003 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75h-.15c-3.2 0-6.1-1.25-8.25-3.29z" />
            </svg>
            <span>Use the credentials shared with you by DawoodTech NextGen.</span>
          </div>
        </div>

        <p class="lg:hidden mt-8 text-center text-xs text-gray-400">&copy; <?= date('Y') ?> DawoodTech NextGen</p>
      </div>
    </main>
  </div>

  <?php if (!empty($error)): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        showToast("error", <?= json_encode($error) ?>);
      });
    </script>
  <?php endif; ?>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const toggleBtn = document.getElementById("toggle-password");
      const passwordInput = document.getElementById("login-password");

      const eyeOpen = `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3.01 9.96 7.18a1 1 0 010 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3.01-9.96-7.18z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`;
      const eyeClosed = `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 001.93 12C3.23 16.34 7.24 19.5 12 19.5c.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0112 4.5c4.76 0 8.77 3.16 10.07 7.5a10.52 10.52 0 01-4.29 5.77M6.23 6.23L3 3m3.23 3.23l3.65 3.65m7.89 7.89L21 21m-3.23-3.23l-3.65-3.65m0 0a3 3 0 10-4.24-4.24m4.24 4.24L9.88 9.88"/></svg>`;

      toggleBtn.innerHTML = eyeOpen;
      toggleBtn.addEventListener("click", function() {
        const isHidden = passwordInput.type === "password";
        passwordInput.type = isHidden ? "text" : "password";
        toggleBtn.innerHTML = isHidden ? eyeClosed : eyeOpen;
        toggleBtn.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
      });

      // Loading state while the credentials are checked
      document.getElementById("login-form").addEventListener("submit", function() {
        const btn = document.getElementById("signin-btn");
        btn.disabled = true;
        document.getElementById("signin-text").textContent = "Signing in...";
        document.getElementById("signin-arrow").classList.add("hidden");
        document.getElementById("signin-loader").classList.remove("hidden");
      });
    });

    // Coming back with the browser's Back button restores the page from cache with the
    // button still disabled, so reset it.
    window.addEventListener("pageshow", () => {
      const btn = document.getElementById("signin-btn");
      btn.disabled = false;
      document.getElementById("signin-text").textContent = "Sign in";
      document.getElementById("signin-arrow").classList.remove("hidden");
      document.getElementById("signin-loader").classList.add("hidden");
    });
  </script>
  <script src="./assets/js/script.js?v=<?= filemtime(__DIR__ . '/assets/js/script.js') ?>"></script>
</body>

</html>
