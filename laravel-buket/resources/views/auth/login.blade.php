<x-guest-layout>
    <style>
        /* Menghilangkan Logo Laravel Breeze */
        header, .flex.justify-center.mb-4, svg, a[href="/"] { display: none !important; }

        /* Container Utama */
        .login-container {
            width: 100%;
            max-width: 400px; /* Lebar maksimal di laptop */
            margin: 20px auto;
            background: #ffffff;
            border-top: 6px solid #f472b6;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 30px;
            box-sizing: border-box; /* Penting agar padding tidak merusak lebar */
        }

        /* Responsif untuk HP */
        @media (max-width: 480px) {
            .login-container {
                max-width: 90%; /* Mengikuti lebar layar HP */
                padding: 20px;
                margin: 10px auto;
            }
        }

        .title-pink { color: #db2777; font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 5px; }
        .subtitle { color: #888; font-size: 14px; text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 20px; }
        .label-pink { display: block; color: #db2777; font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        
        .input-pink { 
            width: 100%;
            padding: 12px;
            border: 1px solid #fbcfe8;
            border-radius: 8px;
            box-sizing: border-box; /* Agar input tidak keluar kotak */
            font-size: 16px; /* Mencegah auto-zoom di iPhone */
        }
        .input-pink:focus { border-color: #f472b6; outline: none; box-shadow: 0 0 5px rgba(244, 114, 182, 0.3); }

        .btn-pink {
            width: 100%;
            background-color: #f472b6;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-pink:hover { background-color: #db2777; }

        .flex-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #666; }
        .link-pink { color: #f472b6; text-decoration: none; }
    </style>

    <div class="login-container">
        <div class="title-pink">Masuk Akun</div>
        <div class="subtitle">Silahkan isi data diri kamu ✨</div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label class="label-pink">Alamat Email</label>
                <input id="email" type="email" name="email" class="input-pink" placeholder="nama@email.com" required autofocus>
                <x-input-error :messages="$errors->get('email')" style="color:red; font-size:12px; margin-top:5px;" />
            </div>

            <div class="form-group">
                <label class="label-pink">Kata Sandi</label>
                <input id="password" type="password" name="password" class="input-pink" placeholder="••••••••" required>
                <x-input-error :messages="$errors->get('password')" style="color:red; font-size:12px; margin-top:5px;" />
            </div>

            <div class="flex-row">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="remember" style="margin-right: 5px;"> Ingat saya
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link-pink">Lupa?</a>
                @endif
            </div>

            <button type="submit" class="btn-pink">
                LOGIN SEKARANG
            </button>
        </form>
    </div>
</x-guest-layout>
