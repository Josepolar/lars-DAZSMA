// Initialize filters and table functionality
document.addEventListener('DOMContentLoaded', function() {
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
                if (selectedGrade === 'all') {
                    row.style.display = '';
                } else {
                    const gradeCell = row.querySelector('td:nth-child(6)'); // Grade Level column
                    if (gradeCell) {
                        const studentGrade = gradeCell.textContent.replace('Grade ', '').trim();
                        row.style.display = studentGrade === selectedGrade ? '' : 'none';
                    }
                }
            });
        });
    });
});

function openEditModal(studentId) {
    // You can implement the edit modal functionality here
    console.log('Edit student:', studentId);
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        try {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'user_id';
            idInput.value = studentId;

            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_student';
            deleteInput.value = '1';

            form.appendChild(idInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            console.error("Error deleting student:", error);
            alert("Error trying to delete student. Please try again.");
        }
    }
}
