<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear athlete session
unset($_SESSION['athlete_id']);
unset($_SESSION['athlete_name']);
unset($_SESSION['athlete_card_id']);

header('Location: athlete_login.php');
exit;