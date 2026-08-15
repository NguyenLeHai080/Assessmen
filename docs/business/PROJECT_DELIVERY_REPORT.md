# BÁO CÁO BÀN GIAO MINI ASSESSMENT

**Dự án:** Mini Assessment – Headless WordPress + React SPA

**Vị trí:** CTV Phát triển Hệ thống

**Phiên bản bàn giao:** 1.0.0

**Ngày báo cáo:** 15/08/2026

**Repository:** <https://github.com/NguyenLeHai080/Assessmen>

**Nhánh phát hành:** `prod`

**Issue tham chiếu cho bản cập nhật UI:** `#9`

---

## 1. Mục đích báo cáo

Tài liệu này trình bày cách tiếp cận, kiến trúc, phạm vi triển khai, biện pháp bảo
mật, cách cài đặt, sử dụng và kiểm thử sản phẩm Mini Assessment. Mục tiêu là giúp
người đánh giá có thể cài plugin trên một WordPress sạch, kiểm chứng các yêu cầu và
đối chiếu quyết định kỹ thuật với mã nguồn.

## 2. Yêu cầu đã được xác nhận

Sau khi trao đổi với technical lead, phạm vi được xác nhận như sau:

1. Dữ liệu được tổ chức thành ba nhóm `Assessment`, `Question` và `Answer`, lưu trong
   các bảng tương ứng.
2. Người chưa đăng nhập được xem danh sách Assessment đã `published`.
3. Thao tác tạo dữ liệu yêu cầu đăng nhập WordPress; cập nhật và xóa được bảo vệ bằng
   capability.
4. Database và REST API phải bám theo contract của đề bài.
5. Không tự bổ sung hệ thống attempt/submission khi đề chưa cung cấp schema và API
   contract cho chức năng nộp bài.
6. Toàn bộ REST API và production React SPA được đóng gói thành một WordPress plugin
   ZIP.

Artifact chính để bàn giao là `plugin-assessment.zip`. Source React được giữ trong
`frontend/` để phục vụ review, test và build lại.

## 3. Cách tiếp cận thực hiện

### 3.1. Phân tích yêu cầu

Yêu cầu được tách thành các nhóm có thể kiểm chứng độc lập:

- Vòng đời WordPress plugin và database migration.
- Mô hình dữ liệu Assessment – Question – Answer.
- REST API, HTTP status và response contract.
- Authentication, authorization và bảo vệ dữ liệu public.
- React SPA, API client và các trạng thái giao diện.
- Kiểm thử, tài liệu và đóng gói bàn giao.

Các điểm chưa rõ được hỏi lại lead trước khi triển khai. Phạm vi được giữ nhỏ và bám
contract; không thêm bảng hoặc endpoint không được yêu cầu.

### 3.2. Thiết kế kiến trúc

Luồng xử lý chính:

```text
React SPA
    -> WordPress REST API /wp-json/assessment/v1
        -> REST Controller: permission + validation
            -> Repository: truy vấn và transaction
                -> MySQL custom tables
```

Plugin được chia trách nhiệm thành các lớp:

- `MA_Plugin`: khởi tạo module và đăng ký hook.
- `MA_Migrator`: tạo/nâng cấp database và capability.
- `MA_REST_Controller`: route, permission, validation và HTTP response.
- `MA_Repository`: truy cập database và cascade delete.
- `MA_Frontend`: shortcode, React bundle và REST nonce.
- `MA_Logger`: logging có allow-list, không ghi dữ liệu nhạy cảm.

### 3.3. Quy trình Git

Thay đổi được chia theo Conventional Commits và đi qua luồng:

```text
feature/fix -> dev -> staging -> prod
```

Các pull request được kiểm tra bằng GitHub Actions. Nhánh staging và prod yêu cầu
review độc lập trước khi merge. Commit phát hành trên `prod` là commit được ghi ở đầu
báo cáo.

## 4. Database

Plugin tạo ba bảng theo prefix thực tế của WordPress:

```text
{$wpdb->prefix}assessment
{$wpdb->prefix}assessment_questions
{$wpdb->prefix}assessment_answers
```

Quan hệ dữ liệu:

```text
Assessment (1)
└── Question (N)
    └── Answer (N)
```

