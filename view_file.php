<?php
if(!isset($_GET['file'])) {
    die("No file specified.");
}

function fixPath($path){
    $path = str_replace("C:\\xampp\\htdocs\\BSCS PROGRESS EDIT\\", "", $path);
    $path = str_replace("\\", "/", $path);
    $path = str_replace(" ", "%20", $path);
    return $path;
}

$file = fixPath($_GET['file']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View File</title>
    <style>
        body {
            margin:0;
            font-family: Arial;
            background:#0B2540;
        }
        .topbar {
            padding:10px;
            background:#071A2A;
            color:white;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        .btn-back {
            background:#FFD700;
            border:none;
            padding:8px 15px;
            font-weight:bold;
            cursor:pointer;
        }
        iframe {
            width:100%;
            height:95vh;
            border:none;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div>FILE VIEWER</div>
    <button class="btn-back" onclick="window.location.href='admin_manage_instructors.php'">
    ⬅ Back
</button>
</div>

<iframe src="<?= $file ?>"></iframe>

</body>
</html>