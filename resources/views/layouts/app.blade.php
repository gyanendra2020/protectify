<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,700" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="/css/main.css">

    <script src="https://www.paypal.com/sdk/js?client-id=Ae1NTiH9vXajtoJENNENqfU-GYNVRgLaAZHSLWb3Ke9L7BpekzG3IFob6aNhrDANae-Ep1LbrBwnlkkp&vault=true&intent=subscription&disable-funding=credit,card" data-sdk-integration-source="button-factory"></script>

    <script>
        paypal.Buttons({
            style: {
                shape: 'rect',
                color: 'gold',
                layout: 'vertical',
                label: 'subscribe'
            },
            createSubscription: function(data, actions) {
                return actions.subscription.create({
                    'plan_id': 'P-463296927T554882PMBASSZY'
                });
            },
            onApprove: function(data, actions) {
                var orderID = data.orderID;
                var subscriptionID = data.subscriptionID;
                $.ajax({
                    url : '{{url("dashboard/create")}}',
                    type : 'POST',
                    data  : {"_token": "{{ csrf_token() }}", orderID : orderID, subscriptionID: subscriptionID},
                    success:function(data){
                        var res = JSON.parse(data)
                        alert(res.msg);
                        setTimeout(function(){
                            window.location.reload();
                        }, 2000);
                    }
                });
                //return false;

            },
            onCancel: function (data) {
                alert(data);
            }

        }).render('#paypal-button-container');
    </script>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard.ub_pages') }}">{{ __('Pages') }}</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav mr-auto">
                        @if (auth()->check() && in_array(auth()->user()->role, ['ADMIN']))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.index') }}">{{ __('Admin Panel') }}</a>
                            </li>
                        @endif
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                @if(auth()->user()->role == 'user' && auth()->user()->created_by == '')
                                    <a class="dropdown-item" href="{{ route('dashboard.subscription') }}">
                                        {{ __('Subscription') }}
                                    </a>

                                    <a class="dropdown-item" href="{{ route('dashboard.user-list') }}">
                                        {{ __('Add User') }}
                                    </a>
                                    
                                    <a class="dropdown-item" href="{{ route('dashboard.get_code') }}">
                                        {{ __('Get Code') }}
                                    </a>

                                @endif

                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        <main class="py-4">
            @if (auth()->check() && auth()->user()->is_disabled)
                <div class="text-center alert alert-danger" style="font-size: 20px;">Hello <strong>{{ucfirst(auth()->user()->name)}} </strong> your account is temporary disabled.</div>
            @else
                @yield('content')
            @endif
        </main>
    </div>
    <!-- Scripts -->
    <script>window.auth = @json(['user' => auth()->user()]);</script>
    @stack('scripts_before')
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts_after')
</body>
</html>
