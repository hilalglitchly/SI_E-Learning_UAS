<?php
$host = "localhost";
$dbname = "db_elearning";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Mengaktifkan mode exception untuk penanganan error PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Mengatur default fetch mode menjadi associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// === SECURITY HELPERS ===

// 1. Generate CSRF Token jika belum ada di session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Fungsi validasi CSRF Token
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
// 3. Fungsi Sanitasi Rich Text (Pencegahan XSS)
function sanitize_rich_text($html) {
    if (empty(trim($html))) return '';
    
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    // Gunakan root element semu agar struktur valid
    $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // 1. Hapus tag berbahaya
    $bad_tags = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'base', 'form'];
    foreach ($bad_tags as $tag) {
        $nodes = $dom->getElementsByTagName($tag);
        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $node = $nodes->item($i);
            $node->parentNode->removeChild($node);
        }
    }

    // 2. Hapus atribut berbahaya (on*, javascript:)
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//*');
    foreach ($nodes as $node) {
        if ($node->hasAttributes()) {
            $attributes_to_remove = [];
            foreach ($node->attributes as $attr) {
                $attr_name = strtolower($attr->nodeName);
                $attr_value = strtolower($attr->nodeValue);
                if (strpos($attr_name, 'on') === 0) {
                    $attributes_to_remove[] = $attr_name;
                }
                if (($attr_name === 'href' || $attr_name === 'src') && strpos(str_replace(' ', '', $attr_value), 'javascript:') !== false) {
                    $attributes_to_remove[] = $attr_name;
                }
            }
            foreach ($attributes_to_remove as $attr_name) {
                $node->removeAttribute($attr_name);
            }
        }
    }

    $clean_html = $dom->saveHTML();
    // Hapus wrapper
    $clean_html = str_replace('<?xml encoding="utf-8" ?><div>', '', $clean_html);
    $clean_html = preg_replace('/<\/div>$/', '', trim($clean_html));
    return $clean_html;
}
?>
