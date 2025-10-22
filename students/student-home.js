// Global variables
let dashboardData = null;
let currentSubjectActivities = {};

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Ensure leaderboard is visible (server-side rendered)
    const leaderboardList = document.getElementById('leaderboardList');
    if (leaderboardList) {
        leaderboardList.style.display = 'block';
    }
    
    loadDashboardData();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Modal functionality for achievements
    const modal = document.getElementById("rewardsModal");
    const btn = document.getElementById("rewardsBtn");
    const span = modal.querySelector(".close");

    btn.onclick = function() {
        updateAchievements();
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
        if (event.target === recitsModal) {
            recitsModal.style.display = "none";
        }
    }

    // Activities Modal
    const recitsModal = document.getElementById("recitsModal");
    const recitsClose = recitsModal.querySelector(".close");

    recitsClose.onclick = function() {
        recitsModal.style.display = "none";
    }

    // Activity of the day button
    const takeActivityBtn = document.getElementById("takeActivityBtn");
    if (takeActivityBtn) {
        takeActivityBtn.addEventListener('click', function() {
            if (dashboardData && dashboardData.activity_of_day) {
                window.location.href = `student-activities.php?take=${dashboardData.activity_of_day.activity_id}`;
            }
        });
    }
}

// Load dashboard data from API
async function loadDashboardData() {
    try {
        showLoadingState();
        
        const response = await fetch('../api/student_dashboard.php');
        const data = await response.json();
        
        console.log('Dashboard response:', {
            status: response.status,
            data: data
        });
        
        if (!response.ok) {
            throw new Error(data.error || `HTTP error! status: ${response.status}`);
        }
        
        if (data.error) {
            throw new Error(typeof data.error === 'object' ? JSON.stringify(data.error) : data.error);
        }
        
        if (!data || typeof data !== 'object') {
            throw new Error('Invalid data received from server');
        }
        
        dashboardData = data;
        updateDashboardUI(data);
        hideLoadingState();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        const errorMessage = error.message || 'Failed to load dashboard data';
        showErrorState(errorMessage);
    }
}

// Update dashboard UI with fetched data
function updateDashboardUI(data) {
    // Update profile information
    updateProfileInfo(data.profile);

    // Update statistics
    updateStatistics(data.stats);
    // Update subjects list
    updateSubjectsList(data.subjects);
    // Update activity of the day
    updateActivityOfDay(data.activity_of_day);
    // Update recent activities
    updateRecentActivities(data.pending_activities);
    // Update leaderboard (disabled - now using server-side PHP rendering)
    // updateLeaderboard(data.leaderboard);
    // Update submission lists
    updateSubmissionLists(data.recent_submissions, data.pending_activities);
}

// Update profile information
function updateProfileInfo(profile) {
    const profileName = document.getElementById('profileName');
    const studentName = document.getElementById('studentName');
    const studentSection = document.getElementById('studentSection');
    
    if (profileName) {
        profileName.textContent = `${profile.first_name} ${profile.last_name}`;
    }
    
    if (studentName) {
        studentName.textContent = `${profile.first_name.toUpperCase()} ${profile.last_name.toUpperCase()}`;
    }
    
    if (studentSection) {
        studentSection.textContent = `Grade ${profile.grade_level}`;
    }
}

// Update statistics
function updateStatistics(stats) {
    const totalPoints = document.getElementById('totalPoints');
    const completionRate = document.getElementById('completionRate');
    
    if (totalPoints) {
        totalPoints.textContent = Math.round(stats.total_points_earned);
    }
    
    if (completionRate) {
        completionRate.textContent = `${stats.completion_rate}%`;
    }
}

// Update subjects list
function updateSubjectsList(subjects) {
    console.log('Updating subjects list with:', subjects);
    
    const loadingSubjects = document.getElementById('loadingSubjects');
    const subjectsList = document.getElementById('subjectsList');
    
    if (loadingSubjects) loadingSubjects.style.display = 'none';
    if (subjectsList) subjectsList.style.display = 'block';
    
    if (!subjectsList) return;
    
    subjectsList.innerHTML = '';
    
    if (subjects.length === 0) {
        subjectsList.innerHTML = '<li style="text-align: center; color: #666;">No active activities found</li>';
        return;
    }
    
    subjects.forEach(subject => {
        console.log('Adding subject:', subject);
        const li = document.createElement('li');
        li.innerHTML = `
            <span class="subject-name">${subject.subject_name}</span>
            <span class="recit-count">${subject.active_activities_count}</span>
            <button class="eye-btn" data-subject-id="${subject.subject_id}" data-subject-name="${subject.subject_name}">👁</button>
        `;
        subjectsList.appendChild(li);
    });
    
    // Add event listeners to eye buttons
    document.querySelectorAll('.eye-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const subjectId = this.getAttribute('data-subject-id');
            const subjectName = this.getAttribute('data-subject-name');
            showSubjectActivities(subjectId, subjectName);
        });
    });
}

