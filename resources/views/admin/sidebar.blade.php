<div class="d-flex align-items-stretch">
    <!-- Sidebar Navigation-->
    <nav id="sidebar">
      <!-- Sidebar Header-->
      <div class="sidebar-header d-flex align-items-center">
        <div class="avatar"><img src="admin/img/avatar-6.jpg" alt="..." class="img-fluid rounded-circle"></div>
        <div class="title">
          <h1 class="h5">Mark Stephen</h1>
          <p>Web Designer</p>
        </div>
      </div>
      <!-- Sidebar Navidation Menus--><span class="heading">Main</span>
      <ul class="list-unstyled">
              <li class="active"><a href="{{url('home')}}"> <i class="icon-home"></i>Home </a></li>
              <li>
                <a href="#hotelDropdown" aria-expanded="false" data-toggle="collapse">
                  <i class="icon-windows"></i>Hotel
                </a>
                <ul id="hotelDropdown" class="collapse list-unstyled">
                  <li><a href="{{url('create_hotel')}}">Add Hotel</a></li>
                  
                  <li><a href="{{url('view_hotel')}}">View Hotel</a></li>
                </ul>
              </li>
              
              <li>
                <a href="#roomDropdown" aria-expanded="false" data-toggle="collapse">
                  <i class="icon-windows"></i>Room
                </a>
                <ul id="roomDropdown" class="collapse list-unstyled">
                  <li><a href="{{url('create_room')}}">Add Room</a></li>
                    
                  <li><a href="{{url('view_room')}}">View Room</a></li>
                </ul>
              </li>
    </nav>