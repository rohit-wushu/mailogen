<?php

declare(strict_types=1);

final class AuthController extends BaseController
{
    public function login(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        if ($this->isPost()) {
            csrf_guard();
            $email = str_input('email');
            $pass  = (string) input('password', '');

            // Throttle brute-force: max 10 attempts / 15 min per IP+email.
            $bucket = 'login:' . client_ip() . ':' . strtolower($email);
            if (RateLimiter::tooMany($bucket, 10, 900)) {
                flash('error', 'Too many sign-in attempts. Please wait a few minutes and try again.');
                redirect('login');
            }
            if (Auth::attempt($email, $pass)) {
                RateLimiter::clear($bucket);
                User::update(Auth::id(), ['last_login_at' => date('Y-m-d H:i:s')]);
                redirect('dashboard');
            }
            flash('error', 'Invalid email or password.');
        }
        view_bare('auth/login', [], 'Sign in');
    }

    public function register(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        if ($this->isPost()) {
            csrf_guard();

            // Throttle signup abuse: max 5 new accounts / hour per IP.
            if (RateLimiter::tooMany('register:' . client_ip(), 5, 3600)) {
                flash('error', 'Too many sign-ups from this network. Please try again later.');
                redirect('register');
            }

            $name  = str_input('name');
            $email = str_input('email');
            $pass  = (string) input('password', '');
            $company = str_input('company');

            $errors = [];
            if (mb_strlen($name) < 2)                              $errors[] = 'Please enter your name.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'Please enter a valid email.';
            if (strlen($pass) < 8)                                 $errors[] = 'Password must be at least 8 characters.';
            if (User::findByEmail($email))                         $errors[] = 'That email is already registered.';
            if (!input('agree'))                                   $errors[] = 'Please accept the Terms and policies to continue.';

            if ($errors === []) {
                $id = User::create($name, $email, $pass, $company ?: null);
                $user = User::find($id);
                self::sendVerificationEmail($user);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $id;
                flash('success', 'Welcome to ' . APP_NAME . '! We sent a verification link to your email.');
                redirect('dashboard');
            }
            flash('error', implode(' ', $errors));
        }
        view_bare('auth/register', [], 'Create account');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('login');
    }

    public function forgot(): void
    {
        if ($this->isPost()) {
            csrf_guard();
            // Throttle reset-email bombing: max 5 / hour per IP.
            if (RateLimiter::tooMany('forgot:' . client_ip(), 5, 3600)) {
                flash('success', 'If that email exists, a password reset link has been sent.');
                redirect('login');
            }
            $email = str_input('email');
            $user  = User::findByEmail($email);
            if ($user) {
                $token = bin2hex(random_bytes(20));
                User::update((int) $user['id'], [
                    'reset_token'   => $token,
                    'reset_expires' => date('Y-m-d H:i:s', time() + 3600),
                ]);
                self::sendResetEmail($user, $token);
            }
            // Always show success (don't leak which emails exist).
            flash('success', 'If that email exists, a password reset link has been sent.');
            redirect('login');
        }
        view_bare('auth/forgot', [], 'Forgot password');
    }

    public function reset(): void
    {
        $token = str_input('token');
        $user  = $token ? User::findByResetToken($token) : null;
        if (!$user) {
            flash('error', 'This reset link is invalid or has expired.');
            redirect('login');
        }
        if ($this->isPost()) {
            csrf_guard();
            $pass = (string) input('password', '');
            if (strlen($pass) < 8) {
                flash('error', 'Password must be at least 8 characters.');
            } else {
                User::updatePassword((int) $user['id'], $pass);
                User::update((int) $user['id'], ['reset_token' => null, 'reset_expires' => null]);
                flash('success', 'Password updated. Please sign in.');
                redirect('login');
            }
        }
        view_bare('auth/reset', ['token' => $token], 'Reset password');
    }

    public function verify(): void
    {
        $token = str_input('token');
        $user  = $token ? User::findByVerifyToken($token) : null;
        if ($user) {
            User::update((int) $user['id'], ['is_verified' => 1, 'verify_token' => null]);
            flash('success', 'Your email is verified. Thank you!');
        } else {
            flash('error', 'Invalid verification link.');
        }
        redirect(Auth::check() ? 'dashboard' : 'login');
    }

    // ----------------------------------------------------------------
    //  Transactional email. Uses the user's first available SMTP if any,
    //  otherwise PHP mail() as a best-effort fallback.
    // ----------------------------------------------------------------
    private static function sendVerificationEmail(array $user): void
    {
        $link = url('verify?token=' . $user['verify_token']);
        $html = '<p>Hi ' . e($user['name']) . ',</p><p>Please confirm your email for ' . APP_NAME
              . ' by clicking the link below:</p><p><a href="' . $link . '">' . $link . '</a></p>';
        self::systemSend($user['email'], 'Verify your email - ' . APP_NAME, $html);
    }

    private static function sendResetEmail(array $user, string $token): void
    {
        $link = url('reset?token=' . $token);
        $html = '<p>Hi ' . e($user['name']) . ',</p><p>We received a request to reset your password. '
              . 'This link expires in 1 hour:</p><p><a href="' . $link . '">' . $link . '</a></p>'
              . '<p>If you did not request this, you can ignore this email.</p>';
        self::systemSend($user['email'], 'Reset your password - ' . APP_NAME, $html);
    }

    private static function systemSend(string $to, string $subject, string $html): void
    {
        // Routes through a connected SMTP account when available, else mail().
        Mailer::sendSystem($to, $subject, $html);
    }
}
