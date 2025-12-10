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
                populateSubjectSelect(); // This will silently skip if element doesn't exist
            } else {
                console.error('Error loading subjects:', data.message);
                // Only show notification if there's actually an error that affects functionality
                // Since Create Activity was removed, we don't show this error anymore
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Only log to console - games can still be created even if this fails
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
    // Check if element exists (it was removed when Create Activity feature was removed)
    if (!select) {
        console.log('Activity subject select not found - skipping (feature removed)');
        return;
    }
    
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
        
        // Check if this is a game activity
        const isGame = activity.is_game || activity.activity_type === 'game';
        const activityId = isGame ? activity.game_id : activity.activity_id;
        const status = isGame ? activity.status : (activity.is_active ? 'active' : 'inactive');
        const statusLabel = isGame ? activity.status.charAt(0).toUpperCase() + activity.status.slice(1) : (activity.is_active ? 'Active' : 'Inactive');
        const isMatchingGame = isGame && activity.game_type_flag === 'matching';
        
        // Different actions for games vs regular activities
        let actionsHTML = '';
        if (isGame) {
            if (isMatchingGame) {
                // Matching game actions
                actionsHTML = `
                    <button class="btn btn-small btn-info" onclick="window.location.href='games/add-matching-pairs.php?matching_game_id=${activityId}'" title="Manage Pairs">
                        <i class="fas fa-puzzle-piece"></i>
                    </button>
                    <button class="btn btn-small btn-secondary" onclick="window.location.href='games/matching-game-results.php?matching_game_id=${activityId}'" title="View Results">
                        <i class="fas fa-trophy"></i>
                    </button>
                    <button class="btn btn-small ${status === 'active' ? 'btn-warning' : 'btn-success'}" 
                            onclick="toggleMatchingGameStatus(${activityId}, '${status}')" 
                            title="${status === 'active' ? 'Deactivate' : 'Activate'}">
                        <i class="fas ${status === 'active' ? 'fa-pause' : 'fa-play'}"></i>
                    </button>
                    <button class="btn btn-small btn-danger" onclick="deleteMatchingGame(${activityId})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            } else {
                // Quiz game actions
                actionsHTML = `
                    <button class="btn btn-small btn-info" onclick="window.location.href='games/add-questions.php?game_id=${activityId}'" title="Manage Questions">
                        <i class="fas fa-question-circle"></i>
                    </button>
                    <button class="btn btn-small btn-secondary" onclick="window.location.href='games/game-results.php?game_id=${activityId}'" title="View Results">
                        <i class="fas fa-trophy"></i>
                    </button>
                    <button class="btn btn-small btn-export" onclick="exportGameResults(${activityId})" title="Export Results">
                        <i class="fas fa-file-export"></i>
                    </button>
                    <button class="btn btn-small ${status === 'active' ? 'btn-warning' : 'btn-success'}" 
                            onclick="toggleGameStatus(${activityId}, '${status}')" 
                            title="${status === 'active' ? 'Deactivate' : 'Activate'}">
                        <i class="fas ${status === 'active' ? 'fa-pause' : 'fa-play'}"></i>
                    </button>
                    <button class="btn btn-small btn-danger" onclick="deleteGame(${activityId})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            }
        } else {
            actionsHTML = `
                <button class="btn btn-small btn-info" onclick="viewActivityDetails(${activityId})" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-small btn-secondary" onclick="viewSubmissions(${activityId})" title="View Submissions">
                    <i class="fas fa-clipboard-list"></i>
                </button>
                <button class="btn btn-small btn-export" onclick="exportGrades(${activityId})" title="Export Grades">
                    <i class="fas fa-file-export"></i>
                </button>
                <button class="btn btn-small ${activity.is_active ? 'btn-warning' : 'btn-success'}" 
                        onclick="toggleActivityStatus(${activityId})" 
                        title="${activity.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="fas ${activity.is_active ? 'fa-pause' : 'fa-play'}"></i>
                </button>
                <button class="btn btn-small btn-danger" onclick="deleteActivity(${activityId})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        }
        
        // Add game type icon
        let gameIcon = '';
        if (isGame) {
            gameIcon = isMatchingGame ? 
                ' <i class="fas fa-puzzle-piece" style="color: #26890D;" title="Matching Game"></i>' :
                ' <i class="fas fa-gamepad" style="color: #ff6b6b;" title="Quiz Game"></i>';
        }
        
        row.innerHTML = `
            <td data-label="Title" class="activity-title">${activity.title}${gameIcon}</td>
            <td data-label="Subject">${activity.subject_name}</td>
            <td data-label="Type"><span class="activity-type ${activity.activity_type}">${activity.activity_type}</span></td>
            <td data-label="Points">${activity.total_points || 0}</td>
            <td data-label="Due Date">${formatDate(activity.due_date)}</td>
            <td data-label="Submissions" class="submissions-count">${activity.total_submissions || 0}</td>
            <td data-label="Avg Score" class="avg-score">${activity.avg_score ? Math.round(activity.avg_score) + '%' : 'N/A'}</td>
            <td data-label="Status">
                <span class="status-badge ${status}">
                    ${statusLabel}
                </span>
            </td>
            <td data-label="Actions" class="actions">
                ${actionsHTML}
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Load activity statistics
function loadActivityStats() {
    const totalActivities = activities.length;
    const totalSubmissions = activities.reduce((sum, activity) => sum + (activity.total_submissions || 0), 0);
    
    // Calculate average score properly - only include activities with valid scores
    let totalScore = 0;
    let activitiesWithScores = 0;
    
    activities.forEach(activity => {
        const score = parseFloat(activity.avg_score);
        if (!isNaN(score) && score !== null) {
            totalScore += score;
            activitiesWithScores++;
        }
    });
    
    const avgScore = activitiesWithScores > 0 ? (totalScore / activitiesWithScores) : 0;
    
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

// Game Activity Functions
function toggleGameStatus(gameId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'draft' : 'active';
    const confirmMsg = newStatus === 'active' ? 
        'Activate this game for students?' : 
        'Deactivate this game?';
    
    if (!confirm(confirmMsg)) return;
    
    // Call backend API to change game status
    const formData = new FormData();
    formData.append('action', 'toggle_game_status');
    formData.append('game_id', gameId);
    formData.append('new_status', newStatus);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Game status updated successfully!', 'success');
            loadActivities(); // Reload the activities list
        } else {
            showNotification(data.message || 'Failed to update game status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating game status', 'error');
    });
}

function deleteGame(gameId) {
    if (!confirm('Delete this game? This action cannot be undone and will delete all questions and student responses!')) {
        return;
    }
    
    // Call backend API to delete game
    const formData = new FormData();
    formData.append('action', 'delete_game');
    formData.append('game_id', gameId);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Game deleted successfully!', 'success');
            loadActivities(); // Reload the activities list
        } else {
            showNotification(data.message || 'Failed to delete game', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting game', 'error');
    });
}

function exportGameResults(gameId) {
    // Create a form to export game results as CSV
    fetch(`teacher-bulk-operations.php?action=export_game_results&game_id=${gameId}`)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `game_${gameId}_results.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
            showNotification('Game results exported successfully!', 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error exporting game results', 'error');
        });
}

// Matching Game Functions
function toggleMatchingGameStatus(gameId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'draft' : 'active';
    const confirmMsg = newStatus === 'active' ? 
        'Activate this matching game for students?' : 
        'Deactivate this matching game?';
    
    if (!confirm(confirmMsg)) return;
    
    const formData = new FormData();
    formData.append('action', 'toggle_matching_game_status');
    formData.append('matching_game_id', gameId);
    formData.append('new_status', newStatus);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Matching game status updated successfully!', 'success');
            loadActivities();
        } else {
            showNotification(data.message || 'Failed to update matching game status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating matching game status', 'error');
    });
}

function deleteMatchingGame(gameId) {
    if (!confirm('Delete this matching game? This action cannot be undone and will delete all pairs and student responses!')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_matching_game');
    formData.append('matching_game_id', gameId);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Matching game deleted successfully!', 'success');
            loadActivities();
        } else {
            showNotification(data.message || 'Failed to delete matching game', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting matching game', 'error');
    });
}

// Create Game Modal Functions
function showCreateGameModal() {
    document.getElementById('createGameModal').style.display = 'block';
    resetCreateGameForm();
    loadGameSubjects();
}

function closeCreateGameModal() {
    document.getElementById('createGameModal').style.display = 'none';
}

function resetCreateGameForm() {
    document.getElementById('createGameForm').reset();
    document.getElementById('gameFormError').style.display = 'none';
    document.getElementById('gameFormSuccess').style.display = 'none';
    document.getElementById('gameTimeLimit').value = 30;
    document.getElementById('showLeaderboard').checked = true;
}

function loadGameSubjects() {
    const select = document.getElementById('gameSubject');
    select.innerHTML = '<option value="">Select a subject</option>';
    
    // Use the subjects already loaded
    subjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject.subject_id;
        option.textContent = subject.subject_name;
        select.appendChild(option);
    });
}

// Update game type info dynamically
function updateGameTypeInfo(gameType) {
    const helpText = document.getElementById('game-type-help');
    const timeLimitLabel = document.getElementById('gameTimeLimitLabel');
    const timeLimitHelp = document.getElementById('gameTimeLimitHelp');
    const timeLimitInput = document.getElementById('gameTimeLimit');
    
    if (gameType === 'matching') {
        helpText.textContent = 'Matching games let students connect related items, images, or words';
        timeLimitLabel.innerHTML = '⏱️ Total Game Time (seconds) *';
        timeLimitHelp.textContent = 'Recommended: 180-600 seconds (3-10 minutes)';
        timeLimitInput.value = 300;
        timeLimitInput.max = 1800;
        timeLimitInput.min = 60;
    } else {
        helpText.textContent = 'Quiz games test knowledge with multiple-choice questions';
        timeLimitLabel.innerHTML = '⏱️ Time per Question (seconds) *';
        timeLimitHelp.textContent = 'Recommended: 20-60 seconds';
        timeLimitInput.value = 30;
        timeLimitInput.max = 300;
        timeLimitInput.min = 10;
    }
}

// Validate game title
function validateGameTitle(input) {
    const feedback = document.getElementById('titleFeedback');
    const value = input.value.trim();
    
    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        feedback.style.display = 'none';
        return false;
    }
    
    if (value.length < 5) {
        input.classList.add('invalid');
        input.classList.remove('valid');
        feedback.textContent = '❌ Title is too short (minimum 5 characters)';
        feedback.className = 'validation-feedback invalid';
        return false;
    }
    
    if (value.length > 100) {
        input.classList.add('invalid');
        input.classList.remove('valid');
        feedback.textContent = '❌ Title is too long (maximum 100 characters)';
        feedback.className = 'validation-feedback invalid';
        return false;
    }
    
    input.classList.add('valid');
    input.classList.remove('invalid');
    feedback.textContent = '✓ Great title!';
    feedback.className = 'validation-feedback valid';
    return true;
}

// Handle game creation form submission
document.getElementById('createGameForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const gameType = formData.get('game_type');
    const defaultPoints = formData.get('default_points') || 100;
    
    console.log('Creating game with form data:', Object.fromEntries(formData));
    
    // Hide previous messages
    document.getElementById('gameFormError').style.display = 'none';
    document.getElementById('gameFormSuccess').style.display = 'none';
    
    // If it's a matching game, show matching game modal
    if (gameType === 'matching') {
        const title = formData.get('title');
        const description = formData.get('description');
        const subjectId = formData.get('subject_id');
        const timeLimit = formData.get('time_limit');
        const showLeaderboard = formData.get('show_leaderboard') ? 1 : 0;
        const pointsPerPair = defaultPoints;
        const dueDate = formData.get('due_date');
        
        // Get subject name
        const subjectSelect = document.getElementById('gameSubject');
        const subjectName = subjectSelect.options[subjectSelect.selectedIndex].text;
        
        // Close create game modal and open matching game modal
        closeCreateGameModal();
        openCreateMatchingGameModal(subjectId, subjectName, title, description, timeLimit, showLeaderboard, pointsPerPair);
        return;
    }
    
    // Otherwise, create a quiz game via AJAX
    formData.append('action', 'create_game');
    
    console.log('Sending quiz game creation request...');
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showNotification('Game created successfully!', 'success');
            closeCreateGameModal();
            // Open add questions modal with default points
            openAddQuestionsModal(data.game_id, data.game_title, data.subject_name, data.time_limit, defaultPoints);
        } else {
            const errorDiv = document.getElementById('gameFormError');
            errorDiv.textContent = data.message || 'Failed to create game';
            errorDiv.style.display = 'block';
            console.error('Game creation failed:', data.message);
        }
    })
    .catch(error => {
        console.error('Error creating game:', error);
        const errorDiv = document.getElementById('gameFormError');
        errorDiv.textContent = 'An error occurred while creating the game: ' + error.message;
        errorDiv.style.display = 'block';
    });
});

// Add Questions Modal Functions
let currentGameData = {};

function openAddQuestionsModal(gameId, gameTitle, subjectName, timeLimit, defaultPoints = 100) {
    currentGameData = {
        game_id: gameId,
        title: gameTitle,
        subject_name: subjectName,
        time_limit: timeLimit,
        default_points: defaultPoints
    };
    
    document.getElementById('gameTitle').textContent = gameTitle;
    document.getElementById('gameSubjectName').textContent = subjectName;
    document.getElementById('currentGameId').value = gameId;
    document.getElementById('questionTimeLimit').value = timeLimit;
    document.getElementById('questionPoints').value = defaultPoints; // Set default points
    
    // Reset form
    resetAddQuestionForm();
    
    // Load existing questions
    loadGameQuestions(gameId);
    
    document.getElementById('addQuestionsModal').style.display = 'block';
}

function closeAddQuestionsModal() {
    document.getElementById('addQuestionsModal').style.display = 'none';
    // Reload activities to show the new game
    loadActivities();
}

function resetAddQuestionForm() {
    document.getElementById('addQuestionForm').reset();
    document.getElementById('questionFormError').style.display = 'none';
    document.getElementById('questionFormSuccess').style.display = 'none';
    // Reset time limit to game default
    if (currentGameData.time_limit) {
        document.getElementById('questionTimeLimit').value = currentGameData.time_limit;
    }
}

function loadGameQuestions(gameId) {
    fetch(`teacher-activities-backend.php?action=get_game_questions&game_id=${gameId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayGameQuestions(data.questions);
                updateQuestionCount(data.questions.length);
            } else {
                console.error('Failed to load questions:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading questions:', error);
        });
}

