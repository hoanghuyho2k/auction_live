<?php
/**
 * Name: Hoang Huy Ho
 * File: buyNow.php
 * Purpose: Handles Buy It Now purchases by updating auction status and bid information.
 */

session_start();
header('Content-Type: text/plain');

$auctionFile = __DIR__ . '/../data/auction.xml';

// Check if user is logged in before allowing purchase
if (!isset($_SESSION['customerID'])) {
    echo "Please log in before buying an item.";
    exit;
}

// Get and validate item number from POST request
$itemNum = isset($_POST['itemNumber']) ? trim($_POST['itemNumber']) : '';
if ($itemNum == '') {
    echo "Invalid item number.";
    exit;
}

// Verify auction.xml exists
if (!file_exists($auctionFile)) {
    echo "No auction file found.";
    exit;
}

// Load XML document
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;
$doc->load($auctionFile);

$items = $doc->getElementsByTagName('item');
$found = false;

// Iterate through auction items
foreach ($items as $item) {
    $numNode = $item->getElementsByTagName('itemNumber')->item(0);
    if (!$numNode) continue;
    if ($numNode->nodeValue != $itemNum) continue;
    $found = true;

    // Check if item still active
    $statusNode = $item->getElementsByTagName('status')->item(0);
    $status = $statusNode ? $statusNode->nodeValue : 'in_progress';
    if ($status != 'in_progress') {
        echo "Item already sold or closed.";
        exit;
    }

    // Time check
    $sd = $item->getElementsByTagName('startDate')->item(0)->nodeValue;
    $st = $item->getElementsByTagName('startTime')->item(0)->nodeValue;
    $durDays = intval($item->getElementsByTagName('duration')->item(0)->nodeValue);
    $end = strtotime($sd . ' ' . $st) + ($durDays * 24 * 3600);
    if (time() >= $end) {
        echo "Auction expired.";
        exit;
    }

    // Get Buy It Now price
    $buyNode = $item->getElementsByTagName('buyItNowPrice')->item(0);
    $buyPrice = $buyNode ? floatval($buyNode->nodeValue) : 0.0;

    // Update current bid info
    $currNode = $item->getElementsByTagName('currentBid')->item(0);
    if (!$currNode) {
        $currNode = $doc->createElement('currentBid');
        $currNode->appendChild($doc->createElement('bidderID', $_SESSION['customerID']));
        $currNode->appendChild($doc->createElement('price', number_format($buyPrice, 2, '.', '')));
        $item->appendChild($currNode);
    } else {
        $bidderNode = $currNode->getElementsByTagName('bidderID')->item(0);
        $priceNode = $currNode->getElementsByTagName('price')->item(0);
        if (!$bidderNode) {
            $bidderNode = $doc->createElement('bidderID', '');
            $currNode->appendChild($bidderNode);
        }
        if (!$priceNode) {
            $priceNode = $doc->createElement('price', '0.00');
            $currNode->appendChild($priceNode);
        }
        $bidderNode->nodeValue = $_SESSION['customerID'];
        $priceNode->nodeValue = number_format($buyPrice, 2, '.', '');
    }

    // Mark as sold
    if ($statusNode) $statusNode->nodeValue = 'sold';
    else $item->appendChild($doc->createElement('status', 'sold'));

    // Save updated XML
    if ($doc->save($auctionFile) === false) {
        echo "Error saving auction file.";
        exit;
    }

    echo "Item $itemNum bought for $" . number_format($buyPrice, 2) . ".";
    exit;
}

if (!$found) echo "Item not found.";
?>
