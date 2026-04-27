<?php
// supabase.php - Helper for Supabase REST API
// Usage: require_once __DIR__ . '/../supabase.php';

class SupabaseClient {
    private $projectUrl;
    private $apiKey;
    private $restUrl;
    private $headers;

    public function __construct($projectUrl, $apiKey) {
        $this->projectUrl = rtrim($projectUrl, '/');
        $this->apiKey = $apiKey;
        $this->restUrl = $this->projectUrl . '/rest/v1';
        $this->headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];
    }

    // Query users table for teacher login
    public function getTeacherByEmail($email) {
        $url = $this->restUrl . '/users?email=eq.' . urlencode($email) . '&role_id=eq.3&select=user_id,password,first_name,last_name&limit=1';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data[0] ?? null;
        }
        return null;
    }
}
