<base href="/public">
@include('home.css ')
<div class="header">
    <div class="container">
       <div class="row">
          <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 col logo_section">
             <div class="full">
                <div class="center-desk">
                   <div class="logo">
                      <a href="."><img src="images/logo.png" alt="#" /></a>
                   </div>
                </div>
             </div>
          </div>
          <div class="col-xl-9 col-lg-9 col-md-9 col-sm-9" >
             <nav class="navigation navbar navbar-expand-md navbar-dark  " style="display: flex; justify-content: center;">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExample04" aria-controls="navbarsExample04" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarsExample04" >
                   <ul class="navbar-nav mr-auto"  >
                      <li class="nav-item active" >
                         <a class="nav-link" style="font-size: 14px "href="/">Trang chủ</a>
                      </li>
                      <li class="nav-item">
                         <a class="nav-link"style="font-size: 14px" href="{{url('coupon')}}">ưu đãi</a>
                      </li>
                      <li class="nav-item">
                         <a class="nav-link" style="font-size: 14px" href="{{url('room')}}">Tìm phòng</a>
                      </li>
                      <li class="nav-item">
                         <a class="nav-link" style="font-size: 14px" href="contact.html">Liên hệ ngay</a>
                      </li>



                      @if (Route::has('login'))
                      @auth
                          <li class="nav-item dropdown" style="padding-right: 10px;">
                              <div class="dropdown">
                                  <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                      {{ Auth::user()->name }}
                                  </button>
                                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                      <li><a class="dropdown-item" href="{{ route('profile.show') }}">Thông tin tài khoản</a></li>
                                      <li><hr class="dropdown-divider"></li>
                                      <li>
                                          <form method="POST" action="{{ route('logout') }}">
                                              @csrf
                                              <button type="submit" class="dropdown-item">Đăng xuất</button>
                                          </form>
                                      </li>
                                  </ul>
                              </div>
                          </li>
                      @else
                          <li class="nav-item" style="padding-right: 10px;">
                              <a class="btn btn-primary" style="font-size: 14px;" href="{{ url('login') }}">Đăng nhập</a>
                          </li>
                          @if (Route::has('register'))
                              <li class="nav-item">
                                  <a class="btn btn-success" style="font-size: 14px;" href="{{ url('register') }}">Đăng kí</a>
                              </li>
                          @endif
                      @endauth
                    @endif
                  
                      

                     <li class="nav-item" style="margin-top:5px; padding-left:20px" >
                        <a href="{{url('cart')}}" >
                           <img src="images/cart_logo.png" width="20px" height="20px" >
                        </a>
                     </li>
                   </ul>
                </div>
             </nav>
          </div>
       </div>
    </div>
 </div>
 <section class="hero-banner position-relative d-flex align-items-center" style="height: 500px;">
    <div class="bg-image"></div>

    <div class="container position-relative z-2">
        <div class="text-center mb-4">
            <h1 style="color: aliceblue" class="fw-bold">Khách sạn và nơi để ở tại Hà Nội</h1>
            <p  style="color: aliceblue" class="lead">Tìm kiếm để so sánh giá cả và khám phá ưu đãi tuyệt vời </p>
        </div>

        <form action="{{ route('room.filter')}}" method="GET" id="searchForm" class="search-form">
            <div class="row justify-content-center g-2 bg-white bg-opacity-75 rounded-3 p-3 shadow-lg">
                
                <div class="col-md-3">
                    <input type="text" class="form-control" id="location" name="location" placeholder="🔍"
                        value="{{ request('location') }}">
                    <div id="locationSuggestions" class="position-absolute bg-white border rounded w-100 z-3" style="top: 100%; left: 0; text-color:black; position:absolute"></div>
                </div>

                <div class="col-md-2">
                    <input type="number" class="form-control" id="guests" name="guests" placeholder="Nhập số người" min="1"
                        value="{{ request('guests') ?? 2}}">
                </div>

                <div class="col-md-2">
                    <select class="form-control" id="room_type" name="room_type">
                        <option value="">Phòng bất kỳ</option>
                        <option value="standard" {{ request('room_type') == 'standard' ? 'selected' : '' }}>Phòng tiêu chuẩn</option>
                        <option value="deluxe" {{ request('room_type') == 'deluxe' ? 'selected' : '' }}>Phòng cao cấp (Deluxe)</option>
                        <option value="family" {{ request('room_type') == 'family' ? 'selected' : '' }}>Phòng gia đình</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="text" class="form-control rounded-pill px-3" id="checkin" name="checkin" placeholder="Chọn ngày">
                </div>

                <div class="col-md-2">
                    <input type="text" class="form-control rounded-pill px-3" id="checkout" name="checkout" placeholder="Chọn ngày">
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">TÌM</button>
                </div>

            </div>
        </form>
    </div>
