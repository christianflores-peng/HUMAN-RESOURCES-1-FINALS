<!-- Hire Confirmation Modal -->
<div class="modal" id="hireModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i data-lucide="user-check"></i> Confirm Hiring</h2>
            <button class="modal-close" onclick="closeModal('hireModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="hireForm">
                <input type="hidden" name="hire_applicant" value="1">
                <input type="hidden" name="applicant_id" id="hireApplicantId">

                <p style="margin-bottom: 1rem; color: #94a3b8;">
                    Confirm hiring of <strong id="hireApplicantName" style="color: #ffffff;"></strong>?
                </p>

                <div class="form-group">
                    <label>Job Title / Role</label>
                    <input type="text" name="job_title" id="hireJobTitle" required placeholder="e.g., Truck Driver - Class A">
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Assigned Role</label>
                    <select name="role_id" required>
                        <option value="">Select Role</option>
                        <option value="7">Employee</option>
                        <option value="8">New Hire (Probation)</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="background: #2a3544; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.5rem;">
                        <i data-lucide="info" style="width: 16px; height: 16px; vertical-align: middle;"></i>
                        Auto-Fill Action:
                    </p>
                    <p style="color: #0ea5e9; font-size: 0.9rem;">
                        System will generate: <strong>[firstname].[lastname]@slatefreight.com</strong>
                    </p>
                </div>

                <button type="submit" class="btn btn-success" style="width: 100%; justify-content: center;" id="hireSubmitBtn">
                    <i data-lucide="check-circle"></i>
                    Confirm Hire & Generate Email
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Form (Hidden) -->
<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="update_status" value="1">
    <input type="hidden" name="applicant_id" id="statusApplicantId">
    <input type="hidden" name="new_status" id="statusNewStatus">
</form>

<script>
    function showHireModal(applicantId, name, jobTitle) {
        document.getElementById('hireApplicantId').value = applicantId;
        document.getElementById('hireApplicantName').textContent = name;
        document.getElementById('hireJobTitle').value = jobTitle;
        document.getElementById('hireModal').classList.add('active');
    }

    function moveToStatus(applicantId, newStatus) {
        if (confirm('Move this applicant to ' + newStatus.replace('_', ' ').toUpperCase() + '?')) {
            document.getElementById('statusApplicantId').value = applicantId;
            document.getElementById('statusNewStatus').value = newStatus;
            document.getElementById('statusForm').submit();
        }
    }

    function showApplicantDetails(applicantId) {
        // TODO: Show detailed view modal
        console.log('View applicant:', applicantId);
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
</script>
