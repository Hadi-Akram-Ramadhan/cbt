<?php
// ============================================
// Database Seeder — run this once to create
// proper bcrypt password hashes for users
// Usage: http://localhost/brainstorm/cbt/database/seed.php
// IMPORTANT: Delete this file after running!
// ============================================

define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// Fix admin password
$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
$db->prepare("UPDATE users SET password=? WHERE username='admin' AND role='admin'")->execute([$adminHash]);

// Fix student passwords (or insert if not there)
$siswaHash = password_hash('siswa123', PASSWORD_DEFAULT);

// Upsert budi
$check = $db->prepare("SELECT id FROM users WHERE username='budi'"); $check->execute();
if (!$check->fetch()) {
    $db->prepare("INSERT INTO users (name,username,password,role,nis,kelas) VALUES (?,?,?,?,?,?)")
       ->execute(['Budi Santoso','budi',$siswaHash,'siswa','2024001','XII-IPA-1']);
} else {
    $db->prepare("UPDATE users SET password=? WHERE username='budi'")->execute([$siswaHash]);
}

// Upsert siti
$check = $db->prepare("SELECT id FROM users WHERE username='siti'"); $check->execute();
if (!$check->fetch()) {
    $db->prepare("INSERT INTO users (name,username,password,role,nis,kelas) VALUES (?,?,?,?,?,?)")
       ->execute(['Siti Rahayu','siti',$siswaHash,'siswa','2024002','XII-IPA-1']);
} else {
    $db->prepare("UPDATE users SET password=? WHERE username='siti'")->execute([$siswaHash]);
}

echo '<div style="font-family:monospace;padding:20px">';
echo '<h2 style="color:green">✓ Seed berhasil!</h2>';
echo '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse">';
echo '<tr><th>Username</th><th>Password</th><th>Role</th></tr>';
echo '<tr><td>admin</td><td>admin123</td><td>Admin</td></tr>';
echo '<tr><td>budi</td><td>siswa123</td><td>Siswa</td></tr>';
echo '<tr><td>siti</td><td>siswa123</td><td>Siswa</td></tr>';
echo '</table>';
echo '<p style="color:red;margin-top:20px">⚠️ <strong>PENTING:</strong> Hapus file ini setelah selesai!</p>';
echo '<p><a href="/brainstorm/cbt/auth/login.php">→ Pergi ke halaman login</a></p>';
echo '</div>';
