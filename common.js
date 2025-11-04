/**
 * Name: Hoang Huy Ho
 * Student ID: 105726741
 * File: common.js
 * Purpose: Common JavaScript functions for AJAX operations and form handling across ShopOnline application.
 */

/**
 * Generic AJAX POST function for client-server communication
 * @param {string} url - The PHP endpoint to send the request to
 * @param {string} data - The data to send in the request body
 * @param {function} callback - The function to call when response is received
 */
function ajaxPost(url,data,callback){
  var xhr=new XMLHttpRequest();
  xhr.open("POST",url,true);
  xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
  xhr.onreadystatechange=function(){
    if(xhr.readyState==4 && xhr.status==200){
      callback(xhr.responseText,xhr.responseXML);
    }
  };
  xhr.send(data);
}

/**
 * Handles user registration form submission
 * Validates password confirmation and sends registration data to server
 * @returns {boolean} false to prevent form submission
 */
function registerUser(){
  if (!validatePassword()) {
    document.getElementById("msg").innerHTML = "Passwords do not match.";
    return false;
  }
  
  var first=document.getElementById("first").value;
  var surname=document.getElementById("surname").value;
  var email=document.getElementById("email").value;
  var password=document.getElementById("password").value;
  var data="first="+encodeURIComponent(first)+"&surname="+encodeURIComponent(surname)+"&email="+encodeURIComponent(email)+"&password="+encodeURIComponent(password);
  ajaxPost("register.php",data,function(resp){document.getElementById("msg").innerHTML=resp;});
  return false;
}

/**
 * Handles user login form submission
 * Sends login credentials to server and redirects to bidding page on success
 * @returns {boolean} false to prevent form submission
 */
function loginUser(){
  var email=document.getElementById("email").value;
  var password=document.getElementById("password").value;
  var data="email="+encodeURIComponent(email)+"&password="+encodeURIComponent(password);
  ajaxPost("login.php",data,function(resp){
    if(resp.indexOf("OK")>=0) window.location="bidding.htm";
    else document.getElementById("msg").innerHTML=resp;
  });
  return false;
}

/**
 * Validates that password and confirmation password match
 * @returns {boolean} true if passwords match, false otherwise
 */
function validatePassword() {
  var password = document.getElementById("password").value;
  var confirm = document.getElementById("confirm").value;
  return password === confirm;
}

/**
 * Handles item listing form submission
 * Validates form inputs and sends item data to server for listing
 * @returns {boolean} false to prevent form submission
 */
function listItem(){
  var name = document.getElementById("name").value.trim();
  var cat = document.getElementById("category").value;
  var other = document.getElementById("otherCat").value.trim();
  var category = (cat === "Other") ? other : cat;
  var desc = document.getElementById("desc").value.trim();
  var start = parseFloat(document.getElementById("start").value);
  var reserve = parseFloat(document.getElementById("reserve").value);
  var buy = parseFloat(document.getElementById("buy").value);
  var dur = parseInt(document.getElementById("duration").value);

  if (!category){ alert("Please select or enter a category."); return false; }
  if (start > reserve){ alert("Start price must not exceed reserve price."); return false; }
  if (reserve >= buy){ alert("Reserve price must be less than buy-it-now price."); return false; }

  var data = "name="+encodeURIComponent(name)+
             "&category="+encodeURIComponent(category)+
             "&desc="+encodeURIComponent(desc)+
             "&start="+start+
             "&reserve="+reserve+
             "&buy="+buy+
             "&duration="+dur;

  ajaxPost("listing.php",data,function(resp){
    document.getElementById("msg").innerHTML = resp;
  });
  return false;
}

/**
 * Fetches and displays current auction items from server
 * Updates the bidding page with formatted auction data
 * Automatically refreshes every 5 seconds
 */
