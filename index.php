<?php
session_start();

// Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "bro";

$con = new mysqli($host, $user, $pass, $db);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Prices of Vegetables
$prices = [
    "corn" => ["price" => 20, "image" => "images/corn.jpg"],
    "carrots" => ["price" => 30, "image" => "images/carrots.jpg"],
    "potato" => ["price" => 25, "image" => "images/potato.jpg"],
];

// Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_cart"])) {
    $vegetable = $_POST['vegetable'];
    $quantity = intval($_POST['quantity']);
    $base_price = $prices[$vegetable]["price"] ?? 0; // FIXED PRICE ERROR
    $total_price = $base_price * $quantity;
    $image = $prices[$vegetable]["image"];

    if ($base_price > 0 && $quantity > 0) {
        $stmt = $con->prepare("INSERT INTO cart (vegetable, quantity, price, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sids", $vegetable, $quantity, $total_price, $image);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php");
    exit();
}

// Submit Order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_order"])) {
    $cart_items = $con->query("SELECT * FROM cart");

    while ($row = $cart_items->fetch_assoc()) {
        $stmt = $con->prepare("INSERT INTO orders (vegetable, quantity, price) VALUES (?, ?, ?)");
        $stmt->bind_param("sid", $row["vegetable"], $row["quantity"], $row["price"]);
        $stmt->execute();
        $stmt->close();
    }

    // Clear Cart after ordering
    $con->query("DELETE FROM cart");
    header("Location: index.php");
    exit();
}

// Remove an Item
if (isset($_GET["remove"])) {
    $remove_id = intval($_GET["remove"]);
    $con->query("DELETE FROM cart WHERE id = $remove_id");
    header("Location: index.php");
    exit();
}

// Clear Cart
if (isset($_GET["clear"])) {
    $con->query("DELETE FROM cart");
    header("Location: index.php");
    exit();
}

// Calculate Total Price for Cart
$total = 0;
$cart_items = $con->query("SELECT * FROM cart");
while ($row = $cart_items->fetch_assoc()) {
    $total += $row['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vegetable Market</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <div class="container mt-4">
        <h2 class="text-center text-success">Welcome to the Vegetable Market! 🥦🥕</h2>

        <!-- Item Selection -->
        <form method="POST">
            <div class="row mt-4">
                <div class="col-md-4">
                    <label for="itemSelect">Choose a Vegetable:</label>
                    <select id="itemSelect" name="vegetable" class="form-select">
                        <option value="corn">Corn - ₱20</option>
                        <option value="carrots">Carrot - ₱30</option>
                        <option value="potato">Potato - ₱25</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="add_to_cart" class="btn btn-danger">Add to Cart</button>
                </div>
            </div>
        </form>

        <!-- Shopping Cart Table -->
        <h4 class="mt-4 text-center">🛒 Shopping Cart</h4>
        <table class="table table-bordered mt-3">
            <thead class="bg-success text-white">
                <tr>
                    <th>Image</th>
                    <th>Vegetable</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cart_items = $con->query("SELECT * FROM cart");
                while ($row = $cart_items->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><img src='" . $row['image'] . "' width='50' height='50'></td>";
                    echo "<td>" . $row['vegetable'] . "</td>";
                    echo "<td>" . $row['quantity'] . "</td>";
                    echo "<td>₱" . $row['price'] . "</td>";
                    echo "<td><a href='index.php?remove=" . $row['id'] . "' class='btn btn-danger'>Remove</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Total Price -->
        <h4 class="text-end">Total Price: ₱<span><?= $total; ?></span></h4>

        <!-- Buttons -->
        <div class="text-center mt-3">
            <form method="POST">
                <button type="submit" name="submit_order" class="btn btn-primary">Submit Order</button>
            </form>
            <a href="?clear=1" class="btn btn-danger">Clear Cart</a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
