<?php
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: generateReport.php
 * Purpose: Generates sales report for sold/failed items and removes them from active auctions.
 */

// Set content type to HTML for proper display in browser
header('Content-Type: text/html');

// Define path to auction XML file
$auctionFile = '/home/students/accounts/s105726741/cos80021/www/data/auction.xml';

// Check if auction file exists before processing
if (!file_exists($auctionFile)) {
    echo "<p>No auction data available.</p>";
    exit;
}

// Load and configure auction XML document
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;
$doc->load($auctionFile);

$items = $doc->getElementsByTagName('item');

// Create report XML document to store sold/failed items
$reportDoc = new DOMDocument('1.0', 'UTF-8');
$reportRoot = $reportDoc->createElement('items');
$reportDoc->appendChild($reportRoot);

// Create new auction XML document without sold/failed items (they will be removed)
$newAuctionDoc = new DOMDocument('1.0', 'UTF-8');
$newAuctionRoot = $newAuctionDoc->createElement('items');
$newAuctionDoc->appendChild($newAuctionRoot);

// Initialize counters for revenue calculation
$totalRevenue = 0;
$soldCount = 0;
$failedCount = 0;

// Process each item - collect sold/failed items and remove them from auction.xml
for ($i = 0; $i < $items->length; $i++) {
    $it = $items->item($i);
    $status = $it->getElementsByTagName('status')->item(0)->nodeValue;
    
    // Check if item is sold or failed for reporting
    if ($status == 'sold' || $status == 'failed') {
        // Add to report XML document
        $imported = $reportDoc->importNode($it, true);
        $reportRoot->appendChild($imported);
        
        // Calculate revenue based on item status
        if ($status == 'sold') {
            // For sold items: charge 4% commission of final sale price
            $currentBid = $it->getElementsByTagName('currentBid')->item(0);
            if ($currentBid) {
                $priceNode = $currentBid->getElementsByTagName('price')->item(0);
                if ($priceNode) {
                    $price = floatval($priceNode->nodeValue);
                    $totalRevenue += 0.04 * $price; // 4% commission as per requirements
                    $soldCount++;
                }
            }
        } else { // failed items
            // For failed items: charge 1% commission of reserve price
            $reserveNode = $it->getElementsByTagName('reservePrice')->item(0);
            if ($reserveNode) {
                $reserve = floatval($reserveNode->nodeValue);
                $totalRevenue += 0.01 * $reserve; // 1% commission as per requirements
                $failedCount++;
            }
        }
        // DO NOT add to new auction file (REMOVE sold/failed items from active auctions)
    } else {
        // Keep only in-progress items in the active auction file
        $imported = $newAuctionDoc->importNode($it, true);
        $newAuctionRoot->appendChild($imported);
    }
}

// Save updated auction file (WITHOUT sold/failed items - they are removed as per requirements)
if ($newAuctionDoc->save($auctionFile) === false) {
    echo "<p>Error updating auction file.</p>";
    exit;
}

// Save report file for historical record
$reportFile = '/home/students/accounts/s105726741/cos80021/www/data/report.xml';
$reportDoc->formatOutput = true;
$reportDoc->save($reportFile);

// Generate output using XSLT transformation for formatted display
$xslPath = '/home/students/accounts/s105726741/cos80021/www/htdocs/Project2/report.xsl';
if (file_exists($xslPath)) {
    // Load XSLT stylesheet and transform XML report
    $xsl = new DOMDocument();
    $xsl->load($xslPath);
    $proc = new XSLTProcessor();
    $proc->importStylesheet($xsl);
    echo $proc->transformToXML($reportDoc);
} else {
    // Fallback table display if XSLT file is not available
    echo "<h3>📊 Sales Report</h3>";
    $reportItems = $reportDoc->getElementsByTagName('item');
    
    if ($reportItems->length == 0) {
        echo "<p>No sold or failed items to report.</p>";
    } else {
        // Create HTML table to display report data
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>
                <th>Item Number</th>
                <th>Name</th>
                <th>Category</th>
                <th>Status</th>
                <th>Final Price ($)</th>
              </tr>";
        
        // Iterate through each reported item and display in table
        foreach ($reportItems as $item) {
            $num = $item->getElementsByTagName('itemNumber')->item(0)->nodeValue;
            $name = $item->getElementsByTagName('name')->item(0)->nodeValue;
            $category = $item->getElementsByTagName('category')->item(0)->nodeValue;
            $status = $item->getElementsByTagName('status')->item(0)->nodeValue;
            
            // Determine final price based on item status
            $price = '';
            if ($status == 'sold') {
                $currentBid = $item->getElementsByTagName('currentBid')->item(0);
                if ($currentBid) {
                    $priceNode = $currentBid->getElementsByTagName('price')->item(0);
                    if ($priceNode) {
                        $price = number_format(floatval($priceNode->nodeValue), 2);
                    }
                }
            } else {
                $reserveNode = $item->getElementsByTagName('reservePrice')->item(0);
                if ($reserveNode) {
                    $price = number_format(floatval($reserveNode->nodeValue), 2);
                }
            }
            
            // Apply color coding based on status
            $statusColor = $status == 'sold' ? 'color: #28a745;' : 'color: #6c757d;';
            
            echo "<tr>
                    <td>$num</td>
                    <td>$name</td>
                    <td>$category</td>
                    <td style='$statusColor; font-weight: bold;'>$status</td>
                    <td style='text-align: right;'>\$$price</td>
                  </tr>";
        }
        echo "</table>";
    }
}

// Display report summary with totals and revenue
echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>";
echo "<h4>📈 Report Summary</h4>";
echo "<p><strong>✅ Sold Items:</strong> $soldCount</p>";
echo "<p><strong>❌ Failed Items:</strong> $failedCount</p>";
echo "<p><strong>💰 Total Revenue:</strong> $" . number_format($totalRevenue, 2) . "</p>";
echo "<p><em>Note: Reported items have been removed from the active auction system.</em></p>";
echo "</div>";
?>