<?php
// Start session and include database connection
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID
$username = $_SESSION['username']; // Get the logged-in user's username

// Handle deletion of selected items 
if (isset($_POST['delete'])) {
    if (!empty($_POST['selected_meals'])) {
        foreach ($_POST['selected_meals'] as $meal_id) {
            $meal_id = intval($meal_id);
            $deleteQuery = "DELETE FROM cart WHERE user_id = $user_id AND meal_id = $meal_id";
            mysqli_query($conn, $deleteQuery);
        }
        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit();
    }
}

// Query to fetch the user's cart items
$cartQuery = "SELECT c.meal_id, c.quantity, c.meal_name, c.rice_option, c.rice_price, c.drinks, c.drink_price, m.price, m.image
              FROM cart c 
              JOIN meals m ON c.meal_id = m.id 
              WHERE c.user_id = $user_id";
$cartResult = mysqli_query($conn, $cartQuery);

// Initialize total price variable
$totalPrice = 0;




if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['user_action'])) {
        $action = $_POST['user_action'];

        switch ($action) {
            case 'Home':
                header("Location: user_dashboard.php");
                exit();
            case 'view_cart':
                header("Location: cart.php");
                exit();
            case 'edit_profile':
                header("Location: user_edit.php");
                exit();
            case 'logout':
                session_destroy();
                header("Location: login.php");
                exit();
            default:
                echo "Invalid action!";
        }
        // Redirect to confirmation page
        header("Location: confirmation.php");
        exit();
    } else {
        echo '<script>alert("Please select at least one item to proceed to checkout.");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CART</title>
    <style>
        :root {
            --primary-color: #6A5ACD;
            --secondary-color: #F2F2F2;
            --font-primary: 'Roboto', sans-serif;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            width: 100%;
            padding: 1rem 2rem;
            background-color: var(--secondary-color);
            color: #333;
            text-align: center;
            box-shadow: 0 2px 4px var(--shadow-color);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .back-button {
            text-decoration: none;
            color: #fff;
            text-decoration: underline;
            font-size: 1rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .meal-container {
            width: 90%;
            max-width: 800px;
            margin: 1.5rem auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .meal {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #ddd;
        }

        .meal img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            margin-right: 1.5rem;
            object-fit: cover;
            box-shadow: 0 2px 6px var(--shadow-color);
        }

        .meal h3 {
            font-size: 1.2rem;
            margin: 0;
            color: #333;
            text-align: left;
        }

        .meal p {
            margin: 0.2rem 0;
            color: #666;
            font-size: 0.95rem;
        }

        .meal:last-child {
            border-bottom: none;
        }



        .btn-group {
            display: flex;
            justify-content: space-evenly;
            background-color: var(--secondary-color);
            box-shadow: 0 2px 6px var(--shadow-color);
            padding: 20px 5px 20px 5px;
            position: fixed;
            bottom: 0;
            left: 37.5%;
            right: 37.5%;
            width: 25%;
            margin-bottom: 10px;
            max-width: 800px;
            margin: 1.5rem auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);

            /* Responsive adjustments */
            @media (max-width: 768px) {
                width: 100%;
                /* Full width on smaller screens */
                left: 0;
                right: 0;
                justify-content: space-around;
                /* Adjust button spacing */
            }
        }

        .button {
            padding: 10px 10px;
            margin: 0 5px;
            /* Add 5px margin on each side */
            background-color: var(--primary-color);
            border: none;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color:
                #555;
        }

        h3 {
            color: #333;
            text-align: right;
        }

        input[type="checkbox"] {
            margin-right: 1rem;
            accent-color: var(--primary-color);
        }

        h3.total {
            color: var(--text-color);
            text-align: right;
            margin-top: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
        }

        select {
            border-radius: 10px;
            background-color: #6A5ACD;
            border: 1px solid #6A5ACD;
            color: #fff;
        }

        .action-select {
            padding: 10px;
            font-size: 16px;
            margin-left: 10px;
            border-radius: 10px;
            background-color: var(--primary-color);
            border: 1px solid var(--primary-color);
            color: #fff;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            const totalSelectedPriceDisplay = document.createElement("h3");
            totalSelectedPriceDisplay.style.color = "#333";
            totalSelectedPriceDisplay.innerText = "Total: ₱0.00";
            document.querySelector(".meal-container").appendChild(totalSelectedPriceDisplay);

            function updateSelectedTotalPrice() {
                let selectedTotalPrice = 0;
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const itemTotal = parseFloat(checkbox.dataset.price) || 0;
                        selectedTotalPrice += itemTotal;
                    }
                });
                totalSelectedPriceDisplay.innerText = "Total: ₱" + selectedTotalPrice.toFixed(2);
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener("change", updateSelectedTotalPrice);
            });

            document.querySelector("button[name='checkout']").addEventListener("click", function(e) {
                const isChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

                if (!isChecked) {
                    e.preventDefault(); // Prevent form submission
                    alert("Please select at least one item to proceed to checkout.");
                }
            });

            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
    </script>
    <script>
        function checkoutSelectedItems() {
            const selectedMeals = Array.from(document.querySelectorAll("input[name='selected_meals[]']:checked"))
                .map(meal => meal.value);

            if (selectedMeals.length === 0) {
                alert("Please select at least one item to proceed to checkout.");
                return;
            }

            // Convert array of meal IDs to a JSON string
            const data = new URLSearchParams();
            data.append("selected_meals", JSON.stringify(selectedMeals));

            // Send selected meal IDs to PHP
            fetch("checkout_process.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: data
                })
                .then(response => response.text())
                .then(response => {
                    console.log("Checkout processed:", response);
                    window.location.href = "confirmation.php";
                })
                .catch(error => console.error("Error:", error));
        }
    </script>


