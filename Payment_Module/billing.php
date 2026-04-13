<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Billing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <h2>Update Billing Information</h2>
        
        <?php
        $current_patient_id = 1; 
        $sql = "SELECT * FROM billing_info WHERE patient_id = '$current_patient_id'";
        $result = $conn->query($sql);
        $patient = $result->fetch_assoc();
        ?>
        
        <form action='process_billing.php' method='POST'>
            <input type='hidden' name='patient_id' value='<?php print $patient['patient_id']; ?>'>
            
            <label><strong>Full Name:</strong></label>
            <input type='text' name='full_name' value='<?php print $patient['full_name']; ?>' required>
            
            <label><strong>Billing Address:</strong></label>
            <textarea name='billing_address' rows='4' required><?php print $patient['billing_address']; ?></textarea>
            
            <label><strong>Default Payment Method:</strong></label>
            <select name='default_method'>
                <option value='Credit Card' <?php if($patient['default_payment_method'] == 'Credit Card') print 'selected'; ?>>Credit Card</option>
                <option value='E-Wallet' <?php if($patient['default_payment_method'] == 'E-Wallet') print 'selected'; ?>>Virtual E-Wallet</option>
                <option value='Bank Transfer' <?php if($patient['default_payment_method'] == 'Bank Transfer') print 'selected'; ?>>Bank Transfer</option>
            </select>
            
            <button type='submit'>Save Changes</button>
        </form>
        
        <a href="checkout.php">Back to Checkout</a>
        
        <?php $conn->close(); ?>
    </div>

</body>
</html>