function displayGameQuestions(questions) {
    const questionsList = document.getElementById('questionsList');
    const questionsSection = document.getElementById('questionsListSection');
    
    if (questions.length === 0) {
        questionsSection.style.display = 'none';
        return;
    }
    
    questionsSection.style.display = 'block';
    
    const questionsHtml = questions.map((question, index) => `
        <div class="question-item-display">
            <div class="question-header-display">
                <div class="question-text-display">
                    ${index + 1}. ${escapeHtml(question.question_text)}
                </div>
                <div class="question-actions-display">
                    <button class="btn-delete-question" onclick="deleteQuestion(${question.question_id})" title="Delete question">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
            <div class="question-meta">
                ⏱️ ${question.time_limit}s | 🎯 ${question.points} points | 📝 ${question.option_count} options
            </div>
        </div>
    `).join('');
    
    questionsList.innerHTML = questionsHtml;
}

function updateQuestionCount(count) {
    document.getElementById('questionCount').textContent = count;
    document.getElementById('questionsListCount').textContent = count;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle add question form submission
document.getElementById('addQuestionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_question');
    
    // Hide previous messages
    document.getElementById('questionFormError').style.display = 'none';
    document.getElementById('questionFormSuccess').style.display = 'none';
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Question added successfully!', 'success');
            resetAddQuestionForm();
            // Reload questions list
            loadGameQuestions(currentGameData.game_id);
        } else {
            const errorDiv = document.getElementById('questionFormError');
            errorDiv.textContent = data.message || 'Failed to add question';
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorDiv = document.getElementById('questionFormError');
        errorDiv.textContent = 'An error occurred while adding the question';
        errorDiv.style.display = 'block';
    });
});

