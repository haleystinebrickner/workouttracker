<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['user_id'];

// Handle AJAX requests
if (isset($_GET['action'])) {
    try {
        if ($_GET['action'] == 'workouts') {
            $start = $_GET['start'];
            $end = $_GET['end'];
            $sql = "CALL sp_GetClientProgress(?, ?, ?)";
            $stmt = executeQuery($sql, [$client_id, $start, $end]);
            $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json');
            echo json_encode($workouts);
            exit();
        }
        else if ($_GET['action'] == 'nutrition') {
            $date = $_GET['date'];
            $sql = "SELECT * FROM Nutrition WHERE ClientID = ? AND Date = ?";
            $stmt = executeQuery($sql, [$client_id, $date]);
            $nutrition = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json');
            echo json_encode($nutrition);
            exit();
        }
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Progress</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Your Progress</h1>
        </header>

        <section class="progress-container">
            <a href="client_dashboard.php" class="back-btn">Back to Dashboard</a>
            
            <div class="profile-info">
                <h2>Profile Information</h2>
                <div id="profileData">
                    <?php
                    try {
                        $sql = "SELECT * FROM Clients WHERE ClientID = ?";
                        $stmt = executeQuery($sql, [$client_id]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo "<p>Name: " . htmlspecialchars($profile['Name']) . "</p>";
                        echo "<p>Current Weight: " . htmlspecialchars($profile['Weight_lbs']) . " lbs</p>";
                        echo "<p>Height: " . htmlspecialchars($profile['Height_inches']) . " inches</p>";
                    } catch(PDOException $e) {
                        echo "Error loading profile data";
                    }
                    ?>
                </div>
            </div>

            <!-- Rest of your HTML remains the same -->
            <div class="workout-history">
                <h2>Workout History</h2>
                <div class="date-filter">
                    <label for="start_date">From:</label>
                    <input type="date" id="start_date" name="start_date">
                    <label for="end_date">To:</label>
                    <input type="date" id="end_date" name="end_date">
                    <button onclick="filterWorkouts()">Filter</button>
                </div>
                <div id="workoutHistory">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <div class="nutrition-tracking">
                <h2>Nutrition History</h2>
                <div class="date-filter">
                    <label for="nutrition_date">Date:</label>
                    <input type="date" id="nutrition_date" name="nutrition_date">
                    <button onclick="viewNutrition()">View</button>
                </div>
                <div id="nutritionData">
                    <!-- Will be populated by JavaScript -->
                </div>
                <div id="caloriesSummary">
                    <!-- Will show daily calorie totals -->
                </div>
            </div>
        </section>
    </div>

    <script>
    // Your existing JavaScript remains the same
    function filterWorkouts() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        fetch(`view_progress.php?action=workouts&start=${startDate}&end=${endDate}`)
            .then(response => response.json())
            .then(data => {
                const workoutDiv = document.getElementById('workoutHistory');
                // Implementation details...
            });
    }

    function viewNutrition() {
        const date = document.getElementById('nutrition_date').value;
        
        fetch(`view_progress.php?action=nutrition&date=${date}`)
            .then(response => response.json())
            .then(data => {
                const nutritionDiv = document.getElementById('nutritionData');
                // Implementation details...
            });
    }
    </script>
</body>
</html>
