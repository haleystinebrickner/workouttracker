<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'trainer') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - Workout Wonderland</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
        </header>

        <nav>
            <h2>Manage Clients</h2>
            <ul>
                <li><a href="manage_clients.php">📋 View Clients</a></li>
            </ul>

            <h2>Create & Assign Plans</h2>
            <ul>
                <li><a href="create_plan.php">🏋️‍♂️ Assign Workout Plan</a></li>
                <li><a href="create_nutrition_plan.php">🥗 Assign Nutrition Plan</a></li>
            </ul>

            <h2>Monitor Client's Progress</h2>
            <ul>
                <li><a href="monitor_client_progress.php">📈 View Progress</a></li>
            </ul>
        </nav>

        <footer>
            <p><a href="logout.php" class="logout-btn">Logout</a></p>
        </footer>
    </div>
</body>
</html>
