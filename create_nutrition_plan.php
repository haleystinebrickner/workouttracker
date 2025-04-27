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
    $sql = "SELECT ClientID, Name FROM Clients WHERE TrainerID = CONVERT(INT, ?)";
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
        $date = $_POST['date'];
        
        // Insert each meal
        foreach ($_POST['meals'] as $meal) {
            $sql = "
                INSERT INTO Nutrition (
                    ClientID,
                    Date,
                    MealTime,
                    Food,
                    Calories_kcal,
                    Protein_g,
                    Carbs_g,
                    Fats_g
                ) VALUES (
                    CONVERT(INT, ?),
                    CONVERT(DATE, ?),
                    CONVERT(VARCHAR(20), ?),
                    CONVERT(VARCHAR(255), ?),
                    CONVERT(INT, ?),
                    CONVERT(INT, ?),
                    CONVERT(INT, ?),
                    CONVERT(INT, ?)
                )
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $client_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $date, PDO::PARAM_STR);
            $stmt->bindValue(3, $meal['meal_time'], PDO::PARAM_STR);
            $stmt->bindValue(4, $meal['food'], PDO::PARAM_STR);
            $stmt->bindValue(5, $meal['calories'], PDO::PARAM_INT);
            $stmt->bindValue(6, $meal['protein'], PDO::PARAM_INT);
            $stmt->bindValue(7, $meal['carbs'], PDO::PARAM_INT);
            $stmt->bindValue(8, $meal['fats'], PDO::PARAM_INT);
            
            $stmt->execute();
        }
        
        $conn->commit();
        header("Location: create_nutrition_plan.php?success=1");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error assigning nutrition plan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Nutrition Plan</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .meal-entry {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .remove-meal {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        #add-meal {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Assign Nutrition Plan</h1>
        </header>

        <section class="content-container">
            <a href="trainer_dashboard.php" class="back-btn">Back to Dashboard</a>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="success">Nutrition plan assigned successfully!</div>
            <?php endif; ?>

            <form id="nutrition-form" method="POST">
                <div class="form-group">
                    <label for="client_id">Select Client:</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Select a client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client['ClientID']); ?>">
                                <?php echo htmlspecialchars($client['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" name="date" id="date" required>
                </div>

                <div id="meals-container">
                    <!-- Meal entries will be added here -->
                </div>

                <button type="button" id="add-meal">Add Meal</button>
                <button type="submit" class="submit-btn">Assign Nutrition Plan</button>
            </form>
        </section>
    </div>

    <script>
        function createMealEntry(index) {
            return `
                <div class="meal-entry">
                    <button type="button" class="remove-meal" onclick="removeMeal(this)">Remove Meal</button>
                    
                    <div class="form-group">
                        <label for="meals[${index}][meal_time]">Meal Time:</label>
                        <select name="meals[${index}][meal_time]" required>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Morning Snack">Morning Snack</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Afternoon Snack">Afternoon Snack</option>
                            <option value="Dinner">Dinner</option>
                            <option value="Evening Snack">Evening Snack</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="meals[${index}][food]">Food Items:</label>
                        <textarea name="meals[${index}][food]" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="meals[${index}][calories]">Calories (kcal):</label>
                        <input type="number" name="meals[${index}][calories]" required min="0">
                    </div>

                    <div class="form-group">
                        <label for="meals[${index}][protein]">Protein (g):</label>
                        <input type="number" name="meals[${index}][protein]" required min="0">
                    </div>

                    <div class="form-group">
                        <label for="meals[${index}][carbs]">Carbs (g):</label>
                        <input type="number" name="meals[${index}][carbs]" required min="0">
                    </div>

                    <div class="form-group">
                        <label for="meals[${index}][fats]">Fats (g):</label>
                        <input type="number" name="meals[${index}][fats]" required min="0">
                    </div>
                </div>
            `;
        }

        let mealIndex = 0;
        
        document.getElementById('add-meal').addEventListener('click', function() {
            const container = document.getElementById('meals-container');
            container.insertAdjacentHTML('beforeend', createMealEntry(mealIndex++));
        });

        function removeMeal(button) {
            button.closest('.meal-entry').remove();
        }

        // Add initial meal entry
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('add-meal').click();
        });
    </script>
</body>
</html> 