<?php
$content = file_get_contents('C:/Users/user/.gemini/antigravity/brain/54bfb974-bd67-493c-ad90-0ada8374c7bb/.user_uploaded/media_1794936822379_e1081980.pdf');
preg_match_all('#stream[\r\n]+(.*?)[\r\n]+endstream#s', $content, $matches);
$fullText = '';
foreach ($matches[1] as $stream) {
    $decomp = @gzuncompress($stream);
    if ($decomp) {
        if (preg_match_all('#\((.*?)\)\s*Tj#s', $decomp, $tjs)) {
            $fullText .= implode('', $tjs[1]) . "\n";
        }
        if (preg_match_all('#\[(.*?)\]\s*TJ#s', $decomp, $tjs)) {
            foreach ($tjs[1] as $tj) {
                preg_match_all('#\((.*?)\)#s', $tj, $sub);
                $fullText .= implode('', $sub[1]) . "\n";
            }
        }
    }
}
echo "Extracted " . strlen($fullText) . " characters.\n";
echo "=== PREVIEW ===\n";
echo substr($fullText, 0, 1500) . "\n";
