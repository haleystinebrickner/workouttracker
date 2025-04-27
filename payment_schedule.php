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
        if ($_GET['action'] == 'summary') {
            // Get payment summary
            $sql = "SELECT TotalAmountPaid FROM ClientPaymentsTotal WHERE ClientID = ?";
            $stmt = executeQuery($sql, [$client_id]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql = "SELECT NextPaymentDue FROM ClientPaymentDue WHERE ClientID = ?";
            $stmt = executeQuery($sql, [$client_id]);
            $next = $stmt->fetch(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'total_paid' => $total['TotalAmountPaid'] ?? 0,
                'next_payment_date' => $next['NextPaymentDue'] ?? 'Not scheduled'
            ]);
            exit();
        }
        else if ($_GET['action'] == 'history') {
            $start = $_GET['start'];
            $end = $_GET['end'];
            
            $sql = "CALL sp_GetPaymentHistory(?)";
            $stmt = executeQuery($sql, [$client_id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($payments);
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
    <title>Payment Schedule</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Payment Schedule</h1>
            <a href="client_dashboard.php" class="back-btn">Back to Dashboard</a>
        </header>

        <section class="payment-summary">
            <h2>Payment Summary</h2>
            <div id="totalPaid">
                <!-- Will be populated by JavaScript -->
            </div>
            <div id="nextPayment">
                <!-- Will be populated by JavaScript -->
            </div>
        </section>

        <section class="payment-history">
            <h2>Payment History</h2>
            <div class="date-filter">
                <label for="start_date">From:</label>
                <input type="date" id="start_date">
                <label for="end_date">To:</label>
                <input type="date" id="end_date">
                <button onclick="filterPayments()">Filter</button>
            </div>
            <div id="paymentHistory">
                <!-- Will be populated by JavaScript -->
            </div>
        </section>
    </div>

    <script>
    // Your existing JavaScript remains the same
    document.addEventListener('DOMContentLoaded', function() {
        loadPaymentData();
    });

    function loadPaymentData() {
        fetch('payment_schedule.php?action=summary')
            .then(response => response.json())
            .then(data => {
                document.getElementById('totalPaid').innerHTML = `
                    <p><strong>Total Amount Paid:</strong> $${data.total_paid}</p>
                `;
                document.getElementById('nextPayment').innerHTML = `
                    <p><strong>Next Payment Due:</strong> ${data.next_payment_date}</p>
                `;
            });

        filterPayments();
    }

    function filterPayments() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        fetch(`payment_schedule.php?action=history&start=${startDate}&end=${endDate}`)
            .then(response => response.json())
            .then(data => {
                const tableHtml = `
                    <table>
                        <thead>
                            <tr>
                                <th>Payment Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Trainer</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(payment => `
                                <tr>
                                    <td>${payment.PaymentDate}</td>
                                    <td>$${payment.Amount}</td>
                                    <td>${payment.PaymentMethod}</td>
                                    <td>${payment.TrainerName}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                document.getElementById('paymentHistory').innerHTML = tableHtml;
            });
    }
    </script>
</body>
</html>
