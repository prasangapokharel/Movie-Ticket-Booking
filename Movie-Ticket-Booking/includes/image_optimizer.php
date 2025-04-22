<?php
require 'vendor/autoload.php'; // Include Composer dependencies


use Spatie\ImageOptimizer\OptimizerChainFactory;

function optimizeImage($imagePath) {
    $optimizerChain = OptimizerChainFactory::create();
    
    if (file_exists($imagePath)) {
        $optimizerChain->optimize($imagePath);
        return true;
    }
    return false;
}
?>
