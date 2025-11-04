<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: s105726741
 * File: logout.php
 * Purpose: Handles user logout by clearing session data and redirecting to login page.
 */

// Set session save path for Mercury server compatibility
ini_set('session.save_path', '/tmp');
// Start session to access current user session data
session_start();

// Clear all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect user to login page after successful logout
header("Location: login.htm");

// Ensure no further code execution after redirect
exit;
?>