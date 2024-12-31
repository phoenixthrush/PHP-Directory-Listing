<?php
$authToken = 'SECURE_RANDOM_STRING';

if (!isset($_GET['token']) || $_GET['token'] !== $authToken) {
    http_response_code(403);
    echo "Access Denied: Invalid token.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'], $_POST['file'])) {
    $fileToDelete = realpath($_SERVER['DOCUMENT_ROOT'] . $_POST['file']);
    if (strpos($fileToDelete, $_SERVER['DOCUMENT_ROOT']) === 0 && file_exists($fileToDelete)) {
        unlink($fileToDelete);
        header("Location: ?page=" . ($_GET['page'] ?? 1) . "&token=$authToken");
        exit;
    } else {
        echo "Error: Unable to delete the file.";
    }
}

function listDirectory($dir, $authToken) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'mp4', 'avi', 'mov', 'mkv', 'webm'];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    $videoExtensions = ['mp4', 'avi', 'mov', 'mkv', 'webm'];

    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];

    foreach ($items as $item) {
        if ($item->isFile()) {
            $extension = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
            if (in_array($extension, $allowedExtensions)) {
                $files[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $item->getRealPath());
            }
        }
    }

    $itemsPerPage = 5;
    $totalItems = count($files);
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;

    $start = ($currentPage - 1) * $itemsPerPage;
    $currentItems = array_slice($files, $start, $itemsPerPage);

    echo "<ul>";
    foreach ($currentItems as $relativePath) {
        $filename = basename($relativePath);
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        echo "<li>
                <a href=\"$relativePath\">$filename</a>
                <form method=\"POST\" style=\"display: inline;\">
                    <input type=\"hidden\" name=\"file\" value=\"$relativePath\">
                    <button type=\"submit\" name=\"delete\">Delete</button>
                </form><br>";
        if (in_array($extension, $imageExtensions)) {
            echo "<img src=\"$relativePath\" alt=\"$filename\" style=\"max-width: 200px; max-height: 150px;\"><br>";
        } elseif (in_array($extension, $videoExtensions)) {
            echo "<video controls style=\"max-width: 300px; max-height: 200px;\">
                    <source src=\"$relativePath\" type=\"video/$extension\">
                  </video><br>";
        }
        echo "</li>";
    }
    echo "</ul>";

    echo "<div style=\"position: fixed; bottom: 10px; width: 100%; text-align: center;\">";
    if ($currentPage > 1) {
        echo "<a href=\"?page=" . ($currentPage - 1) . "&token=$authToken\">Back</a> ";
    }
    echo "Page $currentPage of $totalPages ";
    if ($currentPage < $totalPages) {
        echo "<a href=\"?page=" . ($currentPage + 1) . "&token=$authToken\">Next</a>";
    }
    echo "</div>";
}

$root = __DIR__;
listDirectory($root, $authToken);
?>