Các bảng có primary key, index theo parent/status/sort order và thời gian tạo/cập
nhật. Schema được quản lý bằng `dbDelta()` và option
`mini_assessment_db_version`, nên plugin có thể nâng cấp mà không cần deactivate rồi
activate lại.

Để tương thích với WordPress hosting, schema không áp dụng foreign key vật lý. Tính
toàn vẹn được bảo vệ tại application layer:

- Không tạo Question nếu Assessment cha không tồn tại.
- Không tạo Answer nếu Question cha không tồn tại.
- Xóa Assessment sử dụng transaction và xóa lần lượt Answer, Question, Assessment.
- Query Question/Answer luôn sắp xếp theo `sort_order`, sau đó theo `id` để ổn định.

## 5. REST API

Base URL:

```text
/wp-json/assessment/v1
```

| Method | Endpoint | Quyền truy cập |
| --- | --- | --- |
| GET | `/assessments` | Public, chỉ published |
| POST | `/assessments` | Đăng nhập + `edit_assessments` |
| GET | `/assessments/{id}` | Public nếu published |
| PUT/PATCH | `/assessments/{id}` | Đăng nhập + `edit_assessments` |
| DELETE | `/assessments/{id}` | Đăng nhập + `delete_assessments` |
| GET | `/assessments/{id}/questions` | Theo visibility của Assessment |
| POST | `/questions` | Đăng nhập + `edit_assessments` |
| GET | `/questions/{id}/answers` | Theo visibility của parent chain |
| POST | `/answers` | Đăng nhập + `edit_assessments` |

Danh sách Assessment hỗ trợ `page`, `per_page`, giới hạn tối đa 100 bản ghi và trả
metadata `total`, `total_pages` cùng các header `X-WP-Total` và
`X-WP-TotalPages`.

HTTP status chính:

- `200`: truy vấn, cập nhật hoặc xóa thành công.
- `201`: tạo dữ liệu thành công.
- `401`: chưa đăng nhập.
- `403`: đã đăng nhập nhưng thiếu capability.
- `404`: tài nguyên không tồn tại hoặc không được phép nhìn thấy.
- `422`: dữ liệu không hợp lệ theo business validation.
- `500`: thao tác database thất bại; không trả lỗi database nội bộ cho client.

## 6. Bảo mật

### 6.1. Xác thực và phân quyền

Các thao tác ghi không dựa vào việc ẩn nút trên frontend. Backend luôn kiểm tra:

1. User đã đăng nhập hay chưa.
2. User có capability tương ứng hay không.
3. User có `publish_assessments` khi chuyển trạng thái sang published hay không.

Administrator được gán bốn capability:

```text
read_assessments
edit_assessments
publish_assessments
delete_assessments
```

React chạy cùng origin với WordPress, sử dụng cookie session và REST nonce ngắn hạn.
Nonce được tạo bằng `wp_create_nonce('wp_rest')` và gửi qua header `X-WP-Nonce`.
Không lưu password, Personal Access Token hoặc Application Password trong bundle.

### 6.2. Validation và chống SQL injection

- REST args giới hạn type, enum, min/max và required fields.
- Text được xử lý bằng `sanitize_text_field()` hoặc
  `sanitize_textarea_field()`.
- Status dùng allow-list; score chỉ nhận từ 0 đến 100.
- Query có dữ liệu động dùng `$wpdb->prepare()` hoặc các CRUD helper
  `$wpdb->insert()`, `$wpdb->update()`, `$wpdb->delete()` với format rõ ràng.
- Tên bảng chỉ được tạo từ `$wpdb->prefix` và suffix cố định.

### 6.3. Bảo vệ dữ liệu và XSS

- Anonymous chỉ xem Assessment published và Question active.
- Direct request đến draft/inactive resource trả `404` để không làm lộ sự tồn tại.
- Public Answer query không select cột `score`.
- React render nội dung dưới dạng text và không dùng `dangerouslySetInnerHTML`.
- Output PHP dùng hàm escape và JSON dùng các cờ `JSON_HEX_*`.
- Logger chỉ nhận context đã allow-list và không ghi cookie, nonce, credential hoặc
  raw request body.

## 7. React SPA

React production build được nhúng trong plugin và hiển thị bằng shortcode:

```text
[mini_assessment_app]
```

Các chức năng giao diện hiện có:

