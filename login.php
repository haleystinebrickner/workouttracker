<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $email = trim($_POST['email']);
        $user_type = trim($_POST['user_type']);
        
        // Validate required fields
        if (empty($email) || empty($user_type)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        // First try Trainers table
        if ($user_type == 'trainer') {
            $sql = "SELECT TrainerID, Name, Email FROM Trainers WHERE Email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['TrainerID'];
                $_SESSION['name'] = $user['Name'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['user_type'] = 'trainer';
                header("Location: trainer_dashboard.php");
                exit();
            }
        }
        // Then try Clients table
        else if ($user_type == 'client') {
            $sql = "SELECT ClientID, Name, Email FROM Clients WHERE Email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['ClientID'];
                $_SESSION['name'] = $user['Name'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['user_type'] = 'client';
                header("Location: client_dashboard.php");
                exit();
            }
        }

        // If we get here, login failed
        throw new Exception("Invalid email or account type");
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Personal Trainer Platform</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Login to Your Account</h2>
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="user_type">Account Type:</label>
                    <select id="user_type" name="user_type" required>
                        <option value="">Select Account Type</option>
                        <option value="trainer">Trainer</option>
                        <option value="client">Client</option>
                    </select>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="links">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
