// Teacher Activities JavaScript
let activities = [];
let subjects = [];
let currentActivityId = null;
let questionCount = 0;

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    loadTeacherSubjects();
    loadActivities();
    loadActivityStats();
    
    // Initialize bulk upload form handler
    document.getElementById('bulkUploadForm').addEventListener('submit', handleBulkUpload);
    
    // Set minimum date for due date to today
    const now = new Date();
    const dateString = now.toISOString().slice(0, 16);
    document.getElementById('dueDate').min = dateString;
});

// Load teacher's subjects
function loadTeacherSubjects() {
    fetch('teacher-activities-backend.php?action=get_teacher_subjects')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                subjects = data.subjects;
                populateSubjectSelect();
            } else {
                showNotification('Error loading subjects: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading subjects', 'error');
        });
}

// Bulk Upload Modal Functions
function showBulkUploadModal() {
    document.getElementById('bulkUploadModal').style.display = 'block';
}

function closeBulkUploadModal() {
    document.getElementById('bulkUploadModal').style.display = 'none';
}

// Handle bulk upload submission
function handleBulkUpload(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const fileInput = document.getElementById('csvFile');
    
    if (!fileInput.files[0]) {
        showNotification('Please select a file to upload', 'error');
        return;
    }
    
    formData.append('csvFile', fileInput.files[0]);
    formData.append('action', 'bulk_upload');
    
    fetch('teacher-bulk-operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Successfully processed ${data.processed} records`, 'success');
            if (data.errors && data.errors.length > 0) {
                showNotification(`${data.errors.length} records had errors`, 'warning');
            }
            closeBulkUploadModal();
            loadActivities(); // Refresh the activities list
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error processing upload', 'error');
    });
}

// Download template
function downloadTemplate(type) {
    window.location.href = `teacher-bulk-operations.php?action=download_template&type=${type}`;
}

// Export grades
function exportGrades(activityId) {
    window.location.href = `teacher-bulk-operations.php?action=export_grades&activity_id=${activityId}`;
}

// Populate subject select dropdown
function populateSubjectSelect() {
    const select = document.getElementById('activitySubject');
    select.innerHTML = '<option value="">Select Subject</option>';
    
    subjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject.subject_id;
        option.textContent = `${subject.subject_name} (Grade ${subject.grade_level})`;
        select.appendChild(option);
    });
}

