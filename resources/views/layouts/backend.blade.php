<!--
|--------------------------------------------------------------------------
| resources/views/layouts/backend.blade.php *** Copyright netprogs.pl | avaiable only at Udemy.com | further distribution is prohibited  ***
|--------------------------------------------------------------------------
-->
<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Admin panel</title>

        <!-- Bootstrap core CSS -->
        <link href="https://bootswatch.com/3/readable/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">


        <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
        <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
        <script src="http://getbootstrap.com/assets/js/ie-emulation-modes-warning.js"></script>

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">  <!-- Lecture 5  -->

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        
        <!-- Lecture 27 -->
        <script>
        var base_url = '{{ url('/admin') }}'; <?php /* Lecture 32 admin argument */?>
        

        <!-- Lecture 34 -->
        <?php
        if (isset($_COOKIE['scroll_val'])) {

            echo 'var scroll_val=' . '"' . (int) $_COOKIE['scroll_val'] . '";';

            setcookie('scroll_val', '', -3000);
        }
        ?>

        </script>
        
    </head>

    <body>

        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="{{ route('home') }}">TDSI BOOKING PROJECT!</a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav navbar-right">
                        
                        <!-- Lecture 49 -->
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                @if( $ncounter = count($notifications->where('status',0)) )
                                <span id="app-notifications-count" class="button__badge">{{ $ncounter }}</span>
                                @else
                                <span id="app-notifications-count" class="button__badge hidden">0</span>
                                @endif
                                <span class="glyphicon glyphicon-envelope"></span> <span class="caret"></span></a>
                            <ul id="app-notifications-list" class="dropdown-menu">
                                @foreach( $notifications as $notification )
                                    @if($notification->status)
                                    <li><a>{{ $notification->content }}</a></li>
                                    @else
                                    <li class="unread_notification"><a href="{{ $notification->id }}">{{ $notification->content }}</a></li>
                                    @endif

                                @endforeach

                            </ul>
                        </li>
                        
                        <li><p class="navbar-text">{{ Auth::user()->FullName /* Lecture 34 */ }}</p></li>
                        <li><a href="{{ route('profile') }}">Profile</a></li>
                        
                        <!-- Lecture 34 -->
                        <li>
                            <a href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                       document.getElementById('logout-form').submit();">
                                Logout
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                {{ csrf_field() }}
                            </form>
                        </li>
                        
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-3 col-md-2 sidebar">
                    <ul class="nav nav-sidebar">
                        <li class="active"><a href="{{ route('adminHome') }}">Booking calendar <span class="sr-only">(current)</span></a></li>

                        <!-- Lecture 36 -->
                        @if( Auth::user()->hasRole(['owner','admin'])  )
                        <li><a href="{{ route('myObjects') }}">My tourist objects</a></li>
                        <li><a href="{{ route('saveObject') }}">Add a new hotel</a></li>
                        @endif
                        @if( Auth::user()->hasRole(['admin']) )
                        <li><a href="{{ route('cities.index') }}">Cities</a></li>
                        @endif

                    </ul>
                </div>

                <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">

                    <!-- Lecture 38 -->
                    @if ($errors->any())
                    <br>
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                

                    <br> <!-- Lecture 35 -->

                    <!-- Lecture 35 -->
                    @if(Session::has('message'))
                    <br>
                    <div class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible show" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        {{ Session::get('message') }}
                    </div>
                    @endif

                    @yield('content') <!-- Lecture 5 -->
                </div>
            </div>
        </div>

        <!-- Bootstrap core JavaScript
        ================================================== -->
        <!-- Placed at the end of the document so the pages load faster -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
        <script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>
        <!-- Just to make our placeholder images work. Don't actually copy the next line! -->
        <script src="http://getbootstrap.com/assets/js/vendor/holder.min.js"></script>
        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="http://getbootstrap.com/assets/js/ie10-viewport-bug-workaround.js"></script>
        <script src="{{ asset('js/app.js') }}"></script> <!-- Lecture 5 -->
        <script src="{{ asset('js/admin.js') }}"></script> <!-- Lecture 5 -->
        @stack('scripts') <!-- Lecture 30 -->
        
       <!-- Lecture 34 -->
        <script>

        $(function () {


        //to prevent scroll top when refreshing
        if (typeof scroll_val !== 'undefined') {

            $(window).scrollTop(scroll_val);
            //scroll(0,scroll_val);
        }

        });


        //to prevent scroll top when refreshing
        function scroll_value()
        {
            document.cookie = 'scroll_val' + '=' + $(window).scrollTop();
        }


        $(document).on('click', '.keep_pos', function (e) {
            scroll_value();
        });

        </script>
        
    </body>
</html>

