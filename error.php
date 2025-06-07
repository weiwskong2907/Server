<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get error information
$error_code = $_SERVER['REDIRECT_STATUS'] ?? 500;
$error_message = '';

switch ($error_code) {
    case 404:
        $error_message = 'Page not found';
        break;
    case 403:
        $error_message = 'Access forbidden';
        break;
    case 500:
        $error_message = 'Internal server error';
        break;
    default:
        $error_message = 'An error occurred';
}

// Get error details from PHP
$error_details = error_get_last();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $error_code; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="display-1 text-danger mb-4"><?php echo $error_code; ?></h1>
                        <h2 class="mb-4"><?php echo $error_message; ?></h2>
                        
                        <?php if ($error_details): ?>
                            <div class="alert alert-danger text-start">
                                <h5>Error Details:</h5>
                                <p><strong>Message:</strong> <?php echo htmlspecialchars($error_details['message']); ?></p>
                                <p><strong>File:</strong> <?php echo htmlspecialchars($error_details['file']); ?></p>
                                <p><strong>Line:</strong> <?php echo $error_details['line']; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="/" class="btn btn-primary">Go to Homepage</a>
                            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 