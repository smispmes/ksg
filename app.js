// API-based JavaScript for KSG SMI Performance System
// This version uses PHP API endpoints instead of localStorage

// Global variables
let currentUser = null;
let isLoggedIn = false;
let userType = null;
let isAdmin = false;
let currentFilter = 'all';
let currentUploadTaskId = null;
let profilePicContext = 'user';
let selectedProfileImage = null;
let profilePicZoom = 1;

// API helper function
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(endpoint, options);
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
}

// Navigation functions
function showUserAuth() {
    document.getElementById('mainScreen').classList.add('hidden');
    document.getElementById('userAuthScreen').classList.remove('hidden');
}

function showAdminAuth() {
    document.getElementById('mainScreen').classList.add('hidden');
    document.getElementById('adminAuthScreen').classList.remove('hidden');
}

function backToMain() {
    document.getElementById('userAuthScreen').classList.add('hidden');
    document.getElementById('adminAuthScreen').classList.add('hidden');
    document.getElementById('mainScreen').classList.remove('hidden');
    clearForms();
}

function clearForms() {
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => input.value = '');
    const errors = document.querySelectorAll('[id$="Error"]');
    errors.forEach(error => error.classList.add('hidden'));
}

// Tab switching functions
function switchUserTab(tab) {
    const loginTab = document.getElementById('userLoginTab');
    const registerTab = document.getElementById('userRegisterTab');
    const loginForm = document.getElementById('userLoginForm');
    const registerForm = document.getElementById('userRegisterForm');

    if (tab === 'login') {
        loginTab.className = loginTab.className.replace('tab-inactive', 'tab-active');
        registerTab.className = registerTab.className.replace('tab-active', 'tab-inactive');
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
    } else {
        registerTab.className = registerTab.className.replace('tab-inactive', 'tab-active');
        loginTab.className = loginTab.className.replace('tab-active', 'tab-inactive');
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
    }
    clearForms();
}

function switchAdminTab(tab) {
    const loginTab = document.getElementById('adminLoginTab');
    const registerTab = document.getElementById('adminRegisterTab');
    const loginForm = document.getElementById('adminLoginForm');
    const registerForm = document.getElementById('adminRegisterForm');

    if (tab === 'login') {
        loginTab.className = loginTab.className.replace('tab-inactive', 'tab-active');
        registerTab.className = registerTab.className.replace('tab-active', 'tab-inactive');
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
    } else {
        registerTab.className = registerTab.className.replace('tab-inactive', 'tab-active');
        loginTab.className = loginTab.className.replace('tab-active', 'tab-inactive');
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
    }
    clearForms();
}

// User authentication functions
async function userLogin() {
    const email = document.getElementById('userLoginEmail').value;
    const password = document.getElementById('userLoginPassword').value;
    const errorDiv = document.getElementById('userLoginError');

    if (!email || !password) {
        showError(errorDiv, 'Please fill in all fields');
        return;
    }

    try {
        const result = await apiCall('api/auth.php?action=user_login', 'POST', {
            email: email,
            password: password
        });

        if (result.status === 'success') {
            currentUser = result.user;
            isLoggedIn = true;
            userType = 'user';
            showUserDashboard();
        } else {
            showError(errorDiv, result.message || 'Invalid email or password');
        }
    } catch (error) {
        console.error('Login error:', error);
        showError(errorDiv, 'Login failed. Please try again.');
    }
}

