<?php
require 'includes/image_optimizer.php'; // Include the optimizer function

$directory = "uploads/theaters/";
$images = glob($directory . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

foreach ($images as $image) {
    if (optimizeImage($image)) {
        echo "Optimized: $image <br>";
    } else {
        echo "Failed to optimize: $image <br>";
    }
}
?>

<h1>DOne</h1>

<script src="assets/js/tailwind.js"> </script>