// Load activities
function loadActivities() {
    console.log('Loading activities...');
    fetch('teacher-activities-backend.php?action=get_activities')
        .then(response => {
            console.log('Activities response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Activities raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Activities parsed data:', data);
                if (data.success) {
                    activities = data.activities;
                    populateActivitiesTable();
                } else {
                    console.error('Backend error:', data.message);
                    showNotification('Error loading activities: ' + data.message, 'error');
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was:', text);
                showNotification('Parse error loading activities', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showNotification('Network error loading activities: ' + error.message, 'error');
        });
}

// Populate activities table
function populateActivitiesTable() {
    const tableBody = document.getElementById('activitiesTableBody');
    tableBody.innerHTML = '';
    
    if (activities.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No activities found. Create your first activity!</p>
                </td>
            </tr>
        `;
        return;
    }
    
    activities.forEach(activity => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="activity-title">${activity.title}</td>
            <td>${activity.subject_name}</td>
            <td><span class="activity-type ${activity.activity_type}">${activity.activity_type}</span></td>
            <td>${activity.total_points}</td>
            <td>${formatDate(activity.due_date)}</td>
            <td class="submissions-count">${activity.total_submissions || 0}</td>
            <td class="avg-score">${activity.avg_score ? Math.round(activity.avg_score) + '%' : 'N/A'}</td>
            <td>
                <span class="status-badge ${activity.is_active ? 'active' : 'inactive'}">
                    ${activity.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="actions">
                <button class="btn btn-small btn-info" onclick="viewActivityDetails(${activity.activity_id})" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-small btn-secondary" onclick="viewSubmissions(${activity.activity_id})" title="View Submissions">
                    <i class="fas fa-clipboard-list"></i>
                </button>
                <button class="btn btn-small btn-export" onclick="exportGrades(${activity.activity_id})" title="Export Grades">
                    <i class="fas fa-file-export"></i>
                </button>
                <button class="btn btn-small ${activity.is_active ? 'btn-warning' : 'btn-success'}" 
                        onclick="toggleActivityStatus(${activity.activity_id})" 
                        title="${activity.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="fas ${activity.is_active ? 'fa-pause' : 'fa-play'}"></i>
                </button>
                <button class="btn btn-small btn-danger" onclick="deleteActivity(${activity.activity_id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Load activity statistics
function loadActivityStats() {
    const totalActivities = activities.length;
    const totalSubmissions = activities.reduce((sum, activity) => sum + (activity.total_submissions || 0), 0);
    const avgScore = activities.length > 0 ? 
        activities.reduce((sum, activity) => sum + (activity.avg_score || 0), 0) / activities.length : 0;
    
    document.getElementById('totalActivities').textContent = totalActivities;
    document.getElementById('totalSubmissions').textContent = totalSubmissions;
    document.getElementById('avgScore').textContent = Math.round(avgScore) + '%';
}

// Show create activity modal
function showCreateActivityModal() {
    document.getElementById('createActivityModal').style.display = 'block';
    resetCreateActivityForm();
}

// Close create activity modal
function closeCreateActivityModal() {
    document.getElementById('createActivityModal').style.display = 'none';
}

// Reset create activity form
function resetCreateActivityForm() {
    document.getElementById('createActivityForm').reset();
    document.getElementById('questionsContainer').innerHTML = '';
    questionCount = 0;
}

// Add question to form
function addQuestion() {
    questionCount++;
    const container = document.getElementById('questionsContainer');
    
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item';
    questionDiv.id = `question_${questionCount}`;
    
    questionDiv.innerHTML = `
        <div class="question-header">
            <h4>Question ${questionCount}</h4>
            <button type="button" class="btn btn-small btn-danger" onclick="removeQuestion(${questionCount})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="form-row">
            <div class="form-group flex-grow">
                <label>Question Text *</label>
                <textarea name="questions[${questionCount}][text]" rows="2" required></textarea>
            </div>
            <div class="form-group">
                <label>Question Type *</label>
                <select name="questions[${questionCount}][type]" onchange="handleQuestionTypeChange(${questionCount})" required>
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="short_answer">Short Answer</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            <div class="form-group">
                <label>Points *</label>
                <input type="number" name="questions[${questionCount}][points]" min="1" value="10" required>
            </div>
        </div>
        
        <div class="choices-container" id="choices_${questionCount}">
            <div class="form-group">
                <label>Answer Choices</label>
                <div class="choices-list" id="choicesList_${questionCount}">
                    <!-- Choices will be added here -->
                </div>
                <button type="button" class="btn btn-small btn-secondary" onclick="addChoice(${questionCount})">
                    <i class="fas fa-plus"></i> Add Choice
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(questionDiv);
    
    // Add default choices for multiple choice
    addChoice(questionCount);
    addChoice(questionCount);
}

// Remove question
function removeQuestion(questionId) {
    const questionDiv = document.getElementById(`question_${questionId}`);
    if (questionDiv) {
        questionDiv.remove();
    }
}

// Handle question type change
function handleQuestionTypeChange(questionId) {
    const select = document.querySelector(`select[name="questions[${questionId}][type]"]`);
    const choicesContainer = document.getElementById(`choices_${questionId}`);
    
    if (select.value === 'multiple_choice') {
        choicesContainer.style.display = 'block';
    } else {
        choicesContainer.style.display = 'none';
    }
}

// Add choice to question
function addChoice(questionId) {
    const choicesList = document.getElementById(`choicesList_${questionId}`);
    const choiceCount = choicesList.children.length;
    
    const choiceDiv = document.createElement('div');
    choiceDiv.className = 'choice-item';
    
    choiceDiv.innerHTML = `
        <div class="choice-input">
            <input type="radio" name="questions[${questionId}][correct_choice]" value="${choiceCount}">
            <input type="text" name="questions[${questionId}][choices][${choiceCount}][text]" placeholder="Choice ${choiceCount + 1}" required>
            <button type="button" class="btn btn-small btn-danger" onclick="removeChoice(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    choicesList.appendChild(choiceDiv);
}

// Remove choice
function removeChoice(button) {
    button.closest('.choice-item').remove();
}

// Create activity form submission
document.getElementById('createActivityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_activity');
    
    // Process questions
    const questions = [];
    const questionElements = document.querySelectorAll('.question-item');
    
    questionElements.forEach((questionElement, index) => {
        const questionData = {
            text: questionElement.querySelector('textarea').value,
            type: questionElement.querySelector('select').value,
            points: parseInt(questionElement.querySelector('input[type="number"]').value)
        };
        
        // Add choices for multiple choice questions
        if (questionData.type === 'multiple_choice') {
            const choices = [];
            const choiceInputs = questionElement.querySelectorAll('.choice-item input[type="text"]');
            const correctChoiceRadio = questionElement.querySelector('input[type="radio"]:checked');
            
            choiceInputs.forEach((input, choiceIndex) => {
                if (input.value.trim()) {
                    choices.push({
                        text: input.value.trim(),
                        is_correct: correctChoiceRadio && correctChoiceRadio.value == choiceIndex
                    });
                }
            });
            
            questionData.choices = choices;
        }
        
        questions.push(questionData);
    });
    
    formData.append('questions', JSON.stringify(questions));
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Activity created successfully!', 'success');
            closeCreateActivityModal();
            loadActivities();
            loadActivityStats();
        } else {
            showNotification('Error creating activity: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error creating activity', 'error');
    });
});

// View activity details
function viewActivityDetails(activityId) {
    fetch(`teacher-activities-backend.php?action=get_activity_details&activity_id=${activityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayActivityDetails(data.activity);
                document.getElementById('activityDetailsModal').style.display = 'block';
            } else {
                showNotification('Error loading activity details: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading activity details', 'error');
        });
}

// Display activity details
function displayActivityDetails(activity) {
    const content = document.getElementById('activityDetailsContent');
    
    let questionsHtml = '';
    if (activity.questions && activity.questions.length > 0) {
        questionsHtml = activity.questions.map((question, index) => {
            let choicesHtml = '';
            if (question.choices && question.choices.length > 0) {
                choicesHtml = question.choices.map(choice => 
                    `<li class="${choice.is_correct ? 'correct-choice' : ''}">${choice.choice_text}</li>`
                ).join('');
            }
            
            return `
                <div class="question-detail">
                    <h4>Question ${index + 1} (${question.points} points)</h4>
                    <p>${question.question_text}</p>
                    ${choicesHtml ? `<ul class="choices-list">${choicesHtml}</ul>` : ''}
                </div>
            `;
        }).join('');
    }
    
    content.innerHTML = `
        <div class="activity-details">
            <div class="detail-section">
                <h3>Activity Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Title:</label>
                        <span>${activity.title}</span>
                    </div>
                    <div class="detail-item">
                        <label>Type:</label>
                        <span class="activity-type ${activity.activity_type}">${activity.activity_type}</span>
                    </div>
                    <div class="detail-item">
                        <label>Total Points:</label>
                        <span>${activity.total_points}</span>
                    </div>
                    <div class="detail-item">
                        <label>Time Limit:</label>
                        <span>${activity.time_limit ? activity.time_limit + ' minutes' : 'No limit'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Due Date:</label>
                        <span>${formatDate(activity.due_date)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="status-badge ${activity.is_active ? 'active' : 'inactive'}">
                            ${activity.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                </div>
                <div class="detail-item full-width">
                    <label>Description:</label>
                    <p>${activity.description || 'No description provided'}</p>
                </div>
            </div>
            
            ${questionsHtml ? `
                <div class="detail-section">
                    <h3>Questions (${activity.questions.length})</h3>
                    ${questionsHtml}
                </div>
            ` : ''}
        </div>
    `;
}

// View submissions
function viewSubmissions(activityId) {
    currentActivityId = activityId;
    
    fetch(`teacher-activities-backend.php?action=get_activity_submissions&activity_id=${activityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySubmissions(data.submissions);
                document.getElementById('viewSubmissionsModal').style.display = 'block';
            } else {
                showNotification('Error loading submissions: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading submissions', 'error');
        });
}

// Display submissions
function displaySubmissions(submissions) {
    const content = document.getElementById('submissionsContent');
    
    if (submissions.length === 0) {
        content.innerHTML = `
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <p>No submissions found for this activity.</p>
            </div>
        `;
        return;
    }
    
    const submissionsHtml = submissions.map(submission => `
        <tr>
            <td>${submission.student_name}</td>
            <td>Grade ${submission.grade_level}</td>
            <td>
                <span class="status-badge ${submission.submission_status}">
                    ${submission.submission_status.replace('_', ' ')}
                </span>
            </td>
            <td>${submission.total_score !== null ? submission.total_score + '/' + submission.max_score : 'N/A'}</td>
            <td>${submission.percentage !== null ? Math.round(submission.percentage) + '%' : 'N/A'}</td>
            <td>${formatDate(submission.submitted_at)}</td>
            <td class="actions">
                ${submission.submission_status === 'submitted' ? `
                    <button class="btn btn-small btn-primary" onclick="gradeSubmission(${submission.submission_id})">
                        <i class="fas fa-edit"></i> Grade
                    </button>
                ` : ''}
                <button class="btn btn-small btn-info" onclick="viewSubmissionDetails(${submission.submission_id})">
                    <i class="fas fa-eye"></i> View
                </button>
            </td>
        </tr>
    `).join('');
    
    content.innerHTML = `
        <div class="submissions-table">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${submissionsHtml}
                </tbody>
            </table>
        </div>
    `;
}

// Toggle activity status
function toggleActivityStatus(activityId) {
    const formData = new FormData();
    formData.append('action', 'toggle_activity_status');
    formData.append('activity_id', activityId);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Activity status updated successfully!', 'success');
            loadActivities();
        } else {
            showNotification('Error updating activity status: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating activity status', 'error');
    });
}

// Delete activity
function deleteActivity(activityId) {
    if (!confirm('Are you sure you want to delete this activity? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_activity');
    formData.append('activity_id', activityId);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Activity deleted successfully!', 'success');
            loadActivities();
            loadActivityStats();
        } else {
            showNotification('Error deleting activity: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting activity', 'error');
    });
}

// Filter activities
function filterActivities() {
    const searchTerm = document.getElementById('searchActivities').value.toLowerCase();
    const typeFilter = document.getElementById('filterType').value;
    
    const tableBody = document.getElementById('activitiesTableBody');
    const rows = tableBody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const title = row.querySelector('.activity-title')?.textContent.toLowerCase() || '';
        const type = row.querySelector('.activity-type')?.textContent || '';
        
        const matchesSearch = title.includes(searchTerm);
        const matchesType = !typeFilter || type === typeFilter;
        
        row.style.display = matchesSearch && matchesType ? '' : 'none';
    });
}

// Refresh activities
function refreshActivities() {
    loadActivities();
    loadActivityStats();
    showNotification('Activities refreshed!', 'success');
}

// Close modals
function closeViewSubmissionsModal() {
    document.getElementById('viewSubmissionsModal').style.display = 'none';
}

function closeActivityDetailsModal() {
    document.getElementById('activityDetailsModal').style.display = 'none';
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'No due date';
    
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