</head>

<body>
    <!--div class="header">
        <h1>CART</h1>
        <a href="user_dashboard.php" class="back-button">Back to Stores</a>
    </div-->
    <div class="header">
        <div class="site_name">
            <h2>You Chews</h2>
            <p>IKAW BAHALA</p>
        </div>
        <div class="form-container">
            <form action="user_dashboard.php" method="post">
                <select name="user_action" class="action-select" onchange="this.form.submit()">
                    <option value="">Options</option>
                    <option value="Home">Home</option>
                    <option value="view_cart">View Orders</option>
                    <option value="edit_profile">Edit Profile</option>
                    <option value="logout">Logout</option>
                </select>
            </form>
        </div>
    </div>
    <div class="meal-container">
        <form method="POST">
            <?php
            if (mysqli_num_rows($cartResult) > 0) {
                // Display cart items
                while ($cartItem = mysqli_fetch_assoc($cartResult)) {
                    $itemTotal = ($cartItem['price'] * $cartItem['quantity'] + $cartItem['rice_price'] + $cartItem['drink_price']); // Calculate total for this item
                    $totalPrice += $itemTotal; // Add to total price

                    echo "<div class='meal'>";
                    echo "<input type='checkbox' name='selected_meals[]' value='" . htmlspecialchars($cartItem['meal_id']) . "' data-price='" . $itemTotal . "'>";
                    echo "<img src='" . htmlspecialchars($cartItem['image']) . "' alt='" . htmlspecialchars($cartItem['meal_name']) . "'>";
                    echo "<div>";
                    echo "<h3>Meal Name: " . htmlspecialchars($cartItem['meal_name']) . "</h3>";
                    echo "<h3>Price: " . htmlspecialchars($cartItem['price']) . "</h3>";
                    echo "<p>Quantity: " . htmlspecialchars($cartItem['quantity']) . "</p>";
                    echo "<p>Rice: " . htmlspecialchars($cartItem['rice_option']) . " (₱" . htmlspecialchars($cartItem['rice_price']) . ")</p>";
                    echo "<p>Drink: " . htmlspecialchars($cartItem['drinks']) . " (₱" . htmlspecialchars($cartItem['drink_price']) . ")</p>";
                    echo "<p> Total Price: ₱" . number_format($itemTotal, 2) . "</p>";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<p>Your cart is empty!</p>";
            }
            ?>
            <div class="btn-group">
                <button type="submit" name="delete" class="button">Delete Selected Items</button>
                <button type="button" name="checkout" class="button" onclick="checkoutSelectedItems()">Checkout</button>


            </div>
        </form>
    </div>
</body>

</html>