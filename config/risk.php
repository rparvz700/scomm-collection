<?php

return [
    'ranges' => [
        ['label' => '0.00 - 1.50', 'min' => 0, 'max' => 1.50, 'category' => 'Best'],
        ['label' => '1.51 - 2.00', 'min' => 1.51, 'max' => 2.00, 'category' => 'Good'],
        ['label' => '2.01 - 2.50', 'min' => 2.01, 'max' => 2.50, 'category' => 'Moderate'],
        ['label' => '2.51 - 2.99', 'min' => 2.51, 'max' => 2.99, 'category' => 'Risky'],
        ['label' => '3.00 - 3.49', 'min' => 3.00, 'max' => 3.49, 'category' => 'High Risky'],
        ['label' => '>= 3.50', 'min' => 3.50, 'max' => null, 'category' => 'Most Risky'],
    ],
];
