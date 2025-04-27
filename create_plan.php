<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$trainer_id = $_SESSION['user_id'];

// Fetch clients for this trainer
try {
    $sql = "SELECT ClientID, Name, Email FROM Clients WHERE TrainerID = CONVERT(INT, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $trainer_id, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading clients: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        
        $client_id = $_POST['client_id'];
        $workout_date = $_POST['workout_date'];
        
        // Insert each exercise
        foreach ($_POST['exercises'] as $exercise) {
            $sql = "
                INSERT INTO Workouts (
                    ClientID,
                    WorkoutDate,
                    Exercise,
                    Reps,
                    Sets,
                    WeightLifted_lbs,
                    Notes
                ) VALUES (
                    CONVERT(INT, ?),
                    CONVERT(DATE, ?),
                    CONVERT(VARCHAR(100), ?),
                    CONVERT(INT, ?),
                    CONVERT(INT, ?),
                    CONVERT(DECIMAL(5,2), ?),
                    CONVERT(VARCHAR(255), ?)
                )
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $client_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $workout_date, PDO::PARAM_STR);
            $stmt->bindValue(3, $exercise['name'], PDO::PARAM_STR);
            $stmt->bindValue(4, $exercise['reps'], PDO::PARAM_INT);
            $stmt->bindValue(5, $exercise['sets'], PDO::PARAM_INT);
            $stmt->bindValue(6, $exercise['weight'], PDO::PARAM_STR);
            $stmt->bindValue(7, $exercise['notes'], PDO::PARAM_STR);
            
            $stmt->execute();
        }

        // Handle payment if provided
        if (!empty($_POST['payment_amount'])) {
            $sql = "
                INSERT INTO Payments (
                    ClientID,
                    TrainerID,
                    PaymentDate,
                    Amount,
                    PaymentMethod
                ) VALUES (
                    CONVERT(INT, ?),
                    CONVERT(INT, ?),
                    CONVERT(DATE, ?),
                    CONVERT(DECIMAL(10,2), ?),
                    CONVERT(VARCHAR(50), ?)
                )
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $client_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $trainer_id, PDO::PARAM_INT);
            $stmt->bindValue(3, $_POST['payment_date'] ?? date('Y-m-d'), PDO::PARAM_STR);
            $stmt->bindValue(4, $_POST['payment_amount'], PDO::PARAM_STR);
            $stmt->bindValue(5, $_POST['payment_method'], PDO::PARAM_STR);
            
            $stmt->execute();
        }

        $conn->commit();
        header("Location: create_plan.php?success=1");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error creating plan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Workout Plan</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Create Workout Plan</h1>
        </header>

        <section class="create-plan-container">
            <a href="trainer_dashboard.php" class="back-btn">Back to Dashboard</a>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="success">Workout plan created successfully!</div>
            <?php endif; ?>
            
            <form action="create_plan.php" method="POST" id="workoutForm">
                <div class="form-group">
                    <label for="client_id">Select Client:</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">--Select a client--</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client['ClientID']); ?>">
                                <?php echo htmlspecialchars($client['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Rest of your form HTML remains the same -->
                <div class="form-group">
                    <label for="workout_date">Workout Date:</label>
                    <input type="date" name="workout_date" id="workout_date" required>
                </div>

                <div id="exercises">
                    <div class="exercise-entry">
                        <h3>Exercise 1</h3>
                        <div class="form-group">
                            <label for="exercise_1">Exercise Name:</label>
                            <input type="text" name="exercises[0][name]" id="exercise_1" required>
                        </div>

                        <div class="form-group">
                            <label for="reps_1">Reps:</label>
                            <input type="number" name="exercises[0][reps]" id="reps_1" required>
                        </div>

                        <div class="form-group">
                            <label for="sets_1">Sets:</label>
                            <input type="number" name="exercises[0][sets]" id="sets_1" required>
                        </div>

                        <div class="form-group">
                            <label for="weight_1">Weight (lbs):</label>
                            <input type="number" step="0.1" name="exercises[0][weight]" id="weight_1" required>
                        </div>

                        <div class="form-group">
                            <label for="notes_1">Notes:</label>
                            <textarea name="exercises[0][notes]" id="notes_1"></textarea>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addExercise()" class="add-btn">Add Another Exercise</button>

                <div class="payment-section">
                    <h2>Payment Information (Optional)</h2>
                    
                    <div class="form-group">
                        <label for="payment_date">Payment Date:</label>
                        <input type="date" name="payment_date" id="payment_date">
                    </div>

                    <div class="form-group">
                        <label for="payment_amount">Amount ($):</label>
                        <input type="number" step="0.01" name="payment_amount" id="payment_amount">
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <select name="payment_method" id="payment_method">
                            <option value="">--Select Payment Method--</option>
                            <option value="credit">Credit Card</option>
                            <option value="debit">Debit Card</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Create Workout Plan</button>
            </form>
        </section>
    </div>

    <script>
    // Your existing JavaScript remains the same
    let exerciseCount = 1;
    
    function addExercise() {
        exerciseCount++;
        const exerciseDiv = document.createElement('div');
        exerciseDiv.className = 'exercise-entry';
        exerciseDiv.innerHTML = `
            <h3>Exercise ${exerciseCount}</h3>
            <div class="form-group">
                <label for="exercise_${exerciseCount}">Exercise Name:</label>
                <input type="text" name="exercises[${exerciseCount-1}][name]" id="exercise_${exerciseCount}" required>
            </div>

            <div class="form-group">
                <label for="reps_${exerciseCount}">Reps:</label>
                <input type="number" name="exercises[${exerciseCount-1}][reps]" id="reps_${exerciseCount}" required>
            </div>

            <div class="form-group">
                <label for="sets_${exerciseCount}">Sets:</label>
                <input type="number" name="exercises[${exerciseCount-1}][sets]" id="sets_${exerciseCount}" required>
            </div>

            <div class="form-group">
                <label for="weight_${exerciseCount}">Weight (lbs):</label>
                <input type="number" step="0.1" name="exercises[${exerciseCount-1}][weight]" id="weight_${exerciseCount}" required>
            </div>

            <div class="form-group">
                <label for="notes_${exerciseCount}">Notes:</label>
                <textarea name="exercises[${exerciseCount-1}][notes]" id="notes_${exerciseCount}"></textarea>
            </div>
        `;
        document.getElementById('exercises').appendChild(exerciseDiv);
    }
    </script>
</body>
</html>