<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: register.php
 * Purpose: Handles new customer registration and stores user data in XML database.
 */

header('Content-Type: text/plain');
session_start();

// Retrieve and sanitize form data from POST request
$first = isset($_POST['first']) ? trim($_POST['first']) : '';
$surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Validate that all required fields are filled
if ($first == '' || $surname == '' || $email == '' || $password == '') {
  echo "All fields are required.";
  exit;
}

// Validate email format using regular expression
// Pattern allows: local-part@domain-part with common email characters
if (!preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $email)) {
  echo "Invalid email address.";
  exit;
}

// Define path to customer XML file
$customerFile = '/home/students/accounts/s105726741/cos80021/www/data/customer.xml';
$doc = new DOMDocument('1.0','UTF-8');

// Load existing customer file or create new one
if (file_exists($customerFile)) {
  $doc->load($customerFile);
} else {
  // Create root element for new customer database
  $root = $doc->createElement('customers');
  $doc->appendChild($root);
}

// Check if email already exists in the system (case-insensitive comparison)
$emails = $doc->getElementsByTagName('email');
for ($i=0;$i<$emails->length;$i++){
  if (strcasecmp($emails->item($i)->nodeValue, $email)==0){
    echo "Email already exists.";
    exit;
  }
}

// Generate unique customer ID using timestamp
$cid = 'C'.time();

// Create new customer element and add to XML document
$customer = $doc->createElement('customer');
$doc->documentElement->appendChild($customer);

// Add all customer information to the XML structure
$customer->appendChild($doc->createElement('customerID',$cid));
$customer->appendChild($doc->createElement('firstname',$first));
$customer->appendChild($doc->createElement('surname',$surname));
$customer->appendChild($doc->createElement('email',$email));
$customer->appendChild($doc->createElement('password',$password));

// Save the updated customer database with proper formatting
$doc->formatOutput = true;
$doc->save($customerFile);

// Store customer information in session for immediate login
$_SESSION['customerID']=$cid;
$_SESSION['firstname']=$first;

// Send welcome email to new customer
$to=$email;
$subject="Welcome to ShopOnline";
$msg="Dear $first, welcome! Your ID: $cid and password: $password.";
$headers="From: registration@shoponline.com.au\r\n";

// Send email (using @ to suppress errors if mail function fails)
@mail($to,$subject,$msg,$headers);

// Return success message to client
echo "Dear $first, you have successfully registered, a confirm email sent to $email.";
?>