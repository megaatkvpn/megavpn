<?php
/**
 * =====================================================
 *  Password Hash Generator — শুধু একবার ব্যবহারের জন্য
 * =====================================================
 *  এই ফাইলটা admin.php এর মতো একই ফোল্ডারে রেখে ব্রাউজারে
 *  খুলুন, নতুন পাসওয়ার্ড হ্যাশ কপি করে config.php তে বসান।
 *
 *  *** IMPORTANT: হ্যাশ কপি করা হয়ে গেলে এই ফাইলটা ডিলিট
 *  করে দেবেন — এটা লাইভ সার্ভারে রাখা নিরাপদ না। ***
 */

$hash = null;
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Hash Generator</title>
<style>
    body { font-family: system-ui, sans-serif; background: #0f1220; color: #e2e8f0; display: flex; justify-content: center; padding: 60px 20px; }
    .box { background: #1a1f36; border: 1px solid #2d3350; border-radius: 16px; padding: 32px; max-width: 520px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
    h1 { font-size: 20px; margin: 0 0 8px; }
    p { color: #94a3b8; font-size: 14px; margin: 0 0 20px; }
    input[type=text] { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #2d3350; background: #11162a; color: #e2e8f0; box-sizing: border-box; font-size: 14px; }
    button { margin-top: 14px; width: 100%; padding: 11px; border: none; border-radius: 10px; background: linear-gradient(90deg,#6366f1,#9333ea); color: #fff; font-weight: 600; font-size: 14px; cursor: pointer; }
    button:hover { filter: brightness(1.1); }
    .result { margin-top: 22px; padding: 14px; background: #11162a; border: 1px solid #2d3350; border-radius: 10px; word-break: break-all; font-family: monospace; font-size: 13px; color: #34d399; }
    .warn { margin-top: 16px; font-size: 12px; color: #f87171; }
    label { font-size: 13px; color: #cbd5e1; }
</style>
</head>
<body>
<div class="box">
    <h1>🔑 Password Hash Generator</h1>
    <p>আপনার পছন্দের পাসওয়ার্ড লিখুন, নিচে হ্যাশ তৈরি হয়ে যাবে — সেটা কপি করে config.php এর ADMIN_PASSWORD_HASH এ বসিয়ে দিন।</p>
    <form method="POST">
        <label>আপনার নতুন পাসওয়ার্ড</label>
        <input type="text" name="password" value="<?= htmlspecialchars($password) ?>" placeholder="যেমন: Atk@2026Secure" required autofocus>
        <button type="submit">হ্যাশ তৈরি করুন</button>
    </form>

    <?php if ($hash): ?>
        <div class="result"><?= htmlspecialchars($hash) ?></div>
        <p style="margin-top:10px; font-size:13px; color:#cbd5e1;">
            উপরের পুরো টেক্সটটা কপি করে config.php ফাইলে এভাবে বসান:<br>
            <code style="color:#a5b4fc;">define('ADMIN_PASSWORD_HASH', '<?= htmlspecialchars($hash) ?>');</code>
        </p>
    <?php endif; ?>

    <p class="warn">⚠️ কাজ শেষ হলে এই ফাইলটা (hash_generator.php) সার্ভার থেকে ডিলিট করে দিন।</p>
</div>
</body>
</html>
