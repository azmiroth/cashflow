<?php
/**
 * Debug script to output all January transactions to a file
 * Run from SiteGround: php debug_transactions.php
 * Output will be saved to: ../debug_transactions.txt
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;

$transactions = Transaction::whereBetween('transaction_date', ['2026-01-01', '2026-01-31'])
    ->orderBy('transaction_date')
    ->get();

$output = "=== JANUARY 2026 TRANSACTIONS ===\n";
$output .= "Total count: " . $transactions->count() . "\n\n";

$totalInflows = 0;
$totalOutflows = 0;
$includedInflows = 0;
$includedOutflows = 0;

foreach ($transactions as $t) {
    $excluded = $t->excluded_from_analysis ? 'YES' : 'NO';
    $output .= sprintf(
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

$output .= "\n=== SUMMARY ===\n";
$output .= "Total Inflows (all): " . $totalInflows . "\n";
$output .= "Total Outflows (all): " . $totalOutflows . "\n";
$output .= "Total Net (all): " . ($totalInflows - $totalOutflows) . "\n\n";

$output .= "Included Inflows: " . $includedInflows . "\n";
$output .= "Included Outflows: " . $includedOutflows . "\n";
$output .= "Included Net: " . ($includedInflows - $includedOutflows) . "\n";

// Save to parent directory
$filePath = dirname(__DIR__) . '/debug_transactions.txt';
file_put_contents($filePath, $output);

echo "Debug file saved to: " . $filePath . "\n";
echo "You can access it via SiteGround File Manager\n\n";
echo $output;
