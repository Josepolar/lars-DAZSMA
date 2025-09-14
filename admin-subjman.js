// Function to open modals
function openModal(id) {
    document.getElementById(id).style.display = "block";
}

// Function to close modals
function closeModal(id) {
    document.getElementById(id).style.display = "none";
}

// Function to handle edit subject
function editSubject(subjectId, subjectName, gradeLevel) {
    // Set the values in the edit form
    document.getElementById('edit_subject_id').value = subjectId;
    document.getElementById('edit_subject_name').value = subjectName;
    document.getElementById('edit_grade_level').value = gradeLevel;
    
    // Open the edit modal
    openModal('editModal');
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
    }
}

// Function to handle delete subject
function deleteSubject(subjectId) {
    if (confirm('Are you sure you want to delete this subject?')) {
        const formData = new FormData();
        formData.append('subject_id', subjectId);
        formData.append('delete_subject', '1');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                throw new Error('Network response was not ok. Status: ' + response.status);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting subject: ' + error.message);
        });
    }
}
