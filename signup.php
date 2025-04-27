<?php
session_start();
include 'db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Common data for both user types
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $user_type = $_POST['user_type'];

        if ($user_type === 'trainer') {
            // Insert into Trainers table using OUTPUT clause
            $sql = "INSERT INTO Trainers (Name, Email, Phone, Specialization, Certification, ExperienceYears) 
                    OUTPUT INSERTED.TrainerID
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $name,
                $email,
                $phone,
                $_POST['specialization'] ?? '',
                $_POST['certification'] ?? '',
                intval($_POST['experience_years'] ?? 0)
            ]);
            
            // Get the inserted ID
            $trainer_id = $stmt->fetch(PDO::FETCH_COLUMN);
            
            $_SESSION['user_id'] = $trainer_id;
            $_SESSION['user_type'] = 'trainer';
            $_SESSION['name'] = $name;
            
            $conn->commit();
            header("Location: trainer_dashboard.php");
            exit();

        } else if ($user_type === 'client') {
            // Insert into Clients table using OUTPUT clause
            $sql = "INSERT INTO Clients (Name, Email, Phone, DateOfBirth, Goal, Weight_lbs, Height_inches) 
                    OUTPUT INSERTED.ClientID
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            // Convert weight and height to proper decimal values
            $weight = !empty($_POST['weight_lbs']) ? floatval($_POST['weight_lbs']) : 0.00;
            $height = !empty($_POST['height_inches']) ? floatval($_POST['height_inches']) : 0.00;
            
            $stmt->execute([
                $name,
                $email,
                $phone,
                $_POST['date_of_birth'] ?? '',
                $_POST['goal'] ?? '',
                $weight,  // Will be properly handled as DECIMAL(5,2)
                $height   // Will be properly handled as DECIMAL(5,2)
            ]);
            
            // Get the inserted ID
            $client_id = $stmt->fetch(PDO::FETCH_COLUMN);
            
            $_SESSION['user_id'] = $client_id;
            $_SESSION['user_type'] = 'client';
            $_SESSION['name'] = $name;
            
            $conn->commit();
            header("Location: client_dashboard.php");
            exit();
        }

    } catch(PDOException $e) {
        $conn->rollBack();
        if (strpos($e->getMessage(), 'Violation of UNIQUE KEY constraint') !== false) {
            $error = "Email already exists. Please use a different email.";
        } else {
            $error = "Database error: " . $e->getMessage(); // Show the actual error for debugging
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Personal Trainer Platform</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function toggleFields() {
            const userType = document.getElementById('user_type').value;
            document.getElementById('trainer-fields').style.display = 
                userType === 'trainer' ? 'block' : 'none';
            document.getElementById('client-fields').style.display = 
                userType === 'client' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Create New Account</h1>
        </header>

        <section class="signup-form">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Common fields -->
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>

                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" required>

                <label for="user_type">Sign Up As:</label>
                <select id="user_type" name="user_type" required onchange="toggleFields()">
                    <option value="">Select Account Type</option>
                    <option value="trainer">Trainer</option>
                    <option value="client">Client</option>
                </select>

                <!-- Trainer-specific fields -->
                <div id="trainer-fields" style="display: none;">
                    <label for="specialization">Specialization:</label>
                    <input type="text" id="specialization" name="specialization">

                    <label for="certification">Certification:</label>
                    <input type="text" id="certification" name="certification">

                    <label for="experience_years">Years of Experience:</label>
                    <input type="number" id="experience_years" name="experience_years" min="0">
                </div>

                <!-- Client-specific fields -->
                <div id="client-fields" style="display: none;">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="text" id="date_of_birth" name="date_of_birth" placeholder="Enter your date of birth">

                    <label for="goal">Fitness Goal:</label>
                    <textarea id="goal" name="goal"></textarea>

                    <label for="weight">Weight (lbs):</label>
                    <input type="number" step="0.01" id="weight" name="weight_lbs">

                    <label for="height">Height (inches):</label>
                    <input type="number" step="0.01" id="height" name="height_inches">
                </div>

                <button type="submit">Create Account</button>
            </form>

            <p>Already have an account? <a href="login.php">Log in here</a></p>
        </section>
    </div>
</body>
</html>
