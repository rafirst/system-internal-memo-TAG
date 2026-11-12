<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#a9001e">
    <title>Masuk · Internal Memo</title>
    <!-- Buatan gue nih Mohammad Rafi Abdillah. IG : @rafirst_ -->

    <style>
        :root {
            color-scheme: light;
            font-family: Arial, Helvetica, sans-serif;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            color: #111827;
            background: #eaf1fb url('{{ asset('assets/images/bg-im-tag.png') }}') center / cover no-repeat;
        }

        .topbar,
        .footerbar {
            position: absolute;
            z-index: 1;
            right: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            background: repeating-linear-gradient(
                130deg,
                #a4001e 0,
                #a4001e 88px,
                #c40028 88px,
                #c40028 122px,
                #900019 122px,
                #900019 198px
            );
            font-weight: 700;
        }

        .topbar {
            top: 0;
            height: 8%;
            min-height: 54px;
            padding: 0 2%;
        }

        .brand-logo {
            display: block;
            width: clamp(72px, 7.5vw, 104px);
            height: auto;
        }

        .topbar-label,
        .footerbar {
            font-size: clamp(9px, 0.85vw, 12px);
            letter-spacing: 0.12em;
        }

        .login-layout {
            position: absolute;
            top: 15%;
            right: 10.5%;
            bottom: 15%;
            left: 10.5%;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .intro {
            align-self: start;
            padding: 20% 7% 0;
        }

        .intro h1 {
            margin: 0 0 8px;
            color: #982031;
            font-size: clamp(25px, 2.8vw, 36px);
            line-height: 1.1;
        }

        .intro p {
            max-width: 360px;
            margin: 0;
            color: #718096;
            font-size: clamp(13px, 1.05vw, 15px);
            line-height: 1.5;
        }

        .login-form {
            align-self: center;
            justify-self: center;
            width: min(72%, 350px);
            margin-top: -1%;
        }

        .login-form h2 {
            margin: 0 0 26px;
            font-size: clamp(24px, 2.3vw, 31px);
            font-weight: 500;
            letter-spacing: -0.04em;
        }

        .field {
            margin-bottom: 15px;
        }

        .field label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
        }

        .field input {
            width: 100%;
            height: 45px;
            padding: 0 15px;
            border: 1px solid #d8dce3;
            border-radius: 11px;
            outline: none;
            background: rgba(255, 255, 255, 0.88);
            color: #172033;
            font: inherit;
            font-size: 13px;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .field input::placeholder {
            color: #9aa3b2;
        }

        .field input:focus {
            border-color: #a21d32;
            box-shadow: 0 0 0 3px rgba(162, 29, 50, 0.12);
        }

        .error-message {
            margin-bottom: 15px;
            padding: 10px 12px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
        }

        .submit-button {
            width: 100%;
            min-height: 46px;
            margin-top: 10px;
            border: 0;
            border-radius: 12px;
            background: #991d31;
            box-shadow: 0 8px 14px rgba(128, 20, 40, 0.18);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            transition: background-color 150ms ease, transform 150ms ease;
        }

        .submit-button:hover {
            background: #801729;
        }

        .submit-button:active {
            transform: translateY(1px);
        }

        .form-note {
            margin: 31px 0 0;
            padding-top: 28px;
            border-top: 1px dotted #aeb7c5;
            color: #7b8492;
            font-size: 9px;
            text-align: center;
        }

        .footerbar {
            bottom: 0;
            min-height: 46px;
            padding: 0 2.5%;
            letter-spacing: 0;
        }

        @media (max-width: 700px) {
            body {
                min-height: 100svh;
                background-size: auto 100%;
                background-position: 52% center;
            }

            .topbar {
                min-height: 58px;
                padding: 0 5%;
            }

            .topbar-label {
                max-width: 145px;
                text-align: right;
            }

            .login-layout {
                position: relative;
                top: auto;
                right: auto;
                bottom: auto;
                left: auto;
                display: flex;
                min-height: 100svh;
                align-items: center;
                justify-content: center;
                padding: 88px 20px 78px;
            }

            .intro {
                display: none;
            }

            .login-form {
                width: min(100%, 390px);
                margin: 0;
                padding: 32px 25px;
                border: 1px solid rgba(214, 224, 238, 0.85);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.94);
                box-shadow: 0 18px 48px rgba(39, 62, 96, 0.18);
            }

            .login-form h2 {
                margin-bottom: 24px;
            }

            .footerbar {
                min-height: 48px;
                padding: 0 5%;
                font-size: 8px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <img class="brand-logo" src="{{ asset('assets/images/Logo-TAG-white.png') }}" alt="Tunas Auto Graha">
        <span class="topbar-label">INTERNAL MEMO SYSTEM</span>
    </header>

    <main class="login-layout">
        <section class="intro" aria-label="Tentang Internal Memo">
            <h1>INTERNAL MEMO</h1>
            <p>Kelola pengajuan, pantau, dan selesaikan persetujuan memo internal di satu tempat.</p>
        </section>

        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf
            <h2>Masuk ke akun Anda</h2>

            @if($errors->any())
                <div class="error-message" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
            </div>

            <button class="submit-button" type="submit">Login</button>
            <p class="form-note">SEWAJARNYA BUAT IM AJA MAS / MBA 👩‍💼</p>
        </form>
    </main>

    <footer class="footerbar">
        <span>PT. TUNAS AUTO GRAHA {{ now()->year }} ©</span>
        <span>SYARAT &amp; KETENTUAN BERLAKU</span>
    </footer>
</body>
</html>
