// Format date helper function
function formatDate(dateString) {
    if (!dateString) return 'No due date';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Global variables
let activitiesData = null;
let currentFilters = {
    status: 'all',
    subject: 'all',
    type: 'all'
};
let activityTimer = null;
let timeRemaining = 0;

// Initialize page when loaded
document.addEventListener('DOMContentLoaded', function() {
    loadUserProfile();
    loadActivities();
    setupEventListeners();
    checkUrlParameters();
});

// Setup event listeners
function setupEventListeners() {
    // Filter change listeners
    document.getElementById('statusFilter').addEventListener('change', function() {
        currentFilters.status = this.value;
        loadActivities();
    });
    
    document.getElementById('subjectFilter').addEventListener('change', function() {
        currentFilters.subject = this.value;
        loadActivities();
    });
    
    document.getElementById('typeFilter').addEventListener('change', function() {
        currentFilters.type = this.value;
        loadActivities();
    });
    
    // Refresh button
    document.getElementById('refreshBtn').addEventListener('click', function() {
        loadActivities();
    });
    
    // Modal close handlers
    window.addEventListener('click', function(event) {
        const activityModal = document.getElementById('activityModal');
        const takeActivityModal = document.getElementById('takeActivityModal');
        
        if (event.target === activityModal) {
            closeActivityModal();
        }
        if (event.target === takeActivityModal) {
            closeTakeActivityModal();
        }
    });
}

// Check URL parameters for direct actions
function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewActivity = urlParams.get('view');
    const takeActivity = urlParams.get('take');
    
    if (viewActivity) {
        setTimeout(() => showActivityDetails(viewActivity), 1000);
    } else if (takeActivity) {
        setTimeout(() => {
            console.log('Attempting to start activity:', takeActivity);
            startActivity(takeActivity);
        }, 1000);
    }
}