function deleteQuestion(questionId) {
    if (!confirm('Delete this question? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_question');
    formData.append('question_id', questionId);
    formData.append('game_id', currentGameData.game_id);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Question deleted successfully!', 'success');
            loadGameQuestions(currentGameData.game_id);
        } else {
            showNotification(data.message || 'Failed to delete question', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting question', 'error');
    });
}

function finishAddingQuestions() {
    const questionCount = parseInt(document.getElementById('questionCount').textContent);
    
    if (questionCount === 0) {
        if (!confirm('You haven\'t added any questions yet. Are you sure you want to finish?')) {
            return;
        }
    }
    
    // Ask teacher if they want to activate the game for students
    const activateGame = confirm(`Game "${currentGameData.title}" created with ${questionCount} question(s)!\n\nDo you want to activate this game now so students can play it?`);
    
    if (activateGame && currentGameData.game_id) {
        // Activate the game
        const formData = new FormData();
        formData.append('action', 'toggle_game_status');
        formData.append('game_id', currentGameData.game_id);
        formData.append('new_status', 'active');
        
        fetch('teacher-activities-backend.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeAddQuestionsModal();
                showNotification(`Game "${currentGameData.title}" is now active and available to students!`, 'success');
            } else {
                closeAddQuestionsModal();
                showNotification(`Game created, but failed to activate: ${data.message}`, 'warning');
            }
        })
        .catch(error => {
            console.error('Error activating game:', error);
            closeAddQuestionsModal();
            showNotification(`Game created, but failed to activate. You can activate it manually from the activities list.`, 'warning');
        });
    } else {
        closeAddQuestionsModal();
        showNotification(`Game "${currentGameData.title}" created! Don't forget to activate it when you're ready for students to play.`, 'info');
    }
}