</section>

 


<div class="hotel_list-body" style="margin-top: 20px">
    <div class="hotel-list-container">
        
        <aside class="hotel-list-sidebar">
            <!-- Preview bản đồ nhỏ -->
            <div class="sidebar-map-preview" onclick="openMapModal()">
                <img src="{{ asset('images/ggmap.jpeg') }}" style="width:240px; height:150px" alt="Xem bản đồ vị trí">
                <div class="map-overlay">
                    <span>XEM VỊ TRÍ</span>
                </div>
            </div>

            

            <!-- Tìm kiếm và bộ lọc -->
            <div class="sidebar-search">
                <input type="text" placeholder="Tìm kiếm văn bản" class="sidebar-search-input">
                <span class="sidebar-search-icon">🔍</span>
            </div>
            <ul>
                <h5><strong>Giá mỗi đêm</strong></h5>
                <div id="price-slider"></div>

                <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                    <div>
                        <label for="min_price">TỐI THIỂU</label>
                        <input type="number" id="min_price" name="min_price" class="form-control" style="width: 100px;">
                    </div>
                    <div>
                        <label for="max_price">TỐI ĐA</label>
                        <input type="number" id="max_price" name="max_price" class="form-control" style="width: 100px;" min="0" max="2000000" value="{{ request('max_price', 2000000) }}"> 
                    </div>
                </div>

                <li><a href="#">Theo đánh giá</a></li>
            </ul>
        </aside>

        <!-- Nội dung chính -->
        <main class="hotel-list-content">
            <h1>Danh sách khách sạn tại {{ $city }}</h1>

            <div class="hotel-list-grid">
                @forelse ($hotels as $hotel)
                    <div class="hotel-list-card">
                        <div class="hotel-info-left">
                            <img src="{{ asset('hotelImg/' . $hotel->hotel_image) }}"
                                 alt="{{ $hotel->hotel_name }}"
                                 class="hotel-list-image">
                            <div class="hotel-list-info">
                                <h3 class="hotel-list-name">
                                    <a href="{{ route('rooms.by.hotel', ['id' => $hotel->id]) }}">
                                        {{ $hotel->hotel_name }}
                                    </a>
                                </h3>

                                <p class="hotel-list-rating">
                                    ⭐ Đánh giá: {{ isset($hotel->hotel_rating) ? $hotel->hotel_rating : 5 }} / 5
                                </p>
                                <p class="hotel-list-address">📍 Địa chỉ: {{ $hotel->hotel_address }}</p>
                                <p class="hotel-list-price">
                                    💰 Giá trung bình 1 đêm:
                                    <span>{{ number_format($hotel->average_price, 0, ',', '.') }} VNĐ</span>
                                </p>
                            </div>
                        </div>
                        <div class="hotel-action-right">
                            <button class="check-room-btn" data-hotel="{{ $hotel->hotel_name }}">Kiểm tra phòng còn trống</button>
                        </div>
                    </div>
                @empty
                    <p>Không có khách sạn nào tại {{ $city }}.</p>
                @endforelse
            </div>
        </main>
    </div>
</div>




<!-- Modal Cảnh Báo -->
<div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="warningModalLabel">⚠️ Cảnh báo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body" id="warningModalMessage">
        <!-- Nội dung cảnh báo  -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đã hiểu</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal gg map-->
<div id="mapModal" class="modal-map" style="display: none;">
    <div class="modal-map-content">
        <span class="close-map" onclick="closeMapModal()">&times;</span>
        <div id="hotel-map-modal" style="width: 100%; height: 600px;"></div>
    </div>
