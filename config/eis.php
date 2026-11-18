<?php

return [
    'target' => [
        'red_below' => 70,
        'yellow_below' => 90,
    ],
    'exception' => [
        'rejected_rate_above' => 50,
        'inactive_days' => 7,
        'response_hours_above' => 24,
    ],
    'filter' => [
        'max_months' => 24,
    ],
    'vikor' => [
        'v' => 0.5,
    ],
    'criteria' => [
        'sales' => ['code' => 'sales', 'name' => 'Penjualan', 'type' => 'benefit', 'weight' => 0.35, 'metric' => 'total_sales'],
        'transactions' => ['code' => 'transaction_count', 'name' => 'Jumlah Transaksi', 'type' => 'benefit', 'weight' => 0.20, 'metric' => 'transactions_count'],
        'approval' => ['code' => 'restock_approval_rate', 'name' => 'Persen Restock Disetujui', 'type' => 'benefit', 'weight' => 0.20, 'metric' => 'approval_rate'],
        'evaluations' => ['code' => 'supplier_evaluations', 'name' => 'Penilaian Supplier Diinput', 'type' => 'benefit', 'weight' => 0.10, 'metric' => 'evaluations_count'],
        'response' => ['code' => 'avg_response_hours', 'name' => 'Waktu Respons rata-rata', 'type' => 'cost', 'weight' => 0.15, 'metric' => 'avg_response_hours'],
    ],
];