// Load user profile information
async function loadUserProfile() {
    try {
        const response = await fetch('../api/student_dashboard.php');
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        const profileName = document.getElementById('profileName');
        if (profileName && data.profile) {
            profileName.textContent = `${data.profile.first_name} ${data.profile.last_name}`;
        }
        
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

// Function to start an activity
function startActivity(activityId) {
    // Open activity in a new window with specific features
    const activityWindow = window.open(
        `take-activity.php?id=${activityId}`,
        '_blank',
        'width=' + screen.width + 
        ',height=' + screen.height + 
        ',fullscreen=yes,channelmode=yes,menubar=no,toolbar=no,location=no,status=no,scrollbars=yes'
    );

    if (activityWindow) {
        activityWindow.moveTo(0, 0); // Move to top-left corner
        activityWindow.focus();
    } else {
        alert('Please enable pop-ups to take the activity. They are required for secure activity taking.');
    }
}

// Load activities data
async function loadActivities() {
    try {
        showLoadingState();
        
        const params = new URLSearchParams(currentFilters);
        const url = `../api/student_activities.php?action=list&${params}`;
        console.log('Loading activities from:', url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        console.log('Parsed data:', data);
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        activitiesData = data;
        updateUI(data);
        hideLoadingState();
        
    } catch (error) {
        console.error('Error loading activities:', error);
        showErrorState('Failed to load activities: ' + error.message + '. Please refresh the page.');
    }
}

// Update UI with activities data
function updateUI(data) {
    updateStatistics(data.counts);
    updateSubjectFilter(data.subjects);
    updateActivitiesGrid(data.activities);
}

// Update statistics cards
function updateStatistics(counts) {
    document.getElementById('pendingCount').textContent = counts.pending || 0;
    document.getElementById('completedCount').textContent = counts.completed || 0;
    document.getElementById('overdueCount').textContent = counts.overdue || 0;
    document.getElementById('totalCount').textContent = counts.total || 0;
}

// Update subject filter options
function updateSubjectFilter(subjects) {
    const subjectFilter = document.getElementById('subjectFilter');
    const currentValue = subjectFilter.value;
    
    // Clear existing options except "All Subjects"
    while (subjectFilter.children.length > 1) {
        subjectFilter.removeChild(subjectFilter.lastChild);
    }
    
    // Add subject options
    subjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject.subject_id;
        option.textContent = subject.subject_name;
        subjectFilter.appendChild(option);
    });
    
    // Restore selected value if it still exists
    subjectFilter.value = currentValue;
}

// Update activities grid
function updateActivitiesGrid(activities) {
    const activitiesGrid = document.getElementById('activitiesGrid');
    const noActivities = document.getElementById('noActivities');
    
    if (activities.length === 0) {
        activitiesGrid.style.display = 'none';
        noActivities.style.display = 'block';
        return;
    }
    
    activitiesGrid.style.display = 'grid';
    noActivities.style.display = 'none';
    
    activitiesGrid.innerHTML = '';
    
    activities.forEach(activity => {
        const card = createActivityCard(activity);
        activitiesGrid.appendChild(card);
    });
}

// Create activity card element
function createActivityCard(activity) {
    const card = document.createElement('div');
    card.className = `activity-card ${getStatusClass(activity.status)}`;
    card.setAttribute('data-activity-id', activity.activity_id);
    
    const isGameActivity = ['crossword', 'flashcards', 'speed_typing'].includes(activity.activity_type);
    const activityTypeDisplay = isGameActivity ? 
        activity.activity_type.replace('_', ' ').toUpperCase() :
        activity.activity_type.toUpperCase();
    
    card.innerHTML = `
        <div class="activity-type">${activityTypeDisplay}</div>
        <div class="activity-title">${activity.title}</div>
        <div class="activity-details">
            <div>Subject: ${activity.subject_name}</div>
            <div>Points: ${activity.total_points}</div>
            <div>Due: ${formatDate(activity.due_date)}</div>
        </div>
        <div class="activity-actions">
            <button onclick="showActivityDetails(${activity.activity_id})" class="btn btn-secondary">
                <i class="fas fa-info-circle"></i> Details
            </button>
            ${activity.status !== 'completed' ? 
                `<button onclick="startActivity(${activity.activity_id})" class="btn btn-primary">
                    <i class="fas fa-play"></i> Take
                </button>` : 
                `<button class="btn btn-success" disabled>
                    <i class="fas fa-check"></i> Completed
                </button>`
            }
        </div>
    `;
    
    return card;
    const isOverdue = dueDate && dueDate < new Date();
    const dueDateText = dueDate ? dueDate.toLocaleDateString() : 'No deadline';
    
    let deadlineClass = 'deadline-normal';
    if (isOverdue) {
        deadlineClass = 'deadline-overdue';
    } else if (dueDate && activity.hours_until_due <= 24) {
        deadlineClass = 'deadline-soon';
    }
    
    const statusText = getStatusText(activity.submission_status);
    
    card.innerHTML = `
        <div class="activity-header">
            <div class="activity-type ${activity.activity_type}">${activity.activity_type}</div>
            <div class="activity-status ${getStatusClass(activity.submission_status)}">${statusText}</div>
        </div>
        
        <div class="activity-subject">${activity.subject_name}</div>
        <div class="activity-title">${activity.title}</div>
        <div class="activity-teacher">Teacher: ${activity.teacher_name}</div>
        
        <div class="activity-details">
            <div class="activity-points">${activity.total_points} points</div>
            ${activity.time_limit ? `<div class="activity-time">${activity.time_limit} min</div>` : ''}
        </div>
        
        <div class="activity-deadline ${deadlineClass}">
            ${isOverdue ? 'Overdue: ' : 'Due: '}${dueDateText}
        </div>
        
        ${activity.total_score !== null ? `
            <div class="score-display">
                <div class="score">${activity.total_score}/${activity.max_score}</div>
                <div class="percentage">${Math.round(activity.percentage)}%</div>
            </div>
        ` : ''}
        
        <div class="activity-actions">
            <button class="btn btn-secondary btn-small" onclick="showActivityDetails('${activity.activity_id}')">
                <i class="fas fa-info-circle"></i> Details
            </button>
            ${canTakeActivity(activity) ? `
                <button class="btn btn-primary btn-small" onclick="startActivity('${activity.activity_id}')">
                    <i class="fas fa-play"></i> ${activity.submission_status === 'in_progress' ? 'Continue' : 'Take'}
                </button>
            ` : ''}
        </div>
    `;
    
    return card;
}

// Check if activity can be taken
function canTakeActivity(activity) {
    if (activity.submission_status === 'submitted' || activity.submission_status === 'graded') {
        return false;
    }
    
    if (activity.deadline_status === 'overdue') {
        return false;
    }
    
    return true;
}

// Show activity details modal
async function showActivityDetails(activityId) {
    try {
        const modal = document.getElementById('activityModal');
        const modalTitle = document.getElementById('modalActivityTitle');
        const modalContent = document.getElementById('modalActivityContent');
        
        modalTitle.textContent = 'Loading...';
        modalContent.innerHTML = '<div class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> Loading activity details...</div>';
        modal.style.display = 'block';
        
        const response = await fetch(`../api/student_activities.php?action=details&activity_id=${activityId}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        modalTitle.textContent = data.activity.title;
        
        // Check if it's a game activity
        const isGameActivity = ['crossword', 'flashcards', 'speed_typing'].includes(data.activity.activity_type);
        if (isGameActivity) {
            modalContent.innerHTML = createGameActivityDetailsContent(data);
        } else {
            modalContent.innerHTML = createActivityDetailsContent(data);
        }
        
    } catch (error) {
        console.error('Error loading activity details:', error);
        document.getElementById('modalActivityContent').innerHTML = `
            <div style="text-align: center; padding: 20px; color: #dc3545;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Failed to load activity details.</p>
            </div>
        `;
    }
}

// Create activity details content
function createGameActivityDetailsContent(data) {
    const activity = data.activity;
    const gameContent = activity.content_data ? JSON.parse(activity.content_data) : null;
    const gameSettings = activity.settings ? JSON.parse(activity.settings) : null;
    
    let gameSpecificContent = '';
    switch (activity.activity_type) {
        case 'flashcards':
            const cards = gameContent?.cards || [];
            gameSpecificContent = `
                <div class="game-content">
                    <h5>Flashcards</h5>
                    <p>Number of Cards: ${cards.length}</p>
                    ${cards.length > 0 ? `
                        <div class="flashcard-preview">
                            <h6>Preview:</h6>
                            <div class="card-list">
                                ${cards.slice(0, 3).map((card, index) => `
                                    <div class="preview-card">
                                        <strong>Card ${index + 1}:</strong>
                                        <p>Question: ${card.question}</p>
                                    </div>
                                `).join('')}
                                ${cards.length > 3 ? '<div class="more-cards">...and ' + (cards.length - 3) + ' more cards</div>' : ''}
                            </div>
                        </div>
                    ` : '<p>No cards available yet.</p>'}
                </div>
            `;
            break;
            
        case 'crossword':
            const words = gameContent?.words || [];
            gameSpecificContent = `
                <div class="game-content">
                    <h5>Crossword Puzzle</h5>
                    <p>Number of Words: ${words.length}</p>
                    ${words.length > 0 ? `
                        <div class="crossword-preview">
                            <h6>Grid Size: ${gameContent.gridSize || 'Not set'}</h6>
                            <p>Contains ${words.length} words to find</p>
                        </div>
                    ` : '<p>No words available yet.</p>'}
                </div>
            `;
            break;
            
        case 'speed_typing':
            gameSpecificContent = `
                <div class="game-content">
                    <h5>Speed Typing Test</h5>
                    ${gameContent?.text ? `
                        <div class="typing-preview">
                            <h6>Text Preview:</h6>
                            <p>${gameContent.text.slice(0, 100)}...</p>
                            <p>Total length: ${gameContent.text.length} characters</p>
                        </div>
                    ` : '<p>No text available yet.</p>'}
                </div>
            `;
            break;
    }
    
    return `
        <div class="modal-body">
            <div class="activity-header">
                <h4>${activity.subject_name}</h4>
                <p><strong>Teacher:</strong> ${activity.teacher_name}</p>
                <p><strong>Type:</strong> ${activity.activity_type.replace('_', ' ').toUpperCase()}</p>
                <p><strong>Points:</strong> ${activity.total_points}</p>
                <p><strong>Due Date:</strong> ${activity.due_date ? new Date(activity.due_date).toLocaleString() : 'No deadline'}</p>
                <p><strong>Time Limit:</strong> ${activity.time_limit || 'No'} minutes</p>
                ${activity.description ? `
                    <div class="description">
                        <h5>Description</h5>
                        <p>${activity.description}</p>
                    </div>
                ` : ''}
            </div>
            
            ${gameSpecificContent}
            
            <div class="activity-actions" style="margin-top: 20px;">
                ${activity.submission_status !== 'completed' ? `
                    <button onclick="startActivity(${activity.activity_id})" class="btn btn-primary">
                        <i class="fas fa-play"></i> Start Activity
                    </button>
                ` : `
                    <div class="completion-info">
                        <i class="fas fa-check-circle"></i>
                        <span>Completed on ${new Date(activity.completed_at).toLocaleString()}</span>
                        <div class="score">Score: ${activity.score || 0}%</div>
                    </div>
                `}
            </div>
        </div>
    `;
}

function createActivityDetailsContent(data) {
    const activity = data.activity;
    const questions = data.questions;
    const answers = data.answers;
    
    const dueDate = activity.due_date ? new Date(activity.due_date).toLocaleString() : 'No deadline';
    const statusText = getStatusText(activity.submission_status);
    
    let content = `
        <div class="modal-body">
            <div style="margin-bottom: 20px;">
                <h4>${activity.subject_name}</h4>
                <p><strong>Teacher:</strong> ${activity.teacher_name}</p>
                <p><strong>Type:</strong> ${activity.activity_type}</p>
                <p><strong>Points:</strong> ${activity.total_points}</p>
                <p><strong>Due Date:</strong> ${dueDate}</p>
                <p><strong>Status:</strong> <span class="activity-status ${getStatusClass(activity.submission_status)}">${statusText}</span></p>
                ${activity.time_limit ? `<p><strong>Time Limit:</strong> ${activity.time_limit} minutes</p>` : ''}
            </div>
            
            ${activity.description ? `
                <div style="margin-bottom: 20px;">
                    <h5>Description</h5>
                    <p>${activity.description}</p>
                </div>
            ` : ''}
            
            ${activity.total_score !== null ? `
                <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                    <h5>Your Score</h5>
                    <div style="font-size: 18px; font-weight: bold; color: #28a745;">
                        ${activity.total_score}/${activity.max_score} (${Math.round(activity.percentage)}%)
                    </div>
                    ${activity.submitted_at ? `<p style="font-size: 14px; color: #666;">Submitted: ${new Date(activity.submitted_at).toLocaleString()}</p>` : ''}
                </div>
            ` : ''}
            
            ${questions.length > 0 ? `
                <div style="margin-bottom: 20px;">
                    <h5>Questions (${questions.length})</h5>
                    ${questions.map((question, index) => `
                        <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #eee; border-radius: 6px;">
                            <div style="font-weight: 600; margin-bottom: 8px;">
                                ${index + 1}. ${question.question_text} (${question.points} points)
                            </div>
                            ${question.question_type === 'multiple_choice' && question.choices ? 
                                question.choices.map(choice => `
                                    <div style="margin-left: 20px; margin-bottom: 4px; font-size: 14px;">
                                        ${choice.choice_text}
                                    </div>
                                `).join('') : ''
                            }
                            ${answers[question.question_id] ? `
                                <div style="margin-top: 8px; padding: 8px; background: #e8f5e8; border-radius: 4px; font-size: 14px;">
                                    <strong>Your Answer:</strong> 
                                    ${answers[question.question_id].answer_text || 
                                      (question.choices ? question.choices.find(c => c.choice_id == answers[question.question_id].choice_id)?.choice_text || 'Selected choice' : '')
                                    }
                                    <br><strong>Points:</strong> ${answers[question.question_id].points_earned}/${question.points}
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            ` : ''}
            
            <div style="text-align: center; margin-top: 20px;">
                ${data.can_take ? `
                    <button class="btn btn-primary" onclick="closeActivityModal(); startActivity('${activity.activity_id}')">
                        <i class="fas fa-play"></i> ${activity.submission_status === 'in_progress' ? 'Continue Activity' : 'Start Activity'}
                    </button>
                ` : ''}
                <button class="btn btn-secondary" onclick="closeActivityModal()">Close</button>
            </div>
        </div>
    `;
    
    return content;
}

// Start activity
async function startActivity(activityId) {
    // Validate activity ID
    if (!activityId || activityId === 'null' || activityId === 'undefined') {
        console.error('Invalid activity ID:', activityId);
        alert('Invalid activity specified.');
        return;
    }
    
    try {
        // First start the activity in the backend
        const formData = new FormData();
        formData.append('activity_id', activityId);
        
        const response = await fetch('../api/student_activities.php?action=start', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Now load the activity for taking
        showTakeActivityModal(activityId);
        
    } catch (error) {
        console.error('Error starting activity:', error);
        
        // Remove the 'take' parameter from URL to prevent repeated attempts
        const url = new URL(window.location);
        url.searchParams.delete('take');
        window.history.replaceState({}, document.title, url.pathname + url.search);
        
        // Show user-friendly error message
        const errorMessage = error.message.includes('Activity not found') 
            ? 'This activity is no longer available or has expired.' 
            : error.message.includes('already completed')
            ? 'You have already completed this activity.'
            : 'Unable to start activity: ' + error.message;
            
        alert(errorMessage);
        
        // Refresh the activities list to show current state
        loadActivities();
    }
}

// Show take activity modal
async function showTakeActivityModal(activityId) {
    try {
        const modal = document.getElementById('takeActivityModal');
        const modalTitle = document.getElementById('takeActivityTitle');
        const modalContent = document.getElementById('takeActivityContent');
        const timerDisplay = document.getElementById('activityTimer');
        
        modalTitle.textContent = 'Loading...';
        modalContent.innerHTML = '<div class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> Loading activity...</div>';
        modal.style.display = 'block';
        
        const response = await fetch(`../api/student_activities.php?action=details&activity_id=${activityId}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        modalTitle.textContent = data.activity.title;
        
        if (data.activity.time_limit) {
            timeRemaining = data.activity.time_limit * 60; // Convert to seconds
            timerDisplay.style.display = 'flex';
            startTimer();
        } else {
            timerDisplay.style.display = 'none';
        }
        
        modalContent.innerHTML = createTakeActivityContent(data);
        
    } catch (error) {
        console.error('Error loading activity:', error);
        document.getElementById('takeActivityContent').innerHTML = `
            <div style="text-align: center; padding: 20px; color: #dc3545;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Failed to load activity.</p>
            </div>
        `;
    }
}

// Create take activity content
function createTakeActivityContent(data) {
    const activity = data.activity;
    const questions = data.questions;
    const answers = data.answers;
    
    if (activity.submission_status === 'submitted' || activity.submission_status === 'graded') {
        return `
            <div class="modal-body">
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 16px;"></i>
                    <h3>Activity Already Completed</h3>
                    <p>You have already submitted this activity.</p>
                    <button class="btn btn-secondary" onclick="closeTakeActivityModal()">Close</button>
                </div>
            </div>
        `;
    }
    
    let content = `
        <div class="modal-body">
            <form id="activityForm" onsubmit="submitActivity(event, '${activity.activity_id}')">
                <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                    <h5>${activity.subject_name} - ${activity.title}</h5>
                    <p>${activity.description || ''}</p>
                    <div style="display: flex; justify-content: space-between; font-size: 14px;">
                        <span><strong>Type:</strong> ${activity.activity_type}</span>
                        <span><strong>Total Points:</strong> ${activity.total_points}</span>
                        ${activity.time_limit ? `<span><strong>Time Limit:</strong> ${activity.time_limit} minutes</span>` : ''}
                    </div>
                </div>
    `;
    
    if (questions.length === 0) {
        content += `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-info-circle" style="font-size: 48px; color: #007bff; margin-bottom: 16px;"></i>
                    <h4>No Questions Available</h4>
                    <p>This activity doesn't have any questions set up yet.</p>
                </div>
        `;
    } else {
        questions.forEach((question, index) => {
            content += `
                <div class="question-container">
                    <div class="question-header">
                        <div class="question-number">${index + 1}</div>
                        <div class="question-points">${question.points} points</div>
                    </div>
                    
                    <div class="question-text">${question.question_text}</div>
                    
                    ${question.question_type === 'multiple_choice' && question.choices ? `
                        <div class="question-choices">
                            ${question.choices.map(choice => `
                                <div class="choice-option">
                                    <input type="radio" 
                                           name="question_${question.question_id}" 
                                           value="${choice.choice_id}" 
                                           id="choice_${choice.choice_id}"
                                           ${answers[question.question_id] && answers[question.question_id].choice_id == choice.choice_id ? 'checked' : ''}>
                                    <label for="choice_${choice.choice_id}" class="choice-text">${choice.choice_text}</label>
                                </div>
                            `).join('')}
                        </div>
                    ` : `
                        <textarea 
                            name="question_${question.question_id}" 
                            class="answer-textarea" 
                            placeholder="Type your answer here..."
                            ${activity.submission_status === 'submitted' || activity.submission_status === 'graded' ? 'readonly' : ''}
                        >${answers[question.question_id] ? answers[question.question_id].answer_text || '' : ''}</textarea>
                    `}
                </div>
            `;
        });
    }
    
    content += `
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <button type="button" class="btn btn-secondary" onclick="closeTakeActivityModal()">Cancel</button>
                    ${questions.length > 0 && activity.submission_status !== 'submitted' && activity.submission_status !== 'graded' ? `
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Submit Activity
                        </button>
                    ` : ''}
                </div>
            </form>
        </div>
    `;
    
    return content;
}

// Submit activity
async function submitActivity(event, activityId) {
    event.preventDefault();
    
    if (!confirm('Are you sure you want to submit this activity? You cannot change your answers after submission.')) {
        return;
    }
    
    try {
        const form = event.target;
        const formData = new FormData();
        formData.append('activity_id', activityId);
        
        // Collect answers
        const answers = {};
        const inputs = form.querySelectorAll('input[type="radio"]:checked, textarea');
        
        inputs.forEach(input => {
            const match = input.name.match(/question_(\d+)/);
            if (match) {
                const questionId = match[1];
                answers[questionId] = input.value;
            }
        });
        
        formData.append('answers', JSON.stringify(answers));
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
        }
        
        const response = await fetch('../api/student_activities.php?action=submit', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Stop timer if running
        if (activityTimer) {
            clearInterval(activityTimer);
            activityTimer = null;
        }
        
        // Show success message
        document.getElementById('takeActivityContent').innerHTML = `
            <div class="modal-body">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 16px;"></i>
                    <h3>Activity Submitted Successfully!</h3>
                    <p>Your total score: <strong>${data.total_score} points</strong></p>
                    <button class="btn btn-primary" onclick="closeTakeActivityModal(); loadActivities();">Continue</button>
                </div>
            </div>
        `;
        
    } catch (error) {
        console.error('Error submitting activity:', error);
        alert('Failed to submit activity: ' + error.message);
        
        // Re-enable submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Activity';
            submitBtn.disabled = false;
        }
    }
}

// Timer functions
function startTimer() {
    if (activityTimer) {
        clearInterval(activityTimer);
    }
    
    activityTimer = setInterval(function() {
        timeRemaining--;
        
        if (timeRemaining <= 0) {
            clearInterval(activityTimer);
            alert('Time is up! The activity will be automatically submitted.');
            // Auto-submit the activity
            const form = document.getElementById('activityForm');
            if (form) {
                const event = new Event('submit');
                form.dispatchEvent(event);
            }
            return;
        }
        
        updateTimerDisplay();
    }, 1000);
    
    updateTimerDisplay();
}

function updateTimerDisplay() {
    const minutes = Math.floor(timeRemaining / 60);
    const seconds = timeRemaining % 60;
    const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    const timerElement = document.getElementById('timeRemaining');
    if (timerElement) {
        timerElement.textContent = display;
        
        // Change color when time is running low
        if (timeRemaining <= 300) { // 5 minutes
            timerElement.style.color = '#dc3545';
        } else if (timeRemaining <= 600) { // 10 minutes
            timerElement.style.color = '#ffc107';
        }
    }
}

// Modal control functions
function closeActivityModal() {
    document.getElementById('activityModal').style.display = 'none';
}

function closeTakeActivityModal() {
    if (activityTimer) {
        clearInterval(activityTimer);
        activityTimer = null;
    }
    document.getElementById('takeActivityModal').style.display = 'none';
}

// Utility functions
function getStatusClass(status) {
    switch (status) {
        case 'completed':
        case 'submitted':
        case 'graded':
            return 'completed';
        case 'in_progress':
            return 'pending';
        case 'not_started':
        default:
            return 'pending';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'completed':
        case 'submitted':
        case 'graded':
            return 'Completed';
        case 'in_progress':
            return 'In Progress';
        case 'not_started':
        default:
            return 'Not Started';
    }
}

function showLoadingState() {
    document.getElementById('loadingActivities').style.display = 'block';
    document.getElementById('activitiesGrid').style.display = 'none';
    document.getElementById('noActivities').style.display = 'none';
}

function hideLoadingState() {
    document.getElementById('loadingActivities').style.display = 'none';
}

function showErrorState(message) {
    hideLoadingState();
    document.getElementById('activitiesGrid').style.display = 'none';
    document.getElementById('noActivities').style.display = 'block';
    document.querySelector('#noActivities h3').textContent = 'Error Loading Activities';
    document.querySelector('#noActivities p').textContent = message;
}