// ========================================
// Matching Game Modal Functions
// ========================================

let currentMatchingGameData = {};

// Open create matching game modal with pre-filled data
function openCreateMatchingGameModal(subjectId, subjectName, title, description, timeLimit, showLeaderboard, pointsPerPair = 100) {
    document.getElementById('matchingGameSubjectId').value = subjectId;
    document.getElementById('matchingGameSubjectName').textContent = subjectName;
    document.getElementById('matchingGameTitle').value = title || '';
    document.getElementById('matchingGameDescription').value = description || '';
    document.getElementById('matchingGameTimeLimit').value = timeLimit || 300;
    document.getElementById('matchingGameShowLeaderboard').value = showLeaderboard || 1;
    document.getElementById('matchingGamePointsPerPair').value = pointsPerPair;
    document.getElementById('matchingGameType').value = '';
    document.getElementById('matching-game-type-preview').style.display = 'none';
    
    // Clear any previous messages
    document.getElementById('matchingGameFormError').style.display = 'none';
    document.getElementById('matchingGameFormSuccess').style.display = 'none';
    
    document.getElementById('createMatchingGameModal').style.display = 'block';
}

// Close matching game modal
function closeCreateMatchingGameModal() {
    document.getElementById('createMatchingGameModal').style.display = 'none';
    document.getElementById('createMatchingGameForm').reset();
}

