<?php
// ============================================================
//  MediaBoost — Notification Helper
//  Call addNotification() anywhere to create a notification
// ============================================================

function addNotification(string $message, string $type='system', ?int $userId=null, ?string $link=null): void {
    try {
        $db = getDB();
        $db->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, 0)")
           ->execute([$userId, $type, $message, $link]);
    } catch(Exception $e) {
        // Silent fail — notifications are non-critical
    }
}
?>
