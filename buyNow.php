<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: buyNow.php
 * Purpose: Handles Buy It Now purchases by updating auction status and bid information.
 */

// Set session save path for Mercury server compatibility
ini_set('session.save_path','/tmp');
session_start();
header('Content-Type: text/plain');

// Check if user is logged in before allowing purchase
if (!isset($_SESSION['customerID'])) {
    echo "Please log in before buying an item.";
    exit;
}

// Get and validate item number from POST request
$itemNum = isset($_POST['itemNumber']) ? trim($_POST['itemNumber']) : '';
if ($itemNum==''){
    echo "Invalid item number.";
    exit;
}

// Define path to auction XML file
$auctionFile = '/home/students/accounts/s105726741/cos80021/www/data/auction.xml';
if (!file_exists($auctionFile)){
    echo "No auction file found.";
    exit;
}

// Load and configure XML document for processing
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true; // Ensure proper XML formatting
$doc->load($auctionFile);

$items = $doc->getElementsByTagName('item');
$found = false;

// Iterate through all auction items to find the specified item
foreach ($items as $item){
    $numNode = $item->getElementsByTagName('itemNumber')->item(0);
    if (!$numNode) continue;
    if ($numNode->nodeValue != $itemNum) continue;
    $found = true;

    // Check if item is still available for purchase
    $statusNode = $item->getElementsByTagName('status')->item(0);
    $status = $statusNode ? $statusNode->nodeValue : 'in_progress';
    if ($status != 'in_progress'){
        echo "Item already sold or closed.";
        exit;
    }

    // --- Time check ---
    // Calculate if auction has expired based on start date/time and duration
    $sd = $item->getElementsByTagName('startDate')->item(0)->nodeValue;
    $st = $item->getElementsByTagName('startTime')->item(0)->nodeValue;
    $durDays = intval($item->getElementsByTagName('duration')->item(0)->nodeValue);
    $end = strtotime($sd.' '.$st) + ($durDays * 24 * 3600);
    if (time() >= $end){
        echo "Auction expired.";
        exit;
    }

    // --- Get Buy Price ---
    $buyNode = $item->getElementsByTagName('buyItNowPrice')->item(0);
    $buyPrice = $buyNode ? floatval($buyNode->nodeValue) : 0.0;

    // --- Update current bid info ---
    // Set current bid to buy-it-now price and update bidder information
    $currNode = $item->getElementsByTagName('currentBid')->item(0);
    if (!$currNode){
        $currNode = $doc->createElement('currentBid');
        $currNode->appendChild($doc->createElement('bidderID', $_SESSION['customerID']));
        $currNode->appendChild($doc->createElement('price', number_format($buyPrice,2,'.','')));
        $item->appendChild($currNode);
    } else {
        $bidderNode = $currNode->getElementsByTagName('bidderID')->item(0);
        $priceNode = $currNode->getElementsByTagName('price')->item(0);
        if (!$bidderNode) { $bidderNode = $doc->createElement('bidderID',''); $currNode->appendChild($bidderNode); }
        if (!$priceNode) { $priceNode = $doc->createElement('price','0.00'); $currNode->appendChild($priceNode); }
        $bidderNode->nodeValue = $_SESSION['customerID'];
        $priceNode->nodeValue = number_format($buyPrice,2,'.','');
    }

    // --- Mark as sold ---
    // Update item status to 'sold' since it was purchased via Buy It Now
    if ($statusNode) $statusNode->nodeValue = 'sold';
    else $item->appendChild($doc->createElement('status','sold'));

    // Save updated XML back to file
    if ($doc->save($auctionFile) === false) {
        echo "Error saving auction file.";
        exit;
    }

    echo "Item $itemNum bought for $".number_format($buyPrice,2).".";
    exit;
}

if (!$found) echo "Item not found.";
?>