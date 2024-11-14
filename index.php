<?php
require 'constants.php';
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['document'])) {
    $blocks = isset($_POST['blocks']) ? json_decode($_POST['blocks'], true) : [];
    $fileName = $_POST['fileName'] ?? '';
    
    if (isset($_POST['save'])) {
        $timestamp = time();
        $folder = "dist/import_$timestamp";
        mkdir($folder);

        $htmlContent = json_decode($_POST['docmentRes']);

        file_put_contents("$folder/document.html", $htmlContent);
        copyDirectory("temp_extract/word/media","$folder/assets");

        $extractPath = 'temp_extract/';
        deleteDirectory($extractPath);
        echo '<div class="comfirm-msg">
            <div class="msg-body">
                <div>Document saved successfully in folder: '.$folder.' </div>
                <button type="button" onclick="location.href=\'index.php\'">OK</button>    
            </div>
        </div>';
        // exit;
    }
} elseif (isset($_FILES['document'])) {
    $filePath = $_FILES['document']['tmp_name'];
    $fileExt = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
    $fileName = $_FILES['document']['name'];

    $extractPath = 'temp_extract/';
    deleteDirectory($extractPath);
    if (in_array($fileExt, ['doc', 'docx'])) {
        $blocks = parseDocument($filePath);
        // echo "<pre>";
        // print_r($blocks);exit;
    } else {
        $error = 'Please upload a .doc or .docx file.';
    }
}

$flexwrap="";
$allImages = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Importer</title>
    <link rel="stylesheet" href="assets/multi-upload.css?v=1">
    <link rel="stylesheet" href="assets/dragdrop.css?v=1">
    <link rel="stylesheet" href="assets/main.css?v=1">
    <script>
        var warningSize = <?php echo $warningSize; ?>;
    </script>
</head>
<body>
    <div class="container">
        <h1><?php echo isset($blocks) ? "The Document has been imported" : "Import MS Word Document"; ?></h1>
        <?php if (isset($blocks)) { ?>
            <div>Mark items to remove and click "Save Document"</div>
            <div class="filename">Name of file: <?php echo htmlspecialchars($fileName); ?></div>

            <form method="POST" id="doc-home">
                <input type="hidden" name="blocks" value='<?php echo json_encode($blocks); ?>' />
                <input type="hidden" name="fileName" value="<?php echo htmlspecialchars($fileName); ?>" />
                <input type="hidden" id="removeIndices" name="removeIndices" value="" />
                <input type="hidden" name="docmentRes" id="docmentRes" value="" />
                <input type="hidden" name="save" id="save" value="" />

                <?php foreach ($blocks as $index => $sections) { ?>
                    <div class="section" id="section-<?php echo $index; ?>">
                        <div class="header">
                            <div>
                                <?php echo isset($sections['images']) ? getImageInfo($sections['images']) : ""; ?>
                                <?php if(isset($sections[0]) && !empty(trim($sections[0]))) { ?>
                                    <label>
                                        <input type="checkbox" id="title-<?php echo $index; ?>" onclick="toggleStrong(<?php echo $index; ?>, 'title')"> Title
                                    </label>
                                    <label>
                                        <input type="checkbox" id="introduction-<?php echo $index; ?>" onclick="toggleStrong(<?php echo $index; ?>, 'introduction')"> Introduction
                                    </label>
                                <?php $flexwrap=""; } else { $flexwrap=" flex-wrap"; } ?>
                            </div>
                            <span class="remove" onclick="markForRemoval(<?php echo $index; ?>)">🗑️</span>
                        </div>
                        <div class="<?php echo isset($sections['images']) ? 'flex' : 'block'; echo $flexwrap; ?>">
                            <?php
                            $html = '';

                            $textGroup = '';
                            foreach ($sections as $skey => $section) {
                                if ($skey === 'images') {
                                    if (!empty($textGroup)) {
                                        $html .= '<div><p>' . $textGroup . '</p></div>';
                                        $textGroup = '';
                                    }
                                    $imageHtm = '';
                                    foreach ($section as $ikey => $image) {
                                        $allImages[] = $image;
                                        $statusHtm = '';
                                        if(!isset($sections[0]) || empty(trim($sections[0]))) {
                                            $statusHtm .= '<div>'.photoStatus($image, $warningSize).'</div>';
                                        }
                                        $imageHtm .= '<div class="article-photo" style="width:calc('.((100)/(count($section)>1?2:1) - 2).'% - 20px);">
                                            <img src="' . htmlspecialchars($image['url']) . '?v='.$index.$ikey.time().'" alt="image" loading="lazy" class="img-responsive" />
                                            '.$statusHtm.'
                                        </div>';
                                    }
                                    $html .= $imageHtm;
                                } else {
                                    $textGroup .= $section;
                                }
                            }
                            if (!empty($textGroup)) {
                                $html .= '<p id="text_'.$index.'">' .$textGroup. '</p>';
                            }
                            echo $html;
                            ?>
                        </div>
                    </div>
                <?php } ?>
                <div class="section" >
                    <div class="header">
                        <div>
                            Photos in the article
                        </div>
                    </div>
                    <div class="all-article-images">
                        <?php
                            foreach ($allImages as $key => $image) {
                                $imgstatusHtm = '<div>'.photoStatus($image, $warningSize).'</div>';
                                echo '<div class="article-photo" ondrop="drop(event)" ondragover="allowDrop(event)" style="width:calc(31% - 20px);">
                                    <img src="' . htmlspecialchars($image['url']) . '?v='.$key.time().'" alt="image" loading="lazy" class="img-responsive" />
                                    '.$imgstatusHtm.'
                                </div>';
                            }
                        ?>
                    </div>
                </div>
                <div class="section" >
                    <div class="header">
                        <div>
                            New Photos
                        </div>
                    </div>
                    <div class="file-drop-area">
                        <div class="file-drop-zone" id="fileDropZone">
                            Drag & drop your files here or click to select
                        </div>
                        <div class="file-list-preview" id="fileListPreview">No files selected</div>
                        <button class="upload-btn" id="uploadBtn" type="button" disabled>Import new photos</button>
                    </div>  
                </div>
                <button type="button" onclick="saveDocument()" id="doc-save">Save Document</button>
                <button type="button" onclick="location.href='index.php'">Cancel</button>
            </form>
        <?php } else { ?>
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="drop-area" id="dropArea">
                Drag & drop your file here or click to select
                </div>
                <input type="file" name="document" id="fileInput" accept=".doc,.docx" style="display: none;">
                <div class="file-preview" id="filePreview">No file selected</div>
                <button type="submit" id="submitBtn" disabled>Import Document</button>
            </form>

            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <?php } ?>
    </div>
</body>

<script src="assets/script.js?v=1"></script>
<script src="assets/multi-upload-script.js?v=1"></script>
<script src="assets/dragdrop.js?v=1"></script>
</html>
