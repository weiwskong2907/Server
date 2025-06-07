<?php
// Custom error handler
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $log_file = __DIR__ . '/../logs/error.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0775, true);
    }
    
    // Format error message
    $timestamp = date('Y-m-d H:i:s');
    $error_type = match($errno) {
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER DEPRECATED',
        default => 'UNKNOWN ERROR'
    };
    
    $message = "[$timestamp] $error_type: $errstr in $errfile on line $errline\n";
    
    // Log to file
    error_log($message, 3, $log_file);
    
    // Display error if in development mode
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>$error_type:</strong> $errstr<br>";
        echo "<small>File: $errfile<br>Line: $errline</small>";
        echo "</div>";
    }
    
    // Don't execute PHP internal error handler
    return true;
}

// Custom exception handler
function custom_exception_handler($exception) {
    $log_file = __DIR__ . '/../logs/error.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0775, true);
    }
    
    // Format exception message
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] EXCEPTION: " . $exception->getMessage() . "\n";
    $message .= "File: " . $exception->getFile() . "\n";
    $message .= "Line: " . $exception->getLine() . "\n";
    $message .= "Stack trace:\n" . $exception->getTraceAsString() . "\n";
    
    // Log to file
    error_log($message, 3, $log_file);
    
    // Display exception if in development mode
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<small>File: " . $exception->getFile() . "<br>";
        echo "Line: " . $exception->getLine() . "</small>";
        echo "</div>";
    }
}

// Set error and exception handlers
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
?> 