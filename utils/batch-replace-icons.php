<?php
/**
 * Batch Icon Replacement Script
 * Replaces all Google Material Icons with Lucide Icons across the system
 * 
 * Usage: Run this script from command line
 * php batch-replace-icons.php
 */

// Include the icon mapping
require_once __DIR__ . '/replace-icons.php';

// Directories to scan
$directories = [
    '../admin',
    '../modals',
    '../pages',
    '../partials',
    '../public',
    '../includes'
];

// File extensions to process
$extensions = ['php', 'html'];

// Statistics
$stats = [
    'files_processed' => 0,
    'files_modified' => 0,
    'icons_replaced' => 0,
    'cdn_links_replaced' => 0
];

/**
 * Replace Google Material Icons CDN with Lucide CDN
 */
function replaceCDN($content) {
    global $stats;
    
    // Replace Google Fonts Material Icons link with Lucide CDN
    $pattern = '/<link[^>]*fonts\.googleapis\.com\/css2\?family=Material\+Symbols\+Outlined[^>]*>/i';
    $replacement = '<script src="https://unpkg.com/lucide@latest"></script>';
    
    $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
    
    if ($count > 0) {
        $stats['cdn_links_replaced'] += $count;
    }
    
    return $newContent;
}

/**
 * Replace Material Icon syntax with Lucide Icon syntax
 */
function replaceIconSyntax($content) {
    global $iconMap, $stats;
    
    // Pattern to match: <span class="material-symbols-outlined">icon_name</span>
    $pattern = '/<span\s+class="material-symbols-outlined"(?:[^>]*)>([^<]+)<\/span>/i';
    
    $newContent = preg_replace_callback($pattern, function($matches) use ($iconMap, &$stats) {
        $materialIcon = trim($matches[1]);
        
        // Get Lucide equivalent or use the same name
        $lucideIcon = $iconMap[$materialIcon] ?? $materialIcon;
        
        // Extract any additional attributes from the original span
        $attributes = '';
        if (preg_match('/<span\s+class="material-symbols-outlined"([^>]*)>/i', $matches[0], $attrMatch)) {
            $attrs = trim($attrMatch[1]);
            if (!empty($attrs)) {
                // Remove class attribute and keep others
                $attrs = preg_replace('/class="[^"]*"/i', '', $attrs);
                $attributes = trim($attrs);
            }
        }
        
        $stats['icons_replaced']++;
        
        // Return Lucide icon format
        if (!empty($attributes)) {
            return '<i data-lucide="' . $lucideIcon . '" ' . $attributes . '></i>';
        } else {
            return '<i data-lucide="' . $lucideIcon . '"></i>';
        }
    }, $content);
    
    return $newContent;
}

/**
 * Add Lucide initialization script if not present
 */
function addLucideInit($content) {
    // Check if lucide.createIcons() is already present
    if (stripos($content, 'lucide.createIcons()') !== false) {
        return $content;
    }
    
    // Add initialization script before closing body tag
    $initScript = "\n    <script>\n        // Initialize Lucide icons\n        if (typeof lucide !== 'undefined') {\n            lucide.createIcons();\n        }\n    </script>\n";
    
    // Insert before </body>
    if (stripos($content, '</body>') !== false) {
        $content = str_ireplace('</body>', $initScript . '</body>', $content);
    }
    
    return $content;
}

/**
 * Process a single file
 */
function processFile($filePath) {
    global $stats;
    
    $stats['files_processed']++;
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Step 1: Replace CDN links
    $content = replaceCDN($content);
    
    // Step 2: Replace icon syntax
    $content = replaceIconSyntax($content);
    
    // Step 3: Add Lucide initialization
    $content = addLucideInit($content);
    
    // Only write if content changed
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $stats['files_modified']++;
        echo "✓ Modified: " . basename($filePath) . "\n";
        return true;
    }
    
    return false;
}

/**
 * Scan directory recursively
 */
function scanDirectory($dir, $extensions) {
    $files = [];
    
    if (!is_dir($dir)) {
        return $files;
    }
    
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            $files = array_merge($files, scanDirectory($path, $extensions));
        } elseif (is_file($path)) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $files[] = $path;
            }
        }
    }
    
    return $files;
}

// Main execution
echo "=== Lucide Icon Replacement Script ===\n\n";
echo "Scanning directories...\n";

$allFiles = [];
foreach ($directories as $dir) {
    $dirPath = __DIR__ . '/' . $dir;
    $files = scanDirectory($dirPath, $extensions);
    $allFiles = array_merge($allFiles, $files);
    echo "Found " . count($files) . " files in " . basename($dirPath) . "\n";
}

echo "\nTotal files to process: " . count($allFiles) . "\n";
echo "Starting replacement...\n\n";

foreach ($allFiles as $file) {
    processFile($file);
}

echo "\n=== Replacement Complete ===\n";
echo "Files processed: " . $stats['files_processed'] . "\n";
echo "Files modified: " . $stats['files_modified'] . "\n";
echo "Icons replaced: " . $stats['icons_replaced'] . "\n";
echo "CDN links replaced: " . $stats['cdn_links_replaced'] . "\n";
echo "\nDone!\n";
?>
