<?php
$zip = new ZipArchive();
if ($zip->open('C:/Users/user/.gemini/antigravity/brain/54bfb974-bd67-493c-ad90-0ada8374c7bb/.user_uploaded/media_1794965956253_edcca18e.docx') === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    $text = strip_tags(str_replace(['<w:p>', '</w:p>'], ["\n", "\n"], $xml));
    echo substr($text, 5000);
}
