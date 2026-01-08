<?php

function connectToDatabase() {
    static $db = null;
    if ($db === null) {
        $db = new SQLite3('../data/ABC_DBA.db');
        if (!$db) {
            die("Database connection error: " . $db->lastErrorMsg());
        }
    }
    return $db;
}

function logActivity($userID, $complaintID, $activity, $status) {
    $db = connectToDatabase();
    $stmt = $db->prepare("INSERT INTO activities (userID, complaintID, activity, date, status) VALUES (:userID, :complaintID, :activity, :date, :status)");
    $stmt->bindValue(':userID', $userID, SQLITE3_INTEGER);
    $stmt->bindValue(':complaintID', $complaintID, SQLITE3_INTEGER);
    $stmt->bindValue(':activity', $activity, SQLITE3_TEXT);
    $stmt->bindValue(':date', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->execute();
}
?>