async function userRegister() {
    const name = document.getElementById('userRegisterName').value;
    const email = document.getElementById('userRegisterEmail').value;
    const password = document.getElementById('userRegisterPassword').value;
    const confirmPassword = document.getElementById('userRegisterConfirm').value;
    const errorDiv = document.getElementById('userRegisterError');

    if (!name || !email || !password || !confirmPassword) {
        showError(errorDiv, 'Please fill in all fields');
        return;
    }

    if (password !== confirmPassword) {
        showError(errorDiv, 'Passwords do not match');
        return;
    }

    if (password.length < 8) {
        showError(errorDiv, 'Password must be at least 8 characters long');
        return;
    }

    try {
        const result = await apiCall('api/auth.php?action=user_register', 'POST', {
            name: name,
            email: email,
            password: password,
            confirm_password: confirmPassword
        });

        if (result.status === 'success') {
            showSuccess(errorDiv, 'Account created successfully! Please login.');
            setTimeout(() => {
                switchUserTab('login');
                document.getElementById('userLoginEmail').value = email;
            }, 1500);
        } else {
            showError(errorDiv, result.message || 'Registration failed');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showError(errorDiv, 'Registration failed. Please try again.');
    }
}

// Admin authentication functions
async function adminLogin() {
    const email = document.getElementById('adminLoginEmail').value;
    const password = document.getElementById('adminLoginPassword').value;
    const indexCode = document.getElementById('adminLoginCode').value;
    const errorDiv = document.getElementById('adminLoginError');

    if (!email || !password || !indexCode) {
        showError(errorDiv, 'Please fill in all fields');
        return;
    }

    try {
        const result = await apiCall('api/auth.php?action=admin_login', 'POST', {
            email: email,
            password: password,
            index_code: indexCode
        });

        if (result.status === 'success') {
            currentUser = result.admin;
            isLoggedIn = true;
            userType = 'admin';
            isAdmin = true;
            showAdminDashboard();
        } else {
            showError(errorDiv, result.message || 'Invalid credentials or index code');
        }
    } catch (error) {
        console.error('Admin login error:', error);
        showError(errorDiv, 'Login failed. Please try again.');
    }
}

async function adminRegister() {
    const name = document.getElementById('adminRegisterName').value;
    const email = document.getElementById('adminRegisterEmail').value;
    const password = document.getElementById('adminRegisterPassword').value;
    const confirmPassword = document.getElementById('adminRegisterConfirm').value;
    const indexCode = document.getElementById('adminRegisterCode').value;
    const errorDiv = document.getElementById('adminRegisterError');

    if (!name || !email || !password || !confirmPassword || !indexCode) {
        showError(errorDiv, 'Please fill in all fields');
        return;
    }

    if (password !== confirmPassword) {
        showError(errorDiv, 'Passwords do not match');
        return;
    }

    if (password.length < 8) {
        showError(errorDiv, 'Password must be at least 8 characters long');
        return;
    }

    try {
        const result = await apiCall('api/auth.php?action=admin_register', 'POST', {
            name: name,
            email: email,
            password: password,
            confirm_password: confirmPassword,
            index_code: indexCode
        });

        if (result.status === 'success') {
            showSuccess(errorDiv, 'Admin account created successfully! Please login.');
            setTimeout(() => {
                switchAdminTab('login');
                document.getElementById('adminLoginEmail').value = email;
            }, 1500);
        } else {
            showError(errorDiv, result.message || 'Registration failed');
        }
    } catch (error) {
        console.error('Admin registration error:', error);
        showError(errorDiv, 'Registration failed. Please try again.');
    }
}

// Logout function
async function logout() {
    try {
        await apiCall('api/auth.php?action=logout', 'POST');
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    // Clear client-side data
    currentUser = null;
    isLoggedIn = false;
    userType = null;
    isAdmin = false;
    
    // Hide all dashboards
    document.getElementById('userDashboard').classList.add('hidden');
    document.getElementById('adminDashboard').classList.add('hidden');
    
    // Show main screen
    document.getElementById('mainScreen').classList.remove('hidden');
    document.getElementById('userAuthScreen').classList.add('hidden');
    document.getElementById('adminAuthScreen').classList.add('hidden');
    
    // Reload page to ensure clean state
    window.location.reload();
}

// Dashboard functions
function showUserDashboard() {
    hideAllScreens();
    document.getElementById('userDashboard').classList.remove('hidden');
    document.getElementById('userWelcome').textContent = `Welcome, ${currentUser.name}`;
    loadUserDashboardData();
}

function showAdminDashboard() {
    hideAllScreens();
    document.getElementById('adminDashboard').classList.remove('hidden');
    document.getElementById('adminWelcome').textContent = `Welcome, ${currentUser.name}`;
    loadAdminDashboardData();
}

function hideAllScreens() {
    document.getElementById('mainScreen').classList.add('hidden');
    document.getElementById('userAuthScreen').classList.add('hidden');
    document.getElementById('adminAuthScreen').classList.add('hidden');
    document.getElementById('userDashboard').classList.add('hidden');
    document.getElementById('adminDashboard').classList.add('hidden');
}

// Load dashboard data
async function loadUserDashboardData() {
    try {
        // Load user tasks and stats
        await loadUserTasks();
        await updateTaskStats();
    } catch (error) {
        console.error('Error loading user dashboard:', error);
    }
}

async function loadAdminDashboardData() {
    try {
        // Load admin analytics and data
        await updateAdminStats();
    } catch (error) {
        console.error('Error loading admin dashboard:', error);
    }
}

// Utility functions
function showError(errorDiv, message) {
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden');
    setTimeout(() => {
        errorDiv.classList.add('hidden');
    }, 5000);
}

function showSuccess(successDiv, message) {
    successDiv.textContent = message;
    successDiv.classList.remove('hidden');
    successDiv.classList.add('text-green-500');
    successDiv.classList.remove('text-red-500');
    setTimeout(() => {
        successDiv.classList.add('hidden');
    }, 5000);
}

async function updateAdminStats() {
    try {
        const result = await apiCall('api/admin.php?action=get_analytics');
        if (result.status === 'success') {
            const analytics = result.analytics;
            document.getElementById('totalUsers').textContent = analytics.total_users || 0;
        }
    } catch (error) {
        console.error('Error updating admin stats:', error);
    }
}

// Password visibility toggle function
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const eyeIcon = document.getElementById(inputId + 'Eye');

    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
    } else {
        input.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

// Menu toggle functions
function toggleUserMenu() {
    const sidebar = document.getElementById('userSidebar');
    const overlay = document.getElementById('userSidebarOverlay');

    if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}

function toggleAdminMenu() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminSidebarOverlay');

    if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}

// --- Category/Task Data ---
// --- Admin Task Assignment Section Frontend JS v5 ---
// Add these functions to your app.js file to fix the missing admin section functions

// Admin Section Management Functions
function showTaskAssignmentSection() {
    hideAllAdminSections();
    
    // Create task assignment section if it doesn't exist
    let taskAssignmentSection = document.getElementById('taskAssignmentSection');
    if (!taskAssignmentSection) {
        taskAssignmentSection = createTaskAssignmentSection();
        document.querySelector('#adminDashboard .max-w-7xl').appendChild(taskAssignmentSection);
    }
    
    taskAssignmentSection.classList.remove('hidden');
    
    // Load necessary data
    fetchUserList();
    fetchRecentAssignments();
    renderAdminQuickAssign();
}

function showUserManagementSection() {
    hideAllAdminSections();
    
    let userManagementSection = document.getElementById('adminUserManagement');
    if (!userManagementSection) {
        userManagementSection = createUserManagementSection();
        document.querySelector('#adminDashboard .max-w-7xl').appendChild(userManagementSection);
    }
    
    userManagementSection.classList.remove('hidden');
    loadUserManagementData();
}

function showSecuritySettingsSection() {
    hideAllAdminSections();
    
    let securitySection = document.getElementById('adminSecuritySettings');
    if (!securitySection) {
        securitySection = createSecuritySettingsSection();
        document.querySelector('#adminDashboard .max-w-7xl').appendChild(securitySection);
    }
    
    securitySection.classList.remove('hidden');
    loadSecuritySettings();
}

function showBackupRestoreSection() {
    hideAllAdminSections();
    
    let backupSection = document.getElementById('adminBackupRestore');
    if (!backupSection) {
        backupSection = createBackupRestoreSection();
        document.querySelector('#adminDashboard .max-w-7xl').appendChild(backupSection);
    }
    
    backupSection.classList.remove('hidden');
    loadBackupHistory();
}

function showAnalyticsSection() {
    hideAllAdminSections();
    
    let analyticsSection = document.getElementById('adminAnalytics');
    if (!analyticsSection) {
        analyticsSection = createAnalyticsSection();
        document.querySelector('#adminDashboard .max-w-7xl').appendChild(analyticsSection);
    }
    
    analyticsSection.classList.remove('hidden');
    loadAnalyticsData();
}

// Create Task Assignment Section
function createTaskAssignmentSection() {
    const section = document.createElement('div');
    section.id = 'taskAssignmentSection';
    section.className = 'hidden';
    section.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                Task Assignment Center
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Manual Task Assignment -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Assign New Task</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
                            <select id="assignUserSelect" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                                <option value="">Choose a user...</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select id="assignCategorySelect" onchange="populateTasksForCategory(this.value)" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                                <option value="">Choose a category...</option>
                                <option value="Financial Stewardship and Discipline">Financial Stewardship and Discipline</option>
                                <option value="Service Delivery">Service Delivery</option>
                                <option value="Core Mandate">Core Mandate</option>
                                <option value="Administration and Infrastructure">Administration and Infrastructure</option>
                                <option value="Cross-Cutting Issues">Cross-Cutting Issues</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Task</label>
                            <select id="assignTaskSelect" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                                <option value="">Choose a task...</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                <select id="assignPrioritySelect" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                    <option value="high">High</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                                <input type="date" id="assignDueDateInput" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instructions (Optional)</label>
                            <textarea id="assignInstructionsInput" rows="3" placeholder="Additional instructions for the user..." class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none resize-none"></textarea>
                        </div>

                        <div id="taskAssignSuccess" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 text-green-800"></div>
                        <div id="taskAssignError" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 text-red-800"></div>

                        <button onclick="assignTaskToUser()" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
                            Assign Task
                        </button>
                    </div>
                </div>

                <!-- Quick Assignment Panel -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Task Assignment</h3>
                    <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                        <div id="adminQuickAssignCategories">
                            <!-- Quick assign buttons will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Assignments -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Assignments</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div id="recentAssignments" class="space-y-2">
                        <!-- Recent assignments will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    `;
    return section;
}

// Create User Management Section
function createUserManagementSection() {
    const section = document.createElement('div');
    section.id = 'adminUserManagement';
    section.className = 'hidden';
    section.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                User Management
            </h2>

            <!-- User Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalUsers">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Active Users</p>
                            <p class="text-2xl font-bold text-gray-900" id="activeUsers">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Recent Logins</p>
                            <p class="text-2xl font-bold text-gray-900" id="recentLogins">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-red-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Inactive Users</p>
                            <p class="text-2xl font-bold text-gray-900" id="inactiveUsers">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User List -->
            <div class="bg-gray-50 rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">All Users</h3>
                    <button onclick="refreshUserList()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Refresh List
                    </button>
                </div>
                <div id="userManagementList" class="space-y-3">
                    <!-- User list will be populated here -->
                </div>
            </div>
        </div>
    `;
    return section;
}

// Create Security Settings Section
function createSecuritySettingsSection() {
    const section = document.createElement('div');
    section.id = 'adminSecuritySettings';
    section.className = 'hidden';
    section.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                Security Settings
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Password Policy -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Password Policy</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Minimum Length: 8 characters</span>
                            <span class="text-green-600">✓</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Require Special Characters</span>
                            <span class="text-green-600">✓</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Require Numbers</span>
                            <span class="text-green-600">✓</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Password Expiry: 90 days</span>
                            <span class="text-green-600">✓</span>
                        </div>
                    </div>
                </div>

                <!-- Session Settings -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Session Settings</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Session Timeout: 30 minutes</span>
                            <span class="text-green-600">✓</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Max Failed Attempts: 5</span>
                            <span class="text-green-600">✓</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Account Lockout: 30 minutes</span>
                            <span class="text-green-600">✓</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access Logs -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Access Logs</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div id="accessLogs" class="space-y-2">
                        <p class="text-gray-500 text-sm">Loading access logs...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    return section;
}

// Create Backup & Restore Section
function createBackupRestoreSection() {
    const section = document.createElement('div');
    section.id = 'adminBackupRestore';
    section.className = 'hidden';
    section.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Backup & Restore
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Create Backup -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800">Create New Backup</h3>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-blue-800 text-sm mb-4">Create a complete backup of the system including all user data, tasks, and configurations.</p>
                        <button onclick="createSystemBackup()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
                            Create Backup Now
                        </button>
                    </div>
                </div>

                <!-- Backup History -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800">Backup History</h3>
                    <div class="bg-gray-50 rounded-lg p-4 max-h-64 overflow-y-auto">
                        <div id="backupHistory" class="space-y-2">
                            <p class="text-gray-500 text-sm">Loading backup history...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Status -->
            <div class="mt-8">
                <div id="backupStatus" class="hidden bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-green-800">Backup operation in progress...</p>
                </div>
            </div>
        </div>
    `;
    return section;
}

// Create Analytics Section
function createAnalyticsSection() {
    const section = document.createElement('div');
    section.id = 'adminAnalytics';
    section.className = 'hidden';
    section.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                System Analytics
            </h2>

            <!-- Analytics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">Task Completion Rate</h3>
                    <p class="text-3xl font-bold" id="completionRate">0%</p>
                    <p class="text-blue-100 text-sm">This month</p>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">Active Users</h3>
                    <p class="text-3xl font-bold" id="analyticsActiveUsers">0</p>
                    <p class="text-green-100 text-sm">Last 30 days</p>
                </div>

                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">Total Tasks</h3>
                    <p class="text-3xl font-bold" id="analyticsTotalTasks">0</p>
                    <p class="text-purple-100 text-sm">All time</p>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Performance Metrics</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Average Task Completion Time</span>
                        <span class="font-semibold" id="avgCompletionTime">Loading...</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Most Active Department</span>
                        <span class="font-semibold" id="mostActiveDept">Loading...</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Peak Usage Hours</span>
                        <span class="font-semibold" id="peakHours">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    return section;
}

// Data loading functions for admin sections
async function loadUserManagementData() {
    try {
        const result = await apiCall('api/admin.php?action=get_user_stats');
        if (result.status === 'success') {
            const stats = result.stats;
            document.getElementById('totalUsers').textContent = stats.total_users || 0;
            document.getElementById('activeUsers').textContent = stats.active_users || 0;
            document.getElementById('recentLogins').textContent = stats.recent_logins || 0;
            document.getElementById('inactiveUsers').textContent = stats.inactive_users || 0;
        }
        
        // Load user list
        const userResult = await apiCall('api/admin.php?action=get_users');
        if (userResult.status === 'success') {
            renderUserList(userResult.users);
        }
    } catch (error) {
        console.error('Error loading user management data:', error);
    }
}

function renderUserList(users) {
    const container = document.getElementById('userManagementList');
    if (!users || users.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-sm">No users found.</p>';
        return;
    }
    
    container.innerHTML = users.map(user => `
        <div class="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700">${user.name.charAt(0).toUpperCase()}</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900">${user.name}</p>
                    <p class="text-sm text-gray-500">${user.email}</p>
                    <p class="text-xs text-gray-400">${user.department || 'No department'}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${user.status || 'active'}
                </span>
                <button onclick="viewUserDetails(${user.id})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View Details
                </button>
            </div>
        </div>
    `).join('');
}

async function loadSecuritySettings() {
    // Load access logs
    try {
        const result = await apiCall('api/admin.php?action=get_access_logs&limit=10');
        if (result.status === 'success') {
            renderAccessLogs(result.logs);
        }
    } catch (error) {
        console.error('Error loading security settings:', error);
    }
}

function renderAccessLogs(logs) {
    const container = document.getElementById('accessLogs');
    if (!logs || logs.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-sm">No access logs found.</p>';
        return;
    }
    
    container.innerHTML = logs.map(log => `
        <div class="flex items-center justify-between p-3 bg-white rounded border border-gray-200">
            <div>
                <span class="text-sm font-medium text-gray-900">${log.action}</span>
                <span class="text-xs text-gray-500 ml-2">${log.user_type}: ${log.user_name || 'Unknown'}</span>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500">${new Date(log.timestamp).toLocaleString()}</p>
                <p class="text-xs text-gray-400">${log.ip_address}</p>
            </div>
        </div>
    `).join('');
}

async function loadBackupHistory() {
    try {
        const result = await apiCall('api/admin.php?action=get_backup_history');
        if (result.status === 'success') {
            renderBackupHistory(result.backups);
        }
    } catch (error) {
        console.error('Error loading backup history:', error);
        document.getElementById('backupHistory').innerHTML = '<p class="text-red-500 text-sm">Failed to load backup history.</p>';
    }
}

function renderBackupHistory(backups) {
    const container = document.getElementById('backupHistory');
    if (!backups || backups.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-sm">No backups found.</p>';
        return;
    }
    
    container.innerHTML = backups.map(backup => `
        <div class="flex items-center justify-between p-3 bg-white rounded border border-gray-200">
            <div>
                <p class="text-sm font-medium text-gray-900">${backup.backup_name}</p>
                <p class="text-xs text-gray-500">${new Date(backup.created_at).toLocaleString()}</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-gray-400">${formatFileSize(backup.file_size)}</span>
                <button onclick="downloadBackup(${backup.id})" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    Download
                </button>
            </div>
        </div>
    `).join('');
}

async function loadAnalyticsData() {
    try {
        const result = await apiCall('api/admin.php?action=get_analytics');
        if (result.status === 'success') {
            const analytics = result.analytics;
            document.getElementById('completionRate').textContent = analytics.completion_rate + '%' || '0%';
            document.getElementById('analyticsActiveUsers').textContent = analytics.active_users || 0;
            document.getElementById('analyticsTotalTasks').textContent = analytics.total_tasks || 0;
            document.getElementById('avgCompletionTime').textContent = analytics.avg_completion_time || 'N/A';
            document.getElementById('mostActiveDept').textContent = analytics.most_active_dept || 'N/A';
            document.getElementById('peakHours').textContent = analytics.peak_hours || 'N/A';
        }
    } catch (error) {
        console.error('Error loading analytics data:', error);
    }
}

// Utility functions
function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

async function refreshUserList() {
    await loadUserManagementData();
}

async function createSystemBackup() {
    const statusDiv = document.getElementById('backupStatus');
    statusDiv.classList.remove('hidden');
    statusDiv.innerHTML = '<p class="text-blue-800">Creating backup...</p>';
    
    try {
        const result = await apiCall('api/admin.php?action=create_backup', 'POST');
        if (result.status === 'success') {
            statusDiv.innerHTML = '<p class="text-green-800">✅ Backup created successfully!</p>';
            statusDiv.className = 'bg-green-50 border border-green-200 rounded-lg p-4';
            loadBackupHistory();
        } else {
            statusDiv.innerHTML = '<p class="text-red-800">❌ Backup failed: ' + result.message + '</p>';
            statusDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4';
        }
    } catch (error) {
        statusDiv.innerHTML = '<p class="text-red-800">❌ Backup failed: Network error</p>';
        statusDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4';
    }
    
    setTimeout(() => {
        statusDiv.classList.add('hidden');
    }, 5000);
}

function viewUserDetails(userId) {
    alert(`User details for ID: ${userId} - Feature coming soon!`);
}

function downloadBackup(backupId) {
    window.open(`api/admin.php?action=download_backup&id=${backupId}`, '_blank');
}

// Export the new functions to global scope
window.showTaskAssignmentSection = showTaskAssignmentSection;
window.showUserManagementSection = showUserManagementSection;
window.showSecuritySettingsSection = showSecuritySettingsSection;
window.showBackupRestoreSection = showBackupRestoreSection;
window.showAnalyticsSection = showAnalyticsSection;
window.refreshUserList = refreshUserList;
window.createSystemBackup = createSystemBackup;
window.viewUserDetails = viewUserDetails;
window.downloadBackup = downloadBackup;

 // Random background photo logic
        document.addEventListener('DOMContentLoaded', function () {
            // Add your own URLs or use Unsplash
            const photos = [
                "https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1024&q=80",
                "https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=1024&q=80",
                "https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=1024&q=80",
                "https://images.unsplash.com/photo-1470770841072-f978cf4d019e?auto=format&fit=crop&w=1024&q=80",
                "https://lh3.googleusercontent.com/gps-cs-s/AC9h4npEn-DhvvbFjcmn_ZNY6unPyYk6U2YzUwAU6s3qyTawtnhf71EquFwmO8Up2fnApbpoccnEXLyTeNA7XzWwhHLzOxYc36401LT7JFsnbxz0H-0e42EGGOy3AQ6RtjYuPgUodH8=w143-h102-n-k-no-nu"
            ];
            const randomPhoto = photos[Math.floor(Math.random() * photos.length)];

            // Target all screens needing a background image
            const screens = [
                document.getElementById('mainScreen'),
                document.getElementById('userAuthScreen'),
                document.getElementById('adminAuthScreen')
            ];

            screens.forEach(screen => {
                if (screen) {
                    screen.style.backgroundImage = `url('${randomPhoto}')`;
                    screen.style.backgroundSize = "cover";
                    screen.style.backgroundPosition = "center";
                    screen.style.backgroundRepeat = "no-repeat";
                }
            });
        });
// Category/task mapping (should match backend Task.php)
const categoryTasks = {
    "Financial Stewardship and Discipline": [
        "Revenue",
        "Debt Management",
        "Pending Bills",
        "Zero Fault Audits"
    ],
    "Service Delivery": [
        "Implementation of Citizens' Service Delivery Charter",
        "Resolution of Public Complaints"
    ],
    "Core Mandate": [
        "Review existing training programs.",
        "Develop and roll out new training programs.",
        "Undertake consultancy and research activities.",
        "Organize and host national symposia or conferences.",
        "Improve productivity.",
        "Manage the customer experience and satisfaction score.",
        "Conduct a training needs assessment.",
        "Mobilize participants for training.",
        "Convert and offer existing programs online.",
        "Carry out program and facilitator evaluations.",
        "Identify and implement innovation and creativity initiatives.",
        "Institutionalize Performance Management Culture"
    ],
    "Administration and Infrastructure": [
        "Operationalize digitalized processes.",
        "Implement a risk register.",
        "Implement Quality Management Systems.",
        "Implementation of Presidential Directives"
    ],
    "Cross-Cutting Issues": [
        "Youth Internships, Industrial Attachment and Apprenticeship",
        "Competence Development",
        "National Cohesion and Values"
    ]
};

// --- Populate User Dropdown ---
async function fetchUserList() {
    try {
        const response = await fetch('api/admin.php?action=get_users');
        const data = await response.json();
        const userSelect = document.getElementById('assignUserSelect');
        userSelect.innerHTML = '<option value="">Choose a user...</option>';
        if (data.status === 'success') {
            (data.users || []).forEach(user => {
                const opt = document.createElement('option');
                opt.value = user.id;
                opt.textContent = user.name + " (" + user.email + ")";
                userSelect.appendChild(opt);
            });
        }
    } catch (error) {
        console.error('Error fetching user list:', error);
    }
}
document.addEventListener('DOMContentLoaded', fetchUserList);

// --- Populate Task Dropdown based on Category ---
function populateTasksForCategory(category) {
    const taskSelect = document.getElementById('assignTaskSelect');
    taskSelect.innerHTML = '<option value="">Choose a task...</option>';
    if (category && categoryTasks[category]) {
        categoryTasks[category].forEach(task => {
            let opt = document.createElement('option');
            opt.value = task;
            opt.textContent = task;
            taskSelect.appendChild(opt);
        });
    }
}

// --- Render Quick Assign Buttons ---
function renderAdminQuickAssign() {
    const container = document.getElementById('adminQuickAssignCategories');
    container.innerHTML = '';
    Object.entries(categoryTasks).forEach(([cat, tasks]) => {
        let div = document.createElement('div');
        div.className = "mb-4";
        let h4 = document.createElement('h4');
        h4.className = "text-sm font-medium text-gray-700 mb-2";
        h4.textContent = cat;
        div.appendChild(h4);
        let btnList = document.createElement('div');
        btnList.className = "space-y-2";
        tasks.forEach(task => {
            let btn = document.createElement('button');
            btn.className = "w-full text-left bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg p-3 transition-colors";
            btn.onclick = () => quickAssignTask(cat, task);
            btn.innerHTML = `<div class="font-medium text-gray-800">${task}</div>`;
            btnList.appendChild(btn);
        });
        div.appendChild(btnList);
        container.appendChild(div);
    });
}
document.addEventListener('DOMContentLoaded', renderAdminQuickAssign);

// --- Quick Assign Handler ---
function quickAssignTask(category, task) {
    document.getElementById('assignCategorySelect').value = category;
    populateTasksForCategory(category);
    document.getElementById('assignTaskSelect').value = task;
    document.getElementById('assignUserSelect').focus();
}

// --- Assign Task to User via API ---
async function assignTaskToUser() {
    const userId = document.getElementById('assignUserSelect').value;
    const category = document.getElementById('assignCategorySelect').value;
    const task = document.getElementById('assignTaskSelect').value;
    const priority = document.getElementById('assignPrioritySelect').value;
    const dueDate = document.getElementById('assignDueDateInput').value;
    const instructions = document.getElementById('assignInstructionsInput').value;
    const successDiv = document.getElementById('taskAssignSuccess');
    const errorDiv = document.getElementById('taskAssignError');

    if (!userId || !category || !task || !dueDate) {
        errorDiv.textContent = 'Please select user, category, task, and due date';
        errorDiv.classList.remove('hidden');
        successDiv.classList.add('hidden');
        return;
    }

    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');

    try {
        const response = await fetch('api/admin.php?action=assign_predefined_task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                category: category,
                title: task,
                due_date: dueDate,
                priority: priority,
                instructions: instructions
            })
        });
        const data = await response.json();
        if (data.status === 'success') {
            successDiv.textContent = 'Task assigned successfully!';
            successDiv.classList.remove('hidden');
            errorDiv.classList.add('hidden');
            fetchRecentAssignments();
        } else {
            errorDiv.textContent = data.message || 'Error assigning task';
            errorDiv.classList.remove('hidden');
            successDiv.classList.add('hidden');
        }
    } catch (error) {
        errorDiv.textContent = 'Network error. Please try again.';
        errorDiv.classList.remove('hidden');
        successDiv.classList.add('hidden');
    }
}

// --- Fetch and Display Recent Assignments ---
async function fetchRecentAssignments() {
    try {
        const response = await fetch('api/admin.php?action=get_recent_assignments&limit=10');
        const data = await response.json();
        const container = document.getElementById('recentAssignments');
        container.innerHTML = '';
        if (data.status === 'success' && data.assignments && data.assignments.length > 0) {
            data.assignments.forEach(assign => {
                const div = document.createElement('div');
                div.className = "flex items-center justify-between p-3 bg-gray-50 rounded-lg";
                div.innerHTML = `
                    <span class="text-gray-700">${assign.title} → <b>${assign.user_name}</b> <span class="text-xs text-gray-500">(${new Date(assign.due_date).toLocaleDateString()})</span></span>
                    <span class="text-xs text-gray-500">${assign.status.charAt(0).toUpperCase() + assign.status.slice(1)}</span>
                `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<div class="text-gray-400 text-sm">No recent assignments found.</div>';
        }
    } catch (error) {
        document.getElementById('recentAssignments').innerHTML = '<div class="text-red-500 text-sm">Failed to load recent assignments.</div>';
    }
}
document.addEventListener('DOMContentLoaded', fetchRecentAssignments);

// Make functions available globally
window.assignTaskToUser = assignTaskToUser;
window.quickAssignTask = quickAssignTask;
window.populateTasksForCategory = populateTasksForCategory;
// Section navigation functions
function showUserSection(section) {
    toggleUserMenu();

    switch (section) {
        case 'profile':
            showProfileManagement();
            break;
        case 'tasks':
            showTaskManagement();
            break;
        case 'reports':
            showUserReports();
            break;
        case 'settings':
            showUserSettings();
            break;
        default:
            showDashboardHome();
            break;
    }
}

function showAdminSection(section) {
    toggleAdminMenu();
    hideAllAdminSections();

    switch (section) {
        case 'taskAssignment':
            showTaskAssignmentSection();
            break;
        case 'userManagement':
            showUserManagementSection();
            break;
        case 'securitySettings':
            showSecuritySettingsSection();
            break;
        case 'backupRestore':
            showBackupRestoreSection();
            break;
        case 'analytics':
            showAnalyticsSection();
            break;
        default:
            document.getElementById('adminActivitySection')?.classList.remove('hidden');
            break;
    }
}

function hideAllAdminSections() {
    document.getElementById('taskAssignmentSection')?.classList.add('hidden');
    document.getElementById('adminUserManagement')?.classList.add('hidden');
    document.getElementById('adminSecuritySettings')?.classList.add('hidden');
    document.getElementById('adminBackupRestore')?.classList.add('hidden');
    document.getElementById('adminAnalytics')?.classList.add('hidden');
    document.getElementById('adminActivitySection')?.classList.add('hidden');
}

// Task Management Functions
function showDashboardHome() {
    document.getElementById('dashboardHome').classList.remove('hidden');
    document.getElementById('taskManagement').classList.add('hidden');
    document.getElementById('profileManagement').classList.add('hidden');
}

function showTaskManagement() {
    document.getElementById('dashboardHome').classList.add('hidden');
    document.getElementById('taskManagement').classList.remove('hidden');
    document.getElementById('profileManagement').classList.add('hidden');
    loadUserTasks();
    updateTaskStats();
}

function showProfileManagement() {
    document.getElementById('dashboardHome').classList.add('hidden');
    document.getElementById('taskManagement').classList.add('hidden');
    document.getElementById('profileManagement').classList.remove('hidden');
    document.getElementById('userReports')?.classList.add('hidden');
    document.getElementById('userSettings')?.classList.add('hidden');
    loadProfileData();
}

async function loadUserTasks() {
    try {
        const result = await apiCall('api/user.php?action=get_tasks');
        if (result.status === 'success') {
            const tasks = result.tasks;
            renderUserTasks(tasks);
        }
    } catch (error) {
        console.error('Error loading user tasks:', error);
    }
}

function renderUserTasks(tasks) {
    const taskList = document.getElementById('taskList');
    
    if (tasks.length === 0) {
        taskList.innerHTML = `
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-gray-500 text-lg">No tasks assigned yet</p>
                <p class="text-gray-400 text-sm mt-2">Tasks will appear here when assigned by administrators</p>
            </div>
        `;
        return;
    }

    const filteredTasks = filterTasksByStatus(tasks);
    taskList.innerHTML = filteredTasks.map(task => createTaskCard(task)).join('');
}

function filterTasksByStatus(tasks) {
    const now = new Date();

    switch (currentFilter) {
        case 'pending':
            return tasks.filter(task => task.status === 'pending');
        case 'completed':
            return tasks.filter(task => task.status === 'completed');
        case 'overdue':
            return tasks.filter(task => {
                const dueDate = new Date(task.due_date);
                return task.status === 'pending' && dueDate < now;
            });
        default:
            return tasks;
    }
}

function createTaskCard(task) {
    const dueDate = new Date(task.due_date);
    const now = new Date();
    const isOverdue = task.status === 'pending' && dueDate < now;
    const isCompleted = task.status === 'completed';

    let statusBadge = '';
    let cardBorder = '';

    if (isCompleted) {
        statusBadge = '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Completed</span>';
        cardBorder = 'border-l-4 border-green-500';
    } else if (isOverdue) {
        statusBadge = '<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Overdue</span>';
        cardBorder = 'border-l-4 border-red-500';
    } else {
        statusBadge = '<span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Pending</span>';
        cardBorder = 'border-l-4 border-yellow-500';
    }

    const priorityColor = task.priority === 'high' ? 'text-red-600' :
        task.priority === 'medium' ? 'text-yellow-600' : 'text-green-600';

    return `
        <div class="bg-gray-50 rounded-lg p-4 ${cardBorder}">
            <div class="flex justify-between items-start mb-3">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800 mb-1">${task.title}</h3>
                    <p class="text-gray-600 text-sm mb-2">${task.description}</p>
                </div>
                <div class="flex flex-col items-end space-y-2">
                    ${statusBadge}
                    <span class="text-xs ${priorityColor} font-medium uppercase">${task.priority} Priority</span>
                </div>
            </div>
            
            <div class="flex justify-between items-center text-sm text-gray-500">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Due: ${dueDate.toLocaleDateString()}
                </div>
                <div class="flex items-center space-x-2">
                    ${!isCompleted ? `
                        <button onclick="markTaskComplete(${task.id})" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                            Mark Complete
                        </button>
                    ` : ''}
                    <button onclick="viewTaskDetails(${task.id})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                        View Details
                    </button>
                </div>
            </div>
        </div>
    `;
}

async function markTaskComplete(taskId) {
    try {
        const result = await apiCall(`api/user.php?action=update_task_status&task_id=${taskId}`, 'PUT', {
            status: 'completed'
        });

        if (result.status === 'success') {
            alert('✅ Task marked as complete!');
            loadUserTasks();
            updateTaskStats();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error marking task complete:', error);
        alert('Failed to update task status');
    }
}

async function updateTaskStats() {
    try {
        const result = await apiCall('api/user.php?action=get_dashboard_stats');
        if (result.status === 'success') {
            const stats = result.stats;
            document.getElementById('pendingCount').textContent = stats.pending_count || 0;
            document.getElementById('completedCount').textContent = stats.completed_this_week || 0;
            document.getElementById('overdueCount').textContent = stats.overdue_count || 0;
            document.getElementById('totalTasks').textContent = stats.total_tasks || 0;
        }
    } catch (error) {
        console.error('Error updating task stats:', error);
    }
}

function filterTasks(filter) {
    currentFilter = filter;

    // Update filter button styles
    document.querySelectorAll('.task-filter-btn').forEach(btn => {
        btn.className = 'task-filter-btn bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-medium transition-colors';
    });

    event.target.className = 'task-filter-btn bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors';

    loadUserTasks();
}

// Profile Management Functions
async function loadProfileData() {
    try {
        const result = await apiCall('api/user.php?action=get_profile');
        if (result.status === 'success') {
            const user = result.user;
            
            // Load basic profile information
            document.getElementById('profileName').value = user.name || '';
            document.getElementById('profileEmail').value = user.email || '';
            document.getElementById('profilePhone').value = user.phone || '';
            document.getElementById('profileDepartment').value = user.department || '';
            document.getElementById('profileJobTitle').value = user.job_title || '';

            // Update profile avatar/initials
            const initials = user.name ? user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
            const initialsEl = document.getElementById('profileInitials');
            const avatarContainer = initialsEl ? initialsEl.parentElement : null;
            
            if (user.profile_picture && avatarContainer) {
                initialsEl.textContent = '';
                avatarContainer.style.backgroundImage = `url(${user.profile_picture})`;
                avatarContainer.style.backgroundSize = 'cover';
                avatarContainer.style.backgroundPosition = 'center';
            } else if (avatarContainer) {
                avatarContainer.style.backgroundImage = '';
                initialsEl.textContent = initials;
            }

            // Load notification preferences
            const preferences = user.notification_preferences ? JSON.parse(user.notification_preferences) : {};
            document.getElementById('emailNotifications').checked = preferences.emailNotifications !== false;
            document.getElementById('taskReminders').checked = preferences.taskReminders !== false;
            document.getElementById('systemUpdates').checked = preferences.systemUpdates === true;
            document.getElementById('weeklyReports').checked = preferences.weeklyReports !== false;

            // Update account information
            if (user.created_at) {
                const createdDate = new Date(user.created_at).toLocaleDateString();
                document.getElementById('accountCreated').textContent = createdDate;
            }
        }
    } catch (error) {
        console.error('Error loading profile data:', error);
    }
}

async function updateProfile() {
    const name = document.getElementById('profileName').value.trim();
    const phone = document.getElementById('profilePhone').value.trim();
    const department = document.getElementById('profileDepartment').value;
    const jobTitle = document.getElementById('profileJobTitle').value.trim();

    if (!name) {
        alert('Please enter your full name.');
        return;
    }

    try {
        const result = await apiCall('api/user.php?action=update_profile', 'PUT', {
            name: name,
            phone: phone,
            department: department,
            job_title: jobTitle
        });

        if (result.status === 'success') {
            currentUser.name = name;
            document.getElementById('userWelcome').textContent = `Welcome, ${name}`;
            
            // Update profile initials
            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
            document.getElementById('profileInitials').textContent = initials;
            
            alert('✅ Profile updated successfully!');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert('Failed to update profile');
    }
}

async function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPasswordProfile').value;
    const confirmPassword = document.getElementById('confirmPasswordProfile').value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        alert('Please fill in all password fields.');
        return;
    }

    if (newPassword !== confirmPassword) {
        alert('New passwords do not match.');
        return;
    }

    if (newPassword.length < 8) {
        alert('New password must be at least 8 characters long.');
        return;
    }

    if (newPassword === currentPassword) {
        alert('New password must be different from current password.');
        return;
    }

    try {
        const result = await apiCall('api/auth.php?action=change_password', 'POST', {
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
        });

        if (result.status === 'success') {
            // Clear password fields
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPasswordProfile').value = '';
            document.getElementById('confirmPasswordProfile').value = '';
            
            alert('✅ Password changed successfully!');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error changing password:', error);
        alert('Failed to change password');
    }
}
/**
/**
 * Displays details of a specific task in a modal with upload and delete options for assigned users.
 * @param {number|string} taskId
 */
async function viewTaskDetails(taskId) {
    // Helper to close modal
    function closeModal() {
        const modal = document.getElementById('taskDetailsModal');
        if (modal) modal.remove();
    }

    // Show loading modal while fetching details
    showLoadingModal('Loading task details...');
    try {
        // Replace this with your actual task-fetching logic. Example:
        const response = await fetch(`api/user.php?action=get_task&task_id=${taskId}`);
        if (!response.ok) throw new Error('Network error');
        const data = await response.json();
        hideLoadingModal();

        if (data.status !== 'success' || !data.task) {
            showErrorMessage(data.message || 'Task not found');
            return;
        }
        const task = data.task;

        // Fetch task uploads
        let uploads = [];
        try {
            const upres = await fetch(`api/user.php?action=get_task_uploads&task_id=${taskId}`);
            uploads = (upres.ok ? (await upres.json()).uploads : []);
            if (!Array.isArray(uploads)) uploads = [];
        } catch (e) {
            uploads = [];
        }

        // Build modal markup
        document.body.insertAdjacentHTML('beforeend', `
        <div 
            id="taskDetailsModal" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white max-w-lg mx-auto rounded-lg shadow-lg overflow-auto w-full relative">
                <button 
                    class="absolute top-2 right-3 text-gray-500 hover:text-gray-800 text-2xl"
                    onclick="document.getElementById('taskDetailsModal').remove();">
                    &times;
                </button>
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-2">${task.title}</h2>
                    <p class="text-gray-700 mb-4">${task.description}</p>
                    <div class="mb-4">
                        <strong>Status:</strong>
                        <span class="inline-block px-2 rounded-full text-sm ${task.status === 'completed' ? 'bg-green-100 text-green-700' : (task.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700')}">
                            ${task.status.charAt(0).toUpperCase() + task.status.slice(1)}
                        </span>
                    </div>
                    <div class="mb-4">
                        <strong>Due Date:</strong> ${new Date(task.due_date).toLocaleString()}<br>
                        <strong>Assigned By:</strong> ${task.assigned_by || 'System'}
                    </div>
                    <div class="mb-4">
                        <strong>Instructions:</strong><br>
                        <span class="block text-gray-600 whitespace-pre-line">${task.instructions || '-'}</span>
                    </div>
                    <div class="mb-4">
                        <strong>Files:</strong>
                        <ul id="task-files-list" class="space-y-2 mt-2">
                            ${(uploads && uploads.length) ? uploads.map(u => `
                                <li class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded">
                                    <a href="api/user.php?action=download_task_file&upload_id=${u.id}&task_id=${task.id}" target="_blank" class="text-blue-600 underline">${u.file_name}</a>
                                    <button class="text-red-600 text-xs ml-3" onclick="deleteTaskFileModal(${u.id},${task.id})">Delete</button>
                                </li>
                            `).join('') : `<li class="text-gray-400">No files uploaded yet.</li>`}
                        </ul>
                    </div>
                    <div class="mb-6">
                        <form id="uploadTaskFileForm" class="flex flex-col gap-2" enctype="multipart/form-data">
                            <label><strong>Upload File:</strong></label>
                            <input type="file" name="file" class="block border p-1 rounded" required>
                            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded">Upload</button>
                        </form>
                        <div id="uploadTaskFileMsg" class="text-sm mt-2"></div>
                    </div>
                    <div class="border-t pt-4 flex justify-between">
                        <button class="bg-red-600 text-white px-4 py-2 rounded shadow"
                            id="deleteTaskBtn">Delete Task</button>
                        <button class="bg-gray-300 text-gray-800 px-4 py-2 rounded shadow"
                            onclick="document.getElementById('taskDetailsModal').remove();">Close</button>
                    </div>
                </div>
            </div>
        </div>
        `);

        // Upload handler
        document.getElementById('uploadTaskFileForm').onsubmit = async (e) => {
            e.preventDefault();
            const msgDiv = document.getElementById('uploadTaskFileMsg');
            msgDiv.textContent = 'Uploading...';
            const formData = new FormData(e.target);
            formData.append('task_id', taskId);
            try {
                const resp = await fetch('api/user.php?action=upload_task_file', {
                    method: 'POST',
                    body: formData
                });
                const res = await resp.json();
                if (res.status === 'success') {
                    msgDiv.textContent = 'File uploaded!';
                    document.getElementById('uploadTaskFileForm').reset();
                    // Refresh uploads list
                    if (typeof viewTaskDetails === 'function')
                        closeModal(), viewTaskDetails(taskId);
                    else
                        window.location.reload();
                } else {
                    msgDiv.textContent = 'Error: ' + (res.message || 'Upload failed');
                }
            } catch {
                msgDiv.textContent = 'Upload failed (network error)';
            }
        };

        // Delete task handler
        document.getElementById('deleteTaskBtn').onclick = () => {
            if (window.showConfirmDialog) {
                showConfirmDialog('Are you sure you want to delete this task? This cannot be undone.', async () => {
                    showLoadingModal('Deleting...');
                    try {
                        const resp = await fetch(`api/user.php?action=delete_task&task_id=${taskId}`, {method: 'DELETE'});
                        const res = await resp.json();
                        hideLoadingModal();
                        if (res.status === 'success') {
                            showSuccessMessage('Task deleted!');
                            closeModal();
                            if (typeof loadUserTasks === 'function') loadUserTasks();
                        } else {
                            showErrorMessage(res.message || 'Delete failed');
                        }
                    } catch {
                        hideLoadingModal();
                        showErrorMessage('Network error');
                    }
                });
            } else {
                // fallback
                if (confirm('Are you sure you want to delete this task?')) {
                    // see above for actual deletion
                }
            }
        };

        // Handler for deleting individual files (make globally available for inline onclick above)
        window.deleteTaskFileModal = async (uploadId, tId) => {
            if (!confirm('Delete this file?')) return;
            showLoadingModal('Deleting file...');
            try {
                const resp = await fetch(`api/user.php?action=delete_task_upload&upload_id=${uploadId}&task_id=${tId}`, {method: 'DELETE'});
                const res = await resp.json();
                hideLoadingModal();
                if (res.status === 'success') {
                    showSuccessMessage('File deleted!');
                    closeModal();
                    viewTaskDetails(tId);
                } else {
                    showErrorMessage(res.message || 'Delete failed');
                }
            } catch {
                hideLoadingModal();
                showErrorMessage('Network error');
            }
        };

    } catch (err) {
        hideLoadingModal();
        showErrorMessage('Failed to load task details.');
        console.error(err);
    }
}

// Export globally for inline onclick support
window.viewTaskDetails = viewTaskDetails;
// Make sure the function is exported globally for event handlers
window.viewTaskDetails = viewTaskDetails;
// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is already logged in via PHP session
    if (window.currentUser && window.isLoggedIn) {
        currentUser = window.currentUser;
        isLoggedIn = window.isLoggedIn;
        userType = window.userType;
        isAdmin = window.isAdmin || false;
        
        if (userType === 'user') {
            showUserDashboard();
        } else if (userType === 'admin') {
            showAdminDashboard();
        }
    }
});

// Admin functions (basic implementations to prevent errors)
function manageUsers() {
    alert('User Management feature coming soon!');
}

function systemConfig() {
    alert('System Configuration feature coming soon!');
}

function securitySettings() {
    alert('Security Settings feature coming soon!');
}

function backupRestore() {
    alert('Backup & Restore feature coming soon!');
}

// Export functions for global access
window.showUserAuth = showUserAuth;
window.showAdminAuth = showAdminAuth;
window.backToMain = backToMain;
window.switchUserTab = switchUserTab;
window.switchAdminTab = switchAdminTab;
window.userLogin = userLogin;
window.userRegister = userRegister;
window.adminLogin = adminLogin;
window.adminRegister = adminRegister;
window.logout = logout;
window.togglePassword = togglePassword;
window.toggleUserMenu = toggleUserMenu;
window.toggleAdminMenu = toggleAdminMenu;
window.showUserSection = showUserSection;
window.showAdminSection = showAdminSection;
window.filterTasks = filterTasks;
window.markTaskComplete = markTaskComplete;
window.updateProfile = updateProfile;
window.changePassword = changePassword;
window.manageUsers = manageUsers;
window.systemConfig = systemConfig;
window.securitySettings = securitySettings;
window.backupRestore = backupRestore;