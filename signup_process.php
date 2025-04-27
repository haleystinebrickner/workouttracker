<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $user_type = $_POST['user_type'];
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($phone) || empty($user_type)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if ($user_type === 'trainer') {
            // Insert trainer
            $sql = "INSERT INTO Trainers (Name, Email, Phone, Specialization, Certification, ExperienceYears) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [
                $name,
                $email,
                $phone,
                $_POST['specialization'] ?? '',
                $_POST['certification'] ?? '',
                intval($_POST['experience_years'] ?? 0)
            ];
        } else {
            // Insert client
            $sql = "INSERT INTO Clients (Name, Email, Phone, DateOfBirth, Goal, Weight_lbs, Height_inches) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $name,
                $email,
                $phone,
                $_POST['date_of_birth'] ?? null,
                $_POST['goal'] ?? '',
                floatval($_POST['weight_lbs'] ?? 0),
                floatval($_POST['height_inches'] ?? 0)
            ];
        }
        
        // Execute the query using the helper function
        executeQuery($sql, $params);
        
        // Redirect to login page with success message
        header("Location: login.html?signup=success");
        exit();
        
    } catch (Exception $e) {
        // Redirect back to signup page with error message
        header("Location: signup.html?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If accessed directly without POST data
    header("Location: signup.html");
    exit();
}
?>