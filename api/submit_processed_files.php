<?php
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

try {
   
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Read incoming JSON data
    $data = json_decode(file_get_contents("php://input"), true);

    // Extract parameters from the received data
    $uploadId = $data['id'] ?? '';
    $pdf = $data['pdf'] ?? '';
    $json = $data['json'] ?? '';
    $time = $data['time'] ?? '';
    $improvements = $data['improvements'] ?? 0;

    $dataArray = [
        'uploadId' => $uploadId,
        'pdf' => $pdf,
        'json' => $json,
        'time' => $time,
        'improvements' => $improvements
    ];

    // Validate the received data
    if (empty($uploadId) || empty($pdf) || empty($json) || empty($time) || empty($improvements)) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Assuming the `uploadDate` is coming from the session or another source.
    $uploadDate = date('Y-m-d H:i:s'); // Or get from session or another source.


    $conn = getDBConnection();
    if (!$conn) {
        unlink($tempPath);
        throw new Exception("Failed to connect to the database.");
    }

    try {
        $conn->beginTransaction();

        // Prepare the SQL statement for updating the database
        $stmt = $conn->prepare("UPDATE processed_files SET
                                    processed_file_path = :processed_file_path,
                                    proof_data_path = :proof_data_path,
                                    error_count = :error_count,
                                    processing_time = :processing_time,
                                    processed_date = :processed_date
                                WHERE upload_id = :upload_id");

        // Bind the parameters
        $stmt->bindParam(':upload_id', $uploadId);
        $stmt->bindParam(':processed_file_path', $pdf);
        $stmt->bindParam(':proof_data_path', $json);
        $stmt->bindParam(':error_count', $improvements);
        $stmt->bindParam(':processing_time', $time);
        $stmt->bindParam(':processed_date', $uploadDate);

        // Execute the query
        $stmt->execute();

        // Commit the transaction if everything goes fine
        $conn->commit();

        // Return a success message
        echo json_encode(['message' => 'success']);
    } catch (Exception $e) {
        // Rollback in case of failure
        $conn->rollBack();
        echo json_encode(['error' => 'Failed to process file: ' . $e->getMessage()]);
    }
} else {
    // If the request method is not POST, return an error
    echo json_encode(['error' => 'Invalid request method']);
}

} catch (\Throwable $th) {
    echo 'erro: ' . $th;
}