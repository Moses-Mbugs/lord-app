<div class="header" >

    <!-- Logo -->
    <div class="header-left">
        <a href="{{url('home')}}" class="logo">
            <img src="{{asset('assets/img/logo.svg')}}" width="100" alt="">
        </a>
    </div>
    <!-- /Logo -->

    <a id="toggle_btn" href="javascript:void(0);">
        <span class="bar-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </a>

    <!-- Header Title -->
    <div class="page-title-box">
        <h3></h3>
    </div>
    <!-- /Header Title -->

    <a id="mobile_btn" class="mobile_btn" href="#sidebar"><i class="fa fa-bars"></i></a>

    <!-- Header Menu -->
    <ul class="nav user-menu">

        <!-- Search -->
        <!-- <li class="nav-item">
            <div class="top-nav-search">
                <a href="javascript:void(0);" class="responsive-search">
                    <i class="fa fa-search"></i>
               </a>
                <form action="">
                    <input class="form-control" type="text" placeholder="Search here">
                    <button class="btn" type="submit"><i class="fa fa-search"></i></button>
                </form>
            </div>
        </li> -->
        <!-- /Search -->

        <!-- Flag -->

        <!-- /Flag -->

        <!-- Notifications -->
        <li class="nav-item dropdown">
            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                <i class="fa fa-bell-o"></i> <span class="badge badge-pill">3</span>
            </a>
        <!-- 	 -->
        </li>
        <!-- /Notifications -->

        <!-- Message Notifications -->
        <li class="nav-item dropdown">
            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                <i class="fa fa-comment-o"></i> <span class="badge badge-pill">8</span>
            </a>
            <!-- -->
        </li>
        <!-- /Message Notifications -->

        <li class="nav-item dropdown has-arrow main-drop">
            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                <span class="user-img">
                    @if(!empty(Auth::user()->photo_bio))
                        <img src="{{asset('files/USER-'.Auth::id().'.jpg')}}" alt="">
                    @else
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#0082BB;color:#fff;font-weight:700;font-size:13px;">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                    @endif
                <span class="status online"></span></span>
                <span>@auth {{Auth::user()->name}} @else uplifted @endif</span>
            </a>
            <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{url('my-profile')}}">My Profile</a>
                <a class="dropdown-item" href="#">Settings</a>

                  <a href="{{ route('logout') }}"   onclick="event.preventDefault();    document.getElementById('logout-form').submit();" class="dropdown-item">
    <i class="typcn typcn-power-outline"></i> Sign Out</a>

      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                           @csrf
                       </form>
            </div>
        </li>
    </ul>
    <!-- /Header Menu -->

    <!-- Mobile Menu -->
    <div class="dropdown mobile-user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
        <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item" href="{{url('my-profile')}}">My Profile</a>
            <a class="dropdown-item" href="">Settings</a>
              <a href="{{ route('logout') }}"   onclick="event.preventDefault();    document.getElementById('logout-form').submit();" class="dropdown-item">
    <i class="typcn typcn-power-outline"></i> Sign Out</a>

      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                           @csrf
                       </form>
        </div>
    </div>
    <!-- /Mobile Menu -->

</div>
