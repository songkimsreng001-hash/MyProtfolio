<?php
// register.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/header.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error   = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    if (!verifyCsrfToken()) {
        $error = 'Invalid or expired session security token. Please refresh and try again.';
    } else {
        $rateLimit = checkRateLimit('register', 5, 600);
        if (!$rateLimit['allowed']) {
            $error = 'Too many account creation attempts. Please try again in ' . ceil($rateLimit['retry_after'] / 60) . ' minute(s).';
        } else {
            $name     = trim($_POST['name'] ?? '');
            $email    = trim(strtolower($_POST['email'] ?? ''));
            $password = trim($_POST['password'] ?? '');
            $confirm  = trim($_POST['confirm_password'] ?? '');

            if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
                $error = 'Please fill in all fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $error = 'Password must be at least 8 characters and contain both letters and numbers.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                // Check if email already exists
                $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $check->bind_param('s', $email);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    recordRateLimitAttempt('register', 600);
                    $error = 'This email is already registered.';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                    $stmt->bind_param('sss', $name, $email, $hashed);
                    if ($stmt->execute()) {
                        resetRateLimit('register');
                        $success = 'Account created successfully!';
                    } else {
                        recordRateLimitAttempt('register', 600);
                        $error = 'Registration failed. Please try again.';
                    }
                    $stmt->close();
                }
                $check->close();
            }
        }
    }
}
?>

<?php include 'includes/navbar.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">Kimsreng<span class="text-accent">.</span></div>
        <h2 class="auth-title mt-3">Create Account</h2>
        <p class="auth-subtitle">Join and explore my portfolio</p>

        <!-- Google Sign-Up Section -->
        <div class="google-auth-container mb-4">
            <div id="g_id_onload"
                 data-client_id="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>"
                 data-context="signup"
                 data-ux_mode="popup"
                 data-callback="handleGoogleCredentialResponse"
                 data-auto_prompt="false">
            </div>

            <div class="g_id_signin d-flex justify-content-center mb-2"
                 data-type="standard"
                 data-shape="pill"
                 data-theme="outline"
                 data-text="signup_with"
                 data-size="large"
                 data-logo_alignment="left"
                 data-width="100%">
            </div>

            <!-- Custom Styled Google Button (Direct OAuth / GIS Flow) -->
            <a href="<?= htmlspecialchars(getGoogleAuthUrl()) ?>" class="btn btn-google w-100 text-decoration-none" id="customGoogleBtn">
                <svg class="google-icon me-2" viewBox="0 0 24 24" width="18" height="18">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                Sign up with Google
            </a>
        </div>

        <div class="auth-separator">
            <span>or sign up with email</span>
        </div>

        <form method="POST" id="registerForm">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="passInput" class="form-control" placeholder="Min. 6 characters" required>
                    <button class="btn btn-toggle-pw" type="button" onclick="toggleVis('passInput','eye1')">
                        <i class="bi bi-eye" id="eye1"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="confirm_password" id="confirmInput" class="form-control" placeholder="Repeat password" required>
                    <button class="btn btn-toggle-pw" type="button" onclick="toggleVis('confirmInput','eye2')">
                        <i class="bi bi-eye" id="eye2"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-accent w-100">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>

        <div class="auth-divider mt-4">
            Already have an account? <a href="login.php" class="auth-link">Login here</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
function toggleVis(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// Google Credential Callback for GIS
function handleGoogleCredentialResponse(response) {
    if (!response || !response.credential) {
        return;
    }

    Swal.fire({
        title: 'Creating account with Google...',
        text: 'Please wait a moment.',
        allowOutsideClick: false,
        background: document.documentElement.getAttribute('data-theme') === 'light' ? '#ffffff' : '#0f0f1a',
        color: document.documentElement.getAttribute('data-theme') === 'light' ? '#0f172a' : '#e2e8f0',
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('google-auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ credential: response.credential })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: data.message || 'Account Ready!',
                text: 'Redirecting...',
                background: document.documentElement.getAttribute('data-theme') === 'light' ? '#ffffff' : '#0f0f1a',
                color: document.documentElement.getAttribute('data-theme') === 'light' ? '#0f172a' : '#e2e8f0',
                showConfirmButton: false,
                timer: 1200
            }).then(() => {
                window.location.href = data.redirect || 'index.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Authentication Failed',
                text: data.message || 'Could not verify your Google account.',
                background: document.documentElement.getAttribute('data-theme') === 'light' ? '#ffffff' : '#0f0f1a',
                color: document.documentElement.getAttribute('data-theme') === 'light' ? '#0f172a' : '#e2e8f0',
                confirmButtonColor: '#6c63ff'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Could not connect to authentication server.',
            background: document.documentElement.getAttribute('data-theme') === 'light' ? '#ffffff' : '#0f0f1a',
            color: document.documentElement.getAttribute('data-theme') === 'light' ? '#0f172a' : '#e2e8f0',
            confirmButtonColor: '#6c63ff'
        });
    });
}

<?php if ($error): ?>
Swal.fire({
    icon: 'error',
    title: 'Registration Error',
    text: '<?= addslashes($error) ?>',
    background: document.documentElement.getAttribute('data-theme') === 'light' ? '#ffffff' : '#0f0f1a',
    color: document.documentElement.getAttribute('data-theme') === 'light' ? '#0f172a' : '#e2e8f0',
    confirmButtonColor: '#6c63ff'
});
<?php endif; ?>

<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'Account Created!',
    text: 'You can now login with your credentials.',
    background: document.documentElement.getAttribute('data-theme') === 'light' ? '#ffffff' : '#0f0f1a',
    color: document.documentElement.getAttribute('data-theme') === 'light' ? '#0f172a' : '#e2e8f0',
    confirmButtonColor: '#6c63ff',
    confirmButtonText: 'Go to Login'
}).then(() => {
    window.location.href = 'login.php';
});
<?php endif; ?>
</script>
