<!-- Edit Job Requisition Modal -->
<div id="editModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:var(--card-bg, #1e293b); padding:2rem; border-radius:8px; width:90%; max-width:700px; max-height:90vh; overflow-y:auto;">
        <h3 style="margin-bottom:1rem;">Edit Job Requisition</h3>
        <form action="recruitment.php" method="POST">
            <input type="hidden" name="action" value="update_requisition">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" id="edit_department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= h($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" id="edit_location" required>
                </div>
                <div class="form-group">
                    <label>Employment Type</label>
                    <select name="employment_type" id="edit_employment_type" required>
                        <?php foreach ($employmentTypes as $type): ?>
                            <option value="<?= h($type) ?>"><?= h($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Minimum Salary</label>
                    <input type="number" name="salary_min" id="edit_salary_min" step="0.01">
                </div>
                <div class="form-group">
                    <label>Maximum Salary</label>
                    <input type="number" name="salary_max" id="edit_salary_max" step="0.01">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Closing Date</label>
                    <input type="date" name="closing_date" id="edit_closing_date">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="closed">Closed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Job Description</label>
                <textarea name="description" id="edit_description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label>Requirements</label>
                <textarea name="requirements" id="edit_requirements" rows="3" required></textarea>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Question Modal -->
<div id="viewQuestionModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:var(--card-bg, #1e293b); padding:2rem; border-radius:8px; width:90%; max-width:500px;">
        <h3 style="margin-bottom:1.5rem;">View Screening Question</h3>
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#94a3b8;">Question Text:</label>
            <p id="view_question_text" style="padding:0.75rem; background:#0f172a; border-radius:4px; color:#e2e8f0;"></p>
        </div>
        <div style="display:flex; gap:1rem; margin-bottom:1rem;">
            <div style="flex:1;">
                <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#94a3b8;">Type:</label>
                <p id="view_question_type" style="padding:0.75rem; background:#0f172a; border-radius:4px; color:#e2e8f0; text-transform:capitalize;"></p>
            </div>
            <div style="flex:1;">
                <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#94a3b8;">Required:</label>
                <p id="view_question_required" style="padding:0.75rem; background:#0f172a; border-radius:4px; color:#e2e8f0;"></p>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; margin-top:1.5rem;">
            <button type="button" class="btn" onclick="closeViewQuestionModal()">Close</button>
        </div>
    </div>
</div>

<!-- Edit Question Modal -->
<div id="editQuestionModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:var(--card-bg, #1e293b); padding:2rem; border-radius:8px; width:90%; max-width:600px; max-height:90vh; overflow-y:auto;">
        <h3 style="margin-bottom:1rem;">Edit Screening Question</h3>
        <form action="recruitment.php" method="POST">
            <input type="hidden" name="action" value="update_screening_question">
            <input type="hidden" name="question_id" id="edit_question_id">
            <input type="hidden" name="job_posting_id" id="edit_question_job_id">
            
            <div class="form-group">
                <label>Question Text</label>
                <textarea name="question_text" id="edit_question_text" rows="3" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Question Type</label>
                    <select name="question_type" id="edit_question_type" required>
                        <option value="text">Text Answer</option>
                        <option value="yes_no">Yes/No</option>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="rating">Rating (1-5)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Required</label>
                    <select name="required" id="edit_question_required">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            
            <div style="display:flex; gap:1rem; margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn" onclick="closeEditQuestionModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Interview Scheduling Modal -->
<div id="interviewModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:var(--card-bg, #1e293b); padding:2rem; border-radius:8px; width:90%; max-width:600px; max-height:90vh; overflow-y:auto;">
        <h3 style="margin-bottom:1rem;">Schedule Interview</h3>
        <p style="color:#94a3b8; margin-bottom:1.5rem;">Applicant: <strong id="interview_applicant_name" style="color:#e2e8f0;"></strong></p>
        
        <form id="interviewScheduleForm" action="recruitment.php" method="POST">
            <input type="hidden" name="action" value="schedule_interview">
            <input type="hidden" name="application_id" id="interview_application_id">
            
            <div class="form-group">
                <label>Interview Date</label>
                <input type="date" name="interview_date" required>
            </div>
            
            <div class="form-group">
                <label>Interview Time</label>
                <input type="time" name="interview_time" required>
            </div>
            
            <div class="form-group">
                <label>Interview Type</label>
                <select name="interview_type" required>
                    <option value="">Select Type</option>
                    <option value="phone">Phone Interview</option>
                    <option value="video">Video Interview</option>
                    <option value="in-person">In-Person Interview</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Venue/Location</label>
                <textarea name="interview_location" rows="3" required placeholder="Enter the interview venue address or video call link..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Interviewer(s)</label>
                <input type="text" name="interviewers" placeholder="e.g., John Doe, Jane Smith">
            </div>
            
            <div class="form-group">
                <label>Additional Notes</label>
                <textarea name="interview_notes" rows="3" placeholder="Any additional information for the candidate..."></textarea>
            </div>
            
            <div style="display:flex; gap:1rem; margin-top:1rem;">
                <button type="button" class="btn btn-primary" onclick="submitInterviewSchedule()">Schedule Interview</button>
                <button type="button" class="btn" onclick="closeInterviewModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(req) {
    document.getElementById('edit_id').value = req.id;
    document.getElementById('edit_title').value = req.title;
    document.getElementById('edit_department_id').value = req.department_id;
    document.getElementById('edit_location').value = req.location || '';
    document.getElementById('edit_employment_type').value = req.employment_type || '';
    document.getElementById('edit_salary_min').value = req.salary_min || '';
    document.getElementById('edit_salary_max').value = req.salary_max || '';
    document.getElementById('edit_closing_date').value = req.closing_date || '';
    document.getElementById('edit_status').value = req.status;
    document.getElementById('edit_description').value = req.description || '';
    document.getElementById('edit_requirements').value = req.requirements || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function viewQuestion(question) {
    document.getElementById('view_question_text').textContent = question.question_text;
    document.getElementById('view_question_type').textContent = question.question_type;
    document.getElementById('view_question_required').textContent = question.required == 1 ? 'Yes' : 'No';
    document.getElementById('viewQuestionModal').style.display = 'flex';
}

function closeViewQuestionModal() {
    document.getElementById('viewQuestionModal').style.display = 'none';
}

function editQuestion(question) {
    document.getElementById('edit_question_id').value = question.id;
    document.getElementById('edit_question_job_id').value = question.job_posting_id;
    document.getElementById('edit_question_text').value = question.question_text;
    document.getElementById('edit_question_type').value = question.question_type;
    document.getElementById('edit_question_required').value = question.required;
    document.getElementById('editQuestionModal').style.display = 'flex';
}

function closeEditQuestionModal() {
    document.getElementById('editQuestionModal').style.display = 'none';
}

function openInterviewModal(applicationId, applicantName) {
    document.getElementById('interview_application_id').value = applicationId;
    document.getElementById('interview_applicant_name').textContent = applicantName;
    document.getElementById('interviewModal').style.display = 'flex';
}

function closeInterviewModal() {
    document.getElementById('interviewModal').style.display = 'none';
}

function submitInterviewSchedule() {
    document.getElementById('interviewScheduleForm').submit();
}

function deleteRequisition(id) {
    if (confirm('Are you sure you want to delete this job requisition?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'recruitment.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_requisition';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
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
