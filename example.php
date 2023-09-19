<?php

include "git.php";

$git = new Git(
    "github_pat_...",
    "kissmarcell",
    "gitphp",
    "master",
    "marcell@examp.le",
    "Marcell K."
);

// Define the files you want to add
$filesToAdd = [
    "answer_to_life" => "forty-two",
    "the cake is" => "a lie",
];

$commitMessage = "Add multiple files";

$out = $git->commit($commitMessage, $filesToAdd);

echo $out;