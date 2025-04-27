<?php
session_start();
include 'db_connect.php'; // Database connection file


// Modify the session check to be more specific
if (!isset($_SESSION['user_id'])) {
    echo "No user_id in session";
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'client') {
    echo "Not a client user type";
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['user_id'];

// Fetch current goals
try {
    $sql = "SELECT Goal FROM Clients WHERE ClientID = ?";
    $stmt = executeQuery($sql, [$client_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $goals = [];
    if ($result && $result['Goal']) {
        $goals[] = ['goal_name' => 'Current Goal', 'goal_amount' => $result['Goal']];
    }

    // Handle new goal submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['goal_name'], $_POST['goal_amount'])) {
        $goal_name = trim($_POST['goal_name']);
        $goal_amount = trim($_POST['goal_amount']);
        
        if (!empty($goal_name) && !empty($goal_amount)) {
            // Use the stored procedure to update client goal
            $sql = "CALL sp_UpdateClientGoal(?, ?)";
            executeQuery($sql, [$client_id, $goal_amount]);
            header("Location: set_goals.php");
            exit();
        }
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Fitness Goals - Workout Wonderland</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Your Fitness Goals</h1>
        </header>

        <section class="goals-container">
            <a href="client_dashboard.php" class="back-btn">Back to Dashboard</a>
            
            <h2>Current Goals</h2>
            <ul class="goals-list">
                <?php foreach ($goals as $goal): ?>
                    <li class="goal-item">
                        <?php echo htmlspecialchars($goal['goal_name']) . ": " . htmlspecialchars($goal['goal_amount']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <h2>Add a New Goal</h2>
            <form action="set_goals.php" method="POST" class="goal-form">
                <label for="goal_name">Goal Name:</label>
                <input type="text" id="goal_name" name="goal_name" required>
                
                <label for="goal_amount">Goal Amount:</label>
                <input type="text" id="goal_amount" name="goal_amount" required>
                
                <button type="submit" class="submit-btn">Add Goal</button>
            </form>
        </section>

        <footer>
            <p><a href="logout.php" class="logout-btn">Logout</a></p>
        </footer>
    </div>
</body>
</html>