// Update matching game type preview
function updateMatchingGameTypePreview(gameType) {
    const previewDiv = document.getElementById('matching-game-type-preview');
    
    if (!gameType) {
        previewDiv.style.display = 'none';
        return;
    }
    
    const previews = {
        'image-to-text': {
            title: '🖼️ Image to Text Matching',
            description: 'Students will match images on the left with corresponding text/words on the right.',
            example: 'Example: Match animal pictures with their names, or flag images with country names.'
        },
        'text-to-text': {
            title: '📝 Text to Text Matching',
            description: 'Students will match related words, definitions, or concepts.',
            example: 'Example: Match vocabulary words with definitions, or capital cities with countries.'
        },
        'image-to-image': {
            title: '🎨 Image to Image Matching',
            description: 'Students will match related images together.',
            example: 'Example: Match baby animal pictures with adult animals, or tools with their uses.'
        },
        'number-to-text': {
            title: '🔢 Number to Text Matching',
            description: 'Students will match numbers with their written forms or related concepts.',
            example: 'Example: Match "5" with "five", or math problems with their answers.'
        }
    };
    
    const preview = previews[gameType];
    if (preview) {
        previewDiv.innerHTML = `
            <div class="game-type-preview">
                <h3>${preview.title}</h3>
                <p>${preview.description}</p>
                <div class="type-example">
                    <strong>📌 ${preview.example}</strong>
                </div>
            </div>
        `;
        previewDiv.style.display = 'block';
    }
}

