<!-- Logout Confirmation Modal -->
<div class="logout-modal-overlay" id="logoutModalOverlay">
    <div class="logout-modal">
        <div class="logout-modal-icon">
            <i data-lucide="log-out"></i>
        </div>
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to logout from your account?</p>
        <div class="logout-timestamp" id="logoutTimestamp">
            <i data-lucide="clock"></i>
            <span id="currentDateTime"></span>
        </div>
        <div class="logout-timer-notice" id="logoutTimerNotice">
            <i data-lucide="timer"></i>
            Please wait <span id="waitCountdown">3</span> seconds before logging out
        </div>
        <div class="logout-modal-buttons">
            <button type="button" class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            <button type="button" class="btn-logout" id="logoutBtn" onclick="performLogout()" disabled>
                <i data-lucide="lock" style="width:1rem;height:1rem;"></i>
                <span id="logoutBtnText">Wait (3s)</span>
            </button>
        </div>
    </div>
</div>

<style>
    .logout-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .logout-modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .logout-modal {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(14, 165, 233, 0.3);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        max-width: 380px;
        width: 90%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 30px rgba(14, 165, 233, 0.1);
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            transform: scale(0.9) translateY(-20px);
            opacity: 0;
        }
        to {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
    }

    .logout-modal-icon {
        width: 70px;
        height: 70px;
        background: rgba(239, 68, 68, 0.15);
        border: 2px solid rgba(239, 68, 68, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }

    .logout-modal-icon i {
        width: 2rem;
        height: 2rem;
        color: #ef4444;
    }

    .logout-modal h3 {
        color: #f1f5f9;
        font-size: 1.4rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    .logout-modal p {
        color: #94a3b8;
        font-size: 0.95rem;
        margin-bottom: 1.75rem;
        line-height: 1.5;
    }

    .logout-modal-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .logout-modal .btn-cancel,
    .logout-modal .btn-logout {
        padding: 0.75rem 1.75rem;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        min-width: 120px;
    }

    .logout-modal .btn-cancel {
        background: rgba(100, 116, 139, 0.2);
        color: #cbd5e1;
        border: 1px solid rgba(100, 116, 139, 0.3);
    }

    .logout-modal .btn-cancel:hover {
        background: rgba(100, 116, 139, 0.35);
        border-color: rgba(100, 116, 139, 0.5);
    }

    .logout-modal .btn-logout {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }

    .logout-modal .btn-logout:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }

    .logout-modal .btn-logout:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    #logoutCountdown {
        display: inline-block;
        min-width: 25px;
        font-weight: 600;
    }

    .logout-timestamp {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: rgba(14, 165, 233, 0.1);
        border: 1px solid rgba(14, 165, 233, 0.3);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        color: #0ea5e9;
        font-size: 0.9rem;
    }

    .logout-timestamp i {
        width: 1.1rem;
        height: 1.1rem;
    }

    .logout-timer-notice {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 1.5rem;
        color: #f59e0b;
        font-size: 0.85rem;
    }

    .logout-timer-notice i {
        width: 1.1rem;
        height: 1.1rem;
        animation: pulse 1s ease-in-out infinite;
    }

    .logout-timer-notice.hidden {
        display: none;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .logout-modal .btn-logout.ready {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .logout-modal .btn-logout:not(.ready) {
        background: rgba(100, 116, 139, 0.3);
        cursor: not-allowed;
    }
</style>

<script>
    let logoutCountdownInterval = null;
    let logoutCountdownValue = 3;
    let logoutUrl = '<?php echo isset($logout_url) ? $logout_url : "../../logout.php"; ?>';

    function updateCurrentDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
    }

    function showLogoutModal(e) {
        if (e) e.preventDefault();
        const overlay = document.getElementById('logoutModalOverlay');
        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('active'), 10);
        
        // Update timestamp
        updateCurrentDateTime();
        
        // Start the 3-second wait countdown
        startLogoutCountdown();
    }

    function closeLogoutModal() {
        const overlay = document.getElementById('logoutModalOverlay');
        overlay.classList.remove('active');
        setTimeout(() => overlay.style.display = 'none', 300);
        clearInterval(logoutCountdownInterval);
        resetLogoutButton();
    }

    function setLogoutBtnIcon(iconName) {
        const logoutBtn = document.getElementById('logoutBtn');
        // Remove existing icon (could be <i> or <svg> after Lucide processes it)
        const oldIcon = logoutBtn.querySelector('i, svg.lucide');
        if (oldIcon) oldIcon.remove();
        // Create fresh <i> tag for Lucide to process
        const newIcon = document.createElement('i');
        newIcon.setAttribute('data-lucide', iconName);
        newIcon.style.width = '1rem';
        newIcon.style.height = '1rem';
        logoutBtn.insertBefore(newIcon, logoutBtn.firstChild);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function resetLogoutButton() {
        logoutCountdownValue = 3;
        const logoutBtn = document.getElementById('logoutBtn');
        const logoutBtnText = document.getElementById('logoutBtnText');
        const waitCountdown = document.getElementById('waitCountdown');
        const timerNotice = document.getElementById('logoutTimerNotice');
        
        logoutBtn.disabled = true;
        logoutBtn.classList.remove('ready');
        setLogoutBtnIcon('lock');
        logoutBtnText.textContent = 'Wait (3s)';
        waitCountdown.textContent = '3';
        timerNotice.classList.remove('hidden');
    }

    function startLogoutCountdown() {
        logoutCountdownValue = 3;
        const logoutBtn = document.getElementById('logoutBtn');
        const logoutBtnText = document.getElementById('logoutBtnText');
        const waitCountdown = document.getElementById('waitCountdown');
        const timerNotice = document.getElementById('logoutTimerNotice');
        
        // Initially disabled
        logoutBtn.disabled = true;
        logoutBtn.classList.remove('ready');
        setLogoutBtnIcon('lock');
        logoutBtnText.textContent = 'Wait (3s)';
        timerNotice.classList.remove('hidden');
        
        logoutCountdownInterval = setInterval(() => {
            logoutCountdownValue--;
            waitCountdown.textContent = logoutCountdownValue;
            logoutBtnText.textContent = 'Wait (' + logoutCountdownValue + 's)';
            
            if (logoutCountdownValue <= 0) {
                clearInterval(logoutCountdownInterval);
                // Enable the logout button
                logoutBtn.disabled = false;
                logoutBtn.classList.add('ready');
                setLogoutBtnIcon('log-out');
                logoutBtnText.textContent = 'Logout Now';
                timerNotice.classList.add('hidden');
            }
        }, 1000);
    }

    function performLogout() {
        // Only allow if countdown is complete
        if (logoutCountdownValue > 0) {
            return;
        }
        
        const logoutBtn = document.getElementById('logoutBtn');
        const logoutBtnText = document.getElementById('logoutBtnText');
        
        logoutBtn.disabled = true;
        setLogoutBtnIcon('loader-2');
        const spinIcon = logoutBtn.querySelector('svg.lucide, i');
        if (spinIcon) spinIcon.style.animation = 'spin 1s linear infinite';
        logoutBtnText.textContent = 'Logging out...';
        
        // Show loading screen if available
        if (typeof showLoadingScreen === 'function') {
            showLoadingScreen();
        }
        
        setTimeout(() => {
            window.location.href = logoutUrl;
        }, 500);
    }

    // Attach to logout links
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('a[href*="logout"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                logoutUrl = this.href;
                showLogoutModal(e);
            });
        });
    });

    // Close modal on overlay click
    document.getElementById('logoutModalOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogoutModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLogoutModal();
        }
    });
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
