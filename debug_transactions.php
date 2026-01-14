<?php
/**
 * Debug script to output all January transactions to a file
 * Run from SiteGround: php debug_transactions.php > /tmp/january_transactions.txt
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;

$transactions = Transaction::whereBetween('transaction_date', ['2026-01-01', '2026-01-31'])
    ->orderBy('transaction_date')
    ->get();

echo "=== JANUARY 2026 TRANSACTIONS ===\n";
echo "Total count: " . $transactions->count() . "\n\n";

$totalInflows = 0;
$totalOutflows = 0;
$includedInflows = 0;
$includedOutflows = 0;

foreach ($transactions as $t) {
    $excluded = $t->excluded_from_analysis ? 'YES' : 'NO';
    echo sprintf(
        "ID: %d | Date: %s | Type: %s | Amount: %s | Excluded: %s | Description: %s\n",
        $t->id,
        $t->transaction_date,
        $t->type,
        $t->amount,
        $excluded,
        $t->description
    );

    if ($t->type === 'credit') {
        $totalInflows += $t->amount;
        if (!$t->excluded_from_analysis) {
            $includedInflows += $t->amount;
        }
    } else {
        $totalOutflows += $t->amount;
        if (!$t->excluded_from_analysis) {
            $includedOutflows += $t->amount;
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total Inflows (all): " . $totalInflows . "\n";
echo "Total Outflows (all): " . $totalOutflows . "\n";
echo "Total Net (all): " . ($totalInflows - $totalOutflows) . "\n\n";

echo "Included Inflows: " . $includedInflows . "\n";
echo "Included Outflows: " . $includedOutflows . "\n";
echo "Included Net: " . ($includedInflows - $includedOutflows) . "\n";
