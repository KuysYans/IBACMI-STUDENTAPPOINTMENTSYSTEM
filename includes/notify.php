<?php
/**
 * Notification helpers. Requires $pdo (config/db.php) to already be loaded.
 */

function push_notification(PDO $pdo, int $userId, string $message, ?int $appointmentId = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, appointment_id, message) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $appointmentId, $message]);
}

function unread_count(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function mark_all_read(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
}

function mark_one_read(PDO $pdo, int $userId, int $notifId): void
{
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$notifId, $userId]);
}
