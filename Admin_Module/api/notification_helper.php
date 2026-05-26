<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function createNotification($conn, $recipient_role, $title, $message, $type, $reference_id = null, $reference_type = null, $recipient_id = null) {
    $title          = mysqli_real_escape_string($conn, $title);
    $message        = mysqli_real_escape_string($conn, $message);
    $type           = mysqli_real_escape_string($conn, $type);
    $reference_type = $reference_type ? "'" . mysqli_real_escape_string($conn, $reference_type) . "'" : 'NULL';
    $reference_id   = $reference_id   ? (int)$reference_id : 'NULL';
    $recipient_id   = $recipient_id   ? (int)$recipient_id : 'NULL';

    $sql = "INSERT INTO notifications 
                (recipient_role, recipient_id, type, title, message, reference_id, reference_type)
            VALUES 
                ('$recipient_role', $recipient_id, '$type', '$title', '$message', $reference_id, $reference_type)";
    
    mysqli_query($conn, $sql);
    return mysqli_insert_id($conn);
}

function sendCoachBookingEmail($conn, $booking_id) {

    $bid = (int)$booking_id;
    $result = mysqli_query($conn, "
        SELECT 
            b.booking_date, b.start_time, b.end_time, b.session_type, b.notes,
            c.court_name,
            a.email AS coach_email,
            a.username AS coach_name
        FROM bookings b
        JOIN courts c ON b.court_id = c.id
        JOIN coaches co ON b.coach_id = co.id
        JOIN admins a ON co.admin_id = a.id
        WHERE b.id = $bid
          AND b.coach_id IS NOT NULL
          AND b.coach_id != 0
    ");

    if (!$result || mysqli_num_rows($result) === 0) return false;
    $data = mysqli_fetch_assoc($result);

    $date       = date("d M Y", strtotime($data['booking_date']));
    $start      = date("h:i A", strtotime($data['start_time']));
    $end        = date("h:i A", strtotime($data['end_time']));
    $court      = htmlspecialchars($data['court_name']);
    $session    = htmlspecialchars($data['session_type'] ?: 'N/A');
    $notes      = htmlspecialchars($data['notes'] ?: '—');
    $coachName  = htmlspecialchars($data['coach_name']);
    $coachEmail = $data['coach_email'];

    if (empty($coachEmail)) return false;

    require_once __DIR__ . '/Email_System/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/Email_System/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/Email_System/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer(true);
    try {

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'smasharenabadminton@gmail.com';
        $mail->Password   = 'hgrk ocze fowx rbrd';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('no-reply@smasharena.com', 'Smash Arena');
        $mail->addAddress($coachEmail, $coachName);

        $mail->isHTML(true);
        $mail->Subject = "✅ New Session Confirmed – $date";
        $mail->Body    = "
        <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;'>
            <div style='background:#e85d04; padding:24px; text-align:center;'>
                <h2 style='color:white; margin:0;'>🏸 Smash Arena</h2>
                <p style='color:#ffe0cc; margin:6px 0 0;'>Session Booking Confirmed</p>
            </div>
            <div style='padding:28px;'>
                <p>Hi <strong>$coachName</strong>,</p>
                <p>A coaching session has been <strong style='color:#10b981;'>confirmed</strong> for you. Here are the details:</p>
                <table style='width:100%; border-collapse:collapse; margin:20px 0;'>
                    <tr style='background:#f9fafb;'>
                        <td style='padding:10px 14px; font-weight:600; color:#555; width:40%;'>📅 Date</td>
                        <td style='padding:10px 14px;'>$date</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 14px; font-weight:600; color:#555;'>⏰ Time</td>
                        <td style='padding:10px 14px;'>$start – $end</td>
                    </tr>
                    <tr style='background:#f9fafb;'>
                        <td style='padding:10px 14px; font-weight:600; color:#555;'>🏟️ Court</td>
                        <td style='padding:10px 14px;'>$court</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 14px; font-weight:600; color:#555;'>🎯 Session Type</td>
                        <td style='padding:10px 14px;'>$session</td>
                    </tr>
                    <tr style='background:#f9fafb;'>
                        <td style='padding:10px 14px; font-weight:600; color:#555;'>📝 Notes</td>
                        <td style='padding:10px 14px;'>$notes</td>
                    </tr>
                </table>
                <p style='color:#888; font-size:13px;'>Please log in to <strong>Smash Arena</strong> to manage your session.</p>
            </div>
            <div style='background:#f3f4f6; padding:14px; text-align:center; font-size:12px; color:#aaa;'>
                © " . date('Y') . " Smash Arena · This is an automated notification
            </div>
        </div>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error (Booking #$booking_id): " . $mail->ErrorInfo);
        return false;
    }
}