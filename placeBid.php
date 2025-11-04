<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: placeBid.php
 * Purpose: Handles bid placement requests and updates auction current bid information.
 */

// Set session save path for Mercury server compatibility
ini_set('session.save_path','/tmp');
session_start();
header('Content-Type: text/plain');

// Check if user is logged in before allowing bid placement
if (!isset($_SESSION['customerID'])) {
    echo "Please log in before placing a bid.";
    exit;
}

// Retrieve and validate bid data from POST request
$itemNum = isset($_POST['itemNumber']) ? trim($_POST['itemNumber']) : '';
$bidVal  = isset($_POST['bid']) ? floatval($_POST['bid']) : 0.0;

// Validate bid data is present and valid
if ($itemNum=='' || $bidVal<=0){
    echo "Invalid bid data.";
    exit;
}

// Define path to auction XML file
$auctionFile = '/home/students/accounts/s105726741/cos80021/www/data/auction.xml';
if (!file_exists($auctionFile)){
    echo "Auction data not found.";
    exit;
}

// Load and configure auction XML document
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true; // Enable pretty printing for readable XML
$doc->load($auctionFile);

$items = $doc->getElementsByTagName('item');
$found = false;

// Iterate through all auction items to find the specified item
foreach ($items as $item){
    $numNode = $item->getElementsByTagName('itemNumber')->item(0);
    if (!$numNode) continue;
    if ($numNode->nodeValue != $itemNum) continue;
    $found = true;

    // Check if auction is still active and accepting bids
    $statusNode = $item->getElementsByTagName('status')->item(0);
    $status = $statusNode ? $statusNode->nodeValue : 'in_progress';
    if ($status!='in_progress'){
        echo "Auction already closed.";
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

    // --- Current highest bid ---
    // Extract current bid information for validation
    $currNode = $item->getElementsByTagName('currentBid')->item(0);
    $priceNode = $currNode->getElementsByTagName('price')->item(0);
    $bidderNode = $currNode->getElementsByTagName('bidderID')->item(0);

    $currentPrice = floatval($priceNode->nodeValue);
    
    // Validate that new bid is higher than current bid (English auction rules)
    if ($bidVal <= $currentPrice){
        echo "Bid must be higher than current bid ($".number_format($currentPrice,2).").";
        exit;
    }

    // --- Update current bid ---
    // Update bid price and bidder information in XML
    $priceNode->nodeValue = number_format($bidVal,2,'.','');
    $bidderNode->nodeValue = $_SESSION['customerID'];
    
    // Save updated XML back to file
    $doc->save($auctionFile);
    echo "Bid accepted. You are now the highest bidder with $".number_format($bidVal,2).".";
    exit;
}

// Return error if specified item was not found
if (!$found) echo "Item not found.";
?>