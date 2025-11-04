<?php
/**
 * Name: Hoang Huy Ho
 * File: getAuctions.php
 * Purpose: Provides auction data in XML format for AJAX requests to display current auction items.
 */

// Suppress PHP notices/warnings to avoid breaking XML output
error_reporting(0);
header('Content-Type: text/xml; charset=UTF-8');

// ✅ Define portable relative path to auction.xml
$auctionFile = __DIR__ . '/../data/auction.xml';

// Return empty XML if auction file does not exist yet
if (!file_exists($auctionFile)) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><items></items>";
    exit;
}

// --- Load existing auction data ---
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->load($auctionFile);

// --- Prepare response XML ---
$response = new DOMDocument('1.0', 'UTF-8');
$response->formatOutput = true;
$root = $response->createElement('items');
$response->appendChild($root);

// --- Iterate through all items ---
$items = $doc->getElementsByTagName('item');
foreach ($items as $it) {
    $statusNodes = $it->getElementsByTagName('status');
    if ($statusNodes->length === 0) continue;
    $status = $statusNodes->item(0)->nodeValue;

    // Only include valid items
    if (!in_array($status, ['in_progress', 'sold', 'failed'])) continue;

    // Create <item> element
    $item = $response->createElement('item');
    $root->appendChild($item);

    // Safe helper function to get node value
    $getVal = function($tag) use ($it) {
        $n = $it->getElementsByTagName($tag);
        return $n->length > 0 ? htmlspecialchars($n->item(0)->nodeValue) : '';
    };

    // Basic item details
    $item->appendChild($response->createElement('itemNumber', $getVal('itemNumber')));
    $item->appendChild($response->createElement('name', $getVal('name')));
    $item->appendChild($response->createElement('category', $getVal('category')));
    $item->appendChild($response->createElement('description', $getVal('description')));
    $item->appendChild($response->createElement('buyItNowPrice', number_format(floatval($getVal('buyItNowPrice')), 2, '.', '')));

    // --- Handle currentBid safely ---
    $currentBidNode = $it->getElementsByTagName('currentBid')->item(0);
    $bidderID = '';
