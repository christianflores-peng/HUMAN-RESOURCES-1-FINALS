<!-- Edit User Modal -->
<div class="modal" id="editUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User Permissions</h2>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" name="user_id" id="editUserId">

                <div class="form-group">
                    <label>User</label>
                    <input type="text" id="editUserName" disabled style="background: #1e2936;">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role_id" id="editRoleId">
                            <option value="1">System Administrator</option>
                            <option value="2">HR Staff</option>
                            <option value="4">Fleet Manager</option>
                            <option value="5">Warehouse Manager</option>
                            <option value="7">Employee</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editStatus">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>

                <h4 style="color: #cbd5e1; margin: 1rem 0 0.5rem;">Sample Permission Config (Manager)</h4>
                <div class="permission-grid">
                    <div class="permission-item">
                        <span class="permission-label">Can View Documents?</span>
                        <div class="permission-toggle">
                            <button type="button" class="toggle-btn yes active">YES</button>
                            <button type="button" class="toggle-btn no">NO</button>
                        </div>
                    </div>
                    <div class="permission-item">
                        <span class="permission-label">Can Edit Salary?</span>
                        <div class="permission-toggle">
                            <button type="button" class="toggle-btn yes">YES</button>
                            <button type="button" class="toggle-btn no active">NO</button>
                        </div>
                    </div>
                    <div class="permission-item">
                        <span class="permission-label">Can Delete Employee?</span>
                        <div class="permission-toggle">
                            <button type="button" class="toggle-btn yes">YES</button>
                            <button type="button" class="toggle-btn no active">NO</button>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i data-lucide="save"></i>
                        Save Changes
                    </button>
                    <button type="button" class="btn btn-danger" onclick="resetPassword()">
                        <i data-lucide="key-round"></i>
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Form -->
<form id="resetPasswordForm" method="POST" style="display: none;">
    <input type="hidden" name="reset_password" value="1">
    <input type="hidden" name="user_id" id="resetUserId">
</form>

<script>
    function editUserPermissions(userId, userName) {
        document.getElementById('editUserId').value = userId;
        document.getElementById('editUserName').value = userName;
        document.getElementById('resetUserId').value = userId;
        document.getElementById('editUserModal').classList.add('active');
    }

    function resetPassword() {
        if (confirm('Are you sure you want to reset this user\'s password?')) {
            document.getElementById('resetPasswordForm').submit();
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
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
