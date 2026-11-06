<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_role('developer');

$conn = db_connect();
$message = "";
$message_ok = false;

function valid_username($u) {
    return (bool)preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $u);
}
function valid_role($r) {
    return in_array($r, ['employee', 'admin', 'developer'], true) ? $r : 'employee';
}

function role_count($conn, $role) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM users WHERE role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $n = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    return $n;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf();
    $me = (int)$_SESSION['user_id'];

    if (isset($_POST['add_user'])) {
        $new_username = trim($_POST['username']);
        $new_password = $_POST['password'];
        $role = valid_role($_POST['role']);

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $new_username);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if (!valid_username($new_username)) {
            $message = "Username must be 3-30 characters: letters, numbers, dot, dash or underscore only.";
        } elseif ($exists) {
            $message = "Username already exists. Please choose a different username.";
        } elseif (!password_strong_enough($new_password)) {
            $message = "Password is too weak. " . password_rule_text();
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $new_username, $hash, $role);
            $stmt->execute();
            $stmt->close();
            $message = "User successfully added!";
            $message_ok = true;
        }
    }

    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user_id === $me) {
            $message = "You cannot delete the account you are logged in with.";
        } elseif (!$target) {
            $message = "User not found.";
        } elseif (in_array($target['role'], ['admin', 'developer'], true) && role_count($conn, $target['role']) <= 1) {
            $message = "You cannot delete the last " . $target['role'] . " account.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "User successfully deleted!";
            $message_ok = true;
        }
    }

    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $updated_username = trim($_POST['username']);
        $updated_password = $_POST['password'];
        $updated_role = valid_role($_POST['role']);

        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
        $stmt->bind_param("si", $updated_username, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $taken = $stmt->num_rows > 0;
        $stmt->close();

        if (!$target) {
            $message = "User not found.";
        } elseif (!valid_username($updated_username)) {
            $message = "Username must be 3-30 characters: letters, numbers, dot, dash or underscore only.";
        } elseif ($taken) {
            $message = "Username already exists. Please choose a different username.";
        } elseif ($updated_password !== '' && !password_strong_enough($updated_password)) {
            $message = "Password is too weak. " . password_rule_text();
        } elseif (in_array($target['role'], ['admin', 'developer'], true) && $updated_role !== $target['role'] && role_count($conn, $target['role']) <= 1) {
            $message = "You cannot change the role of the last " . $target['role'] . " account.";
        } else {
            if ($updated_password !== '') {
                $hash = password_hash($updated_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, session_token = NULL WHERE id = ?");
                $stmt->bind_param("sssi", $updated_username, $hash, $updated_role, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssi", $updated_username, $updated_role, $user_id);
            }
            $stmt->execute();
            $stmt->close();
            $message = "User successfully updated!";
            $message_ok = true;
        }
    }
}

$weak = [];
$common = ['123456', '654321', '12345678', 'password', 'admin', 'admin123', 'teller', 'teller123', '111111'];
$r = $conn->query("SELECT username, password FROM users");
while ($u = $r->fetch_assoc()) {
    foreach ($common as $c) {
        if (password_check($c, $u['password'])) { $weak[] = $u['username']; break; }
    }
}

$users_result = $conn->query("SELECT id, username, role, created_at, last_seen FROM users ORDER BY role ASC, username ASC");
$conn->close();

$edit_id = (isset($_POST['edit_user']) && $_SERVER["REQUEST_METHOD"] === "POST") ? (int)$_POST['user_id'] : 0;
?>
<?php layout_start('users', 'Manage Users', ['assets/css/admin.css']); ?>
    <h1>Manage Users</h1>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo $message_ok ? 'alert-ok' : 'alert-err'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>Add user</h2>
        <form action="admin" method="POST" class="add-form" autocomplete="off">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="add_user" value="1">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" minlength="3" maxlength="30" required autocapitalize="none">
            </div>
            <div class="field">
                <label for="password">Password (<?php echo htmlspecialchars(password_rule_text()); ?>)</label>
                <input type="password" id="password" name="password" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="employee">Employee (Teller)</option>
                    <option value="admin">Admin</option>
                    <option value="developer">Developer</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Add User</button>
        </form>
    </div>

    <div class="panel">
        <h2>Registered users</h2>
        <div class="table-wrap">
        <table class="dash-table resp">
            <thead>
                <tr><th>Username</th><th>Role</th><th>Status</th><th>Created At</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($users_result->num_rows > 0): ?>
                <?php while ($user = $users_result->fetch_assoc()):
                    $online = !empty($user['last_seen']) && (time() - strtotime($user['last_seen'])) < 120; ?>
                    <tr>
                        <td data-label="Username"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td data-label="Role"><?php echo $user['role'] === 'developer' ? 'Developer' : ($user['role'] === 'admin' ? 'Admin' : 'Teller'); ?></td>
                        <td data-label="Status"><span class="dot <?php echo $online ? 'on' : 'off'; ?>"></span><?php echo $online ? 'Online' : 'Offline'; ?></td>
                        <td data-label="Created"><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td data-label="Actions" class="actions">
                            <form action="admin" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="edit_user" value="1">
                                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                <button type="submit" class="btn-small">Edit</button>
                            </form>
                            <form action="admin" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="delete_user" value="1">
                                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                <button type="submit" class="btn-small danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php if ($edit_id === (int)$user['id']): ?>
                        <tr class="edit-row">
                            <td colspan="5">
                                <form action="admin" method="POST" class="add-form" autocomplete="off">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="update_user" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                    <div class="field">
                                        <label>Username</label>
                                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" minlength="3" maxlength="30" required autocapitalize="none">
                                    </div>
                                    <div class="field">
                                        <label>New password (blank = keep the current one)</label>
                                        <input type="password" name="password" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" autocomplete="new-password">
                                    </div>
                                    <div class="field">
                                        <label>Role</label>
                                        <select name="role" required>
                                            <option value="employee" <?php echo $user['role'] == 'employee' ? 'selected' : ''; ?>>Employee (Teller)</option>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="developer" <?php echo $user['role'] == 'developer' ? 'selected' : ''; ?>>Developer</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn-primary">Update User</button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
<?php layout_end(); ?>
