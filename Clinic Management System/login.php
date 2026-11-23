<?php
require_once __DIR__ . '/includes/auth.php';
cliniccare_start_session();
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
$loginError = (string)($_SESSION['login_error'] ?? '');
unset($_SESSION['login_error']);
$loginCsrf = cliniccare_csrf_token();
$pageTitle = 'Sign in';
$pageDescription = 'Sign in to ClinicCare to manage clinic appointments and patient records.';
$bodyClass = 'login-page';
require __DIR__ . '/includes/header.php';
?>

<main class="login-shell">
    <section class="login-art-panel" aria-label="ClinicCare">
        <a class="brand-lockup brand-lockup-light" href="login.php" aria-label="ClinicCare home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none">
                    <path d="M24 7v34M7 24h34" stroke="currentColor" stroke-width="7" stroke-linecap="round" />
                    <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2" opacity=".28" />
                </svg>
            </span>
            <span class="brand-name">Clinic<span>Care</span></span>
        </a>

        <div class="login-art-copy">
            <span class="eyebrow">CARE, CONNECTED</span>
            <h1>Better organized care starts here.</h1>
            <p>One calm, clear place for your clinic team to coordinate appointments and patient care.</p>
        </div>

        <div class="art-decoration art-decoration-one" aria-hidden="true"></div>
        <div class="art-decoration art-decoration-two" aria-hidden="true"></div>
        <div class="art-grid" aria-hidden="true"></div>
        <div class="login-art-footer">
            <span class="status-dot" aria-hidden="true"></span>
            <span>Supporting your team, every day</span>
        </div>
    </section>

    <section class="login-form-panel" aria-labelledby="login-heading">
        <div class="login-form-wrap">
            <div class="mobile-brand">
                <a class="brand-lockup" href="login.php" aria-label="ClinicCare home">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 48 48" fill="none">
                            <path d="M24 7v34M7 24h34" stroke="currentColor" stroke-width="7" stroke-linecap="round" />
                            <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2" opacity=".28" />
                        </svg>
                    </span>
                    <span class="brand-name">Clinic<span>Care</span></span>
                </a>
            </div>

            <div class="login-heading-block">
                <span class="eyebrow eyebrow-muted">WELCOME BACK</span>
                <h2 id="login-heading">Sign in to your account</h2>
                <p>Enter your clinic account details to continue.</p>
            </div>

            <form class="login-form" action="auth/login_process.php" method="post" autocomplete="on">
                <div id="login-message" class="alert alert-danger login-message" role="alert" aria-live="polite"<?php echo $loginError === '' ? ' hidden' : ''; ?>><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
                <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($loginCsrf, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-4">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-icon-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7" />
                                <path d="M5 20c.5-3.5 3.1-5.5 7-5.5s6.5 2 7 5.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input class="form-control form-control-lg with-leading-icon" type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7" />
                                <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                <circle cx="12" cy="15" r="1" fill="currentColor" />
                            </svg>
                        </span>
                        <input class="form-control form-control-lg with-leading-icon with-trailing-action" type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="password" data-password-toggle>
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7" />
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m3 3 18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c6.1 0 9.5 6 9.5 6a16 16 0 0 1-3.2 3.7M6.2 6.3C3.8 8 2.5 12 2.5 12s3.4 6 9.5 6c1.1 0 2.1-.2 3-.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button class="btn btn-primary btn-lg w-100 login-submit" type="submit">
                    <span>Sign in</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </form>

            <div class="login-security-note">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 3 5 6v5c0 4.7 2.9 8.2 7 10 4.1-1.8 7-5.3 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                    <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>Your clinic workspace is for authorized team members.</span>
            </div>

            <p class="login-copyright">&copy; <?php echo date('Y'); ?> ClinicCare</p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
