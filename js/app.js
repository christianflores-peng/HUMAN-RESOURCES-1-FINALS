// HR Management System JavaScript

class HRSystem {
    constructor() {
        this.currentUser = null;
        this.currentRole = null;
        this.currentModule = 'dashboard';
        this.currentSubmodule = null;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadStoredData();
        this.initializeTheme();
        this.showLoginScreen();
    }

    setupEventListeners() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Logout button (legacy - now using direct href links)
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => this.handleLogout(e));
        }

        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Menu items
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('menu-item')) {
                // Only prevent default if it's a data-module item (old SPA style)
                const module = e.target.getAttribute('data-module');
                if (module) {
                    e.preventDefault();
                    this.switchModule(module);
                }
                // Otherwise, let normal href links work normally
            }
        });

        // Submodule buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('submodule-btn')) {
                const submodule = e.target.getAttribute('data-submodule');
                if (submodule) {
                    this.switchSubmodule(submodule);
                }
            }
        });

        // Recognition type buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('recognition-type-btn')) {
                this.selectRecognitionType(e.target);
            }
        });

        // Plan tabs
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('plan-tab')) {
                const period = e.target.getAttribute('data-period');
                this.switchPlanPeriod(period);
            }
        });

        // Form submissions
        document.addEventListener('submit', async (e) => {
            if (e.target.classList.contains('job-form')) {
                e.preventDefault();
                this.handleJobSubmission(e.target);
            }
            if (e.target.classList.contains('goal-creation-form')) {
                e.preventDefault();
                await this.handleGoalCreation(e.target);
            }
            if (e.target.classList.contains('review-scheduling-form')) {
                e.preventDefault();
                await this.handleReviewScheduling(e.target);
            }
            if (e.target.classList.contains('recognition-creation-form')) {
                e.preventDefault();
                this.handleRecognitionSubmission(e.target);
            }
        });

        // Responsive sidebar
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('open');
            }
        });

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    loadStoredData() {
        // Load any stored user data or preferences
        const storedUser = localStorage.getItem('hrSystemUser');
        if (storedUser) {
            const userData = JSON.parse(storedUser);
            this.currentUser = userData.username;
            this.currentRole = userData.role;
        }
    }

    initializeTheme() {
        const storedTheme = localStorage.getItem('hrTheme') || 'theme-dark';
        document.body.classList.remove('theme-light', 'theme-dark');
        document.body.classList.add(storedTheme);
    }

    toggleTheme() {
        const next = document.body.classList.contains('theme-dark') ? 'theme-light' : 'theme-dark';
        document.body.classList.remove('theme-light', 'theme-dark');
        document.body.classList.add(next);
        localStorage.setItem('hrTheme', next);
    }

    showLoginScreen() {
        document.getElementById('loginScreen').style.display = 'flex';
        document.getElementById('mainApp').style.display = 'none';
    }

    showMainApp() {
        document.getElementById('loginScreen').style.display = 'none';
        document.getElementById('mainApp').style.display = 'flex';
        this.updateUserInfo();
        this.switchModule('dashboard');
    }

    handleLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const username = formData.get('username');
        const password = formData.get('password');
        const role = formData.get('role') || 'employee';

        // Simple validation (in real app, this would be server-side)
        if (!username || !password) {
            alert('Please fill in all fields');
            return;
        }

        // Demo login - accept any credentials
        this.currentUser = username;
        this.currentRole = role;

        // Store user data
        localStorage.setItem('hrSystemUser', JSON.stringify({
            username: this.currentUser,
            role: this.currentRole
        }));

        this.showMainApp();
    }

    handleLogout(e) {
        e.preventDefault();
        
        this.currentUser = null;
        this.currentRole = null;
        
        localStorage.removeItem('hrSystemUser');
        
        this.showLoginScreen();
    }

    updateUserInfo() {
        const currentUserElement = document.getElementById('currentUser');
        const currentRoleElement = document.getElementById('currentRole');
        
        if (currentUserElement) {
            currentUserElement.textContent = this.currentUser || 'User';
        }
        
        if (currentRoleElement) {
            currentRoleElement.textContent = this.currentRole || 'Role';
        }
    }

    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        sidebar.classList.toggle('open');
        mainContent.classList.toggle('expanded');
    }

    switchModule(moduleName) {
        // Update active menu item
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const activeMenuItem = document.querySelector(`[data-module="${moduleName}"]`);
        if (activeMenuItem) {
            activeMenuItem.classList.add('active');
        }

        // Hide all modules
        document.querySelectorAll('.module').forEach(module => {
            module.classList.remove('active');
        });

        // Show selected module
        const targetModule = document.getElementById(`${moduleName}-module`);
        if (targetModule) {
            targetModule.classList.add('active');
        }

        // Update page title
        const pageTitle = document.getElementById('pageTitle');
        if (pageTitle) {
            pageTitle.textContent = this.getModuleTitle(moduleName);
        }

        this.currentModule = moduleName;

        // Initialize module-specific functionality
        this.initializeModule(moduleName);
    }

    switchSubmodule(submoduleName) {
        const currentModuleElement = document.querySelector('.module.active');
        if (!currentModuleElement) return;

        // Update active submodule button
        currentModuleElement.querySelectorAll('.submodule-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = currentModuleElement.querySelector(`[data-submodule="${submoduleName}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        // Hide all submodules in current module
        currentModuleElement.querySelectorAll('.submodule').forEach(submodule => {
            submodule.classList.remove('active');
        });

        // Show selected submodule
        const targetSubmodule = document.getElementById(submoduleName);
        if (targetSubmodule) {
            targetSubmodule.classList.add('active');
        }

        this.currentSubmodule = submoduleName;
    }

    getModuleTitle(moduleName) {
        const titles = {
            'dashboard': 'Dashboard',
            'recruitment': 'Recruitment',
            'applicant-management': 'Applicant Management',
            'onboarding': 'Onboarding',
            'performance': 'Performance Management',
            'recognition': 'Social Recognition'
        };
        return titles[moduleName] || 'HR System';
    }

    initializeModule(moduleName) {
        switch (moduleName) {
            case 'dashboard':
                this.initializeDashboard();
                break;
            case 'recruitment':
                this.initializeRecruitment();
                break;
            case 'applicant-management':
                this.initializeApplicantManagement();
                break;
            case 'onboarding':
                this.initializeOnboarding();
                break;
            case 'performance':
                this.initializePerformance();
                break;
            case 'recognition':
                this.initializeRecognition();
                break;
        }
    }

    initializeDashboard() {
        // Animate stats on load
        this.animateStats();
        
        // Update dashboard data based on role
        this.updateDashboardForRole();
    }

    initializeRecruitment() {
        // Set default submodule
        this.switchSubmodule('job-requisition');
    }

    initializeApplicantManagement() {
        // Set default submodule
        this.switchSubmodule('tracking');
        
        // Initialize drag and drop for kanban board
        this.initializeKanbanBoard();
    }

    initializeOnboarding() {
        // Set default submodule
        this.switchSubmodule('pre-boarding');
        
        // Initialize checklist functionality
        this.initializeChecklists();
    }

    initializePerformance() {
        // Set default submodule
        this.switchSubmodule('goal-setting');
    }

    initializeRecognition() {
        // Set default submodule
        this.switchSubmodule('peer-recognition');
    }

    animateStats() {
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const finalValue = stat.textContent;
            const numericValue = parseFloat(finalValue.replace(/[^0-9.]/g, ''));
            
            if (!isNaN(numericValue)) {
                this.animateNumber(stat, 0, numericValue, finalValue);
            }
        });
    }

    animateNumber(element, start, end, finalText) {
        const duration = 1000;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = start + (end - start) * this.easeOutQuart(progress);
            
            if (finalText.includes('/')) {
                element.textContent = current.toFixed(1) + '/5';
            } else if (finalText.includes('%')) {
                element.textContent = current.toFixed(1) + '%';
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.textContent = finalText;
            }
        };
        
        requestAnimationFrame(animate);
    }

    easeOutQuart(t) {
        return 1 - Math.pow(1 - t, 4);
    }

    updateDashboardForRole() {
        // Customize dashboard content based on user role
        const roleSpecificContent = {
            'recruiter': {
                'Total Employees': 'Active Candidates',
                'Active Recruitments': 'My Job Requisitions',
                'Performance Score': 'Placement Rate',
                'Recognition Awards': 'Successful Hires'
            },
            'candidate': {
                'Total Employees': 'Applications Sent',
                'Active Recruitments': 'Interview Invites',
                'Performance Score': 'Profile Score',
                'Recognition Awards': 'Skill Matches'
            }
        };

        const customContent = roleSpecificContent[this.currentRole];
        if (customContent) {
            document.querySelectorAll('.stat-card h3').forEach(title => {
                const currentText = title.textContent;
                if (customContent[currentText]) {
                    title.textContent = customContent[currentText];
                }
            });
        }
    }

    initializeKanbanBoard() {
        // Add drag and drop functionality to kanban cards
        const kanbanCards = document.querySelectorAll('.kanban-card');
        const kanbanColumns = document.querySelectorAll('.kanban-column');

        kanbanCards.forEach(card => {
            card.draggable = true;
            
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', card.outerHTML);
                e.dataTransfer.setData('application/id', card.getAttribute('data-app-id') || '');
                card.classList.add('dragging');
            });
            
            card.addEventListener('dragend', () => {
                card.classList.remove('dragging');
            });
        });

        kanbanColumns.forEach(column => {
            column.addEventListener('dragover', (e) => {
                e.preventDefault();
                column.classList.add('drag-over');
            });
            
            column.addEventListener('dragleave', () => {
                column.classList.remove('drag-over');
            });
            
            column.addEventListener('drop', async (e) => {
                e.preventDefault();
                column.classList.remove('drag-over');
                
                const cardData = e.dataTransfer.getData('text/plain');
                const appId = e.dataTransfer.getData('application/id');
                const newStatus = column.getAttribute('data-status');
                const cardsContainer = column.querySelector('.kanban-cards');
                
                if (cardsContainer) {
                    try {
                        if (!appId || !newStatus) throw new Error('Missing data');
                        const formData = new FormData();
                        formData.append('id', appId);
                        formData.append('status', newStatus);

                        const res = await fetch('../pages/api/update_application_status.php', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        });
                        const json = await res.json();
                        if (!json.success) throw new Error(json.message || 'Update failed');

                        // Remove the dragged card from its original position
                        const draggingCard = document.querySelector('.dragging');
                        if (draggingCard) {
                            draggingCard.remove();
                        }
                        // Add the card to the new column
                        cardsContainer.innerHTML += cardData;
                        // Update counts and notify
                        this.updateKanbanCounts();
                        this.showNotification('Candidate moved successfully!', 'success');
                    } catch (err) {
                        console.error(err);
                        this.showNotification('Could not update status. Please try again.', 'info');
                    }
                }
            });
        });
    }

    updateKanbanCounts() {
        document.querySelectorAll('.kanban-column').forEach(column => {
            const cards = column.querySelectorAll('.kanban-card');
            const countElement = column.querySelector('.count');
            if (countElement) {
                countElement.textContent = cards.length;
            }
        });
    }

    initializeChecklists() {
        const checkboxes = document.querySelectorAll('.checklist-item input[type="checkbox"]');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const item = e.target.closest('.checklist-item');
                if (e.target.checked) {
                    item.classList.add('completed');
                    this.showNotification('Task completed!', 'success');
                } else {
                    item.classList.remove('completed');
                }
                
                this.updateProgress();
            });
        });
    }

    updateProgress() {
        const progressBars = document.querySelectorAll('.hire-card .progress-fill');
        progressBars.forEach(bar => {
            // Simulate progress update
            const currentWidth = parseInt(bar.style.width) || 0;
            const newWidth = Math.min(currentWidth + 10, 100);
            bar.style.width = newWidth + '%';
            
            const progressText = bar.closest('.hire-progress').querySelector('span');
            if (progressText) {
                progressText.textContent = newWidth + '% Complete';
            }
        });
    }

    selectRecognitionType(button) {
        // Remove active class from all recognition type buttons
        document.querySelectorAll('.recognition-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        button.classList.add('active');
    }

    switchPlanPeriod(period) {
        // Update active tab
        document.querySelectorAll('.plan-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTab = document.querySelector(`[data-period="${period}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Show corresponding plan content
        document.querySelectorAll('.plan-period').forEach(planPeriod => {
            planPeriod.classList.remove('active');
        });
        
        const targetPlan = document.getElementById(`plan-${period}`);
        if (targetPlan) {
            targetPlan.classList.add('active');
        }
    }

    handleJobSubmission(form) {
        const formData = new FormData(form);
        const jobData = {
            title: formData.get('jobTitle'),
            department: formData.get('department'),
            location: formData.get('location'),
            type: formData.get('jobType'),
            description: formData.get('jobDescription'),
            requirements: formData.get('requirements')
        };

        // Simulate job creation
        console.log('Creating job:', jobData);
        
        // Show success message
        this.showNotification('Job requisition created successfully!', 'success');
        
        // Reset form
        form.reset();
        
        // Add to job listings (simulate)
        this.addJobToTable(jobData);
    }

    async handleGoalCreation(form) {
        const formData = new FormData(form);
        try {
            const res = await fetch('../pages/api/create_goal.php', { method: 'POST', body: formData, credentials: 'same-origin' });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');
            this.showNotification('Goal created successfully!', 'success');
            // Add to list visually
            this.addGoalToList({
                title: formData.get('goalTitle'),
                category: formData.get('category'),
                priority: formData.get('priority'),
                description: formData.get('description'),
                targetDate: formData.get('targetDate')
            });
            form.reset();
        } catch (err) {
            console.error(err);
            this.showNotification('Could not create goal.', 'info');
        }
    }

    async handleReviewScheduling(form) {
        const formData = new FormData(form);
        try {
            const res = await fetch('../pages/api/schedule_review.php', { method: 'POST', body: formData, credentials: 'same-origin' });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');
            this.showNotification('Review scheduled!', 'success');
            form.reset();
        } catch (err) {
            console.error(err);
            this.showNotification('Could not schedule review.', 'info');
        }
    }

    handleRecognitionSubmission(form) {
        const formData = new FormData(form);
        const activeType = document.querySelector('.recognition-type-btn.active');
        
        const recognitionData = {
            employee: formData.get('employee'),
            type: activeType ? activeType.getAttribute('data-type') : 'teamwork',
            message: formData.get('message'),
            public: formData.get('public') === 'on'
        };

        // Simulate recognition creation
        console.log('Creating recognition:', recognitionData);
        
        // Show success message
        this.showNotification('Recognition sent successfully!', 'success');
        
        // Reset form
        form.reset();
        document.querySelectorAll('.recognition-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add to recognition feed (simulate)
        this.addRecognitionToFeed(recognitionData);
    }

    addJobToTable(jobData) {
        const tableBody = document.querySelector('#job-requisition .data-table tbody');
        if (tableBody) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${jobData.title}</td>
                <td>${jobData.department}</td>
                <td><span class="status-badge active">Active</span></td>
                <td>0</td>
                <td>
                    <button class="btn btn-sm">View</button>
                    <button class="btn btn-sm">Edit</button>
                </td>
            `;
            tableBody.appendChild(row);
        }
    }

    addGoalToList(goalData) {
        const goalCards = document.querySelector('.goal-cards');
        if (goalCards) {
            const goalCard = document.createElement('div');
            goalCard.className = 'goal-card';
            goalCard.innerHTML = `
                <div class="goal-header">
                    <h5>${goalData.title}</h5>
                    <span class="priority-badge ${goalData.priority.toLowerCase()}">${goalData.priority} Priority</span>
                </div>
                <p>${goalData.description}</p>
                <div class="goal-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <span>0% Complete</span>
                </div>
                <div class="goal-dates">
                    <small>Due: ${goalData.targetDate}</small>
                </div>
            `;
            goalCards.appendChild(goalCard);
        }
    }

    addRecognitionToFeed(recognitionData) {
        const recognitionList = document.querySelector('.recognition-list');
        if (recognitionList) {
            const recognitionItem = document.createElement('div');
            recognitionItem.className = 'recognition-item';
            
            const typeEmojis = {
                'teamwork': '🤝',
                'innovation': '💡',
                'leadership': '👑',
                'excellence': '⭐'
            };
            
            const typeNames = {
                'teamwork': 'Teamwork',
                'innovation': 'Innovation',
                'leadership': 'Leadership',
                'excellence': 'Excellence'
            };
            
            recognitionItem.innerHTML = `
                <div class="recognition-header">
                    <div class="recognition-avatar">👤</div>
                    <div class="recognition-info">
                        <strong>${this.currentUser}</strong> recognized <strong>${recognitionData.employee}</strong>
                        <small>Just now</small>
                    </div>
                    <div class="recognition-badge">${typeEmojis[recognitionData.type]} ${typeNames[recognitionData.type]}</div>
                </div>
                <p>"${recognitionData.message}"</p>
                <div class="recognition-actions">
                    <button class="btn btn-sm">👍 0</button>
                    <button class="btn btn-sm">💬 Comment</button>
                </div>
            `;
            
            recognitionList.insertBefore(recognitionItem, recognitionList.firstChild);
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? 'var(--success-color)' : 'var(--primary-color)'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
        `;
        notification.textContent = message;

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Add to page
        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Utility function to show submodule
    showSubModule(submoduleName) {
        this.switchSubmodule(submoduleName);
    }
}

// Initialize the HR System when the page loads
document.addEventListener('DOMContentLoaded', () => {
    window.hrSystem = new HRSystem();
});

// Global function for onclick handlers
function showSubModule(submoduleName) {
    if (window.hrSystem) {
        window.hrSystem.showSubModule(submoduleName);
    }
}