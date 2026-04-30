<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Court Booking Checkout</title>
    <link rel="stylesheet" href="style.css?v=2"> 
</head>
<body>

    <div class="container">
        <h2>Payment details</h2>
        
        <form action="gateway.php" method="POST">
            
            <label for="time_slot"><strong>Select Time Slot:</strong></label>
            <select name="time_slot" id="time_slot" onchange="updatePrice()">
                
                <optgroup label="Off-Peak: RM 10/hour (8:00 AM - 2:00 PM)">
                    <option value="08:00 AM - 10:00 AM" data-price="20.00">08:00 AM - 10:00 AM (2 Hours)</option>
                    <option value="10:00 AM - 12:00 PM" data-price="20.00">10:00 AM - 12:00 PM (2 Hours)</option>
                    <option value="12:00 PM - 02:00 PM" data-price="20.00">12:00 PM - 02:00 PM (2 Hours)</option>
                </optgroup>
                
                <optgroup label="Peak Hours: RM 15/hour (2:00 PM - 1:00 AM)">
                    <option value="02:00 PM - 04:00 PM" data-price="30.00">02:00 PM - 04:00 PM (2 Hours)</option>
                    <option value="04:00 PM - 06:00 PM" data-price="30.00">04:00 PM - 06:00 PM (2 Hours)</option>
                    <option value="06:00 PM - 08:00 PM" data-price="30.00">06:00 PM - 08:00 PM (2 Hours)</option>
                    <option value="08:00 PM - 10:00 PM" data-price="30.00">08:00 PM - 10:00 PM (2 Hours)</option>
                    <option value="10:00 PM - 12:00 AM" data-price="30.00">10:00 PM - 12:00 AM (2 Hours)</option>
                    <option value="12:00 AM - 01:00 AM" data-price="15.00">12:00 AM - 01:00 AM (1 Hour)</option>
                </optgroup>

            </select>

            <div style="background-color: #e6f7ff; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #0073e6;">
                <p style="margin: 0 0 5px 0; font-size: 16px;"><strong>Court:</strong> Court 2 (Rubber Mat)</p>
                <hr style="border: 0; border-top: 1px solid #b3e0ff; margin: 10px 0;">
                <p style="margin: 0; font-size: 18px;"><strong>Total Court Fee:</strong> RM <span id="display_price">20.00</span></p>
            </div>
            
            <input type="hidden" name="amount" id="hidden_amount" value="20.00">
            
            <label for="promo"><strong>Promo Code (Optional):</strong></label>
            <input type="text" name="promo_code" id="promo" placeholder="e.g. SMASH20">
            
            <h3 style="text-align: left; margin-bottom: 15px; margin-top: 30px;">Payment Methods</h3>
            
            <label class="payment-card">
                <input type="radio" name="payment_method" value="Center App Wallet" checked style="width: auto; margin: 0;">
                <div class="payment-info">
                    <p class="payment-title">
                        Badminton Hub App Wallet <span class="badge-orange">Zero Platform Fee</span>
                    </p>
                    <p class="payment-subtitle">My Balance • RM 15.00</p>
                </div>
            </label>

            <label class="payment-card">
                <input type="radio" name="payment_method" value="Bank Transfer" style="width: auto; margin: 0;">
                <div class="payment-info">
                    <p class="payment-title">Online Payment</p>
                    <p class="payment-subtitle">FPX, Touch 'n Go</p>
                </div>
            </label>

            <label class="payment-card">
                <input type="radio" name="payment_method" value="Credit Card" style="width: auto; margin: 0;">
                <div class="payment-info">
                    <p class="payment-title">Credit / Debit Card</p>
                    <p class="payment-subtitle">Visa, Mastercard</p>
                </div>
            </label>
            <button type="submit">Proceed to Payment</button>

        </form>
    </div>

    <script src="checkout.js"></script>

</body>
</html>