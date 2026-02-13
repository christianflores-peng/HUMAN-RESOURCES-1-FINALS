<!-- Application Detail Modal -->
<div id="applicationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Application Details</h3>
            <button class="btn" onclick="closeModal()">&times;</button>
        </div>
        
        <div class="detail-grid">
            <div class="detail-item">
                <label>First Name</label>
                <div class="value" id="modal_first_name"></div>
            </div>
            <div class="detail-item">
                <label>Last Name</label>
                <div class="value" id="modal_last_name"></div>
            </div>
            <div class="detail-item">
                <label>Email</label>
                <div class="value" id="modal_email"></div>
            </div>
            <div class="detail-item">
                <label>Phone</label>
                <div class="value" id="modal_phone"></div>
            </div>
            <div class="detail-item">
                <label>Job Position</label>
                <div class="value" id="modal_job_title"></div>
            </div>
            <div class="detail-item">
                <label>Department</label>
                <div class="value" id="modal_department"></div>
            </div>
            <div class="detail-item">
                <label>Applied Date</label>
                <div class="value" id="modal_applied_date"></div>
            </div>
            <div class="detail-item">
                <label>Resume</label>
                <div class="value" id="modal_resume"></div>
            </div>
            <div class="detail-item full-width">
                <label>Cover Letter</label>
                <div class="value" id="modal_cover_letter" style="white-space: pre-wrap;"></div>
            </div>
            <div class="detail-item full-width" id="modal_notes_display" style="display: none;">
                <label>Notes</label>
                <div class="value" id="modal_existing_notes" style="white-space: pre-wrap;"></div>
            </div>
        </div>

        <form method="POST" style="margin-top: 1.5rem;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="application_id" id="modal_application_id">
            
            <div class="form-group">
                <label>Update Status</label>
                <select name="status" id="modal_status" required>
                    <option value="new">New</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="screening">Screening</option>
                    <option value="interview">Interview</option>
                    <option value="offer">Offer</option>
                    <option value="hired">Hired</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" id="modal_notes" rows="3" placeholder="Add notes about this application..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary">Update Status</button>
                <button type="button" class="btn" onclick="closeModal()">Close</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewApplication(app) {
    document.getElementById('modal_application_id').value = app.id;
    document.getElementById('modal_first_name').textContent = app.first_name;
    document.getElementById('modal_last_name').textContent = app.last_name;
    document.getElementById('modal_email').textContent = app.email;
    document.getElementById('modal_phone').textContent = app.phone || 'N/A';
    document.getElementById('modal_job_title').textContent = app.job_title;
    document.getElementById('modal_department').textContent = app.department_name;
    document.getElementById('modal_applied_date').textContent = new Date(app.applied_date).toLocaleDateString();
    document.getElementById('modal_cover_letter').textContent = app.cover_letter || 'No cover letter provided';
    document.getElementById('modal_status').value = app.status;
    document.getElementById('modal_notes').value = app.notes || '';
    
    // Handle resume link
    const resumeDiv = document.getElementById('modal_resume');
    if (app.resume_path) {
        resumeDiv.innerHTML = '<a href="../' + app.resume_path + '" target="_blank" class="btn btn-sm">Download Resume</a>';
    } else {
        resumeDiv.textContent = 'No resume uploaded';
    }
    
    // Show existing notes if any
    if (app.notes) {
        document.getElementById('modal_notes_display').style.display = 'block';
        document.getElementById('modal_existing_notes').textContent = app.notes;
    } else {
        document.getElementById('modal_notes_display').style.display = 'none';
    }
    
    document.getElementById('applicationModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('applicationModal').style.display = 'none';
}

function deleteApplication(id) {
    if (confirm('Are you sure you want to delete this application? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'applications.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'application_id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('applicationModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
