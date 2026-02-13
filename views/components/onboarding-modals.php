<!-- Add Task Modal -->
<div id="addTaskModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addTaskModal').style.display='none'">&times;</span>
        <h3>Add Onboarding Task</h3>
        
        <!-- Quick Generate Section -->
        <div style="background: #0f172a; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 3px solid #3b82f6;">
            <h4 style="margin: 0 0 0.5rem 0; color: #e2e8f0; font-size: 0.95rem;">✨ Auto-Generate Tasks</h4>
            <p style="margin: 0 0 0.75rem 0; color: #94a3b8; font-size: 0.875rem;">Automatically create position-based tasks for the selected employee</p>
            <form method="POST" action="onboarding.php" id="generateTasksForm">
                <input type="hidden" name="action" value="generate_tasks">
                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <select name="employee_id" id="generate_employee_id" required style="width: 100%;">
                        <option value="">Select Employee</option>
                        <?php foreach ($allEmployees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo h($emp['name'] . ' - ' . $emp['position']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Generate Position-Based Tasks</button>
            </form>
        </div>
        
        <div style="text-align: center; color: #64748b; margin: 1rem 0; font-size: 0.875rem;">— OR —</div>
        
        <!-- Manual Add Section -->
        <h4 style="margin: 0 0 1rem 0; color: #e2e8f0; font-size: 0.95rem;">Add Custom Task</h4>
        <form method="POST" action="onboarding.php">
            <input type="hidden" name="action" value="add_task">
            
            <div class="form-group">
                <label for="employee_id">Employee *</label>
                <select name="employee_id" id="employee_id" required>
                    <option value="">Select Employee</option>
                    <?php foreach ($allEmployees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>"><?php echo h($emp['name'] . ' - ' . $emp['position']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="task_name">Task Name *</label>
                <input type="text" name="task_name" id="task_name" placeholder="e.g., Complete paperwork" required>
            </div>
            
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" name="due_date" id="due_date">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Task</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTaskModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Orientation Session Modal -->
<div id="addOrientationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addOrientationModal').style.display='none'">&times;</span>
        <h3>Schedule Orientation Session</h3>
        <form method="POST" action="onboarding.php">
            <input type="hidden" name="action" value="add_orientation">
            <input type="hidden" name="employee_id" value="<?php echo $primaryEmployeeId ?? ''; ?>">
            
            <div class="form-group">
                <label for="session_title">Session Title *</label>
                <input type="text" name="session_title" id="session_title" placeholder="e.g., Welcome & Company Overview" required>
            </div>
            
            <div class="form-group">
                <label for="session_description">Description</label>
                <textarea name="session_description" id="session_description" rows="3" placeholder="Brief description of the session..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="session_date">Date *</label>
                <input type="date" name="session_date" id="session_date" required>
            </div>
            
            <div class="form-group">
                <label for="session_time">Time *</label>
                <input type="time" name="session_time" id="session_time" required>
            </div>
            
            <div class="form-group">
                <label for="duration_minutes">Duration (minutes)</label>
                <input type="number" name="duration_minutes" id="duration_minutes" value="60" min="15" step="15">
            </div>
            
            <div class="form-group">
                <label for="location">Location/Venue</label>
                <input type="text" name="location" id="location" placeholder="e.g., Conference Room A or Zoom link">
            </div>
            
            <div class="form-group">
                <label for="facilitator">Facilitator/Presenter</label>
                <input type="text" name="facilitator" id="facilitator" placeholder="e.g., HR Manager">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Schedule Session</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addOrientationModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddTaskModal() {
    document.getElementById('addTaskModal').style.display = 'flex';
}

function showAddOrientationModal() {
    document.getElementById('addOrientationModal').style.display = 'flex';
}

// Close modals on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>
