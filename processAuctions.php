<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: processAuctions.php
 * Purpose: Processes expired auction items and updates their status based on bid results.
 */

header('Content-Type: text/plain');

// Define path to auction XML file
$auctionFile = '/home/students/accounts/s105726741/cos80021/www/data/auction.xml';

// Check if auction file exists before processing
if (!file_exists($auctionFile)) {
    echo "No auction data found.";
    exit;
}

// Load and configure auction XML document
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true; // Enable pretty printing for readable XML
$doc->load($auctionFile);

$items = $doc->getElementsByTagName('item');
$now = time(); // Get current timestamp for expiration comparison
$updated = 0;  // Counter for processed items
$soldCount = 0; // Counter for sold items
$failedCount = 0; // Counter for failed items

// Iterate through all auction items to check for expired auctions
foreach ($items as $item) {
    $statusNode = $item->getElementsByTagName('status')->item(0);
    if (!$statusNode) continue;
    
    $status = $statusNode->nodeValue;
    
    // Only process items that are still in progress (not already sold or failed)
    if ($status != 'in_progress') continue;
    
    // Extract auction timing information
    $startDate = $item->getElementsByTagName('startDate')->item(0)->nodeValue;
    $startTime = $item->getElementsByTagName('startTime')->item(0)->nodeValue;
    $duration = intval($item->getElementsByTagName('duration')->item(0)->nodeValue);
    
    // Calculate end timestamp (start + duration in days)
    $startTimestamp = strtotime($startDate . ' ' . $startTime);
    $endTimestamp = $startTimestamp + ($duration * 24 * 60 * 60); // Convert days to seconds
    
    // Check if auction has expired (current time >= end time)
    if ($now >= $endTimestamp) {
        // Get reserve price for comparison with current bid
        $reserve = floatval($item->getElementsByTagName('reservePrice')->item(0)->nodeValue);
        
        // Extract current bid information
        $currentBidNode = $item->getElementsByTagName('currentBid')->item(0);
        $currentPrice = 0.0;
        if ($currentBidNode) {
            $priceNode = $currentBidNode->getElementsByTagName('price')->item(0);
            if ($priceNode) {
                $currentPrice = floatval($priceNode->nodeValue);
            }
        }
        
        // Determine auction outcome based on bid vs reserve price
        if ($currentPrice >= $reserve) {
            // Item sold: current bid meets or exceeds reserve price
            $statusNode->nodeValue = 'sold';
            $soldCount++;
        } else {
            // Item failed: current bid below reserve price
            $statusNode->nodeValue = 'failed';
            $failedCount++;
        }
        $updated++; // Increment processed items counter
    }
}

// Save the updated auction file (items remain in the file for reporting)
if ($doc->save($auctionFile) === false) {
    echo "Error saving auction file.";
    exit;
}

// Display processing results to user
if ($updated > 0) {
    echo "✅ Processed $updated expired auctions.\n";
    echo "✅ Sold: $soldCount items\n";
    echo "❌ Failed: $failedCount items\n";
    echo "Items updated in the system and available for reporting.";
} else {
    echo "No expired auctions found to process.";
}
?>