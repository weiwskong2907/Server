<?php
// Define secure access constant
define('SECURE_ACCESS', true);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

// Test results array
$results = [];

// 1. Test PHP Environment
$results['PHP Environment'] = [
    'PHP Version' => PHP_VERSION,
    'Display Errors' => ini_get('display_errors'),
    'Error Reporting' => ini_get('error_reporting'),
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time'),
    'Upload Max Filesize' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size')
];

// 2. Test File System
$results['File System'] = [
    'Root Directory' => [
        'Path' => __DIR__,
        'Writable' => is_writable(__DIR__),
        'Permissions' => substr(sprintf('%o', fileperms(__DIR__)), -4)
    ],
    'Includes Directory' => [
        'Path' => __DIR__ . '/includes',
        'Exists' => is_dir(__DIR__ . '/includes'),
        'Writable' => is_writable(__DIR__ . '/includes'),
        'Permissions' => is_dir(__DIR__ . '/includes') ? substr(sprintf('%o', fileperms(__DIR__ . '/includes')), -4) : 'N/A'
    ],
    'Uploads Directory' => [
        'Path' => __DIR__ . '/uploads',
        'Exists' => is_dir(__DIR__ . '/uploads'),
        'Writable' => is_writable(__DIR__ . '/uploads'),
        'Permissions' => is_dir(__DIR__ . '/uploads') ? substr(sprintf('%o', fileperms(__DIR__ . '/uploads')), -4) : 'N/A'
    ],
    'Logs Directory' => [
        'Path' => __DIR__ . '/logs',
        'Exists' => is_dir(__DIR__ . '/logs'),
        'Writable' => is_writable(__DIR__ . '/logs'),
        'Permissions' => is_dir(__DIR__ . '/logs') ? substr(sprintf('%o', fileperms(__DIR__ . '/logs')), -4) : 'N/A'
    ]
];

// 3. Test Database Connection
try {
    $pdo = get_db_connection();
    $results['Database Connection'] = [
        'Status' => 'Connected',
        'Server Info' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
        'Client Info' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION)
    ];
} catch (Exception $e) {
    $results['Database Connection'] = [
        'Status' => 'Failed',
        'Error' => $e->getMessage()
    ];
}

// 4. Test Required Files
$required_files = [
    'config.php',
    'database.php',
    'auth.php',
    'functions.php',
    'layout.php'
];

$results['Required Files'] = [];
foreach ($required_files as $file) {
    $file_path = __DIR__ . '/includes/' . $file;
    $results['Required Files'][$file] = [
        'Exists' => file_exists($file_path),
        'Readable' => is_readable($file_path),
        'Size' => file_exists($file_path) ? filesize($file_path) . ' bytes' : 'N/A'
    ];
}

// 5. Test PHP Configuration
$results['PHP Configuration'] = [
    'Session Save Path' => session_save_path(),
    'Session Status' => session_status(),
    'Default Timezone' => date_default_timezone_get(),
    'GD Library' => extension_loaded('gd'),
    'PDO Extensions' => PDO::getAvailableDrivers(),
    'OpenSSL' => extension_loaded('openssl'),
    'cURL' => extension_loaded('curl'),
    'JSON' => extension_loaded('json')
];

// 6. Test Error Logs
$error_log = __DIR__ . '/logs/error.log';
$results['Error Logs'] = [
    'Log File' => [
        'Path' => $error_log,
        'Exists' => file_exists($error_log),
        'Writable' => is_writable($error_log),
        'Size' => file_exists($error_log) ? filesize($error_log) . ' bytes' : 'N/A',
        'Last Modified' => file_exists($error_log) ? date('Y-m-d H:i:s', filemtime($error_log)) : 'N/A'
    ]
];

// Output results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #444;
            margin-top: 20px;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .warning {
            color: #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Test Results</h1>
        <?php foreach ($results as $section => $data): ?>
            <div class="section">
                <h2><?php echo htmlspecialchars($section); ?></h2>
                <?php if (is_array($data)): ?>
                    <table>
                        <tr>
                            <th>Setting</th>
                            <th>Value</th>
                        </tr>
                        <?php foreach ($data as $key => $value): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($key); ?></td>
                                <td>
                                    <?php
                                    if (is_bool($value)) {
                                        echo $value ? '<span class="success">Yes</span>' : '<span class="error">No</span>';
                                    } elseif (is_array($value)) {
                                        echo '<pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p><?php echo htmlspecialchars($data); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html> 