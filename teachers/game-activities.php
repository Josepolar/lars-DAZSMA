<?php
require_once __DIR__ . '/../Database/database.php';

class GameActivity {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new gamified activity
    public function createGameActivity($teacherId, $data) {
        try {
            $this->pdo->beginTransaction();

            // Insert basic activity info
            $stmt = $this->pdo->prepare("
                INSERT INTO activities (
                    title, description, teacher_id, subject_id, 
                    activity_type, total_points, time_limit, 
                    due_date, game_settings
                ) VALUES (
                    :title, :description, :teacher_id, :subject_id,
                    :activity_type, :total_points, :time_limit,
                    :due_date, :game_settings
                )
            ");

            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':teacher_id' => $teacherId,
                ':subject_id' => $data['subject_id'],
                ':activity_type' => $data['activity_type'],
                ':total_points' => $data['total_points'],
                ':time_limit' => $data['time_limit'],
                ':due_date' => $data['due_date'],
                ':game_settings' => json_encode($data['game_settings'])
            ]);

            $activityId = $this->pdo->lastInsertId();

            // Insert game-specific content
            $stmt = $this->pdo->prepare("
                INSERT INTO game_content (
                    activity_id, content_type, content_data, difficulty_level
                ) VALUES (
                    :activity_id, :content_type, :content_data, :difficulty_level
                )
            ");

            $stmt->execute([
                ':activity_id' => $activityId,
                ':content_type' => $this->getContentType($data['activity_type']),
                ':content_data' => json_encode($data['content_data']),
                ':difficulty_level' => $data['difficulty_level']
            ]);

            $this->pdo->commit();
            return ['success' => true, 'activity_id' => $activityId];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Record student's game progress
    public function recordProgress($studentId, $activityId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO game_progress (
                    student_id, activity_id, score, time_taken,
                    completion_percentage, correct_answers, total_attempts
                ) VALUES (
                    :student_id, :activity_id, :score, :time_taken,
                    :completion_percentage, :correct_answers, :total_attempts
                )
            ");

            $stmt->execute([
                ':student_id' => $studentId,
                ':activity_id' => $activityId,
                ':score' => $data['score'],
                ':time_taken' => $data['time_taken'],
                ':completion_percentage' => $data['completion_percentage'],
                ':correct_answers' => $data['correct_answers'],
                ':total_attempts' => $data['total_attempts']
            ]);

            $this->checkAndAwardAchievements($studentId, $activityId, $data);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Check and award achievements based on student performance
    private function checkAndAwardAchievements($studentId, $activityId, $data) {
        // Perfect Score Achievement
        if ($data['score'] === $data['total_points']) {
            $this->awardAchievement($studentId, $activityId, 'perfect_score');
        }

        // Speed Master Achievement
        if ($data['time_taken'] < ($data['expected_time'] * 0.75)) {
            $this->awardAchievement($studentId, $activityId, 'speed_master');
        }

        // Practice Streak Achievement
        $streak = $this->getStudentStreak($studentId);
        if ($streak >= 5) {
            $this->awardAchievement($studentId, $activityId, 'practice_streak');
        }
    }

    // Get activity statistics for teachers
    public function getActivityStats($activityId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT student_id) as total_students,
                AVG(score) as average_score,
                MAX(score) as highest_score,
                MIN(score) as lowest_score,
                AVG(time_taken) as average_time
            FROM game_progress
            WHERE activity_id = :activity_id
        ");

        $stmt->execute([':activity_id' => $activityId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getContentType($activityType) {
        $contentTypes = [
            'crossword' => 'crossword_grid',
            'flashcards' => 'flashcard_deck',
            'speed_typing' => 'typing_exercise'
        ];
        return $contentTypes[$activityType] ?? null;
    }

    private function awardAchievement($studentId, $activityId, $achievementType) {
        $stmt = $this->pdo->prepare("
            INSERT INTO game_achievements (
                student_id, activity_id, achievement_type
            ) VALUES (
                :student_id, :activity_id, :achievement_type
            )
        ");

        $stmt->execute([
            ':student_id' => $studentId,
            ':activity_id' => $activityId,
            ':achievement_type' => $achievementType
        ]);
    }

    private function getStudentStreak($studentId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT DATE(last_played)) as streak
            FROM game_progress
            WHERE student_id = :student_id
            AND last_played >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        ");

        $stmt->execute([':student_id' => $studentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['streak'] ?? 0;
    }
}
?>