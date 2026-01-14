<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'CashFlow') }}</title>

    <!-- Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue-600">💰 CashFlow</h1>
                <p class="text-gray-600 mt-2">Cash Flow Prediction System</p>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="list-disc list-inside text-red-700 text-sm">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}"
                        required 
                        autofocus
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="admin@example.com"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember" 
                        {{ old('remember') ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="remember" class="ml-2 block text-sm text-gray-700">
                        Remember me
                    </label>
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition"
                >
                    Login
                </button>
            </form>

            <!-- Divider -->
            <div class="my-6 flex items-center">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="px-3 text-gray-500 text-sm">or</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <!-- Links -->
            <div class="space-y-3 text-center">
                <p class="text-gray-600 text-sm">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                        Register here
                    </a>
                </p>

                @if (Route::has('password.request'))
                <p class="text-gray-600 text-sm">
                    <a href="{{ route('password.request') }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                        Forgot password?
                    </a>
                </p>
                @endif
            </div>

            <!-- Demo Credentials -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-xs font-semibold text-blue-900 mb-2">Demo Credentials:</p>
                <p class="text-xs text-blue-800">Email: <code class="bg-white px-2 py-1 rounded">admin@example.com</code></p>
                <p class="text-xs text-blue-800">Password: <code class="bg-white px-2 py-1 rounded">password123</code></p>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-gray-600 text-sm mt-6">
            &copy; {{ date('Y') }} CashFlow. All rights reserved.
        </p>
    </div>
</body>
</html>