</div>





















 @include('home.footer')
 


      <script> // sticky form roll
         window.addEventListener('scroll', function() {
            const form = document.getElementById('searchForm');
            const triggerPoint = 400; // khi scroll xuống 400px

            if (window.scrollY >= triggerPoint) {
                  form.classList.add('sticky');
            } else {
                  form.classList.remove('sticky');
            }
         });
      </script>
      <script> // Auto fill khi ấn "Xem tất cả các phòng"
            document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('.check-room-btn');

                let checkinPicker, checkoutPicker;

                checkinPicker = flatpickr("#checkin", {
                    dateFormat: "Y-m-d",
                    minDate: "today",
                    locale: "vn",
                    onClose: function (selectedDates) {
                        if (selectedDates.length > 0) {
                            const nextDay = new Date(selectedDates[0]);
                            nextDay.setDate(nextDay.getDate() + 1);

                            checkoutPicker.setDate(nextDay);
                            checkoutPicker.set("minDate", nextDay);

                            checkoutPicker.open();
                        }
                    }
                });

                checkoutPicker = flatpickr("#checkout", {
                    dateFormat: "Y-m-d",
                    minDate: new Date().fp_incr(1),
                    locale: "vn"
                });

                document.getElementById('checkin').placeholder = "Nhận phòng";
                document.getElementById('checkout').placeholder = "Trả phòng";

                buttons.forEach(button => {
                    button.addEventListener('click', function () {
                        const hotelName = this.dataset.hotel;
                        const locationInput = document.getElementById('location');

                        if (hotelName) {
                            locationInput.value = hotelName;
                        } else {
                            console.error("data-hotel không tồn tại trên nút!");
                            return;
                        }

                        checkinPicker.open();
                    });
                });
            });
        </script>
         
         <!-- Validate Client = Modal popup-->
         <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('searchForm');
                const modal = new bootstrap.Modal(document.getElementById('warningModal'));
                const modalMessage = document.getElementById('warningModalMessage');

                form.addEventListener('submit', function (e) {
                    const location = document.getElementById('location').value.trim();
                    const guests = parseInt(document.getElementById('guests').value);
                    const checkin = document.getElementById('checkin').value;
                    const checkout = document.getElementById('checkout').value;

                    let errorMessage = '';

                    if (!location) {
                        errorMessage = 'Vui lòng nhập địa điểm bạn muốn tìm.';
                    } else if (!guests || guests < 1) {
                        errorMessage = 'Số người phải lớn hơn 0.';
                    } else if (!checkin || !checkout) {
                        errorMessage = 'Vui lòng chọn cả ngày nhận phòng và trả phòng.';
                    } else if (new Date(checkout) <= new Date(checkin)) {
                        errorMessage = 'Ngày trả phòng phải sau ngày nhận phòng.';
                    }

                    if (errorMessage) {
                        e.preventDefault(); // Ngăn form submit
                        modalMessage.innerText = errorMessage;
                        modal.show();
                    }
                });
            });
        </script>
        <!-- Slider giá -->
        <script>
    document.addEventListener('DOMContentLoaded', function () {
        const slider = document.getElementById('price-slider');
        const minInput = document.getElementById('min_price');
        const maxInput = document.getElementById('max_price');

        const MAX_VALUE = 100000000;

        noUiSlider.create(slider, {
            start: [
                getQueryParam('min_price') || 0,
                getQueryParam('max_price') || MAX_VALUE
            ],
            connect: true,
            step: 100000,
            range: {
                'min': 0,
                'max': MAX_VALUE
            },
            format: {
                to: value => Math.round(value),
                from: value => Number(value)
            }
        });

        // Cập nhật input mỗi khi kéo slider
        slider.noUiSlider.on('update', function (values) {
            minInput.value = values[0];
            maxInput.value = values[1];
        });

        // Gọi hàm lọc (reload trang) mỗi khi người dùng thả chuột sau khi kéo slider
        slider.noUiSlider.on('change', function (values) {
            autoFilter(values[0], values[1]);
        });

        // Người dùng nhập min → cập nhật lại slider và giữ nguyên max
        minInput.addEventListener('blur', function () {
            slider.noUiSlider.set([this.value, maxInput.value]);
            autoFilter(this.value, maxInput.value);
        });

        // Người dùng nhập max → cập nhật lại slider
        maxInput.addEventListener('blur', function () {
            slider.noUiSlider.set([minInput.value, this.value]);
            autoFilter(minInput.value, this.value);
        });

        function autoFilter(min, max) {
            const params = new URLSearchParams(window.location.search);
            params.set('min_price', min);
            params.set('max_price', max);
            window.location.search = params.toString();
        }

        function getQueryParam(name) {
            const params = new URLSearchParams(window.location.search);
            return params.get(name);
        }
    });
