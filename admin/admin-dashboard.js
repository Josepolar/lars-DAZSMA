document.addEventListener('DOMContentLoaded', function() {
    // User Distribution Pie Chart
    const userCtx = document.getElementById('userDistributionChart').getContext('2d');
    new Chart(userCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(userData),
            datasets: [{
                data: Object.values(userData),
                backgroundColor: [
                    '#004b9c',
                    '#1a5fa5',
                    '#3373ae'
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

    // Grade Distribution Bar Chart
    const gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');
    new Chart(gradeCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(gradeData).map(grade => `Grade ${grade}`),
            datasets: [{
                label: 'Number of Students',
                data: Object.values(gradeData),
                backgroundColor: '#004b9c',
                borderColor: '#003b7c',
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

    // User Activity Bar Chart
    const activityCtx = document.getElementById('userActivityChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(activityData),
            datasets: [{
                label: 'Activity Count',
                data: Object.values(activityData),
                backgroundColor: [
                    '#004b9c',
                    '#1a5fa5',
                    '#3373ae'
                ],
                borderColor: '#003b7c',
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
                        stepSize: 5
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
});