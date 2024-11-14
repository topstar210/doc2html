<?php
require 'constants.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'file_move') {
        list($host, $oldpath) = explode("temp_extract", $_POST['oldpath']);
        list($host, $newpath) = explode("temp_extract", $_POST['newpath']);

        copy("temp_extract".$newpath, "temp_extract".$oldpath);
        
        echo json_encode("OK"); exit;
    } else {
        $uploadDirectory = "temp_extract/uploads/";
        
        // Ensure the directory exists, and if not, create it
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }
        
        // Initialize an array to hold the response messages for each file
        $response = [];
        
        if (isset($_FILES['files']) && count($_FILES['files']['name']) > 0) {
            // Loop through each file in the "files" array
            for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                $fileName = basename($_FILES['files']['name'][$i]);
                $filePath = $uploadDirectory . $fileName;
                $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                $targetFilePath = $uploadDirectory . "new_" . $i . "_" . time() . '.' . $fileExtension;
                
                // Check if file was uploaded successfully
                if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetFilePath)) {
                    list($width, $height) = getimagesize($targetFilePath);
                    $response[] = [
                        'path' => $targetFilePath,
                        'info' => [
                            'width' => $width,
                            'height' => $height
                        ],
                        'status' => 'success',
                        'message' => "File uploaded successfully!"
                    ];
                } else {
                    $response[] = [
                        'path' => $targetFilePath,
                        'status' => 'error',
                        'message' => "Failed to upload file."
                    ];
                }
            }
        } else {
            $response[] = [
                'status' => 'error',
                'message' => "No files were uploaded."
            ];
        }
        
        // Send the response back as JSON
        header('Content-Type: application/json');
        echo json_encode($response); exit;
    }
}
?>

<!-- 
<link rel="stylesheet" href="assets/multi-upload.css">

<script>
    var warningSize = <?php echo $warningSize; ?>;
</script>

<div class="file-drop-area">
    <div class="file-drop-zone" id="fileDropZone">
        Drag & drop your files here or click to select
    </div>
    <div class="file-list-preview" id="fileListPreview">No files selected</div>
    <button class="upload-btn" id="uploadBtn" disabled>Import new photos</button>
</div>

<script src="assets/multi-upload-script.js"></script> -->
