<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinic Payment Checkout</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

    <div class="container">
        <h2>Complete Your Clinic Payment</h2>
        
        <form action="process_payment.php" method="POST">
            
            <div style="background-color: #e6f7ff; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #0073e6;">
                <p style="margin: 0; font-size: 18px;"><strong>Consultation Fee:</strong> RM 50.00</p>
            </div>
            
            <input type="hidden" name="amount" value="50.00">
            
            <label for="promo"><strong>Insurance / Promo Code:</strong></label>
            <input type="text" name="promo_code" id="promo" placeholder="e.g. HEALTH20">
            
            <label for="method"><strong>Choose Payment Method:</strong></label>
            <select name="payment_method" id="method">
                <option value="Credit Card">Credit Card</option>
                <option value="E-Wallet">Virtual E-Wallet</option>
                <option value="Bank Transfer">Bank Transfer</option>
            </select>
            
            <button type="submit">Pay Now</button>

        </form>
    </div>

</body>
</html>