// Handle matching game creation form submission
document.getElementById('createMatchingGameForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_matching_game');
    formData.append('subject_id', document.getElementById('matchingGameSubjectId').value);
    formData.append('show_leaderboard', document.getElementById('matchingGameShowLeaderboard').value);
    
    // Hide previous messages
    document.getElementById('matchingGameFormError').style.display = 'none';
    document.getElementById('matchingGameFormSuccess').style.display = 'none';
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Matching game created successfully!', 'success');
            closeCreateMatchingGameModal();
            // Open add pairs modal
            openAddMatchingPairsModal(
                data.matching_game_id,
                data.game_title,
                data.subject_name,
                data.game_type
            );
        } else {
            const errorDiv = document.getElementById('matchingGameFormError');
            errorDiv.textContent = data.message || 'Failed to create matching game';
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorDiv = document.getElementById('matchingGameFormError');
        errorDiv.textContent = 'An error occurred while creating the matching game';
        errorDiv.style.display = 'block';
    });
});

// Open add matching pairs modal
function openAddMatchingPairsModal(matchingGameId, gameTitle, subjectName, gameType) {
    currentMatchingGameData = {
        matching_game_id: matchingGameId,
        title: gameTitle,
        subject_name: subjectName,
        game_type: gameType
    };
    
    document.getElementById('matchingGameTitleDisplay').textContent = gameTitle;
    document.getElementById('matchingGameSubjectDisplay').textContent = subjectName;
    document.getElementById('matchingGameTypeDisplay').textContent = formatGameType(gameType);
    document.getElementById('currentMatchingGameId').value = matchingGameId;
    document.getElementById('currentGameType').value = gameType;
    document.getElementById('pairCount').textContent = '0';
    
    // Configure form based on game type
    configureAddPairForm(gameType);
    
    // Clear form
    document.getElementById('addPairForm').reset();
    document.getElementById('currentMatchingGameId').value = matchingGameId;
    document.getElementById('currentGameType').value = gameType;
    
    // Clear messages
    document.getElementById('pairFormError').style.display = 'none';
    document.getElementById('pairFormSuccess').style.display = 'none';
    
    // Load existing pairs
    loadMatchingPairs(matchingGameId);
    
    document.getElementById('addMatchingPairsModal').style.display = 'block';
}

// Close add matching pairs modal
function closeAddMatchingPairsModal() {
    document.getElementById('addMatchingPairsModal').style.display = 'none';
    document.getElementById('addPairForm').reset();
}

