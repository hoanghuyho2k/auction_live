<?php
/**
 * Name: Hoang Huy Ho
 * File: generateReport.php
 * Purpose: Generates sales report for sold/failed items and removes them from active auctions.
 */

header('Content-Type: text/html; charset=UTF-8');

$baseDir = __DIR__ . '/../data';
$auctionFile = $baseDir . '/auction.xml';
$reportFile  = $baseDir . '/report.xml';
$xslPath     = __DIR__ . '/report.xsl';

// --- Check if auction.xml exists ---
if (!file_exists($auctionFile)) {
    echo "<p>No auction data available.</p>";
    exit;
}

// --- Load auction data ---
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;
$doc->load($auctionFile);
$items = $doc->getElementsByTagName('item');

// --- Prepare report and new auction docs ---
$reportDoc = new DOMDocument('1.0', 'UTF-8');
$reportRoot = $reportDoc->createElement('items');
$reportDoc->appendChild($reportRoot);

$newAuctionDoc = new DOMDocument('1.0', 'UTF-8');
$newAuctionRoot = $newAuctionDoc->createElement('items');
$newAuctionDoc->appendChild($newAuctionRoot);

// --- Counters ---
$totalRevenue = 0;
$soldCount = 0;
$failedCount = 0;

// --- Process items ---
foreach ($items as $it) {
    $statusNode = $it->getElementsByTagName('status')->item(0);
    $status = $statusNode ? $statusNode->nodeValue : '';

    if ($status === 'sold' || $status === 'failed') {
        // Add to report
        $imported = $reportDoc->importNode($it, true);
        $reportRoot->appendChild($imported);

        // Commission logic
        if ($status === 'sold') {
            $currentBid = $it->getElementsByTagName('currentBid')->item(0);
            if ($currentBid) {
                $priceNode = $currentBid->getElementsByTagName('price')->item(0);
                if ($priceNode) {
                    $price = floatval($priceNode->nodeValue);
                    $totalRevenue += 0.04 * $price; // 4% commission
                    $soldCount++;
                }
            }
        } else {
            $reserveNode = $it->getElementsByTagName('reservePrice')->item(0);
            if ($reserveNode) {
                $reserve = floatval($reserveNode->nodeValue);
                $totalRevenue += 0.01 * $reserve; // 1% commission
                $failedCount++;
            }
        }
        // Do not re-add sold/failed items
    } else {
        // Keep in-progress items
        $imported = $newAuctionDoc->importNode($it, true);
        $newAuctionRoot->appendChild($imported);
    }
}

// --- Save updated auction.xml ---
if ($newAuctionDoc->save($auctionFile) === f
