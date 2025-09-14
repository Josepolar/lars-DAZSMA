function openModal(id) {
    document.getElementById(id).style.display = "block";
}

function closeModal(id) {
    document.getElementById(id).style.display = "none";
}

// Function to delete subject with animation
function deleteSubject(subjectId) {
    if (confirm('Are you sure you want to delete this subject?')) {
        const row = event.target.closest('tr');
        row.style.transition = 'opacity 0.3s ease';
        row.style.opacity = '0';
        
        // Use the hidden form for more reliable submission
        const form = document.getElementById('deleteSubjectForm');
        const subjectIdField = document.getElementById('delete_subject_id');
        subjectIdField.value = subjectId;
        
        try {
            setTimeout(() => {
                form.submit();
            }, 300);
        } catch (error) {
            console.error("Error submitting delete form:", error);
            row.style.opacity = '1'; // Restore the row if submission fails
            alert("Error trying to delete subject. Please try again.");
        }
    }
}

// Close modal when clicking outside content
window.onclick = function(event) {
    let modals = document.querySelectorAll(".modal");
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
}
