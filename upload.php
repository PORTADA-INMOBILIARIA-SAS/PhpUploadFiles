<?php

/**
* *@description que gonorrea php
* */


$allowedOrigins = [
    'http://localhost:3000',
    'http://10.1.1.249:3000',
    'https://apicrinmo.azurewebsites.net',
];

// Get request origin
$requestOrigin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;

// Check if request origin is allowed
if (in_array($requestOrigin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
}

// Set other CORS headers
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, X-CSRF-Token, authorization, accept, origin, apiclient, Cache-Control, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET, PUT");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Set up CORS headers


// Function to verify authorization
function verifyAuthorization(string $authToken, string $apiClient) : bool {
    // Get authentication data from environment variables
    $expectedToken = apache_getenv('AUTH_TOKEN');
    $expectedApiClient = apache_getenv( 'API_CLIENT' );

	if ($authToken == "" || $apiClient == "") {
		return false;
   	}


    // Check if provided token and API client match expected values
    if ($authToken == $expectedToken && $apiClient == $expectedApiClient) {
        return true; // Authorization successful
    } else {
        return false; // Authorization failed
    }
}

// Function to verify uploaded files
function verifyFiles(string $mimetype) : bool {
// Define allowed MIME types
    $allowedFiles = [
        'image/jpeg',
        'image/jpg',
        'video/mp4',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];



    return in_array($mimetype, $allowedFiles);
}

// Function to handle file uploads
function handleUpload($module, $id) {

	if (isset($_FILES)) {
		$files = $_FILES;
		$filenames = [];

		foreach ($files as  $file) {

			$fileMimeType = mime_content_type($file['tmp_name']);

			if(!verifyFiles($fileMimeType)) {
				http_response_code(401);
				$response['error'] = "File type not allowed";
				echo json_encode($response);
				exit();
			}


			$uploadDir = "files/$module/$id/";
			$response = [];


			$filename = basename($file['name']);
			$fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
			$destination = $uploadDir . $fileExtension . "/"  ;


			if (!file_exists($destination)) {
				mkdir($destination, 0777, true);
			}



			if (move_uploaded_file($file["tmp_name"], $destination  . $filename)) {
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
		$response['destination'] = $uploadDir . "{file_extension}";

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

		// Get authorization headers
		$authToken = $_SERVER['HTTP_AUTH_TOKEN'] ?? '';
		$apiClient = $_SERVER['HTTP_API_CLIENT'] ?? '';


		if(!verifyAuthorization($authToken, $apiClient)) {
			http_response_code(401);
			$response['response'] = "No tienes permiso para acceder a esto";
			echo json_encode($response);
			return;
		}

		if ($module !== '' && $id !== '') {
			handleUpload($module, $id);
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