// Show subject activities in modal
async function showSubjectActivities(subjectId, subjectName) {
    const modal = document.getElementById("recitsModal");
    const modalTitle = document.getElementById("modal-subject-title");
    const loadingActivities = document.getElementById("loadingActivities");
    const activitiesList = document.getElementById("activitiesList");
    
    modalTitle.textContent = `${subjectName} Activities`;
    modal.style.display = "block";
    
    if (loadingActivities) loadingActivities.style.display = 'block';
    if (activitiesList) activitiesList.style.display = 'none';
    
    try {
        const response = await fetch(`../api/student_activities.php?action=subjects&subject_id=${subjectId}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        if (loadingActivities) loadingActivities.style.display = 'none';
        if (activitiesList) activitiesList.style.display = 'block';
        
        activitiesList.innerHTML = '';
        
        if (data.activities.length === 0) {
            activitiesList.innerHTML = '<li style="text-align: center; color: #666;">No activities found for this subject</li>';
            return;
        }
        
        data.activities.forEach(activity => {
            const li = document.createElement('li');
            const statusClass = getStatusClass(activity.submission_status);
            const statusText = getStatusText(activity.submission_status);
            
            li.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                    <div>
                        <div style="font-weight: 600;">${activity.title}</div>
                        <div style="font-size: 12px; color: #666;">${activity.activity_type} • ${activity.total_points} points</div>
                    </div>
                    <div style="text-align: right;">
                        <div class="activity-status ${statusClass}">${statusText}</div>
                        ${activity.total_score !== null ? `<div style="font-size: 12px; color: #28a745;">${activity.total_score}/${activity.total_points}</div>` : ''}
                    </div>
                </div>
            `;
            
            li.style.cursor = 'pointer';
            li.addEventListener('click', function() {
                window.location.href = `student-activities.php?view=${activity.activity_id}`;
            });
            
            activitiesList.appendChild(li);
        });
        
    } catch (error) {
        console.error('Error loading subject activities:', error);
        if (loadingActivities) loadingActivities.style.display = 'none';
        if (activitiesList) {
            activitiesList.style.display = 'block';
            activitiesList.innerHTML = '<li style="text-align: center; color: #dc3545;">Error loading activities</li>';
        }
    }
}

// Update activity of the day
function updateActivityOfDay(activityOfDay) {
    const loadingActivityOfDay = document.getElementById('loadingActivityOfDay');
    const activityOfDayContent = document.getElementById('activityOfDayContent');
    const activitySubject = document.getElementById('activitySubject');
    const activityTitle = document.getElementById('activityTitle');
    const activityDetails = document.getElementById('activityDetails');
    const takeActivityBtn = document.getElementById('takeActivityBtn');
    
    if (loadingActivityOfDay) loadingActivityOfDay.style.display = 'none';
    if (activityOfDayContent) activityOfDayContent.style.display = 'block';
    
    if (!activityOfDay) {
        if (activitySubject) activitySubject.textContent = 'No Activity';
        if (activityTitle) activityTitle.textContent = 'No active activities';
        if (activityDetails) activityDetails.textContent = 'Check back later for new activities';
        if (takeActivityBtn) takeActivityBtn.style.display = 'none';
        return;
    }
    
    if (activitySubject) activitySubject.textContent = activityOfDay.subject_name;
    if (activityTitle) activityTitle.textContent = activityOfDay.activity_title;
    
    let detailsText = `${activityOfDay.activity_type} • ${activityOfDay.total_points} points`;
    if (activityOfDay.due_date) {
        const dueDate = new Date(activityOfDay.due_date);
        detailsText += ` • Due: ${dueDate.toLocaleDateString()}`;
    }
    if (activityDetails) activityDetails.textContent = detailsText;
    
    if (takeActivityBtn) takeActivityBtn.style.display = 'block';
}

