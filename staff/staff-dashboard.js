document.addEventListener('DOMContentLoaded', function() {
    // Grade Distribution Chart
    const gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');
    new Chart(gradeCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(gradeData).map(grade => `Grade ${grade}`),
            datasets: [{
                label: 'Number of Students',
                data: Object.values(gradeData),
                backgroundColor: [
                    '#004b9c',
                    '#1a5fa5',
                    '#3373ae',
                    '#4d87b8'
                ],
                borderColor: [
                    '#003b7c',
                    '#004b9c',
                    '#1a5fa5',
                    '#3373ae'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Subject Distribution Chart
    const subjectCtx = document.getElementById('subjectDistributionChart').getContext('2d');
    new Chart(subjectCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(subjectData),
            datasets: [{
                data: Object.values(subjectData),
                backgroundColor: [
                    '#004b9c',
                    '#1a5fa5',
                    '#3373ae',
                    '#4d87b8',
                    '#668fc1',
                    '#80a7cb'
                ],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});