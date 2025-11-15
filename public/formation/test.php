<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=localhost;dbname=autoformation", "debian", "Adr4r!");
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $req = $pdo->prepare("SELECT * FROM notification GROUP BY link, category, user_id HAVING COUNT(*) > 1;");
    $req->execute();
    $notifications = $req->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    print_r($notifications);
    echo "</pre>";

    $ids = [];
    foreach ($notifications as $row) {
        $ids[] = $row['id'];
    }

    if (!empty($ids)) {
        $ids = implode(',', $ids);

        var_dump($ids);

        // // Prepare the DELETE statement
        // $sql = "DELETE FROM notification WHERE id IN(:ids)";

        // // Prepare the statement
        // $stmt = $pdo->prepare($sql);

        // // Bind the parameter (securely binds the ID)
        // $stmt->bindParam(':ids', $ids);

        // // Execute the statement
        // if ($stmt->execute()) {
        //     echo "Record deleted successfully";
        // } else {
        //     echo "Error deleting record";
        // }
    } else {
        $ids = 0;
    }
} catch (PDOException $e) {
    // Handle errors
    echo "Error: " . $e->getMessage();
}