// Update recent activities in center column
function updateRecentActivities(pendingActivities) {
    const loadingDetailedActivities = document.getElementById('loadingDetailedActivities');
    const detailedActivities = document.getElementById('detailedActivities');
    
    if (loadingDetailedActivities) loadingDetailedActivities.style.display = 'none';
    
    if (!detailedActivities) return;
    
    // Clear existing content except loading indicator
    const existingBoxes = detailedActivities.querySelectorAll('.box');
    existingBoxes.forEach(box => box.remove());
    
    if (pendingActivities.length === 0) {
        detailedActivities.innerHTML = `
            <div class="box recit-box" style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 16px;"></i>
                <h3 style="color: #666;">No Pending Activities</h3>
                <p style="color: #999;">All caught up! Check back later.</p>
            </div>
        `;
        return;
    }
    
    // Show up to 6 recent activities
    const displayActivities = pendingActivities.slice(0, 6);
    
    displayActivities.forEach(activity => {
        const box = document.createElement('div');
        box.className = 'box recit-box';
        
        const dueDate = activity.due_date ? new Date(activity.due_date) : null;
        const dueDateText = dueDate ? dueDate.toLocaleDateString() : 'No deadline';
        const isOverdue = dueDate && dueDate < new Date();
        
        box.innerHTML = `
            <h3 class="recit-subject">${activity.subject_name}</h3>
            <p class="recit-teacher">Teacher: ${activity.teacher_name}</p>
            <p class="recit-name">${activity.activity_title}</p>
            <p class="recit-deadline ${isOverdue ? 'deadline-overdue' : ''}">${isOverdue ? 'Overdue: ' : 'Due: '}${dueDateText}</p>
            <p class="recit-items">${activity.activity_type} - ${activity.total_points} points</p>
            <button class="take-now" onclick="window.location.href='student-activities.php?take=${activity.activity_id}'">Take Now</button>
        `;
        
        detailedActivities.appendChild(box);
    });
}

// Update leaderboard (COMPLETELY DISABLED - using server-side PHP rendering only)
function updateLeaderboard(leaderboard) {
    // Function disabled to prevent interference with server-side rendering
    console.log('updateLeaderboard called but disabled to avoid conflicts with server-side rendering');
    return;
    
    /* ORIGINAL CODE COMMENTED OUT
    const loadingLeaderboard = document.getElementById('loadingLeaderboard');
    const leaderboardList = document.getElementById('leaderboardList');
    
    if (loadingLeaderboard) loadingLeaderboard.style.display = 'none';
    if (!leaderboardList) return;

    // Get the student's grade from the profile
    const studentGrade = dashboardData && dashboardData.profile ? dashboardData.profile.grade_level : null;
    
    leaderboardList.innerHTML = '';

    // Filter leaderboard to only show students from the same grade
    const filteredLeaderboard = leaderboard.filter(student => String(student.grade_level) === String(studentGrade));

    if (filteredLeaderboard.length === 0) {
        leaderboardList.innerHTML = '<li style="text-align:center;color:#666;">No leaderboard data for your grade.</li>';
        return;
    }

    filteredLeaderboard.forEach(student => {
        const li = document.createElement('li');
        li.className = student.rank <= 3 ? `rank-${student.rank}` : '';
        if (student.is_current_user) {
            li.style.backgroundColor = '#e3f2fd';
            li.style.fontWeight = 'bold';
        }
        
        li.innerHTML = `
            <span class="rank">#${student.rank}</span>
            <img src="assets/dazsma.png" alt="Student ${student.rank}" class="lb-pic">
            <span class="lb-name">${student.full_name}</span>
            <span class="lb-points">${Math.round(student.total_points)} pts</span>
        `;
        
        leaderboardList.appendChild(li);
    });
    */
}

// Update submission lists
function updateSubmissionLists(recentSubmissions, pendingActivities) {
    updateSubmittedList(recentSubmissions);
    updatePendingList(pendingActivities);
}

// Update submitted activities list
function updateSubmittedList(recentSubmissions) {
    const loadingSubmitted = document.getElementById('loadingSubmitted');
    const submittedList = document.getElementById('submittedList');
    const submittedPoints = document.getElementById('submittedPoints');
    
    if (loadingSubmitted) loadingSubmitted.style.display = 'none';
    if (submittedList) submittedList.style.display = 'block';
    
    if (!submittedList) return;
    
    submittedList.innerHTML = '';
    
    const totalPoints = recentSubmissions.reduce((sum, submission) => {
        const score = parseFloat(submission.total_score) || 0;
        return sum + score;
    }, 0);
    if (submittedPoints && !isNaN(totalPoints)) {
        submittedPoints.textContent = Math.round(totalPoints);
    }
    
    if (recentSubmissions.length === 0) {
        submittedList.innerHTML = '<li style="text-align: center; color: #666;">No completed activities</li>';
        return;
    }
    
    recentSubmissions.forEach(submission => {
        const li = document.createElement('li');
        li.innerHTML = `
            <div>
                <span class="recit-name">${submission.activity_title}</span>
                <span class="recit-subject">${submission.subject_name}</span>
                ${submission.total_score !== null ? `<div style="font-size: 12px; color: #28a745;">${submission.total_score}/${submission.max_score} (${Math.round(submission.percentage)}%)</div>` : ''}
            </div>
        `;
        submittedList.appendChild(li);
    });
}

