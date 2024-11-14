<?php
require 'autoload.php'; // Load PHPWord library
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\Image;

function photoStatus($image, $warningSize) {
    $htm = $image['width'] . "×" . $image['height'] . "px ";
    if (intval($image['width']) < $warningSize) {
        $htm .= '<span class="img_no">[NO]</span>';
    } else {
        $htm .= '<span class="img_ok">[OK]</span>';
    }
    return $htm;
}

function deleteDirectory($dir) {
    if (is_dir($dir)) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "$dir/$file";
            (is_dir($filePath)) ? deleteDirectory($filePath) : unlink($filePath);
        }
        rmdir($dir);
    }
}

function getImageFileSize($filePath, $unit = 'bytes') {
    if (!file_exists($filePath)) {
        return "File does not exist.";
    }

    $fileSize = filesize($filePath);

    switch (strtolower($unit)) {
        case 'kb':
            return round($fileSize / 1024, 2) . 'KB';
        case 'mb':
            return round($fileSize / (1024 * 1024), 2) . 'MB';
        case 'bytes':
        default:
            return $fileSize . 'bytes';
    }
}

function getImageInfo($iamges) {
    $htm = '<label>Photo<small>(';
    foreach($iamges as $key => $image) {
        $htm .= $image['width'] . "×" . $image['height'] . "px - " . getImageFileSize($image['url'], 'mb');
        if ($key < count($iamges) - 1) $htm .= " | ";
    }
    $htm .= ')</small></label>';

    return $htm;
}

function copyDirectory($src, $dst) {
    if (!is_dir($src)) {
        return "Source directory does not exist.";
    }
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $srcFile = $src . DIRECTORY_SEPARATOR . $file;
        $dstFile = $dst . DIRECTORY_SEPARATOR . $file;

        if (is_dir($srcFile)) {
            copyDirectory($srcFile, $dstFile);
        } else {
            copy($srcFile, $dstFile);
        }
    }

    closedir($dir);
}

function replaceImgUrl($path) {
    return str_replace("temp_extract/word/media","assets", $path);
}

function renameFileWithTimestamp($filePath) {
    // Check if the file exists
    if (!file_exists($filePath)) {
        return "Error: File does not exist.";
    }
    
    // Get the directory, filename, and extension
    $directory = dirname($filePath);
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    $fileNameWithoutExt = pathinfo($filePath, PATHINFO_FILENAME);
    
    // Create a new filename with the current timestamp
    $newFileName = $fileNameWithoutExt . '_' . time() . '.' . $fileExtension;
    $newFilePath = $directory . '/' . $newFileName;

    // Rename the file
    if (rename($filePath, $newFilePath)) {
        return $newFilePath; // Return the new file path if successful
    } else {
        return "Error: Could not rename the file.";
    }
}

function parseDocument($filePath) {
    $phpWord = IOFactory::load($filePath);
    $blocks = [];

    $zip = new ZipArchive();
    if ($zip->open($filePath) === TRUE) {
        $extractPath = 'temp_extract/';
        $zip->extractTo($extractPath);
        $zip->close();

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof TextRun) {
                    $subBlocks = [];
                    foreach ($element->getElements() as $textElement) {
                        if ($textElement instanceof Image) {
                            $path = $textElement->getSource();
                            $imageSrc = $extractPath . substr($path, strpos($path, '#') + 1);
                            if (file_exists($imageSrc)) {
                                list($width, $height) = getimagesize($imageSrc);
                                $imageData = [
                                    'url' => renameFileWithTimestamp($imageSrc),
                                    'width' => $width,
                                    'height' => $height,
                                    'size' => getImageFileSize($imageSrc)
                                ];
                                $subBlocks['images'][] = $imageData;
                            }
                        } elseif ($textElement instanceof Text) {
                            $text = $textElement->getText();

                            $fontStyles = $textElement->getFontStyle();
                            $htmlText = htmlspecialchars($text);

                            if ($fontStyles) {
                                if ($fontStyles->isBold()) {
                                    $htmlText = "<strong>$htmlText</strong>";
                                }
                                if ($fontStyles->isItalic()) {
                                    $htmlText = "<em>$htmlText</em>";
                                }
                                if ($fontStyles->isStrikethrough()) {
                                    $htmlText = "<s>$htmlText</s>";
                                }
                            }

                            $subBlocks[] = $htmlText;
                        } elseif ($textElement instanceof Link) {
                            // Handle hyperlinks
                            $linkText = htmlspecialchars($textElement->getText());
                            $linkUrl = htmlspecialchars($textElement->getUrl());
                            $htmlLink = "<a href=\"$linkUrl\" target=\"_blank\">$linkText</a>";
                            $subBlocks[] = $htmlLink;
                        }
                    }
                    $blocks[] = $subBlocks;
                }
            }
        }
    }
    return $blocks;
}