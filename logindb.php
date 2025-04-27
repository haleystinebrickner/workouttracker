<?php
session_start();
include 'db_connect.php'; // Database connection file

// Function to safely redirect with error message
/*
function redirectWithError($message) {
    header("Location: login.html?error=" . urlencode($message));
    exit();
}
    */


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $email = trim($_POST['email']);
        $user_type = $_POST['user_type'];
        
        if (empty($email) || empty($user_type)) {
            throw new Exception("Please fill in all fields.");
        }
        
        if ($user_type === 'trainer') {
            $sql = "SELECT TrainerID, Name, Email FROM Trainers WHERE Email = ?";
        } else {
            $sql = "SELECT ClientID, Name, Email, TrainerID FROM Clients WHERE Email = ?";
        }
        
        $stmt = executeQuery($sql, [$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['email'] = $user['Email'];
            $_SESSION['name'] = $user['Name'];
            
            if ($user_type === 'trainer') {
                $_SESSION['trainer_id'] = $user['TrainerID'];
                $_SESSION['user_type'] = 'trainer';
                header("Location: trainer_dashboard.html");
            } else {
                $_SESSION['client_id'] = $user['ClientID'];
                $_SESSION['trainer_id'] = $user['TrainerID'];
                $_SESSION['user_type'] = 'client';
                header("Location: clientdashboard.html");
            }
            exit();
        } else {
            throw new Exception("No account found with this email address.");
        }
    } catch (Exception $e) {
        header("Location: login.html?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If someone tries to access this file directly without POST data
    header("Location: login.html");
    exit();
}
