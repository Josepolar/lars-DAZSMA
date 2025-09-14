let currentDate = new Date();
let selectedDate = new Date();

// Initialize calendar when the page loads
document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
    addEventListeners();
});

function renderCalendar() {
    const calendar = document.getElementById('calendar');
    const monthDisplay = document.getElementById('monthDisplay');
    
    // Clear previous calendar
    calendar.innerHTML = '';
    
    // Set month display
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    monthDisplay.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    
    // Get first day of month and total days
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    
    // Add days from previous month
    const firstDayIndex = firstDay.getDay();
    for (let i = firstDayIndex; i > 0; i--) {
        const prevDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), -i + 1);
        addDayToCalendar(prevDate, true);
    }
    
    // Add days of current month
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), i);
        addDayToCalendar(date, false);
    }
    
    // Add days from next month
    const lastDayIndex = lastDay.getDay();
    for (let i = 1; i < 7 - lastDayIndex; i++) {
        const nextDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, i);
        addDayToCalendar(nextDate, true);
    }
}

function addDayToCalendar(date, isOtherMonth) {
    const calendar = document.getElementById('calendar');
    const dayElement = document.createElement('div');
    dayElement.classList.add('calendar-day');
    
    if (isOtherMonth) {
        dayElement.classList.add('other-month');
    }
    
    // Check if it's today
    const today = new Date();
    if (date.toDateString() === today.toDateString()) {
        dayElement.classList.add('today');
    }
    
    // Add the day number
    dayElement.textContent = date.getDate();
    
    // Store the full date as a data attribute
    dayElement.dataset.date = date.toISOString().split('T')[0];
    
    // Check if there are logs for this date (you'll need to implement this)
    checkLogsForDate(date).then(hasLogs => {
        if (hasLogs) {
            dayElement.classList.add('has-logs');
        }
    });
    
    // Add click event
    dayElement.addEventListener('click', () => showLogs(date));
    
    calendar.appendChild(dayElement);
}

async function checkLogsForDate(date) {
    // Format date as YYYY-MM-DD
    const formattedDate = date.toISOString().split('T')[0];
    
    try {
        const response = await fetch(`check_logs.php?date=${formattedDate}`);
        const data = await response.json();
        
        if (data.status === 'error') {
            console.error('Server error:', data.message);
            return false;
        }
        
        return data.hasLogs;
    } catch (error) {
        console.error('Error checking logs:', error);
        return false;
    }
}

async function showLogs(date) {
    const modal = document.getElementById('logsModal');
    const selectedDateHeader = document.getElementById('selectedDate');
    const tbody = document.getElementById('dailyLogsBody');
    
    // Format date for display
    const formattedDate = date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    selectedDateHeader.textContent = `User Logs for ${formattedDate}`;
    
    try {
        // Fetch logs for the selected date
        const response = await fetch(`get_logs.php?date=${date.toISOString().split('T')[0]}`);
        const result = await response.json();
        
        // Clear previous logs
        tbody.innerHTML = '';
        
        if (result.status === 'error') {
            throw new Error(result.message);
        }
        
        const logs = result.data;
        
        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No logs found for this date</td></tr>';
        } else {
            // Add logs to table
            logs.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${log.user_name}</td>
                    <td>${log.role}</td>
                    <td>${log.action}</td>
                    <td>${log.time}</td>
                `;
                tbody.appendChild(row);
            });
        }
        
        // Show modal
        modal.style.display = 'block';
    } catch (error) {
        console.error('Error fetching logs:', error);
        alert(`Error: ${error.message || 'Could not load logs for this date'}`);
    }
}

function closeModal() {
    const modal = document.getElementById('logsModal');
    modal.style.display = 'none';
}

function addEventListeners() {
    // Previous month button
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1);
        renderCalendar();
    });
    
    // Next month button
    document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1);
        renderCalendar();
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        const modal = document.getElementById('logsModal');
        if (event.target === modal) {
            closeModal();
        }
    });
}
