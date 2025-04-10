<?php
// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="product_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create the CSV content
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel handling
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['product_name', 'price', 'category', 'stock']);

// Add sample data matching the format
$sample_data = [
    ['Organic Apples', '150', 'fruits-organic', '88'],
    ['Red Tomatoes', '30', 'vegetables', '77'],
    ['Fresh Milk', '60', 'milk', '3'],
    ['Sunflower Seeds', '120', 'seeds', '33'],
    ['Wheat Flour', '45', 'grains', '5'],
    ['Carrots', '40', 'vegetables', '50'],
    ['Bananas', '80', 'fruits', '65']
];

// Add sample rows
foreach ($sample_data as $row) {
    fputcsv($output, $row);
}

// Close the file
fclose($output);
exit();
?> 