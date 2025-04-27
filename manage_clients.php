<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$trainer_id = $_SESSION['user_id'];

// Fetch current clients
try {
    $sql = "SELECT Name, Email, Phone 
            FROM Clients 
            WHERE TrainerID = CONVERT(INT, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $trainer_id, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading clients: " . $e->getMessage();
}

// Handle new client addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "
            INSERT INTO Clients (
                TrainerID, 
                Name, 
                Email, 
                Phone, 
                DateOfBirth, 
                Goal, 
                Weight_lbs, 
                Height_inches
            ) 
            VALUES (
                CONVERT(INT, ?),
                CONVERT(VARCHAR(100), ?),
                CONVERT(VARCHAR(100), ?),
                CONVERT(VARCHAR(15), ?),
                GETDATE(),
                'New Client',
                CONVERT(DECIMAL(5,2), 150.00),
                CONVERT(DECIMAL(5,2), 65.00)
            )
        ";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters with explicit types
        $stmt->bindValue(1, $trainer_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $_POST['name'], PDO::PARAM_STR);
        $stmt->bindValue(3, $_POST['email'], PDO::PARAM_STR);
        $stmt->bindValue(4, $_POST['phone'], PDO::PARAM_STR);
        
        $stmt->execute();
        
        header("Location: manage_clients.php?success=1");
        exit();
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Violation of UNIQUE KEY constraint') !== false) {
            $error = "Email or phone number already exists. Please use different ones.";
        } else {
            $error = "Error adding client: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Manage Clients</h1>
        </header>

        <section class="content-container">
            <a href="trainer_dashboard.php" class="back-btn">Back to Dashboard</a>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="success">Client added successfully!</div>
            <?php endif; ?>

            <h2>Current Clients</h2>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                </tr>
                <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?php echo htmlspecialchars($client['Name']); ?></td>
                    <td><?php echo htmlspecialchars($client['Email']); ?></td>
                    <td><?php echo htmlspecialchars($client['Phone']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h2>Add New Client</h2>
            <form action="manage_clients.php" method="POST">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="text" name="phone" required>
                </div>
                <button type="submit" class="submit-btn">Add Client</button>
            </form>
        </section>

        <footer>
            <p><a href="logout.php" class="logout-btn">Logout</a></p>
        </footer>
    </div>
</body>
</html>
