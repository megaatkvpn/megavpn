<?php
/**
 * =====================================================
 *  Atk VPN — Premium Admin Panel
 * =====================================================
 */

session_start();
require_once __DIR__ . '/db_connect.php';

$pdo = getDbConnection();

/* ---------------- CSRF protection ---------------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function verify_csrf(): void
{
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(400);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}

/* ---------------- Logout ---------------- */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

/* ---------------- Login ---------------- */
$login_error = null;
if (isset($_POST['login'])) {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['is_admin'] = true;
        session_regenerate_id(true);
        header('Location: admin.php');
        exit;
    }
    $login_error = 'Invalid password. Please try again.';
}

$is_logged_in = !empty($_SESSION['is_admin']);

/* ---------------- CRUD actions (POST) ---------------- */
$flash = null;

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    if ($_POST['action'] === 'save_server') {
        $id           = (isset($_POST['server_id']) && $_POST['server_id'] !== '') ? (int)$_POST['server_id'] : null;
        $server_name  = trim($_POST['server_name'] ?? '');
        $country_code = strtoupper(trim($_POST['country_code'] ?? ''));
        $ovpn_raw     = $_POST['ovpn_raw'] ?? '';
        $status       = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

        if ($server_name === '' || $country_code === '' || trim($ovpn_raw) === '') {
            $flash = ['type' => 'error', 'msg' => 'All fields (name, country code, .ovpn config) are required.'];
        } else {
            // Automatically Base64-encode the raw .ovpn text before saving
            $ovpn_base64 = base64_encode($ovpn_raw);

            if ($id) {
                $stmt = $pdo->prepare(
                    'UPDATE vpn_servers SET server_name = ?, country_code = ?, ovpn_base64 = ?, status = ? WHERE id = ?'
                );
                $stmt->execute([$server_name, $country_code, $ovpn_base64, $status, $id]);
                $flash = ['type' => 'success', 'msg' => "Server \"$server_name\" updated successfully."];
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO vpn_servers (server_name, country_code, ovpn_base64, status) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$server_name, $country_code, $ovpn_base64, $status]);
                $flash = ['type' => 'success', 'msg' => "Server \"$server_name\" added successfully."];
            }
        }
    }

    if ($_POST['action'] === 'delete_server') {
        $id = (int)($_POST['server_id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM vpn_servers WHERE id = ?');
            $stmt->execute([$id]);
            $flash = ['type' => 'success', 'msg' => 'Server deleted.'];
        }
    }
}

