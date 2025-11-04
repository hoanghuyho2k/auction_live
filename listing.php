<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: listing.php
 * Purpose: Handles item listing requests and adds new auction items to the XML database.
 */

// Set session save path for Mercury server compatibility
ini_set('session.save_path','/tmp');
session_start();
header('Content-Type: text/plain');

// Check if user is logged in before allowing item listing
if (!isset($_SESSION['customerID'])) {
    echo "Please log in before listing an item.";
    exit;
}

// Retrieve and sanitize form data from POST request
// Using isset() with ternary operator for Mercury server compatibility (no ?? operator)
$name     = isset($_POST['name'])     ? trim($_POST['name'])     : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$desc     = isset($_POST['desc'])     ? trim($_POST['desc'])     : '';
$start    = isset($_POST['start'])    ? floatval($_POST['start']) : 0.0;
$reserve  = isset($_POST['reserve'])  ? floatval($_POST['reserve']) : 0.0;
$buy      = isset($_POST['buy'])      ? floatval($_POST['buy']) : 0.0;
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;

// Validate required fields are not empty
if ($name === '' || $category === '' || $desc === '' || $duration <= 0) {
    echo "All fields are required.";
    exit;
}

// Validate pricing logic: start price must not exceed reserve price
if ($start > $reserve) {
    echo "Start price must not exceed reserve price.";
    exit;
}

// Validate pricing logic: reserve price must be less than buy-it-now price
if ($reserve >= $buy) {
    echo "Reserve price must be less than buy-it-now price.";
    exit;
}

// Define path to auction XML file in data directory
$auctionFile = '/home/students/accounts/s105726741/cos80021/www/data/auction.xml';

// Create and configure XML document
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true; // Enable pretty printing for readable XML

// Load existing auction file or create new one
if (file_exists($auctionFile)) {
    // Load existing XML document
    $doc->load($auctionFile);
    $root = $doc->documentElement;
    
    // Handle case where root element might not be 'items'
    if (!$root || $root->nodeName !== 'items') {
        // Create proper root element and migrate existing items
        $newRoot = $doc->createElement('items');
        if ($doc->documentElement) {
            // Import all existing nodes under the new root
            $old = $doc->documentElement;
            while ($old->firstChild) {
                $newRoot->appendChild($old->firstChild);
            }
        }
        $doc->appendChild($newRoot);
        $root = $newRoot;
    }
} else {
    // Create fresh XML document with items root element
    $root = $doc->createElement('items');
    $doc->appendChild($root);
}

// Create new item element for the auction listing
$item = $doc->createElement('item');
$root->appendChild($item);

// Generate unique item number using timestamp
$itemNum = 'I' . time();
// Get current date and time for auction start
$date = date('Y-m-d');
$time = date('H:i:s');

// Add all item details to the XML structure
$item->appendChild($doc->createElement('itemNumber', $itemNum));
$item->appendChild($doc->createElement('sellerID', $_SESSION['customerID']));
$item->appendChild($doc->createElement('name', $name));
$item->appendChild($doc->createElement('category', $category));
$item->appendChild($doc->createElement('description', $desc));
$item->appendChild($doc->createElement('startPrice', number_format($start, 2, '.', '')));
$item->appendChild($doc->createElement('reservePrice', number_format($reserve, 2, '.', '')));
$item->appendChild($doc->createElement('buyItNowPrice', number_format($buy, 2, '.', '')));
$item->appendChild($doc->createElement('duration', $duration));
$item->appendChild($doc->createElement('startDate', $date));
$item->appendChild($doc->createElement('startTime', $time));
$item->appendChild($doc->createElement('status', 'in_progress'));

// Create currentBid structure with initial values
$currentBid = $doc->createElement('currentBid');
$currentBid->appendChild($doc->createElement('bidderID', '')); // Empty bidder initially
$currentBid->appendChild($doc->createElement('price', number_format($start, 2, '.', ''))); // Start price as initial bid
$item->appendChild($currentBid);

// Save the updated XML document back to file
if ($doc->save($auctionFile) === false) {
    echo "Failed to save auction file. Check permissions.";
    exit;
}

// Return success message with item details
echo "Thank you! Your item has been listed in ShopOnline.\n";
echo "The item number is $itemNum, and the bidding starts now: $time on $date.";
?>