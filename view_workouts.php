<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['user_id'];

try {
    // Get workouts for the client
    $sql = "SELECT 
                WorkoutDate,
                Exercise,
                Sets,
                Reps,
                WeightLifted_lbs,
                Notes,
                (SELECT MAX(WeightLifted_lbs) 
                 FROM Workouts w2 
                 WHERE w2.ClientID = w1.ClientID 
                 AND w2.Exercise = w1.Exercise) as personal_best
            FROM Workouts w1 
            WHERE ClientID = ? 
            ORDER BY WorkoutDate DESC, Exercise";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get client's goal
    $sql = "SELECT Goal FROM Clients WHERE ClientID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error retrieving workouts: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Workouts</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .workout-card {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .personal-best {
            color: #4CAF50;
            font-weight: bold;
        }
        .workout-date {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 10px;
        }
        .exercise-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .goal-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>My Workouts</h1>
            <a href="client_dashboard.php" class="back-btn">Back to Dashboard</a>
        </header>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($client_info['Goal']) && !empty($client_info['Goal'])): ?>
            <div class="goal-section">
                <h2>Current Goal</h2>
                <p><?php echo htmlspecialchars($client_info['Goal']); ?></p>
            </div>
        <?php endif; ?>

        <?php
        if (!empty($workouts)) {
            $current_date = null;
            foreach ($workouts as $workout) {
                if ($current_date !== $workout['WorkoutDate']) {
                    if ($current_date !== null) {
                        echo "</div>"; // Close previous workout card
                    }
                    $current_date = $workout['WorkoutDate'];
                    echo "<div class='workout-card'>";
                    echo "<div class='workout-date'>" . htmlspecialchars($workout['WorkoutDate']) . "</div>";
                }
                ?>
                <div class="exercise-row">
                    <div>
                        <strong><?php echo htmlspecialchars($workout['Exercise']); ?></strong>
                        <div>
                            <?php echo htmlspecialchars($workout['Sets']); ?> sets x 
                            <?php echo htmlspecialchars($workout['Reps']); ?> reps @ 
                            <?php echo htmlspecialchars($workout['WeightLifted_lbs']); ?> lbs
                        </div>
                        <?php if (!empty($workout['Notes'])): ?>
                            <div><em>Notes: <?php echo htmlspecialchars($workout['Notes']); ?></em></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($workout['WeightLifted_lbs'] == $workout['personal_best']): ?>
                        <div class="personal-best">Personal Best! 🏆</div>
                    <?php endif; ?>
                </div>
                <?php
            }
            if ($current_date !== null) {
                echo "</div>"; // Close last workout card
            }
        } else {
            echo "<p>No workouts found. Your trainer will add workouts to your plan soon!</p>";
        }
        ?>
    </div>
</body>
</html>
