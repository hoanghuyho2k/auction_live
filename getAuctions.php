<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: getAuctions.php
 * Purpose: Provides auction data in XML format for AJAX requests to display current auction items.
 */

// Suppress error reporting to prevent PHP notices/warnings from breaking XML output
error_reporting(0);
// Set content type to XML for proper AJAX response handling
header('Content-Type: text/xml; charset=UTF-8');

// Define path to auction.xml file
$auctionFile = '/home/students/accounts/s105726741/cos80021/www/data/auction.xml';

// If no auction file exists yet, return empty XML structure
if (!file_exists($auctionFile)) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><items></items>";
    exit;
}

// Load and configure the main auction XML document
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->load($auctionFile);

// Create a new XML document for clean response data
$response = new DOMDocument('1.0', 'UTF-8');
$response->formatOutput = true; // Enable pretty printing for readable XML
$root = $response->createElement('items');
$response->appendChild($root);

// Process all items from the auction XML file
$items = $doc->getElementsByTagName('item');
foreach ($items as $it) {
    // Get status safely with error checking
    $statusNodes = $it->getElementsByTagName('status');
    if ($statusNodes->length === 0) continue;
    $status = $statusNodes->item(0)->nodeValue;
    
    // Only include items that are in_progress, sold, or failed
    // This filters out any items with invalid or unexpected status
    if ($status != 'in_progress' && $status != 'sold' && $status != 'failed') continue;

    // Create new item element for the response
    $item = $response->createElement('item');
    $root->appendChild($item);

    // Add all required fields with safe access and error checking
    $item->appendChild($response->createElement('itemNumber',
        $it->getElementsByTagName('itemNumber')->item(0)->nodeValue));
    $item->appendChild($response->createElement('name',
        $it->getElementsByTagName('name')->item(0)->nodeValue));
    $item->appendChild($response->createElement('category',
        $it->getElementsByTagName('category')->item(0)->nodeValue));
    $item->appendChild($response->createElement('description',
        $it->getElementsByTagName('description')->item(0)->nodeValue));
    $item->appendChild($response->createElement('buyItNowPrice',
        number_format(floatval($it->getElementsByTagName('buyItNowPrice')->item(0)->nodeValue), 2, '.', '')));

    // Handle currentBid safely with nested structure
    $currentBidNode = $it->getElementsByTagName('currentBid')->item(0);
    $bidderID = "";
    $price = 0.00;
    
    if ($currentBidNode) {
        $bidderNodes = $currentBidNode->getElementsByTagName('bidderID');
        $priceNodes = $currentBidNode->getElementsByTagName('price');
        
        if ($bidderNodes->length > 0) $bidderID = $bidderNodes->item(0)->nodeValue;
        if ($priceNodes->length > 0) $price = floatval($priceNodes->item(0)->nodeValue);
    }

    // Create nested currentBid structure for proper XML formatting
    $cb = $response->createElement('currentBid');
    $cb->appendChild($response->createElement('bidderID', $bidderID));
    $cb->appendChild($response->createElement('price', number_format($price, 2, '.', '')));
    $item->appendChild($cb);

    $item->appendChild($response->createElement('status', $status));

    // Add startDate, startTime, duration for client-side time calculation
    $item->appendChild($response->createElement('startDate',
        $it->getElementsByTagName('startDate')->item(0)->nodeValue));
    $item->appendChild($response->createElement('startTime',
        $it->getElementsByTagName('startTime')->item(0)->nodeValue));
    $item->appendChild($response->createElement('duration',
        $it->getElementsByTagName('duration')->item(0)->nodeValue));

    // Remaining time calculation - FIXED to show proper days/hours/minutes/seconds
    $startDate = $it->getElementsByTagName('startDate')->item(0)->nodeValue;
    $startTime = $it->getElementsByTagName('startTime')->item(0)->nodeValue;
    $duration  = intval($it->getElementsByTagName('duration')->item(0)->nodeValue);

    // Calculate end timestamp (start + duration in days)
    $startTimestamp = strtotime($startDate . ' ' . $startTime);
    $endTimestamp = $startTimestamp + ($duration * 24 * 60 * 60); // Convert duration days to seconds
    $remaining = $endTimestamp - time();

    // Calculate and format remaining time for display
    if ($remaining > 0) {
        // Calculate days, hours, minutes, seconds from remaining seconds
        $days = floor($remaining / (60 * 60 * 24));
        $hours = floor(($remaining % (60 * 60 * 24)) / (60 * 60));
        $minutes = floor(($remaining % (60 * 60)) / 60);
        $seconds = $remaining % 60;
        
        // Build human-readable time left string with proper pluralization
        $timeLeft = "";
        if ($days > 0) {
            $timeLeft .= $days . " day" . ($days > 1 ? "s" : "") . ", ";
        }
        if ($hours > 0 || $days > 0) { // Show hours even if 0 when we have days for consistency
            $timeLeft .= $hours . " hour" . ($hours != 1 ? "s" : "") . ", ";
        }
        $timeLeft .= $minutes . " minute" . ($minutes != 1 ? "s" : "") . ", ";
        $timeLeft .= $seconds . " second" . ($seconds != 1 ? "s" : "");
        
    } else {
        $timeLeft = "Ended";
    }

    // Add calculated time left to the response
    $item->appendChild($response->createElement('timeLeft', $timeLeft));
}

// Output the complete XML response for AJAX consumption
echo $response->saveXML();
?>