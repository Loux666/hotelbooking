
@include('home.css')
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
                      <li class="nav-item " >
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
                  
                      

                     <li class="nav-item active" style="margin-top:5px; padding-left:20px" >
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
 <hr>
 

  <div class="cart-main">
    <div class="cart-wrapper">
      <!-- Bên trái -->
      <div class="cart-left">
        <div class="cart-tabs">
          <div class="cart-tab active" data-tab="cart">Giỏ hàng</div>
          <div class="cart-tab" data-tab="history">Lịch sử đặt phòng</div>
        </div>

        <!-- Nội dung giỏ hàng -->
        <div id="tab-cart" class="cart-tab-content active">
          @foreach ($cartItems as $item)
          <div class="cart-item">
            <div class="cart-header">
              <div class="cart-tag">🏢 {{ $item->room->hotel->hotel_name ?? 'Tên khách sạn' }}</div>
              <div class="cart-remove">
                <form method="POST" action="{{ route('cart.remove', $item->id) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit">🗑️ Xóa</button>
               </form>
              </div>
            </div>

            <div class="cart-body">
              <input
                type="checkbox"
                class="cart-checkbox"
                name="selected_rooms[]"
                value="{{ $item->id }}"
                data-price="{{ $item->price }}"
                {{ (!$item->is_available_now || $item->is_expired) ? 'disabled' : '' }}
                checked
              >

              <img src="{{ asset('hotelImg/' . ($item->room->hotel->hotel_image ?? 'default.jpg')) }}" alt="Hotel" class="cart-image">

              <div class="cart-info">
                <div class="hotel-name">
                  <a href="{{ route('rooms.by.hotel', $item->room->hotel->id) }}" class="hotel-link">
                    {{ $item->room->hotel->hotel_name }}
                  </a>
                </div>
                <div class="hotel-sub">
                  <span class="stars">⭐⭐</span>
                  <span class="location">📍 {{ $item->room->hotel->hotel_name }}</span>
                </div>
                <div class="rating">💬 <span class="score">7.5</span> Rất tốt · 280 nhận xét</div>
              </div>

              <div class="cart-details">
                <div class="room-name">1 x {{ $item->room->room_name }} cho {{ $item->room->capacity }} người </div>
                <div class="date">
                  📅 {{ \Carbon\Carbon::parse($item->checkin)->translatedFormat('d \\t\\há\\n\\g m \\nă\\m Y') }}
                  —
                  {{ \Carbon\Carbon::parse($item->checkout)->translatedFormat('d \\t\\há\\n\\g m \\nă\\m Y') }}
                </div>
              </div>

              <div class="cart-price">
                <div class="amount">{{ number_format($item->price, 0, ',', '.') }}  ₫</div>
                <div class="note" style="font-size:10px;font-style:italic">Bao gồm thuế và phí</div>

                @if ($item->is_expired)
                  <div class="unavailable-note" style="color: red; font-weight: bold;">
                    ⚠ Phòng không thể đặt vì ngày nhận phòng đã qua
                  </div>
                @elseif(!$item->is_available_now)
                  <div class="unavailable-note" style="color: red; font-weight: bold;">
                    ⚠ Phòng đã hết cho thời gian này
                  </div>
                @endif
              </div>
            </div>
          </div>
          @endforeach
        </div>

        <!-- Nội dung lịch sử -->
        <div id="tab-history" class="cart-tab-content">
            @if ($bookings->isEmpty())
                <p>Bạn chưa đặt phòng nào.</p>
            @else
                @foreach ($bookings as $booking)
                    <div class="card mb-4">
                        <div class="card-header">
                            Mã đặt phòng: #{{ $booking->id }}<br>
                            Trạng thái: <strong>{{ $booking->status }}</strong> | Thanh toán: <strong>{{ $booking->payment_status }}</strong><br>
                            Ngày tạo: {{ $booking->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div class="card-body">
                            <p><strong>Khách:</strong> {{ $booking->guest_name }} | Email: {{ $booking->guest_email }} | SĐT: {{ $booking->guest_phone }}</p>
                            <p><strong>Ngày nhận phòng:</strong> {{ $booking->checkin_date }} - <strong>Ngày trả:</strong> {{ $booking->checkout_date }}</p>

                            @foreach ($booking->booking_details as $detail)
                                <div class="border p-2 mb-2">
                                    <p><strong>Khách sạn ID:</strong> {{ $detail->hotel_id ?? 'N/A' }}</p>
                                    <p><strong>Phòng:</strong> {{ $detail->room_name ?? 'N/A' }} (x{{ $detail->quantity }})</p>
                                    <p><strong>Giá mỗi đêm:</strong> {{ number_format($detail->price_per_night, 0, ',', '.') }} VND</p>
                                    <p><strong>Số đêm:</strong> {{ $detail->nights }}</p>
                                    <p><strong>Tổng:</strong> {{ number_format($detail->subtotal, 0, ',', '.') }} VND</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
      </div>

      <!-- Bên phải -->
      <div class="cart-right">
        <div class="cart-summary-title">Tổng giá</div>
        <div class="cart-summary-total" id="totalAmount">0 ₫</div>
        <div class="cart-summary-note" id="totalNote">0 món hàng, bao gồm thuế và phí</div>
         <form id="verify-cart-form" action="{{ route('cart.verify') }}" method="POST">
            @csrf
            <input type="hidden" name="selected_ids" id="selected_ids">
            <button type="submit" class="cart-summary-btn">Tiếp theo</button>
         </form>
      </div>
    </div>
  </div>


<!-- Modal báo lỗi nếu có -->
@if (session('modal_error'))
<div class="modal-backdrop" id="cartErrorModal" tabindex="-1">
  <div class="modal-box">
    <p class="modal-message">{{ session('modal_error') }}</p>
    <button class="modal-button" onclick="location.reload()">Cập nhật lại giỏ hàng</button>
  </div>
</div>
@endif

<script>
  window.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('cartErrorModal');
    if (modal) {
      modal.focus();
    }
  });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
      // Tab switch
      const tabs = document.querySelectorAll('.cart-tab');
      const contents = document.querySelectorAll('.cart-tab-content');

      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('active'));
          contents.forEach(c => c.classList.remove('active'));
          tab.classList.add('active');
          const tabContent = document.getElementById('tab-' + tab.dataset.tab);
          if (tabContent) tabContent.classList.add('active');
        });
      });

      // Format tiền
      function formatPrice(num) {
        return num.toLocaleString('vi-VN') + ' ₫';
      }

      // Cập nhật tổng tiền
      function updateTotal() {
        const checkboxes = document.querySelectorAll('.cart-checkbox');
        const totalAmount = document.getElementById('totalAmount');
        const totalNote = document.getElementById('totalNote');
        let total = 0;
        let count = 0;

        checkboxes.forEach(cb => {
          if (cb.checked && !cb.disabled) {
            const price = parseInt(cb.dataset.price);
            if (!isNaN(price)) {
              total += price;
              count++;
            }
          }
        });

        if (totalAmount) totalAmount.textContent = formatPrice(total);
        if (totalNote) totalNote.textContent = `${count} món hàng, bao gồm thuế và phí`;
      }

      // Disable nút nếu không có checkbox hợp lệ
      function updateButtonState() {
        const button = document.querySelector('.cart-summary-btn');
        const validCheckboxes = Array.from(document.querySelectorAll('.cart-checkbox'))
          .filter(cb => !cb.disabled && cb.checked);

        if (validCheckboxes.length === 0) {
          button.disabled = true;
          button.classList.add('disabled');
        } else {
          button.disabled = false;
          button.classList.remove('disabled');
        }
      }

      document.addEventListener('change', function (e) {
        if (e.target.classList.contains('cart-checkbox')) {
          updateTotal();
          updateButtonState();
        }
      });

      updateTotal();
      updateButtonState();

      // Submit form
      const form = document.getElementById('verify-cart-form');
      form.addEventListener('submit', function (e) {
        const selected = Array.from(document.querySelectorAll('.cart-checkbox:checked'))
          .filter(cb => !cb.disabled)
          .map(cb => cb.value);

        if (selected.length === 0) {
          e.preventDefault();
          alert('Vui lòng chọn ít nhất 1 phòng để tiếp tục.');
          return;
        }

        document.getElementById('selected_ids').value = JSON.stringify(selected);
      });
    });
</script>

<style>
.cart-summary-btn.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