</script>


<script> //slider gia tien
            let map;
            let markers = [];

            const hotels = @json($hotels);

            function openMapModal() {
                document.getElementById("mapModal").style.display = "block";
                setTimeout(() => initMap(), 200); // Cho div map kịp hiển thị
            }

            function closeMapModal() {
                document.getElementById("mapModal").style.display = "none";
            }

            function initMap() {
                if (!map) {
                    map = new google.maps.Map(document.getElementById("hotel-map-modal"), {
                        zoom: 12,
                        center: { lat: 16.047079, lng: 108.20623 } // ví dụ: Đà Nẵng
                    });
                }

                // Xóa marker cũ
                markers.forEach(marker => marker.setMap(null));
                markers = [];

                const geocoder = new google.maps.Geocoder();

                hotels.forEach(hotel => {
                    geocoder.geocode({ address: hotel.hotel_address }, (results, status) => {
                        if (status === 'OK') {
                            const position = results[0].geometry.location;
                            const marker = new google.maps.Marker({
                                map: map,
                                position: position,
                                title: hotel.hotel_name
                            });

                            const infoWindow = new google.maps.InfoWindow({
                                content: `<strong>${hotel.hotel_name}</strong><br>${hotel.hotel_address}`
                            });

                            marker.addListener('click', () => {
                                infoWindow.open(map, marker);
                            });

                            markers.push(marker);
                        } else {
                            console.warn("Không tìm được vị trí:", hotel.hotel_address);
                        }
                    });
                });
            }
        </script>
      
        

 <script> //live search
   $(document).ready(function () {
      $('#location').on('keyup', function () {
         let query = $(this).val();
         if (query.length >= 1) {
               $.ajax({
                  url: "{{ route('locations.search') }}",
                  method: 'GET',
                  data: { query: query },
                  success: function (data) {
                     let suggestions = $('#locationSuggestions');
                     suggestions.empty();

                     if (data.length > 0) {
                           data.forEach(function (item) {
                              suggestions.append(`
                                 <div class="suggest-item  items-center gap-2 p-2 cursor-pointer hover:bg-gray-100" data-full="${item.hotel_name}">
                                    <img src="/hotelImg/${item.hotel_image}" alt="${item.hotel_name}" class="w-12 h-12 object-cover rounded">
                                    <div class=hotel_info>
                                       <strong>${item.hotel_name}</strong><br>
                                       <small>${item.hotel_address},    TP: ${item.hotel_city}</small>
                                    </div>
                                 </div>
                              `);
                           });
                     } else {
                           suggestions.append(`<div class="suggest-item">Không tìm thấy</div>`);
                     }
                  }
               });
         } else {
               $('#locationSuggestions').empty();
         }
      });

      // Khi chọn gợi ý
      $(document).on('click', '.suggest-item', function () {
         $('#location').val($(this).data('full'));
         $('#locationSuggestions').empty();
      });

      // Ẩn khi click ra ngoài
      $(document).on('click', function (e) {
         if (!$(e.target).closest('#location, #locationSuggestions').length) {
               $('#locationSuggestions').empty();
         }
      });
   });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.querySelector(".search-form");
        const inputs = form.querySelectorAll("input, select");
        let overlay;

        // Khi một phần tử input/select được focus
        inputs.forEach(input => {
            input.addEventListener("focus", () => {
                // Nếu lớp tối chưa tồn tại, tạo nó
                if (!overlay) {
                    overlay = document.createElement("div");
                    overlay.style.position = "fixed";
                    overlay.style.top = "0";
                    overlay.style.left = "0";
                    overlay.style.width = "100%";
                    overlay.style.height = "100%";
                    overlay.style.background = "rgba(0, 0, 0, 0.5)";
                    overlay.style.zIndex = "1";
                    document.body.appendChild(overlay);
                }
            });
        });

        // Khi click ra ngoài form, ẩn lớp tối
        document.addEventListener("click", (event) => {
            if (overlay && !form.contains(event.target)) {
                overlay.remove();
                overlay = null;
            }
        });
    });
</script>





     
      


        
     

        

         