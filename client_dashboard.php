<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Workout Wonderland</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
        </header>

        <nav>
            <h2>Workout & Goals</h2>
            <ul>
                <li><a href="view_workouts.php">🏋️ View Workouts</a></li>
                <li><a href="set_goals.php">🎯 Set Fitness Goals</a></li>
            </ul>

            <h2>Progress & Payments</h2>
            <ul>
                <li><a href="view_progress.php">📈 View Progress</a></li>
                <li><a href="payment_schedule.php">💳 See Payment Schedule</a></li>
            </ul>
        </nav>

        <footer>
            <p><a href="logout.php" class="logout-btn">Logout</a></p>
        </footer>
    </div>
</body>
</html>
