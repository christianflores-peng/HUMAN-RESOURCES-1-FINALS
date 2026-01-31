# Job Application Workflow Guide

## Overview
This system allows you to post job requisitions that appear on the public careers page, where applicants can submit their applications. All applications are then visible in the admin panel for review and management.

## Complete Workflow

### 1. Create Job Requisition (Admin)
**Location:** Admin Panel → Recruitment → Job Requisition

**Steps:**
1. Log into the admin panel
2. Navigate to "Recruitment" in the sidebar
3. Click "New Job Requisition" button
4. Fill in the job details:
   - Job Title
   - Department
   - Location
   - Employment Type
   - Salary Range (optional)
   - Closing Date (optional)
   - Job Description
   - Requirements
5. Click "Create Requisition"
6. The job is created with status "draft"

**Important:** To make the job visible to applicants, you must:
- Click "Edit" on the job requisition
- Change status from "draft" to "active"
- Click "Save Changes"

### 2. Public Job Board (Applicant View)
**Location:** `http://your-domain.com/careers.php`

**Features:**
- Displays all ACTIVE job postings
- Shows job title, department, location, employment type
- Displays salary range (if provided)
- Filter by department
- Search functionality
- Click "Apply Now" to submit application

### 3. Submit Application (Applicant)
**Location:** `http://your-domain.com/apply.php?job_id=X`

**Required Information:**
- First Name *
- Last Name *
- Email Address *
- Phone Number
- Resume (PDF, DOC, or DOCX - max 5MB)
- Cover Letter *

**Process:**
1. Applicant fills out the form
2. Uploads resume (optional but recommended)
3. Writes cover letter
4. Clicks "Submit Application"
5. System validates:
   - All required fields filled
   - Valid email format
   - No duplicate applications (same email for same job)
   - Resume file type and size
6. Application is stored in database with status "new"
7. Resume is saved to `uploads/resumes/` directory
8. Success message displayed to applicant

### 4. View Applications (Admin)
**Location:** Admin Panel → Job Applications

**Features:**
- View all submitted applications
- Statistics dashboard showing:
  - Total applications
  - New applications
  - Applications in review
  - Interviews scheduled
  - Hired candidates
- Filter by:
  - Status (new, reviewed, screening, interview, offer, hired, rejected)
  - Job position
- Click any application to view full details

### 5. Manage Applications (Admin)
**Actions Available:**

**View Application Details:**
- Click on any row or "View" button
- See complete applicant information:
  - Name, email, phone
  - Job position applied for
  - Department
  - Applied date
  - Cover letter
  - Download resume
  - Previous notes

**Update Status:**
Available statuses:
- **New** - Just submitted
- **Reviewed** - Application has been reviewed
- **Screening** - Initial screening in progress
- **Interview** - Scheduled for interview
- **Offer** - Job offer extended
- **Hired** - Candidate accepted and hired
- **Rejected** - Application rejected

**Add Notes:**
- Add internal notes about the applicant
- Notes are only visible to admin users
- Useful for tracking interview feedback, concerns, etc.

**Delete Application:**
- Permanently removes application from database
- Also deletes the uploaded resume file
- Cannot be undone - use with caution

## File Structure

```
HR1/
├── pages/
│   ├── recruitment.php          # Admin: Create/manage job postings
│   └── applications.php         # Admin: View/manage applications
├── careers.php                  # Public: Job listings page
├── apply.php                    # Public: Application submission form
└── uploads/
    └── resumes/                 # Uploaded resume files
```

## Database Tables

**job_postings:**
- Stores job requisitions created by admin
- Status: draft, active, closed, cancelled
- Only "active" jobs appear on public careers page

**job_applications:**
- Stores all submitted applications
- Links to job_postings via job_posting_id
- Tracks status through hiring pipeline
- Stores applicant contact info and documents

## Access URLs

**Admin Panel:**
- Login: `http://your-domain.com/login.php`
- Recruitment: `http://your-domain.com/pages/recruitment.php`
- Applications: `http://your-domain.com/pages/applications.php`

**Public Pages:**
- Careers: `http://your-domain.com/careers.php`
- Apply: `http://your-domain.com/apply.php?job_id=X`

## Tips for Best Results

1. **Always set jobs to "active"** - Draft jobs won't appear on careers page
2. **Update application status regularly** - Helps track candidates through pipeline
3. **Add notes** - Document interview feedback and decisions
4. **Check "New Applications" regularly** - Don't miss qualified candidates
5. **Use filters** - Quickly find applications by status or position
6. **Download resumes** - Review before interviews
7. **Set closing dates** - Jobs automatically close after date (if implemented)

## Troubleshooting

**Job not appearing on careers page:**
- Check if status is "active" (not draft)
- Verify job has title, description, and requirements filled
- Check database connection

**Application submission fails:**
- Check uploads/resumes/ directory exists and is writable
- Verify file size is under 5MB
- Ensure file type is PDF, DOC, or DOCX
- Check for duplicate email on same job

**Resume download not working:**
- Verify file exists in uploads/resumes/
- Check file permissions
- Ensure path is correct in database

## Security Notes

- Admin pages require login (session-based authentication)
- Public pages (careers.php, apply.php) are accessible without login
- Resume files are stored with unique names to prevent overwrites
- SQL injection protection via prepared statements
- XSS protection via htmlspecialchars() on all output
- File upload validation (type, size, extension)
