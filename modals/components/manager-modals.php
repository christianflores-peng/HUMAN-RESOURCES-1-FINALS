<!-- View Documents Modal -->
<div class="modal" id="documentsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><span class="material-symbols-outlined">folder_open</span> Employee Documents - <span id="docEmployeeName"></span></h2>
            <button class="modal-close" onclick="closeModal('documentsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="doc-section">
                <h3><span class="material-symbols-outlined">badge</span> Licenses & Certifications (Critical for Freight)</h3>
                <div id="licenseDocs">
                    <div class="doc-item">
                        <div class="doc-info">
                            <h4><span class="material-symbols-outlined" style="font-size: 1.2rem; vertical-align: middle;">description</span> Commercial Driver's License (CDL)</h4>
                            <p>Status: <span class="status-badge valid">Valid</span> | Expiry: Dec 2025</p>
                        </div>
                        <button class="btn btn-view" title="View Only - No Delete">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                    <div class="doc-item">
                        <div class="doc-info">
                            <h4><span class="material-symbols-outlined" style="font-size: 1.2rem; vertical-align: middle;">description</span> Medical Certificate</h4>
                            <p>Status: <span class="status-badge expiring"><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">warning</span> Expiring Soon</span> | Expiry: Next Week</p>
                        </div>
                        <button class="btn btn-view" title="View Only - No Delete">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="doc-section">
                <h3><span class="material-symbols-outlined">description</span> HR Files (Filtered)</h3>
                <div class="doc-item">
                    <div class="doc-info">
                        <h4>📄 201 File (Bio-data)</h4>
                        <p>Viewable</p>
                    </div>
                    <button class="btn btn-view">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                </div>
                <div class="doc-item">
                    <div class="doc-info">
                        <h4>📄 Employment Contract</h4>
                        <p>Viewable</p>
                    </div>
                    <button class="btn btn-view">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                </div>
            </div>

            <div class="doc-section">
                <h3><span class="material-symbols-outlined">lock</span> Restricted Files</h3>
                <div class="locked-section">
                    <span class="material-symbols-outlined">lock</span>
                    <p>🚫 Salary / Payslips - HIDDEN / LOCKED</p>
                    <small>Only HR Staff and Admin have access to Payroll Management</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Review Modal -->
<div class="modal" id="reviewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><span class="material-symbols-outlined">rate_review</span> Probation Evaluation - <span id="reviewEmployeeName"></span></h2>
            <button class="modal-close" onclick="closeModal('reviewModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" class="review-form">
                <input type="hidden" name="submit_review" value="1">
                <input type="hidden" name="employee_id" id="reviewEmployeeId">

                <div class="form-group">
                    <label>Safety Score</label>
                    <select name="safety_score" required>
                        <option value="">Select Rating (1-5)</option>
                        <option value="1">⭐ 1 - Poor</option>
                        <option value="2">⭐⭐ 2 - Below Average</option>
                        <option value="3">⭐⭐⭐ 3 - Average</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Good</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Punctuality Score</label>
                    <select name="punctuality_score" required>
                        <option value="">Select Rating (1-5)</option>
                        <option value="1">⭐ 1 - Poor</option>
                        <option value="2">⭐⭐ 2 - Below Average</option>
                        <option value="3">⭐⭐⭐ 3 - Average</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Good</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Quality Score</label>
                    <select name="quality_score" required>
                        <option value="">Select Rating (1-5)</option>
                        <option value="1">⭐ 1 - Poor</option>
                        <option value="2">⭐⭐ 2 - Below Average</option>
                        <option value="3">⭐⭐⭐ 3 - Average</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Good</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Teamwork Score</label>
                    <select name="teamwork_score" required>
                        <option value="">Select Rating (1-5)</option>
                        <option value="1">⭐ 1 - Poor</option>
                        <option value="2">⭐⭐ 2 - Below Average</option>
                        <option value="3">⭐⭐⭐ 3 - Average</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Good</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Recommendation</label>
                    <select name="recommendation" required>
                        <option value="">Select Recommendation</option>
                        <option value="Pass Probation">Pass Probation - Regularize</option>
                        <option value="Extend Probation">Extend Probation (30 more days)</option>
                        <option value="Terminate">Terminate Employment</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Comments / Notes</label>
                    <textarea name="comments" placeholder="Additional observations about the employee's performance..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <span class="material-symbols-outlined">send</span>
                    Submit Evaluation to HR
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function viewDocuments(employeeId, employeeName) {
        document.getElementById('docEmployeeName').textContent = employeeName;
        document.getElementById('documentsModal').classList.add('active');
    }

    function showReviewForm(employeeId, employeeName) {
        document.getElementById('reviewEmployeeId').value = employeeId;
        document.getElementById('reviewEmployeeName').textContent = employeeName;
        document.getElementById('reviewModal').classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function showExpiringLicenses() {
        alert('Feature: View and send reminders to drivers with expiring licenses');
    }

    function showProbationList() {
        alert('Feature: View all employees on probation requiring evaluation');
    }

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
</script>
