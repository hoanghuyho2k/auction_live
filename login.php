<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: login.php
 * Purpose: Handles user authentication by validating credentials against customer XML database.
 */

// Set session save path for Mercury server compatibility
ini_set('session.save_path', '/tmp');
session_start();
// Set content type to plain text for AJAX response handling
header('Content-Type: text/plain');

// Retrieve and sanitize login credentials from POST request
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Define path to customer XML file containing user credentials
$customerFile = '/home/students/accounts/s105726741/cos80021/www/data/customer.xml';

// Check if customer database file exists
if (!file_exists($customerFile)) {
  echo "No customers found.";
  exit;
}

// Load and parse the customer XML database
$doc = new DOMDocument();
$doc->load($customerFile);
$found = false;
$customers = $doc->getElementsByTagName('customer');

// Iterate through all customer records to find matching credentials
for ($i = 0; $i < $customers->length; $i++) {
  // Extract email and password from current customer record
  $e = $customers->item($i)->getElementsByTagName('email')->item(0)->nodeValue;
  $p = $customers->item($i)->getElementsByTagName('password')->item(0)->nodeValue;
  
  // Validate credentials (case-insensitive email comparison)
  if (strcasecmp($e, $email) == 0 && $p == $password) {
    // Store customer information in session for subsequent requests
    $_SESSION['customerID'] = $customers->item($i)->getElementsByTagName('customerID')->item(0)->nodeValue;
    $_SESSION['firstname'] = $customers->item($i)->getElementsByTagName('firstname')->item(0)->nodeValue;
    $found = true;
    break; // Exit loop once matching customer is found
  }
}

// Return authentication result to client
if ($found)
  echo "OK";  // Simple response for AJAX success check
else
  echo "Invalid login."; // Authentication failed message
?>