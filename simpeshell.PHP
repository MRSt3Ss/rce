<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>File Manager Sederhana</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
    margin: 0;
    padding: 20px;
}
.container {
    background-color: #fff;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
h1 { text-align: center; color: #333; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
.file-list { list-style: none; padding: 0; }
.file-item {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.file-item:hover { background-color: #f9f9f9; }
.file-name { font-weight: bold; color: #2c3e50; }
.file-size { color: #7f8c8d; font-size: 0.9em; }
.directory { color: #27ae60; }
form {
    margin-top: 20px;
    background: #f9f9f9;
    padding: 10px;
    border-radius: 8px;
}
input[type=text], textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-top: 5px;
}
button {
    margin-top: 10px;
    padding: 8px 14px;
    border: none;
    background: #3498db;
    color: white;
    border-radius: 6px;
    cursor: pointer;
}
button:hover { background: #2980b9; }
</style>
</head>
<body>
<div class="container">
<h1>📁 File Manager</h1>

<?php
$base = __DIR__;
$dir = isset($_GET['dir']) ? realpath($base . '/' . $_GET['dir']) : $base;
if (strpos($dir, $base) !== 0) die("Akses ditolak.");

chdir($dir);

// ====== Buat file baru ======
if (isset($_POST['newfile']) && $_POST['newfile'] != '') {
    $filename = basename($_POST['newfile']);
    file_put_contents($filename, "");
    echo "<p>✅ File <b>$filename</b> berhasil dibuat.</p>";
}

// ====== Simpan hasil edit ======
if (isset($_POST['savefile']) && isset($_POST['content'])) {
    file_put_contents($_POST['savefile'], $_POST['content']);
    echo "<p>💾 File <b>{$_POST['savefile']}</b> disimpan.</p>";
}

// ====== Mode Edit ======
if (isset($_GET['edit'])) {
    $file = basename($_GET['edit']);
    if (is_file($file)) {
        $content = htmlspecialchars(file_get_contents($file));
        echo "<h3>✏️ Edit File: $file</h3>
        <form method='post'>
            <input type='hidden' name='savefile' value='$file'>
            <textarea name='content' rows='15'>$content</textarea>
            <button type='submit'>Simpan Perubahan</button>
            <a href='?dir=" . htmlspecialchars($_GET['dir'] ?? '') . "'>Batal</a>
        </form>";
        exit;
    }
}

// ====== Navigasi Direktori ======
echo "<p>📂 Direktori saat ini: <b>$dir</b></p>";
if ($dir != $base) {
    $parent = dirname(str_replace($base, '', $dir));
    echo "<p><a href='?dir=" . urlencode($parent) . "'>⬅️ Kembali ke atas</a></p>";
}

// ====== Daftar File & Folder ======
$files = scandir($dir);
echo "<ul class='file-list'>";
foreach ($files as $f) {
    if ($f == '.') continue;
    $path = "$dir/$f";
    $rel = str_replace($base . '/', '', $path);

    echo "<li class='file-item'>";
    if (is_dir($path)) {
        echo "<span class='file-name directory'>📁 <a href='?dir=" . urlencode($rel) . "'>$f</a></span>";
        echo "<span class='file-size'>Folder</span>";
    } else {
        echo "<span class='file-name'>📄 $f</span>";
        echo "<span class='file-size'>" . round(filesize($path)/1024,2) . " KB</span>";
        echo "<a href='?dir=" . urlencode(str_replace($base . '/', '', $dir)) . "&edit=" . urlencode($f) . "'>Edit</a>";
    }
    echo "</li>";
}
echo "</ul>";
?>

<form method="post">
    <h3>➕ Buat File Baru</h3>
    <input type="text" name="newfile" placeholder="Nama file baru (contoh: baru.txt)">
    <button type="submit">Buat</button>
</form>

</div>
</body>
</html>