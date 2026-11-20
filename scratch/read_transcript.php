<?php
$f = file("C:/Users/user/.gemini/antigravity/brain/54bfb974-bd67-493c-ad90-0ada8374c7bb/.system_generated/logs/transcript.jsonl");
for($i = count($f)-1; $i >= 0; $i--) {
    $j = json_decode($f[$i], true);
    if ($j["type"] === "USER_INPUT") {
        echo "Last USER_INPUT index: $i\n";
        echo "Content:\n" . $j["content"] . "\n";
        echo "Media:\n" . json_encode($j["media"] ?? []) . "\n";
        break;
    }
}
