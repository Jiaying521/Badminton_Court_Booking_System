<?php
session_start();
session_destroy();
header('Location: Customer_Module/homepage.php');
exit;