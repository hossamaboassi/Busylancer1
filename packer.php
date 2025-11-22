<?php
// Configuration
$outputFile = 'entire_project_code.txt';
// Extensions to include (Code files)
$allowedExtensions = ['php', 'html', 'css', 'js', 'sql', 'json', 'htaccess'];
// Folders to ignore
$ignoredDirs = ['.git', 'node_modules', 'vendor', 'images', 'uploads', 'img'];

// Open output file
$handle = fopen($outputFile, 'w');

function scanDirectory($dir, $handle, $allowedExtensions, $ignoredDirs) {
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if ($file === 'packer.php' || $file === 'entire_project_code.txt') continue; // Skip self and output

        $path = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_dir($path)) {
            if (!in_array($file, $ignoredDirs)) {
                scanDirectory($path, $handle, $allowedExtensions, $ignoredDirs);
            }
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, $allowedExtensions)) {
                fwrite($handle, "\n\n========================================\n");
                fwrite($handle, "FILE PATH: " . $path . "\n");
                fwrite($handle, "========================================\n\n");
                fwrite($handle, file_get_contents($path));
            }
        }
    }
}

scanDirectory('.', $handle, $allowedExtensions, $ignoredDirs);
fclose($handle);

echo "Done! Created $outputFile. Please upload this file to the AI.";
?>