function updateList() {
  var xhr = new XMLHttpRequest();
  xhr.open("GET", "getAuctions.php?ts=" + new Date().getTime(), true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var xmlText = xhr.responseText;
      
      var parser = new DOMParser();
      var xml = parser.parseFromString(xmlText, "text/xml");
      
      // Check for XML parsing errors
      var parseError = xml.getElementsByTagName("parsererror");
      if (parseError.length > 0) {
        console.error("XML parsing error:", parseError[0].textContent);
        document.getElementById("list").innerHTML = "<p>Error loading auction data.</p>";
        return;
      }
      
      var items = xml.getElementsByTagName("item");
      
      if (items.length === 0) {
        document.getElementById("list").innerHTML = "<p>No auction items available.</p>";
        return;
      }

      var out = "<h2>🛒 Current Auction Items</h2>";
      out += "<div class='auction-grid'>";

      for (var i = 0; i < items.length; i++) {
        var it = items[i];

        /**
         * Helper function to safely extract text content from XML elements
         * @param {string} tag - The XML tag name to extract
         * @returns {string} The text content or empty string if not found
         */
        function safeText(tag) {
          var el = it.getElementsByTagName(tag);
          return el.length > 0 ? el[0].textContent : "";
        }

        var id = safeText("itemNumber");
        var name = safeText("name");
        var cat = safeText("category");
        var desc = safeText("description");
        // Show only first 30 characters of description as per requirements
        var shortDesc = desc.length > 30 ? desc.substring(0, 30) + "..." : desc;
        var buy = safeText("buyItNowPrice");
        var status = safeText("status");
        var timeLeft = safeText("timeLeft");

        var price = "0.00";
        var bidNode = it.getElementsByTagName("currentBid")[0];
        if (bidNode) {
          var priceNode = bidNode.getElementsByTagName("price")[0];
          if (priceNode) {
            price = parseFloat(priceNode.textContent).toFixed(2);
          }
        }

        // Sequential item number (1, 2, 3, ...) for better user experience
        var itemNumber = i + 1;
        
        out += "<div class='auction-box'>";
        out += "<div class='item-number'>" + itemNumber + "</div>";
        out += "<div class='auction-content'>";
        out += "<div class='auction-details'>";
        out += "<p><strong>Item ID:</strong> " + id + "</p>";
        out += "<p><strong>Name:</strong> " + name + "</p>";
        out += "<p><strong>Category:</strong> " + cat + "</p>";
        out += "<p><strong>Description:</strong> " + shortDesc + "</p>";
        out += "<p class='price-highlight'><strong>Buy Now:</strong> $" + parseFloat(buy).toFixed(2) + "</p>";
        out += "<p class='price-highlight'><strong>Current Bid:</strong> $" + price + "</p>";
        out += "<p><strong>Status:</strong> <span class='status-" + status.replace('_', '-') + "'>" + status + "</span></p>";
        out += "<div class='time-remaining'><strong>⏰ Time Left:</strong> " + timeLeft + "</div>";
        out += "</div>"; // close auction-details
        
        out += "<div class='auction-buttons'>";
        // Show bid/buy buttons only for active auctions with time remaining
        if (status === "in_progress" && timeLeft !== "Ended") {
          out += "<button class='bid-button' onclick=\"bidNow('" + id + "')\">💰 Place Bid</button>";
          out += "<button class='buy-button' onclick=\"buyNow('" + id + "')\">🚀 Buy Now</button>";
        } else {
          out += "<span class='ended'>Auction " + status + "</span>";
        }
        out += "</div>"; // close auction-buttons
        
        out += "</div>"; // close auction-content
        out += "</div>"; // close auction-box
      }

      out += "</div>";
      document.getElementById("list").innerHTML = out;
    }
  };
  xhr.onerror = function() {
    console.error("Failed to load auction data");
    document.getElementById("list").innerHTML = "<p>Error loading auction items.</p>";
  };
  xhr.send(null);
}

// Auto-refresh auction list every 5 seconds as per project requirements
setInterval(updateList, 5000);

/**
 * Handles bid placement for an auction item
 * Prompts user for bid amount and sends to server
 * @param {string} id - The item number to bid on
 */
function bidNow(id){
  var val=prompt("Enter bid:");
  if(val){
    ajaxPost("placeBid.php","itemNumber="+id+"&bid="+val,function(r){alert(r);});
  }
}

/**
 * Handles Buy It Now purchase for an auction item
 * Sends purchase request to server without additional user input
 * @param {string} id - The item number to purchase
 */
function buyNow(id){
  ajaxPost("buyNow.php","itemNumber="+id,function(r){alert(r);});
}

/**
 * Processes expired auction items by sending request to server
 * Updates auction statuses based on expiration and bid conditions
 */
function processAuctions(){
  ajaxPost("processAuctions.php","",function(r){document.getElementById("msg").innerHTML=r;});
}

/**
 * Generates sales report by fetching report data from server
 * Displays sold/failed items and revenue calculations
 */
function generateReport(){
  var xhr=new XMLHttpRequest();
  xhr.open("GET","generateReport.php",true);
  xhr.onreadystatechange=function(){
    if(xhr.readyState==4 && xhr.status==200){
      document.getElementById("report").innerHTML=xhr.responseText;
    }
  };
  xhr.send(null);
}