// Format game type for display
function formatGameType(gameType) {
    const types = {
        'image-to-text': '🖼️ Image to Text',
        'text-to-text': '📝 Text to Text',
        'image-to-image': '🎨 Image to Image',
        'number-to-text': '🔢 Number to Text'
    };
    return types[gameType] || gameType;
}

// Configure add pair form based on game type
function configureAddPairForm(gameType) {
    const leftItemText = document.getElementById('leftItemText');
    const leftItemImage = document.getElementById('leftItemImage');
    const rightItemText = document.getElementById('rightItemText');
    const rightItemImage = document.getElementById('rightItemImage');
    const leftItemLabel = document.getElementById('leftItemLabel');
    const rightItemLabel = document.getElementById('rightItemLabel');
    const leftItemHelp = document.getElementById('leftItemHelp');
    const rightItemHelp = document.getElementById('rightItemHelp');
    
    // Reset all
    leftItemText.style.display = 'none';
    leftItemImage.style.display = 'none';
    rightItemText.style.display = 'none';
    rightItemImage.style.display = 'none';
    leftItemText.required = false;
    leftItemImage.required = false;
    rightItemText.required = false;
    rightItemImage.required = false;
    
    if (gameType === 'image-to-text') {
        leftItemLabel.textContent = 'Left Image *';
        rightItemLabel.textContent = 'Right Text *';
        leftItemImage.style.display = 'block';
        rightItemText.style.display = 'block';
        leftItemImage.required = true;
        rightItemText.required = true;
        leftItemHelp.textContent = 'Upload an image for the left side';
        rightItemHelp.textContent = 'Enter text that matches the image';
    } else if (gameType === 'text-to-text') {
        leftItemLabel.textContent = 'Left Text *';
        rightItemLabel.textContent = 'Right Text *';
        leftItemText.style.display = 'block';
        rightItemText.style.display = 'block';
        leftItemText.required = true;
        rightItemText.required = true;
        leftItemHelp.textContent = 'Enter text for the left side';
        rightItemHelp.textContent = 'Enter text that matches';
    } else if (gameType === 'image-to-image') {
        leftItemLabel.textContent = 'Left Image *';
        rightItemLabel.textContent = 'Right Image *';
        leftItemImage.style.display = 'block';
        rightItemImage.style.display = 'block';
        leftItemImage.required = true;
        rightItemImage.required = true;
        leftItemHelp.textContent = 'Upload an image for the left side';
        rightItemHelp.textContent = 'Upload a matching image';
    } else if (gameType === 'number-to-text') {
        leftItemLabel.textContent = 'Number *';
        rightItemLabel.textContent = 'Text *';
        leftItemText.style.display = 'block';
        rightItemText.style.display = 'block';
        leftItemText.required = true;
        rightItemText.required = true;
        leftItemHelp.textContent = 'Enter a number';
        rightItemHelp.textContent = 'Enter the matching text';
    }
    
    // Add preview listeners for images
    leftItemImage.onchange = function() {
        previewImage(this, 'leftPreviewImg', 'leftImagePreview');
    };
    rightItemImage.onchange = function() {
        previewImage(this, 'rightPreviewImg', 'rightImagePreview');
    };
}

// Preview uploaded image
function previewImage(input, previewId, containerId) {
    const container = document.getElementById(containerId);
    const preview = document.getElementById(previewId);
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
            imagePreviewContainer.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Handle add pair form submission
document.getElementById('addPairForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_matching_pair');
    
    // Hide previous messages
    document.getElementById('pairFormError').style.display = 'none';
    document.getElementById('pairFormSuccess').style.display = 'none';
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const successDiv = document.getElementById('pairFormSuccess');
            successDiv.textContent = 'Pair added successfully!';
            successDiv.style.display = 'block';
            
            // Reset form but keep game ID and type
            const matchingGameId = document.getElementById('currentMatchingGameId').value;
            const gameType = document.getElementById('currentGameType').value;
            document.getElementById('addPairForm').reset();
            document.getElementById('currentMatchingGameId').value = matchingGameId;
            document.getElementById('currentGameType').value = gameType;
            
            // Hide image previews
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('leftImagePreview').style.display = 'none';
            document.getElementById('rightImagePreview').style.display = 'none';
            
            // Reload pairs list
            loadMatchingPairs(matchingGameId);
            
            // Hide success message after 3 seconds
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 3000);
        } else {
            const errorDiv = document.getElementById('pairFormError');
            errorDiv.textContent = data.message || 'Failed to add pair';
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorDiv = document.getElementById('pairFormError');
        errorDiv.textContent = 'An error occurred while adding the pair';
        errorDiv.style.display = 'block';
    });
});

