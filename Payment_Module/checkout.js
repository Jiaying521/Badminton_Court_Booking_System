// script.js - Master JavaScript File

function updatePrice() {
    // Find the dropdown
    var select = document.getElementById("time_slot");
    
    // Look at the selected option and grab the number hidden in 'data-price'
    var selectedPrice = select.options[select.selectedIndex].getAttribute("data-price");
    
    // Change the text on the screen so the user sees the new price
    document.getElementById("display_price").innerText = selectedPrice;
    
    // Change the hidden input so PHP processes the correct amount
    document.getElementById("hidden_amount").value = selectedPrice;
}