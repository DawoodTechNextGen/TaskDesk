<form id="login-form" autocomplete="on">
  <div class="mb-2">
    <label class="block text-gray-700 text-sm font-medium mb-2" for="login-email">Email</label>
    <input 
      type="email" 
      id="login-email" 
      name="email" 
      autocomplete="email" 
      class="border border-gray-200 w-full p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500 transition"
      placeholder="Enter your email"
    >
  </div>
  <div class="mb-5 relative">
    <label class="block text-gray-700 text-sm font-medium mb-2" for="login-password">Password</label>
    <input 
      type="password" 
      id="login-password" 
      name="password" 
      autocomplete="current-password"
      class="border border-gray-200 w-full p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500 transition pr-10"
      placeholder="Enter your password"
    >
    <!-- Toggle button -->
    <button type="button" id="toggle-password"
      class="absolute inset-y-0 right-0 top-7 flex items-center px-3 text-gray-500">
       <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.2">
                  <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                  <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                  <g id="SVGRepo_iconCarrier">
                    <path d="M4 12C4 12 5.6 7 12 7M12 7C18.4 7 20 12 20 12M12 7V4M18 5L16 7.5M6 5L8 7.5M15 13C15 14.6569 13.6569 16 12 16C10.3431 16 9 14.6569 9 13C9 11.3431 10.3431 10 12 10C13.6569 10 15 11.3431 15 13Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                  </g>
                </svg>
    </button>
  </div>
  <button type="submit" id="signin" class="w-full auth-btn text-white p-3 rounded-lg font-medium
  bg-gradient-to-r from-blue-500 to-blue-600 cursor-pointer flex items-center justify-center gap-2">
    <span id="signin-text">Sign in</span>
    <div id="signin-loader" class="loader hidden"></div>
  </button>
</form>
