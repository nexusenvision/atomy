<?php

// Script to strip foreign keys and indexes from all migration files
// except the consolidated one

$migrationsDir = __DIR__ . '/database/migrations';
$consolidatedFile = '2025_12_31_999999_add_all_foreign_keys_and_indexes.php';

$files = glob($migrationsDir . '/*.php');

foreach ($files as $file) {
    $filename = basename($file);
    
    if ($filename === $consolidatedFile) {
        continue;
    }
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $output = [];
    $skip = false;
    $skipUntilSemicolon = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Check if this is the start of a foreign key or index definition
        if (preg_match('/\$table->(foreign|foreignUlid|foreignId|index|unique)\(/', $trimmed)) {
            // Check if it ends with semicolon on same line
            if (strpos($trimmed, ';') !== false) {
                // Single line - just comment it out
                $output[] = preg_replace('/^(\s*)/', '$1// ', $line);
            } else {
                // Multi-line - start skipping
                $skipUntilSemicolon = true;
                $output[] = preg_replace('/^(\s*)/', '$1// ', $line);
            }
            continue;
        }
        
        // If we're skipping a multi-line definition
        if ($skipUntilSemicolon) {
            $output[] = preg_replace('/^(\s*)/', '$1// ', $line);
            if (strpos($trimmed, ';') !== false) {
                $skipUntilSemicolon = false;
            }
            continue;
        }
        
        $output[] = $line;
    }
    
    file_put_contents($file, implode("\n", $output));
    echo "✓ Processed: $filename\n";
}

echo "\n✅ All migrations processed!\n";
