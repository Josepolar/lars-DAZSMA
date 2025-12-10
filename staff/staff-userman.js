// Function to open modals
function openModal(id) {
    document.getElementById(id).style.display = "block";
    if (id === 'studentModal') {
        // Reset to single student tab when opening
        switchTab('single');
    }
}

// Function to switch between single and bulk upload tabs
function switchTab(tab) {
    const singleForm = document.getElementById('singleStudentForm');
    const bulkForm = document.getElementById('bulkStudentForm');
    const buttons = document.querySelectorAll('.tab-btn');
    
    if (tab === 'single') {
        singleForm.style.display = 'block';
        bulkForm.style.display = 'none';
        buttons[0].classList.add('active');
        buttons[1].classList.remove('active');
    } else {
        singleForm.style.display = 'none';
        bulkForm.style.display = 'block';
        buttons[0].classList.remove('active');
        buttons[1].classList.add('active');
    }
}

// Function to download grade-specific CSV template
function downloadTemplate(gradeLevel) {
    const header = "First Name,Last Name,Username,Email,Password,Grade Level,Section\n";
    const example = `John,Doe,johndoe${gradeLevel},john.doe${gradeLevel}@example.com,password123,${gradeLevel},A\n`;
    const csvContent = header + example;
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', `grade${gradeLevel}_template.csv`);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Function to close modals
function closeModal(id) {
    document.getElementById(id).style.display = "none";
}

// Function to close edit modal
function closeEditModal() {
    document.getElementById('editUserModal').style.display = "none";
}

// Function to open edit modal and populate with user data
async function openEditModal(userType, userId) {
    const modal = document.getElementById('editUserModal');
    const form = document.getElementById('editUserForm');
    const gradeFieldContainer = document.querySelector('.student-grade-field');
    const sectionFieldContainer = document.querySelector('.student-section-field');
    
    // Show/hide grade and section fields based on user type
    if (gradeFieldContainer) {
        gradeFieldContainer.style.display = userType === 'student' ? 'block' : 'none';
    }
    if (sectionFieldContainer) {
        sectionFieldContainer.style.display = userType === 'student' ? 'block' : 'none';
    }
    
    // Fetch user data via AJAX
    try {
        const response = await fetch(`staff-userman.php?get_user=${userId}`);
        const userData = await response.json();
        
        if (userData.success) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_fname').value = userData.first_name;
            document.getElementById('edit_lname').value = userData.last_name;
            document.getElementById('edit_email').value = userData.email;
            document.getElementById('edit_username').value = userData.username;
            
            // Set grade level and section if it's a student
            if (userType === 'student') {
                document.getElementById('edit_grade').value = userData.grade_level || '';
                if (document.getElementById('edit_section')) {
                    document.getElementById('edit_section').value = userData.section || '';
                }
            }
            
            // Clear password field (it's optional in edit mode)
            document.getElementById('edit_password').value = '';
            
            // Show the modal
            modal.style.display = "block";
        } else {
            alert('Error loading user data');
        }
    } catch (error) {
        console.error('Error fetching user data:', error);
        alert('Error loading user data. Please try again.');
    }
}

// Function to delete a user
function deleteUser(userType, userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        try {
            // Create a form to submit the delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;

            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'delete_user';
            submitInput.value = '1';

            form.appendChild(userIdInput);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            console.error("Error submitting delete form:", error);
            alert("Error trying to delete user. Please try again.");
        }
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName("modal");
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
}

// Initialize filters and table functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add data-id attributes to table rows
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const editBtn = row.querySelector('.edit-btn');
            if (editBtn) {
                const userId = editBtn.getAttribute('onclick').match(/\d+/)[0];
                row.setAttribute('data-id', userId);
            }
        });
    });

    // Initialize grade filters
    const filterButtons = document.querySelectorAll('.grade-filters .filter-btn');
    const studentRows = document.querySelectorAll('.student-list tr');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Update active button state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            const selectedGrade = button.getAttribute('data-grade');
            
            studentRows.forEach(row => {
                const gradeCell = row.querySelector('td:nth-child(2)');
                if (gradeCell) {
                    // Extract grade number from text like "Grade 7" or "Grade 7 - A"
                    const gradeText = gradeCell.textContent;
                    const gradeMatch = gradeText.match(/Grade\s*(\d+)/);
                    const studentGrade = gradeMatch ? gradeMatch[1] : '';
                    
                    if (selectedGrade === 'all' || studentGrade === selectedGrade) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    });
});
