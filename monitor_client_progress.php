<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$trainer_id = $_SESSION['user_id'];

// Handle AJAX requests
if (isset($_GET['action'])) {
    try {
        $client_id = $_GET['client_id'];
        
        switch($_GET['action']) {
            case 'profile':
                $sql = "SELECT 
                            c.*,
                            (SELECT COUNT(*) FROM Workouts w WHERE w.ClientID = c.ClientID) as total_workouts,
                            (SELECT COUNT(DISTINCT Date) FROM Nutrition n WHERE n.ClientID = c.ClientID) as total_nutrition_days
                        FROM Clients c 
                        WHERE c.ClientID = ? AND c.TrainerID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$client_id, $trainer_id]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get latest measurements
                $sql = "SELECT Weight_lbs, Height_inches FROM Clients WHERE ClientID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$client_id]);
                $measurements = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $profile['measurements'] = $measurements;
                
                header('Content-Type: application/json');
                echo json_encode($profile);
                exit();
                
            case 'workouts':
                $sql = "SELECT 
                            w.*,
                            (SELECT MAX(WeightLifted_lbs) 
                             FROM Workouts 
                             WHERE ClientID = w.ClientID 
                             AND Exercise = w.Exercise) as personal_best
                        FROM Workouts w 
                        WHERE w.ClientID = ? 
                        AND w.WorkoutDate BETWEEN ? AND ?
                        ORDER BY w.WorkoutDate DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$client_id, $_GET['start'], $_GET['end']]);
                $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode($workouts);
                exit();
                
            case 'nutrition':
                $sql = "SELECT 
                            n.*,
                            (SELECT SUM(Calories_kcal) 
                             FROM Nutrition 
                             WHERE ClientID = n.ClientID 
                             AND Date = n.Date) as total_daily_calories
                        FROM Nutrition n 
                        WHERE n.ClientID = ? 
                        AND n.Date = ?
                        ORDER BY 
                            CASE n.MealTime 
                                WHEN 'Breakfast' THEN 1 
                                WHEN 'Morning Snack' THEN 2 
                                WHEN 'Lunch' THEN 3 
                                WHEN 'Afternoon Snack' THEN 4 
                                WHEN 'Dinner' THEN 5 
                                WHEN 'Evening Snack' THEN 6 
                            END";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$client_id, $_GET['date']]);
                $nutrition = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode(['meals' => $nutrition]);
                exit();
                
            case 'goals':
                $sql = "SELECT Goal, Weight_lbs as current_weight FROM Clients WHERE ClientID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$client_id]);
                $goals = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get progress data
                $sql = "SELECT 
                            COUNT(DISTINCT WorkoutDate) as workout_days,
                            COUNT(*) as total_exercises,
                            MAX(WeightLifted_lbs) as max_weight
                        FROM Workouts 
                        WHERE ClientID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$client_id]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'goals' => $goals,
                    'progress' => $progress
                ]);
                exit();
        }
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Fetch clients for dropdown
try {
    $sql = "SELECT ClientID, Name FROM Clients WHERE TrainerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$trainer_id]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading clients: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Client Progress</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .progress-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .goal-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            margin-top: 5px;
        }
        .progress-fill {
            height: 100%;
            background: #4CAF50;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Monitor Client Progress</h1>
            <a href="trainer_dashboard.php" class="back-btn">Back to Dashboard</a>
        </header>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="client-selection">
            <label for="client_id">Select Client:</label>
            <select name="client_id" id="client_id" onchange="loadClientData()">
                <option value="">--Select a client--</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo htmlspecialchars($client['ClientID']); ?>">
                        <?php echo htmlspecialchars($client['Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </section>

        <div id="clientData" style="display: none;">
            <section id="profileSection" class="progress-card">
                <h2>Client Profile</h2>
                <div id="profileData"></div>
            </section>

            <section id="goalsSection" class="progress-card">
                <h2>Goals and Progress</h2>
                <div id="goalsData"></div>
            </section>

            <section id="workoutsSection" class="progress-card">
                <h2>Recent Workouts</h2>
                <div id="workoutData"></div>
            </section>

            <section id="nutritionSection" class="progress-card">
                <h2>Nutrition Tracking</h2>
                <input type="date" id="nutritionDate" onchange="loadNutritionData()">
                <div id="nutritionData"></div>
            </section>
        </div>
    </div>

    <script>
    function loadClientData() {
        const clientId = document.getElementById('client_id').value;
        if (!clientId) return;

        document.getElementById('clientData').style.display = 'block';
        
        // Load profile
        fetch(`monitor_client_progress.php?action=profile&client_id=${clientId}`)
            .then(response => response.json())
            .then(data => {
                const profileHtml = `
                    <p><strong>Name:</strong> ${data.Name}</p>
                    <p><strong>Email:</strong> ${data.Email}</p>
                    <p><strong>Phone:</strong> ${data.Phone}</p>
                    <p><strong>Current Weight:</strong> ${data.Weight_lbs} lbs</p>
                    <p><strong>Height:</strong> ${data.Height_inches} inches</p>
                    <p><strong>Total Workouts:</strong> ${data.total_workouts}</p>
                    <p><strong>Days with Nutrition Tracking:</strong> ${data.total_nutrition_days}</p>
                `;
                document.getElementById('profileData').innerHTML = profileHtml;
            });

        // Load goals and progress
        fetch(`monitor_client_progress.php?action=goals&client_id=${clientId}`)
            .then(response => response.json())
            .then(data => {
                const goalsHtml = `
                    <div class="goal-item">
                        <h3>Current Goal</h3>
                        <p>${data.goals.Goal || 'No goal set'}</p>
                    </div>
                    <div class="goal-item">
                        <h3>Progress Overview</h3>
                        <p>Workout Days: ${data.progress.workout_days}</p>
                        <p>Total Exercises Completed: ${data.progress.total_exercises}</p>
                        <p>Maximum Weight Lifted: ${data.progress.max_weight} lbs</p>
                    </div>
                `;
                document.getElementById('goalsData').innerHTML = goalsHtml;
            });

        // Load recent workouts
        const today = new Date();
        const thirtyDaysAgo = new Date(today.setDate(today.getDate() - 30));
        fetch(`monitor_client_progress.php?action=workouts&client_id=${clientId}&start=${thirtyDaysAgo.toISOString().split('T')[0]}&end=${new Date().toISOString().split('T')[0]}`)
            .then(response => response.json())
            .then(data => {
                const workoutsHtml = data.length ? `
                    <table>
                        <tr>
                            <th>Date</th>
                            <th>Exercise</th>
                            <th>Sets</th>
                            <th>Reps</th>
                            <th>Weight (lbs)</th>
                            <th>Personal Best</th>
                        </tr>
                        ${data.map(workout => `
                            <tr>
                                <td>${workout.WorkoutDate}</td>
                                <td>${workout.Exercise}</td>
                                <td>${workout.Sets}</td>
                                <td>${workout.Reps}</td>
                                <td>${workout.WeightLifted_lbs}</td>
                                <td>${workout.personal_best}</td>
                            </tr>
                        `).join('')}
                    </table>
                ` : '<p>No recent workouts found.</p>';
                document.getElementById('workoutData').innerHTML = workoutsHtml;
            });

        // Set up nutrition date picker
        document.getElementById('nutritionDate').value = new Date().toISOString().split('T')[0];
        loadNutritionData();
    }

    function loadNutritionData() {
        const clientId = document.getElementById('client_id').value;
        const date = document.getElementById('nutritionDate').value;
        
        fetch(`monitor_client_progress.php?action=nutrition&client_id=${clientId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                const nutritionHtml = data.meals.length ? `
                    <table>
                        <tr>
                            <th>Time</th>
                            <th>Food</th>
                            <th>Calories</th>
                            <th>Protein (g)</th>
                            <th>Carbs (g)</th>
                            <th>Fats (g)</th>
                        </tr>
                        ${data.meals.map(meal => `
                            <tr>
                                <td>${meal.MealTime}</td>
                                <td>${meal.Food}</td>
                                <td>${meal.Calories_kcal}</td>
                                <td>${meal.Protein_g}</td>
                                <td>${meal.Carbs_g}</td>
                                <td>${meal.Fats_g}</td>
                            </tr>
                        `).join('')}
                        <tr class="total">
                            <td colspan="2"><strong>Daily Total</strong></td>
                            <td><strong>${data.meals[0].total_daily_calories}</strong></td>
                            <td><strong>${data.meals.reduce((sum, meal) => sum + meal.Protein_g, 0)}</strong></td>
                            <td><strong>${data.meals.reduce((sum, meal) => sum + meal.Carbs_g, 0)}</strong></td>
                            <td><strong>${data.meals.reduce((sum, meal) => sum + meal.Fats_g, 0)}</strong></td>
                        </tr>
                    </table>
                ` : '<p>No nutrition data found for this date.</p>';
                document.getElementById('nutritionData').innerHTML = nutritionHtml;
            });
    }
    </script>
</body>
</html>
