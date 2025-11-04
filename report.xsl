<?xml version="1.0"?>
<!--
/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: report.xsl
 * Purpose: XSLT stylesheet for transforming auction report XML into formatted HTML display.
 */
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<!-- Main template that matches the root 'items' element -->
<xsl:template match="/items">
  <html>
  <head>
    <title>ShopOnline Report</title>
    <!-- Embedded CSS for styling the report table and summary -->
    <style>
      table { border-collapse: collapse; width: 100%; margin: 10px 0; }
      th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
      th { background-color: #f2f2f2; }
      .summary { background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-radius: 5px; }
      .sold { color: #28a745; font-weight: bold; } /* Green color for sold items */
      .failed { color: #6c757d; font-weight: bold; } /* Gray color for failed items */
    </style>
  </head>
  <body>
    <h3>ShopOnline Sales Report</h3>
    <!-- Informational note about report behavior -->
    <p><em>Note: Reported items have been removed from the active auction system.</em></p>
    
    <!-- Main report table displaying auction items -->
    <table>
      <tr>
        <th>Item #</th>
        <th>Name</th>
        <th>Category</th>
        <th>Status</th>
        <th>Final Price ($)</th>
      </tr>
      
      <!-- Process each item element in the XML -->
      <xsl:for-each select="item">
        <tr>
          <!-- Display item number -->
          <td><xsl:value-of select="itemNumber"/></td>
          <!-- Display item name -->
          <td><xsl:value-of select="name"/></td>
          <!-- Display item category -->
          <td><xsl:value-of select="category"/></td>
          <td>
            <!-- Conditional formatting for item status -->
            <xsl:choose>
              <!-- Sold items with green checkmark and styling -->
              <xsl:when test="status='sold'">
                <span class="sold">✅ <xsl:value-of select="status"/></span>
              </xsl:when>
              <!-- Failed items with red X and styling -->
              <xsl:otherwise>
                <span class="failed">❌ <xsl:value-of select="status"/></span>
              </xsl:otherwise>
            </xsl:choose>
          </td>
          <td style="text-align: right;">
            <!-- Display appropriate price based on item status -->
            <xsl:choose>
              <!-- For sold items: show final bid price -->
              <xsl:when test="status='sold'">
                $<xsl:value-of select="format-number(currentBid/price, '0.00')"/>
              </xsl:when>
              <!-- For failed items: show reserve price -->
              <xsl:otherwise>
                $<xsl:value-of select="format-number(reservePrice, '0.00')"/>
              </xsl:otherwise>
            </xsl:choose>
          </td>
        </tr>
      </xsl:for-each>
    </table>
    
    <!-- Summary section with item counts -->
    <div class="summary">
      <h4>Report Summary</h4>
      <!-- Total items processed -->
      <p><strong>Total Items Reported: </strong><xsl:value-of select="count(item)"/></p>
      <!-- Count of sold items using XPath predicate -->
      <p><strong>Sold Items: </strong><xsl:value-of select="count(item[status='sold'])"/></p>
      <!-- Count of failed items using XPath predicate -->
      <p><strong>Failed Items: </strong><xsl:value-of select="count(item[status='failed'])"/></p>
    </div>
  </body>
  </html>
</xsl:template>
</xsl:stylesheet>