// Load matching pairs
function loadMatchingPairs(matchingGameId) {
    fetch(`teacher-activities-backend.php?action=get_matching_pairs&matching_game_id=${matchingGameId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMatchingPairs(data.pairs);
                document.getElementById('pairCount').textContent = data.pairs.length;
                document.getElementById('pairsListCount').textContent = data.pairs.length;
                
                if (data.pairs.length > 0) {
                    document.getElementById('pairsListSection').style.display = 'block';
                } else {
                    document.getElementById('pairsListSection').style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error loading pairs:', error);
        });
}

// Display matching pairs
function displayMatchingPairs(pairs) {
    const pairsList = document.getElementById('pairsList');
    
    if (pairs.length === 0) {
        pairsList.innerHTML = '<p style="text-align: center; color: #888;">No pairs added yet</p>';
        return;
    }
    
    pairsList.innerHTML = pairs.map((pair, index) => `
        <div class="pair-item">
            <div class="pair-content">
                <div class="pair-left">
                    ${pair.left_item_image ? `<img src="../${pair.left_item_image}" class="pair-image" alt="Left">` : ''}
                    <span class="pair-text">${pair.left_item_text || ''}</span>
                </div>
                <div class="pair-connector">⟷</div>
                <div class="pair-right">
                    ${pair.right_item_image ? `<img src="../${pair.right_item_image}" class="pair-image" alt="Right">` : ''}
                    <span class="pair-text">${pair.right_item_text || ''}</span>
                </div>
            </div>
            <div class="pair-actions">
                <button class="btn-delete-pair" onclick="deleteMatchingPair(${pair.pair_id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Delete matching pair
function deleteMatchingPair(pairId) {
    if (!confirm('Are you sure you want to delete this pair?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_matching_pair');
    formData.append('pair_id', pairId);
    
    fetch('teacher-activities-backend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Pair deleted successfully', 'success');
            loadMatchingPairs(currentMatchingGameData.matching_game_id);
        } else {
            showNotification('Failed to delete pair: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while deleting the pair', 'error');
    });
}

// Finish adding pairs
function finishAddingPairs() {
    const pairCount = parseInt(document.getElementById('pairCount').textContent);
    
    if (pairCount < 3) {
        alert('Please add at least 3 pairs before finishing. Matching games need a minimum of 3 pairs to be playable.');
        return;
    }
    
    if (confirm('Are you done adding pairs? The game will be set to active and students can play it.')) {
        // Activate the matching game
        const formData = new FormData();
        formData.append('action', 'activate_matching_game');
        formData.append('matching_game_id', currentMatchingGameData.matching_game_id);
        
        fetch('teacher-activities-backend.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeAddMatchingPairsModal();
                showNotification(`Matching game "${currentMatchingGameData.title}" created and activated!`, 'success');
                refreshActivities();
            } else {
                closeAddMatchingPairsModal();
                showNotification(`Game created, but failed to activate: ${data.message}`, 'warning');
            }
        })
        .catch(error => {
            console.error('Error activating game:', error);
            closeAddMatchingPairsModal();
            showNotification(`Game created, but failed to activate. You can activate it manually from the games list.`, 'warning');
        });
    } else {
        closeAddMatchingPairsModal();
        showNotification(`Matching game "${currentMatchingGameData.title}" created! Don't forget to activate it when you're ready for students to play.`, 'info');
    }
}