/* ---------------- Fetch list ---------------- */
$servers = [];
$total_count = $active_count = $inactive_count = 0;
if ($is_logged_in) {
    $servers = $pdo->query('SELECT * FROM vpn_servers ORDER BY id DESC')->fetchAll();
    $total_count    = count($servers);
    $active_count   = count(array_filter($servers, fn($s) => $s['status'] === 'Active'));
    $inactive_count = $total_count - $active_count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Atk VPN — Admin Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #0f1220; }
    .bg-app {
        background: radial-gradient(1200px 600px at 10% -10%, #1e2a4a 0%, transparent 60%),
                    radial-gradient(1000px 500px at 110% 10%, #2a1e4a 0%, transparent 55%),
                    #0f1220;
        min-height: 100vh;
    }
    .card-3d {
        background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 10px 30px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.06);
        border-radius: 18px;
        backdrop-filter: blur(8px);
    }
    .btn-3d {
        transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        box-shadow: 0 6px 14px rgba(0,0,0,0.35);
    }
    .btn-3d:hover { transform: translateY(-2px); filter: brightness(1.08); }
    .btn-3d:active { transform: translateY(0); }
    .row-hover { transition: background-color .15s ease; }
    .row-hover:hover { background-color: rgba(255,255,255,0.04); }
    ::-webkit-scrollbar { height: 8px; width: 8px; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 8px; }
</style>
</head>
<body class="bg-app text-slate-100">

<?php if (!$is_logged_in): ?>
<!-- =========================== LOGIN SCREEN =========================== -->
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="card-3d w-full max-w-sm p-8">
        <div class="text-center mb-6">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-2xl font-bold shadow-lg mb-3">🔒</div>
            <h1 class="text-xl font-bold">Atk VPN Admin</h1>
            <p class="text-slate-400 text-sm mt-1">Sign in to manage your VPN servers</p>
        </div>
        <?php if ($login_error): ?>
            <div class="mb-4 text-sm text-red-300 bg-red-500/10 border border-red-500/30 rounded-lg px-3 py-2">
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div>
                <label class="block text-sm text-slate-300 mb-1">Password</label>
                <input type="password" name="password" required autofocus
                    class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-2.5 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
            </div>
            <button type="submit" name="login" value="1"
                class="btn-3d w-full py-2.5 rounded-xl font-semibold bg-gradient-to-r from-indigo-500 to-purple-600">
                Sign In
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- =========================== DASHBOARD =========================== -->
<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xl shadow-lg">🛡️</div>
            <div>
                <h1 class="text-2xl font-bold">Atk VPN — Server Management</h1>
                <p class="text-slate-400 text-sm">Manage your OpenVPN server pool</p>
            </div>
        </div>
        <a href="?logout=1" class="btn-3d px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-sm font-medium hover:bg-white/10">
            Logout
        </a>
    </div>

    <!-- Flash message -->
    <?php if ($flash): ?>
        <div class="mb-6 rounded-xl px-4 py-3 text-sm border
            <?= $flash['type'] === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300' : 'bg-red-500/10 border-red-500/30 text-red-300' ?>">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="card-3d p-5">
            <p class="text-slate-400 text-sm">Total Servers</p>
            <p class="text-3xl font-bold mt-1"><?= (int)$total_count ?></p>
        </div>
        <div class="card-3d p-5">
            <p class="text-slate-400 text-sm">Active</p>
            <p class="text-3xl font-bold mt-1 text-emerald-400"><?= (int)$active_count ?></p>
        </div>
        <div class="card-3d p-5">
            <p class="text-slate-400 text-sm">Inactive</p>
            <p class="text-3xl font-bold mt-1 text-amber-400"><?= (int)$inactive_count ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Add / Edit form -->
        <div class="lg:col-span-2">
            <div class="card-3d p-6 sticky top-6">
                <h2 id="form-title" class="text-lg font-semibold mb-4">➕ Add New Server</h2>
                <form method="POST" class="space-y-4" id="server-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="save_server">
                    <input type="hidden" name="server_id" id="server_id" value="">

                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Server Name</label>
                        <input type="text" name="server_name" id="server_name" required placeholder="e.g. Japan Server 1"
                            class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-2.5 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-slate-300 mb-1">Country Code</label>
                            <input type="text" name="country_code" id="country_code" required maxlength="10" placeholder="JP"
                                class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-2.5 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 uppercase">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300 mb-1">Status</label>
                            <select name="status" id="status"
                                class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-2.5 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Raw .ovpn Config</label>
                        <textarea name="ovpn_raw" id="ovpn_raw" required rows="8" placeholder="Paste the full raw .ovpn file contents here..."
                            class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-2.5 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 font-mono text-xs"></textarea>
                        <p class="text-xs text-slate-500 mt-1">Will be Base64-encoded automatically on save.</p>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                            class="btn-3d flex-1 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-indigo-500 to-purple-600">
                            <span id="submit-label">Save Server</span>
                        </button>
                        <button type="button" onclick="resetForm()"
                            class="btn-3d px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 font-medium">
                            Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Server list -->
        <div class="lg:col-span-3">
            <div class="card-3d p-6">
                <h2 class="text-lg font-semibold mb-4">📡 Servers (<?= (int)$total_count ?>)</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-400 border-b border-white/10">
                                <th class="py-2 pr-3">Name</th>
                                <th class="py-2 pr-3">Country</th>
                                <th class="py-2 pr-3">Status</th>
                                <th class="py-2 pr-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($servers)): ?>
                            <tr><td colspan="4" class="py-6 text-center text-slate-500">No servers yet. Add your first one!</td></tr>
                        <?php endif; ?>
                        <?php foreach ($servers as $s): ?>
                            <?php $rawOvpn = base64_decode($s['ovpn_base64']); ?>
                            <tr class="row-hover border-b border-white/5"
                                data-id="<?= (int)$s['id'] ?>"
                                data-name="<?= htmlspecialchars($s['server_name'], ENT_QUOTES) ?>"
                                data-country="<?= htmlspecialchars($s['country_code'], ENT_QUOTES) ?>"
                                data-status="<?= htmlspecialchars($s['status'], ENT_QUOTES) ?>"
                                data-ovpn="<?= htmlspecialchars($rawOvpn, ENT_QUOTES) ?>">
                                <td class="py-3 pr-3 font-medium"><?= htmlspecialchars($s['server_name']) ?></td>
                                <td class="py-3 pr-3"><?= htmlspecialchars($s['country_code']) ?></td>
                                <td class="py-3 pr-3">
                                    <span class="px-2 py-1 rounded-lg text-xs font-semibold
                                        <?= $s['status'] === 'Active' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' ?>">
                                        <?= htmlspecialchars($s['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3 pr-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="editServer(this)"
                                            class="btn-3d px-3 py-1.5 rounded-lg bg-indigo-500/20 text-indigo-300 text-xs font-semibold hover:bg-indigo-500/30">Edit</button>
                                        <button type="button" onclick="duplicateServer(this)"
                                            class="btn-3d px-3 py-1.5 rounded-lg bg-purple-500/20 text-purple-300 text-xs font-semibold hover:bg-purple-500/30">Duplicate</button>
                                        <form method="POST" onsubmit="return confirm('Delete this server permanently?');" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete_server">
                                            <input type="hidden" name="server_id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit"
                                                class="btn-3d px-3 py-1.5 rounded-lg bg-red-500/20 text-red-300 text-xs font-semibold hover:bg-red-500/30">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('server_id').value = '';
    document.getElementById('server_name').value = '';
    document.getElementById('country_code').value = '';
    document.getElementById('status').value = 'Active';
    document.getElementById('ovpn_raw').value = '';
    document.getElementById('form-title').textContent = '➕ Add New Server';
    document.getElementById('submit-label').textContent = 'Save Server';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function fillForm(row) {
    document.getElementById('server_name').value  = row.dataset.name;
    document.getElementById('country_code').value = row.dataset.country;
    document.getElementById('status').value       = row.dataset.status;
    document.getElementById('ovpn_raw').value      = row.dataset.ovpn;
}

// Edit: keeps the same server_id so the form UPDATEs the existing row
function editServer(btn) {
    const row = btn.closest('tr');
    fillForm(row);
    document.getElementById('server_id').value = row.dataset.id;
    document.getElementById('form-title').textContent = '✏️ Edit Server: ' + row.dataset.name;
    document.getElementById('submit-label').textContent = 'Update Server';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.getElementById('server_name').focus();
}

// Duplicate: copies the data but clears server_id so the form INSERTs a new row
function duplicateServer(btn) {
    const row = btn.closest('tr');
    fillForm(row);
    document.getElementById('server_id').value = '';
    document.getElementById('server_name').value = row.dataset.name + ' (Copy)';
    document.getElementById('form-title').textContent = '📄 Duplicate Server (tweak & save)';
    document.getElementById('submit-label').textContent = 'Save as New Server';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.getElementById('server_name').focus();
    document.getElementById('server_name').select();
}
</script>

<?php endif; ?>
</body>
</html>
