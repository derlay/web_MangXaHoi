========================================================================
             ĐỒ ÁN: XÂY DỰNG WEBSITE MẠNG XÃ HỘI (PHP & MySQL)
========================================================================

1. THÔNG TIN CHUNG
------------------
- Tên đề tài: Xây dựng website mạng xã hội kết nối bạn bè
- Ngôn ngữ: PHP Thuần 
- Cơ sở dữ liệu: MySQL
- Công nghệ Frontend: HTML5, CSS3, Bootstrap 5, JavaScript 
- Công nghệ Backend: PHP 8, WebSocket (Ratchet Library), cloudlibrary
- Môn học: Phần mềm Mã nguồn mở 

2. TÁC GIẢ (NHÓM THỰC HIỆN)
---------------------------
Họ và tên: Trần Quang Tuấn Vũ	 Mã SV: 2224801030034

Họ và tên: Phan Trọng Tiến     Mã SV: 2224801030035


3. YÊU CẦU HỆ THỐNG (SYSTEM REQUIREMENTS)
-----------------------------------------
Để chạy được mã nguồn này, máy tính cần cài đặt:
- Web Server: XAMPP, WAMP hoặc Laragon (Khuyên dùng XAMPP).
- PHP Version: >= 8.0.
- MySQL Version: >= 5.7.
- Trình duyệt: Google Chrome / Microsoft Edge (Mới nhất).

4. HƯỚNG DẪN CÀI ĐẶT (INSTALLATION)
-----------------------------------
Bước 1: Cấu hình Cơ sở dữ liệu
   - Mở phpMyAdmin (http://localhost:8000/phpmyadmin).
   - Tạo một database mới có tên: "social_media".
   - Chọn tab "Import" và chọn file "database.sql" nằm trong thư mục gốc của dự án.
   - Bấm "Go" để nhập dữ liệu.

Bước 2: Cài đặt Mã nguồn
   - Copy thư mục dự án vào thư mục "htdocs" của XAMPP (thường là C:\xampp\htdocs\ten_du_an).
   - Mở file cấu hình tại: "config/db.php".
   - Kiểm tra thông tin kết nối (mặc định của XAMPP):
     + $servername = "localhost";
     + $username = "root";
     + $password = ""; (để trống)
     + $dbname = "social_media";

Bước 3: Chạy dự án
   - Mở Terminal (CMD) tại thư mục gốc của dự án.
   - chạy lệnh php -S localhost:8000
   - Khởi động Apache và MySQL trong XAMPP Control Panel.
   - Mở trình duyệt và truy cập: http://localhost:8000/ten_du_an

Bước 4: Kích hoạt Chat Real-time (WebSocket) - *Quan trọng*
   - Mở Terminal (CMD) tại thư mục gốc của dự án.
   - Chạy lệnh: docker start redis và php server\websocket-server.php
   - Giữ cửa sổ CMD luôn mở để chức năng Chat hoạt động.

5. TÀI KHOẢN TRUY CẬP (DEMO ACCOUNTS)
-------------------------------------
Hệ thống đã có sẵn dữ liệu mẫu để kiểm thử:

* Tài khoản Admin:
  - Email: admin
  - Pass: 12345

6. CÁC TÍNH NĂNG CHÍNH
----------------------
- Đăng ký / Đăng nhập / Quên mật khẩu.
- Cập nhật thông tin cá nhân (Avatar, Ảnh bìa).
- Đăng bài viết (Text, Ảnh), Chế độ công khai/bạn bè.
- Tương tác: Like, Comment, Share.
- Kết bạn: Gửi lời mời, Chấp nhận, Hủy kết bạn.
- Chat: Nhắn tin thời gian thực (Real-time).
- Admin: Thống kê hệ thống, Khóa tài khoản vi phạm.

========================================================================
Cảm ơn đã sử dụng mã nguồn này!
Mọi thắc mắc xin liên hệ: zalo 0867269672
========================================================================