// Update pending activities list
function updatePendingList(pendingActivities) {
    const loadingPending = document.getElementById('loadingPending');
    const pendingList = document.getElementById('pendingList');
    const pendingCount = document.getElementById('pendingCount');
    
    if (loadingPending) loadingPending.style.display = 'none';
    if (pendingList) pendingList.style.display = 'block';
    
    if (!pendingList) return;
    
    pendingList.innerHTML = '';
    
    if (pendingCount) pendingCount.textContent = pendingActivities.length;
    
    if (pendingActivities.length === 0) {
        pendingList.innerHTML = '<li style="text-align: center; color: #666;">All activities completed!</li>';
        return;
    }
    
    pendingActivities.slice(0, 8).forEach(activity => {
        const li = document.createElement('li');
        const dueDate = activity.due_date ? new Date(activity.due_date) : null;
        const isOverdue = dueDate && dueDate < new Date();
        
        li.innerHTML = `
            <span class="recit-name" style="${isOverdue ? 'color: #dc3545;' : ''}">${activity.activity_title}</span>
            <span class="recit-subject">${activity.subject_name}</span>
            ${isOverdue ? '<div style="font-size: 12px; color: #dc3545;">OVERDUE</div>' : ''}
        `;
        
        li.style.cursor = 'pointer';
        li.addEventListener('click', function() {
            window.location.href = `student-activities.php?take=${activity.activity_id}`;
        });
        
        pendingList.appendChild(li);
    });
}

// Update achievements based on student progress
function updateAchievements() {
    if (!dashboardData) return;
    
    const stats = dashboardData.stats;
    const firstActivityStatus = document.getElementById('firstActivityStatus');
    const perfectScoreStatus = document.getElementById('perfectScoreStatus');
    const activeLearnerStatus = document.getElementById('activeLearnerStatus');
    const streakMasterStatus = document.getElementById('streakMasterStatus');
    
    // First Activity Completed
    if (firstActivityStatus) {
        if (stats.completed_submissions > 0) {
            firstActivityStatus.textContent = 'Earned';
            firstActivityStatus.className = 'status claimed';
        } else {
            firstActivityStatus.textContent = 'Not Yet Earned';
            firstActivityStatus.className = 'status not-claimed';
        }
    }
    
    // Perfect Score Achievement (90% or higher average)
    if (perfectScoreStatus) {
        if (stats.average_percentage >= 90) {
            perfectScoreStatus.textContent = 'Earned';
            perfectScoreStatus.className = 'status claimed';
        } else {
            perfectScoreStatus.textContent = 'Not Yet Earned';
            perfectScoreStatus.className = 'status not-claimed';
        }
    }
    
    // Active Learner (5+ activities completed)
    if (activeLearnerStatus) {
        if (stats.completed_submissions >= 5) {
            activeLearnerStatus.textContent = 'Earned';
            activeLearnerStatus.className = 'status claimed';
        } else {
            activeLearnerStatus.textContent = `${stats.completed_submissions}/5 Completed`;
            activeLearnerStatus.className = 'status not-claimed';
        }
    }
    
    // Streak Master (80% completion rate)
    if (streakMasterStatus) {
        if (stats.completion_rate >= 80) {
            streakMasterStatus.textContent = 'Earned';
            streakMasterStatus.className = 'status claimed';
        } else {
            streakMasterStatus.textContent = 'Not Yet Earned';
            streakMasterStatus.className = 'status not-claimed';
        }
    }
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
    // Show loading indicators
    document.querySelectorAll('.loading-indicator').forEach(indicator => {
        if (indicator) indicator.style.display = 'block';
    });
    
    // Hide content areas (excluding leaderboardList since it's server-side rendered)
    document.querySelectorAll('#subjectsList, #activitiesList, #submittedList, #pendingList, #activityOfDayContent').forEach(element => {
        if (element) element.style.display = 'none';
    });
}

function hideLoadingState() {
    // Hide loading indicators
    document.querySelectorAll('.loading-indicator').forEach(indicator => {
        if (indicator) indicator.style.display = 'none';
    });
    
    // Ensure leaderboard stays visible (server-side rendered)
    const leaderboardList = document.getElementById('leaderboardList');
    if (leaderboardList) {
        leaderboardList.style.display = 'block';
    }
}

function showErrorState(message) {
    console.error(message);
    // You could show a toast notification or alert here
    alert(message);
}