- Danh sách Assessment với pagination.
- Chỉ hiển thị dữ liệu mà API cho phép user hiện tại xem.
- Trang chi tiết hiển thị Question và Answer.
- Form tạo, cập nhật và xóa Assessment có xác nhận trước khi xóa.
- Form tạo Question trong trang chi tiết Assessment.
- Form tạo Answer, score và sort order cho từng Question.
- Các control quản trị chỉ hiển thị khi user đã đăng nhập.
- Loading, empty, error, retry và field validation state.
- Giao diện responsive cho desktop, tablet và mobile.

API backend là lớp kiểm soát permission cuối cùng. Việc ẩn control trên React chỉ cải
thiện trải nghiệm và không thay thế kiểm tra capability/REST nonce phía server.

## 8. Hướng dẫn cài đặt trên WordPress sạch

### 8.1. Yêu cầu hệ thống

- WordPress 6.4 trở lên.
- PHP 8.1 trở lên.
- MySQL 5.7+ hoặc MariaDB tương đương; khuyến nghị InnoDB.

### 8.2. Cài plugin

1. Đăng nhập WordPress Admin.
2. Mở **Plugins → Add New Plugin → Upload Plugin**.
3. Chọn `plugin-assessment.zip`.
4. Chọn **Install Now**, sau đó **Activate**.
5. Mở **Settings → Permalinks**, chọn **Post name** và lưu lại.
6. Tạo một Page mới, nhập shortcode `[mini_assessment_app]` và Publish.
7. Mở page vừa tạo để sử dụng React SPA.

Khi activate, plugin tạo/nâng cấp ba bảng và gán capability cho Administrator. Việc
activate lại không làm mất dữ liệu.

## 9. Hướng dẫn sử dụng

### 9.1. Với Administrator

1. Đăng nhập WordPress.
2. Mở page chứa shortcode.
3. Chọn **Create assessment**.
4. Nhập Title, Description và trạng thái Draft/Published.
5. Submit để lưu vào WordPress database.
6. Mở Assessment, chọn **Edit** để cập nhật hoặc **Delete** để xóa toàn bộ cây dữ liệu.
7. Dùng form **New question** để tạo Question.
8. Dùng form **Add answer** dưới mỗi Question để tạo Answer, nhập score và sort order.
9. Publish Assessment để cho phép anonymous xem.

### 9.2. Với người chưa đăng nhập

1. Mở page bằng cửa sổ ẩn danh.
2. Danh sách chỉ hiển thị Assessment published.
3. Draft/archived Assessment và inactive Question không hiển thị.
4. Score của Answer không được trả cho public client.
5. Nếu cố gọi API ghi dữ liệu, server trả `401`.

## 10. Chạy môi trường Docker trên máy mới

Máy cần Git và Docker Desktop. Node.js 20+ chỉ cần khi muốn test/build lại React.

```powershell
git clone https://github.com/NguyenLeHai080/Assessmen.git
cd Assessmen
git switch prod
Copy-Item .env.example .env
```

Thay password development trong `.env`:

```dotenv
WP_DB_PASSWORD=your_local_database_password
WP_DB_ROOT_PASSWORD=your_local_root_password
```

Khởi động:

```powershell
docker compose up -d
docker compose ps
```

Truy cập <http://localhost:8090>, hoàn tất WordPress installer, activate **Mini
Assessment**, tạo page chứa shortcode và kiểm tra giao diện.

Các lệnh hỗ trợ:

```powershell
docker compose logs wordpress
docker compose logs database
docker compose stop
docker compose down
```

`docker compose down` giữ database volume. Chỉ dùng `docker compose down --volumes`
khi chủ động muốn xóa database test.

## 11. Hướng dẫn kiểm thử

### 11.1. PHP syntax và integration API

```powershell
docker compose exec -T wordpress sh -lc "find /var/www/html/wp-content/plugins/plugin-assessment -name '*.php' -exec php -l {} \;"
docker compose exec -T wordpress php /var/www/html/wp-content/plugins/plugin-assessment/tests/integration-smoke.php
```

Integration smoke test kiểm tra:

- Anonymous create trả `401`.
- Subscriber thiếu capability trả `403`.
- Payload không hợp lệ trả `422`.
- Tài nguyên không tồn tại trả `404`.
- Create/PATCH Assessment thành công.
- Create Question và Answer thành công.
- Delete Assessment thành công.
- Cascade delete không để lại orphan Question/Answer.

Kết quả mong đợi:

```text
ALL INTEGRATION SMOKE TESTS PASSED
```

### 11.2. React

```powershell
cd frontend
npm.cmd install
npm.cmd run lint
npm.cmd test
npm.cmd run build
```

Tại commit phát hành, ESLint đạt, Vitest đạt 3/3 test và Vite production build thành
công.

### 11.3. Checklist kiểm tra thủ công

1. Cài ZIP trên WordPress sạch và activate hai lần không mất dữ liệu.
2. Tạo một Draft và một Published Assessment.
3. Dùng cửa sổ ẩn danh xác nhận chỉ Published xuất hiện.
4. Tạo Question/Answer và kiểm tra thứ tự `sort_order`.
5. Xác nhận public response không chứa Answer score.
6. Gửi request không đăng nhập và request bằng subscriber để xác nhận 401/403.
7. Gửi payload thiếu title/content, ID cha không tồn tại và score ngoài giới hạn.
8. Xóa Assessment và xác nhận không còn Question/Answer mồ côi.
9. Kiểm tra layout trên desktop và mobile.

## 12. Kết quả bàn giao

Tại thời điểm lập báo cáo:

- Pull request đã đi qua `dev`, `staging` và `prod` theo workflow.
- GitHub Actions `validate-flow` và `validate-commits` đã đạt.
- PHP syntax check đạt.
- Integration smoke test đạt toàn bộ trường hợp hiện có.
- ESLint đạt.
- Frontend unit tests đạt 3/3.
- Vite production build thành công.
- Plugin ZIP đã được tạo từ mã nguồn trên nhánh `prod`.
- SHA-256 của artifact đã tạo tại thời điểm bàn giao:
  `0659A9F595AE0F8FD768608AAD4B10B508C99ECD09296C9D1092C891DD1BE28D`.

## 13. Giới hạn và hướng mở rộng

Các giới hạn được chủ động giữ ngoài phạm vi hoặc có thể cải tiến:

- Chưa triển khai attempt/submission vì đề không cung cấp schema và submit contract.
- Contract hiện chỉ yêu cầu full CRUD cho Assessment; update/delete Question và Answer
  chưa được bổ sung để tránh mở rộng ngoài phạm vi bắt buộc.
- Bộ automated test tập trung vào critical smoke path; có thể mở rộng thêm test public
  visibility, score hiding, pagination boundary, invalid parent và rollback failure.
- Chưa triển khai optimistic locking cho trường hợp hai người cập nhật đồng thời.
- Pagination đang dùng offset; cursor pagination phù hợp hơn nếu dữ liệu đạt quy mô
  rất lớn.

## 14. Tài liệu tham chiếu

- `README.md`: tổng quan, cài đặt và testing checklist.
- `docs/business/ASSESSMENT_TEST_ANALYSIS.md`: phân tích yêu cầu chuyên sâu.
- `docs/architecture/SYSTEM_ARCHITECTURE.md`: kiến trúc hệ thống.
- `docs/architecture/LOCAL_WORDPRESS.md`: môi trường WordPress local.
- `docs/api-specs/REST_API.md`: REST API contract và payload mẫu.
- `docs/GIT_WORKFLOW.md`: quy trình nhánh và commit.

## 15. Công khai việc sử dụng công cụ hỗ trợ

**Công cụ:** OpenAI Codex.

**Các bước sử dụng:** hỗ trợ phân tích yêu cầu, tạo khung triển khai ban đầu, gợi ý
review bảo mật, đề xuất test case và cấu trúc tài liệu.

**Ví dụ prompt:**

> Phân tích các WordPress REST route bắt buộc; kiểm tra permission, validation, an
> toàn SQL và tính toàn vẹn dữ liệu ở application layer.

**Phần đã tự review và chỉnh sửa:** đối chiếu implementation với database/API contract
đã được lead xác nhận; kiểm tra capability, sanitize, prepared SQL, public visibility,
ẩn Answer score và cascade delete; chỉnh Git policy để tương thích squash commit của
GitHub; trực tiếp chạy PHP lint, integration smoke test, ESLint, Vitest và production
build trên WordPress/PHP 8.3. Kết quả từ AI không được dùng làm kết quả cuối cùng nếu
chưa được đọc lại, chạy thử và xác minh.

---

**Trạng thái:** Sẵn sàng cài đặt, kiểm thử và đánh giá trên WordPress sạch.
