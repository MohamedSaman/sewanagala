<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <title>{{ config('app.name', 'Thihariya Tile Center') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #0A1128;
        }

        :root {
            --brand-navy: #16285A;
            --brand-navy-dark: #0A1128;
            --brand-navy-light: #234294;
            --brand-orange: #E65F1E;
            --brand-orange-dark: #C2410C;
            --brand-orange-soft: #FFF7ED;
        }

        /* Full-screen page */
        .login-container {
            min-height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            position: relative;
            overflow: hidden;
        }

        .background-image {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 10% 20%, rgba(22, 40, 90, 0.95) 0%, rgba(10, 17, 40, 0.98) 100%);
            z-index: 0;
        }

        /* Decorative background light bubbles */
        .background-image::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(230, 95, 30, 0.15) 0%, rgba(230, 95, 30, 0) 70%);
            border-radius: 50%;
            filter: blur(40px);
        }

        /* ---- Split card ---- */
        .login-card {
            position: relative;
            z-index: 1;
            display: flex;
            width: 100%;
            max-width: 920px;
            min-height: 560px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        /* Left branding panel */
        .brand-panel {
            flex: 1 1 44%;
            background: linear-gradient(155deg, #0A1128 0%, #16285A 60%, #1c3272 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
            text-align: center;
            position: relative;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #16285A, #E65F1E, #F97316);
        }

        .brand-panel img.brand-logo {
            height: 90px;
            width: auto;
            object-fit: contain;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 16px rgba(0, 0, 0, 0.4));
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 16px;
            border-radius: 12px;
        }

        .brand-panel h1 {
            color: #ffffff;
            font-size: 1.4rem;
            font-weight: 800;
            margin: 0 0 8px;
            letter-spacing: -0.01em;
        }

        .brand-panel .brand-tagline {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin: 0 0 28px;
            line-height: 1.6;
        }

        .brand-divider {
            width: 54px;
            height: 3px;
            background: linear-gradient(90deg, #16285A, #E65F1E);
            border-radius: 2px;
            margin: 0 auto 24px;
        }

        .brand-meta {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.82rem;
            line-height: 2;
        }

        .brand-meta p {
            margin: 0;
        }

        .brand-meta i {
            margin-right: 8px;
            color: var(--brand-orange);
        }

        .brand-connect {
            display: flex;
            gap: 14px;
            justify-content: center;
            margin-top: 28px;
        }

        .connect-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
            font-size: 18px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }

        .connect-icon.email {
            background: #E65F1E;
        }

        .connect-icon.whatsapp {
            background: #25d366;
        }

        .connect-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            color: #fff;
        }

        /* Right form panel */
        .form-panel {
            flex: 1 1 56%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 52px;
        }

        .form-panel .panel-heading {
            margin-bottom: 32px;
        }

        .form-panel .panel-heading .user-ring {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #EFF6FF;
            border: 2px solid #DBEAFE;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .form-panel .panel-heading .user-ring i {
            font-size: 1.8rem;
            color: var(--brand-navy);
        }

        .form-panel .panel-heading h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0F172A;
            margin: 0 0 6px;
        }

        .form-panel .panel-heading p {
            color: #64748B;
            font-size: 0.88rem;
            margin: 0;
        }

        /* Inputs */
        .input-wrap {
            position: relative;
            margin-bottom: 18px;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 1rem;
            pointer-events: none;
        }

        .input-wrap .form-control {
            padding: 14px 18px 14px 44px;
            border-radius: 10px;
            border: 1.5px solid #E2E8F0;
            font-size: 0.95rem;
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #F8FAFC;
            color: #0F172A;
        }

        .input-wrap .form-control:focus {
            outline: none;
            border-color: var(--brand-orange);
            box-shadow: 0 0 0 3px rgba(230, 95, 30, 0.18);
            background: #fff;
        }

        .input-wrap .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            50% { transform: translateX(6px); }
            75% { transform: translateX(-4px); }
            100% { transform: translateX(0); }
        }

        .shake {
            animation: shake 0.45s cubic-bezier(.36, .07, .19, .97);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 0.875rem;
        }

        .form-options .form-check-label {
            color: #475569;
        }

        .form-options .form-check-input:checked {
            background-color: var(--brand-navy);
            border-color: var(--brand-navy);
        }

        .forgot-link {
            color: var(--brand-orange);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover {
            color: var(--brand-orange-dark);
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand-navy) 0%, var(--brand-navy-light) 60%, var(--brand-orange) 100%);
            border: none;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 18px rgba(22, 40, 90, 0.3);
        }

        .login-btn:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(230, 95, 30, 0.35);
            color: #fff;
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Responsive: stack on small screens */
        @media (max-width: 680px) {
            .login-card {
                flex-direction: column;
                max-width: 420px;
            }

            .brand-panel {
                padding: 36px 28px 28px;
            }

            .brand-panel .brand-connect {
                margin-top: 20px;
            }

            .form-panel {
                padding: 36px 28px;
            }

            .brand-panel h1 {
                font-size: 1.2rem;
            }
        }
    </style>

</head>

<body>
    <div class="font-sans text-gray-900 antialiased">
        {{ $slot }}
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>