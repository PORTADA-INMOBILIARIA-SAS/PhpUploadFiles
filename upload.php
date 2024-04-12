<?php


// Set up CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Function to verify uploaded files
function verifyFiles() {
    // Implement your file verification logic here
    // For example, check file size, type, etc.
    return true;
}

// Function to handle file uploads
function handleUpload($module, $id) {
    $uploadDir = "files/$module/$id/";
    $response = [];

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }


    if (isset($_FILES)) {
        $files = $_FILES;
        $filenames = [];

        foreach ($files as  $file) {
            $filename = basename($file['name']);
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            $destination = $uploadDir .   $fileExtension ;

			var_dump($file);

            if (move_uploaded_file($file["tmp_name"], $destination . $filename)) {
                $filenames[] = $filename;
            } else {
                http_response_code(500);
                $response['error'] = "Error uploading files!";
                echo json_encode($response);
                exit();
            }
        }

        $response['response'] = "Files uploaded";
        $response['files'] = $filenames;
        $response['destination'] = $uploadDir;

        echo json_encode($response);
    } else {
        http_response_code(400);
        $response['response'] = "No files uploaded";
        echo json_encode($response);
    }
}

// Get request method and execute corresponding action
$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case 'POST':
        $module = $_POST['module'] ?? '';
        $id = $_POST['id'] ?? '';

        if ($module !== '' && $id !== '') {
            if (verifyFiles()) {
                handleUpload($module, $id);
            } else {
                http_response_code(400);
                $response['response'] = "File verification failed";
                echo json_encode($response);
            }
        } else {
            http_response_code(400);
            $response['response'] = "Module or ID not provided";
            echo json_encode($response);
        }
        break;

    default:
        http_response_code(405);
        $response['response'] = "Method Not Allowed";
        echo json_encode($response);
        break;
}
