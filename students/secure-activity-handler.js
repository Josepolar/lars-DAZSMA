// Handles opening activities in a secure window
function startActivity(activityId) {
    // Set window features for maximum security
    const windowFeatures = [
        `width=${screen.width}`,
        `height=${screen.height}`,
        'fullscreen=yes',
        'channelmode=yes',
        'menubar=no',
        'toolbar=no',
        'location=no',
        'personalbar=no',
        'status=no',
        'dependent=yes',
        'scrollbars=yes'
    ].join(',');

    // Open activity in a new window with enforced settings
    const activityWindow = window.open(
        `take-activity.php?id=${activityId}`,
        'secureActivity',
        windowFeatures
    );

    if (activityWindow) {
        // Force window to top-left and maximize
        activityWindow.moveTo(0, 0);
        activityWindow.focus();

        // Monitor window state
        const checkWindow = setInterval(() => {
            if (activityWindow.closed) {
                clearInterval(checkWindow);
                // Refresh activities list when window is closed
                window.location.reload();
            }
        }, 1000);
    } else {
        alert('Please enable pop-ups to take the activity. Pop-ups are required for secure testing.');
    }
}