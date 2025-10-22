function handleListActivities($pdo, $student_id) {
    // Get filter values
    $status = $_GET['status'] ?? 'all';
    $subject = $_GET['subject'] ?? 'all';
    $type = $_GET['type'] ?? 'all';

    // Base query
    $query = "
        SELECT 
            a.activity_id,
            a.title,
            a.description,
            a.activity_type,
            a.total_points,
            a.time_limit,
            a.due_date,
            a.game_settings,
            s.subject_name,
            u.first_name AS teacher_first_name,
            u.last_name AS teacher_last_name,
            COALESCE(gp.score, 0) as student_score,
            CASE 
                WHEN gp.progress_id IS NOT NULL THEN 'completed'
                WHEN a.due_date < NOW() THEN 'expired'
                ELSE 'pending'
            END as status
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users u ON a.teacher_id = u.user_id
        JOIN student_subjects ss ON s.subject_id = ss.subject_id
        LEFT JOIN game_progress gp ON a.activity_id = gp.activity_id AND gp.student_id = ?
        WHERE ss.student_id = ? AND a.is_active = 1
    ";

    $params = [$student_id, $student_id];

    // Apply filters
    if ($status !== 'all') {
        if ($status === 'pending') {
            $query .= " AND gp.progress_id IS NULL AND a.due_date >= NOW()";
        } elseif ($status === 'completed') {
            $query .= " AND gp.progress_id IS NOT NULL";
        } elseif ($status === 'expired') {
            $query .= " AND gp.progress_id IS NULL AND a.due_date < NOW()";
        }
    }

    if ($subject !== 'all') {
        $query .= " AND s.subject_id = ?";
        $params[] = $subject;
    }

    if ($type !== 'all') {
        $query .= " AND a.activity_type = ?";
        $params[] = $type;
    }

    $query .= " ORDER BY a.due_date ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($activities as &$activity) {
        if ($activity['game_settings']) {
            $activity['game_settings'] = json_decode($activity['game_settings'], true);
        }
    }

    echo json_encode(['success' => true, 'activities' => $activities]);
}

function handleActivityDetails($pdo, $student_id) {
    $activity_id = $_GET['id'] ?? null;
    if (!$activity_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Activity ID not provided']);
        return;
    }

    $query = "
        SELECT 
            a.*,
            s.subject_name,
            u.first_name AS teacher_first_name,
            u.last_name AS teacher_last_name,
            gc.content_data,
            gc.difficulty_level
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users u ON a.teacher_id = u.user_id
        LEFT JOIN game_content gc ON a.activity_id = gc.activity_id
        WHERE a.activity_id = ? AND a.is_active = 1
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        http_response_code(404);
        echo json_encode(['error' => 'Activity not found']);
        return;
    }

    if ($activity['game_settings']) {
        $activity['game_settings'] = json_decode($activity['game_settings'], true);
    }
    if ($activity['content_data']) {
        $activity['content_data'] = json_decode($activity['content_data'], true);
    }

    echo json_encode(['success' => true, 'activity' => $activity]);
}