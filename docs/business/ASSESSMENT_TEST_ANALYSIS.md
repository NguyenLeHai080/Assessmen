# Phân tích chuyên sâu bài test Headless WordPress

## 1. Thông tin tài liệu

| Thuộc tính | Nội dung |
| --- | --- |
| Tên bài test | Mini Assessment Plugin – Headless WordPress + React SPA |
| Vị trí tuyển dụng | CTV Phát triển Hệ thống |
| Đơn vị gửi bài test | HR Department – GrowBYCAP |
| Thời lượng trong đề PDF | Khuyến nghị 1–2 ngày |
| Thời lượng HR thông báo | 03 ngày |
| Thời điểm bắt đầu | 09:30 Thứ Năm, ngày 13/08/2026 |
| Hạn gửi bài | 12:00 Chủ nhật, ngày 16/08/2026 |
| Nguồn yêu cầu | `Bai Test Headless Wordpress.pdf` và thông báo của HR |
| Mục đích tài liệu | Chuyển yêu cầu tuyển dụng thành đặc tả kỹ thuật, tiêu chí nghiệm thu và kế hoạch bàn giao |

> **Lưu ý về thời gian:** Đề PDF khuyến nghị hoàn thành trong 1–2 ngày, trong khi HR cấp
> một khoảng thời gian 03 ngày. Kế hoạch nên dành tối đa hai ngày cho phát triển và giữ
> phần thời gian còn lại cho kiểm thử, đóng gói, rà soát tài liệu và gửi bài trước hạn.

---

## 2. Tóm tắt điều hành

### 2.1. Yêu cầu đã được technical lead xác nhận

Sau khi nhận đề, ứng viên đã hỏi lại technical lead và nhận được xác nhận:

1. Dữ liệu sử dụng đúng ba nhóm `Assessment`, `Question`, `Answer` và được lưu vào các
   bảng tương ứng theo mô tả của đề.
2. Khi Assessment được `published`, người dùng không đăng nhập được xem danh sách.
3. Thao tác tạo dữ liệu yêu cầu đăng nhập WordPress. Các thao tác cập nhật/xóa vẫn
   được bảo vệ theo capability vì API contract PDF yêu cầu authenticated access.
4. Database và API thực hiện đúng contract đã mô tả; không tự thêm một hệ thống
   attempt/submission ngoài phạm vi.
5. Toàn bộ REST API và production React SPA phải được đóng gói chung thành **một
   WordPress Plugin ZIP**.

Các mục trên là yêu cầu đã xác nhận, không còn là giả định thiết kế. Vite standalone
chỉ phục vụ quá trình phát triển; artifact bàn giao là plugin ZIP chứa React build.

Bài test yêu cầu xây dựng một module Assessment chạy theo kiến trúc headless:

```text
React SPA
    |
    | HTTP/JSON + Authentication
    v
WordPress REST API: /wp-json/assessment/v1
    |
    | Service/Repository + $wpdb
    v
Custom database tables
```

WordPress chịu trách nhiệm lưu trữ dữ liệu, xác thực, phân quyền, kiểm tra dữ liệu,
logging và cung cấp REST API. React SPA chịu trách nhiệm hiển thị danh sách bài đánh
giá, chi tiết câu hỏi/đáp án, biểu mẫu tạo dữ liệu và các trạng thái giao diện.

Đây không phải bài CRUD thuần túy. Bộ phận tuyển dụng muốn đánh giá đồng thời:

1. Khả năng phân tích yêu cầu chưa hoàn toàn tường minh.
2. Thiết kế database có quan hệ và bảo toàn tính toàn vẹn dữ liệu.
3. Kiến thức về vòng đời WordPress Plugin và migration.
4. Thiết kế REST API và mã trạng thái HTTP.
5. Authentication, authorization và security phía server.
6. Tổ chức React SPA có phân lớp và xử lý lỗi.
7. Khả năng viết tài liệu, quản lý Git và bàn giao sản phẩm có thể chạy lại.

Thứ tự ưu tiên được đề bài nêu trực tiếp là: **correctness, architecture, security,
API contract, database design, khả năng bàn giao và mức độ tự chủ**. Vì vậy, phạm vi
nên nhỏ nhưng hoàn chỉnh; không nên hy sinh tính đúng đắn để làm thêm UI hoặc tính
năng ngoài yêu cầu.

---

## 3. Phạm vi nghiệp vụ

### 3.1. Mô hình dữ liệu cốt lõi

```text
Assessment (1)
└── Question (N)
    └── Answer (N)
```

- Một Assessment có nhiều Question.
- Một Question chỉ thuộc một Assessment.
- Một Question có nhiều Answer.
- Một Answer chỉ thuộc một Question.
- Question và Answer phải hiển thị ổn định theo `sort_order`.

### 3.2. Luồng người dùng chính

1. Người dùng mở React SPA.
2. SPA gọi API lấy danh sách Assessment có phân trang.
3. Người dùng chọn một Assessment.
4. SPA lấy thông tin Assessment và danh sách Question.
5. SPA lấy và hiển thị Answer của từng Question.
6. Người có quyền có thể tạo Assessment hoặc Question bằng biểu mẫu đơn giản.
7. Các thao tác ghi dữ liệu phải được backend xác thực và phân quyền.

### 3.3. Điểm không nhất quán cần nhận diện

Phần bối cảnh nói người dùng có thể “gửi câu trả lời”, nhưng:

- Schema bắt buộc không có bảng lưu lượt làm bài.
- Không có bảng lưu lựa chọn của người dùng.
- Không có endpoint submit bài làm.
- Không có quy tắc tính tổng điểm hoặc hoàn thành bài.

Technical lead yêu cầu lưu Answer vào đúng bảng được mô tả và không yêu cầu thêm
contract mới. Vì vậy, trong phạm vi hiện tại, `Answer` được hiểu là dữ liệu câu trả lời
thuộc một Question; không tự phát minh bảng attempt/submission. Nếu công ty mở rộng
contract trong tương lai, có thể bổ sung:

```text
assessment_attempts
- id
- assessment_id
- user_id
- status
- started_at
- submitted_at
- total_score

assessment_responses
- id
- attempt_id
- question_id
- answer_id
- awarded_score
- created_at
```

Phần mở rộng này chỉ nên ghi trong tài liệu thiết kế, không nên triển khai trước khi
hoàn thành toàn bộ phạm vi bắt buộc.

---

## 4. Ma trận truy vết yêu cầu

| ID | Yêu cầu | Cách đáp ứng đề xuất | Bằng chứng nghiệm thu |
| --- | --- | --- | --- |
| RQ-01 | Plugin đóng gói `.zip`, activate/deactivate không lỗi | Plugin bootstrap chuẩn, activation/deactivation hook | Cài ZIP trên WordPress sạch và kiểm tra log |
| RQ-02 | Tự tạo custom tables | `dbDelta()` trong installer/migrator | Ba bảng tồn tại đúng schema sau activation |
| RQ-03 | Có schema version/migration | Option lưu DB version và migration chạy tuần tự | Nâng version giả lập không mất dữ liệu |
| RQ-04 | Namespace `assessment/v1` | Đăng ký route tại `rest_api_init` | Route xuất hiện trong REST index |
| RQ-05 | CRUD Assessment | GET list/detail, POST, PUT/PATCH, DELETE | Test đủ happy path và error path |
| RQ-06 | API Question/Answer | GET Question, POST Question, GET Answer, POST Answer | Response đúng contract và thứ tự |
| RQ-07 | Validation input | REST args/schema và business validation server-side | Payload sai trả lỗi 400/422 |
| RQ-08 | Permission check | Capability riêng hoặc capability WordPress phù hợp | Anonymous nhận 401, thiếu quyền nhận 403 |
| RQ-09 | Error response | Dùng `WP_Error`, error code ổn định | 401/403/404/422/500 có cấu trúc nhất quán |
| RQ-10 | Logging cơ bản | Logger tập trung, không ghi secret | Có log hành động ghi và lỗi nội bộ |
| RQ-11 | Chống SQL injection | `$wpdb`, CRUD helpers, `$wpdb->prepare()` và allow-list | Review toàn bộ query động |
| RQ-12 | Pagination | `page`, `per_page`, giới hạn tối đa, metadata | List trả đúng total/total_pages |
| RQ-13 | Không hard-code business data | Config/options/env; không seed dữ liệu cố định | Review source và `.env.example` |
| RQ-14 | React hiển thị cây dữ liệu | List page, detail page, Question/Answer components | Chạy SPA và kiểm tra dữ liệu thật từ API |
| RQ-15 | React form đơn giản | Form tạo Assessment hoặc Question | Submit thành công và hiển thị validation error |
| RQ-16 | React xử lý trạng thái | Loading/error/empty và HTTP errors bắt buộc | Test mock/ thủ công từng trạng thái |
| RQ-17 | README đầy đủ | Setup, schema, API, ví dụ, frontend, decisions | Người mới có thể chạy theo README |
| RQ-18 | Git commit rõ ràng | Conventional Commits theo workflow dự án | Lịch sử commit theo từng phần công việc |
| RQ-19 | Công khai việc dùng AI | Ghi tool, bước dùng, prompt mẫu, phần tự review | Mục AI disclosure trong README |

---

## 5. Thiết kế database chuyên sâu

### 5.1. Quy tắc đặt tên

Không được viết cứng tiền tố `wp_`. Tên bảng phải được tạo từ `$wpdb->prefix`:

```php
$assessment_table = $wpdb->prefix . 'assessment';
```

Điều này bảo đảm plugin hoạt động trên các WordPress installation dùng prefix khác
`wp_` và trong môi trường multisite theo thiết kế được lựa chọn.

### 5.2. Bảng Assessment

Tên logic: `{$wpdb->prefix}assessment`.

| Cột | Kiểu đề xuất | Ràng buộc | Ý nghĩa |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT | Định danh |
| `title` | `VARCHAR(255)` | NOT NULL | Tiêu đề bài đánh giá |
| `description` | `TEXT` | NULL | Mô tả |
| `status` | `VARCHAR(20)` | NOT NULL, default `draft` | Trạng thái nghiệp vụ |
| `created_at` | `DATETIME` | NOT NULL | Thời điểm tạo, lưu theo UTC/GMT quy ước |
| `updated_at` | `DATETIME` | NOT NULL | Thời điểm cập nhật |

Index đề xuất:

```text
PRIMARY KEY (id)
INDEX idx_status (status)
INDEX idx_created_at (created_at)
```

Allow-list trạng thái đề xuất:

```text
draft | published | archived
```

Public API chỉ nên trả Assessment `published`. User có capability quản trị mới được
xem và thao tác với `draft`/`archived`.

### 5.3. Bảng Question

Tên logic: `{$wpdb->prefix}assessment_questions`.

| Cột | Kiểu đề xuất | Ràng buộc | Ý nghĩa |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT | Định danh |
| `assessment_id` | `BIGINT UNSIGNED` | NOT NULL | Assessment cha |
| `content` | `TEXT` | NOT NULL | Nội dung câu hỏi |
| `sort_order` | `INT UNSIGNED` | NOT NULL, default `0` | Thứ tự hiển thị |
| `status` | `VARCHAR(20)` | NOT NULL, default `active` | Trạng thái câu hỏi |
| `created_at` | `DATETIME` | NOT NULL | Thời điểm tạo |
| `updated_at` | `DATETIME` | NOT NULL | Thời điểm cập nhật |

Index đề xuất:

```text
PRIMARY KEY (id)
INDEX idx_assessment_id (assessment_id)
INDEX idx_assessment_status (assessment_id, status)
INDEX idx_assessment_sort (assessment_id, sort_order)
```

### 5.4. Bảng Answer

Tên logic: `{$wpdb->prefix}assessment_answers`.

| Cột | Kiểu đề xuất | Ràng buộc | Ý nghĩa |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT | Định danh |
| `question_id` | `BIGINT UNSIGNED` | NOT NULL | Question cha |
| `content` | `TEXT` | NOT NULL | Nội dung phương án |
| `score` | `DECIMAL(10,2)` | NOT NULL, default `0` | Điểm của phương án |
| `sort_order` | `INT UNSIGNED` | NOT NULL, default `0` | Thứ tự hiển thị |
| `created_at` | `DATETIME` | NOT NULL | Thời điểm tạo |
| `updated_at` | `DATETIME` | NOT NULL | Thời điểm cập nhật |

Index đề xuất:

```text
PRIMARY KEY (id)
INDEX idx_question_id (question_id)
INDEX idx_question_sort (question_id, sort_order)
```

`DECIMAL(10,2)` được đề xuất thay cho kiểu số thực binary để điểm số có biểu diễn
thập phân ổn định. Nếu nghiệp vụ chỉ chấp nhận điểm nguyên, có thể dùng `INT`, nhưng
quyết định phải được ghi trong README.

### 5.5. Foreign key và application integrity

Đề không bắt buộc foreign key vật lý. Phương án ưu tiên cho bài test là không khai báo
FK trong schema, vì:

- `dbDelta()` không phải công cụ migration mạnh cho foreign key.
- WordPress hosting có cấu hình database/storage engine khác nhau.
- WordPress thường quản lý quan hệ custom data ở application layer.

Khi không dùng FK, backend bắt buộc bảo đảm:

- Không tạo Question nếu Assessment cha không tồn tại.
- Không tạo Answer nếu Question cha không tồn tại.
- Không tạo orphan records khi xóa dữ liệu cha.
- Các thao tác nhiều bước được bao trong transaction nếu storage engine hỗ trợ.

### 5.6. Chính sách xóa

Đề không quy định soft delete, restrict hay cascade. Phương án đề xuất cho phạm vi này:

- `DELETE Assessment` là hard delete.
- Xóa Answer của các Question thuộc Assessment.
- Xóa các Question.
- Cuối cùng xóa Assessment.
- Dùng transaction; rollback toàn bộ khi một bước thất bại.

Thứ tự này tránh orphan records khi không có physical FK. Nếu chọn restrict thay vì
cascade, API nên trả `409 Conflict` và quyết định đó phải được document rõ.

### 5.7. Thời gian và timezone

Nên lưu thời gian theo UTC bằng API WordPress phù hợp, sau đó chuyển đổi khi hiển thị.
Toàn bộ response phải thống nhất một format, ưu tiên ISO 8601, ví dụ:

```text
2026-08-14T03:15:00Z
```

Không trộn giờ server, giờ WordPress site và giờ trình duyệt mà không ghi timezone.

---

## 6. Kiến trúc WordPress Plugin

### 6.1. Cấu trúc đề xuất

```text
mini-assessment/
├── mini-assessment.php
├── uninstall.php
├── includes/
│   ├── class-plugin.php
│   ├── class-activator.php
│   ├── class-migrator.php
│   ├── class-logger.php
│   ├── repositories/
│   │   ├── class-assessment-repository.php
│   │   ├── class-question-repository.php
│   │   └── class-answer-repository.php
│   ├── services/
│   │   ├── class-assessment-service.php
│   │   ├── class-question-service.php
│   │   └── class-answer-service.php
│   └── rest/
│       ├── class-assessment-controller.php
│       ├── class-question-controller.php
│       └── class-answer-controller.php
├── languages/
├── README.md
└── readme.txt
```

Đây là định hướng phân tách trách nhiệm, không phải yêu cầu phải tạo nhiều class một
cách máy móc. Với thời gian ngắn, có thể gộp các file nhỏ nhưng vẫn phải duy trì ranh
giới rõ giữa REST transport, business rules và truy cập database.

### 6.2. Trách nhiệm từng lớp

| Lớp | Trách nhiệm |
| --- | --- |
| Bootstrap | Khởi tạo plugin, định nghĩa version, đăng ký hooks |
| Activator/Migrator | Cài schema, cập nhật schema version, capabilities |
| REST Controller | Route, request schema, permission, HTTP response |
| Service | Quy tắc nghiệp vụ và orchestration |
| Repository | Query qua `$wpdb`, không chứa HTTP concern |
| Logger | Ghi log tập trung, lọc dữ liệu nhạy cảm |

### 6.3. Activation và migration

Activation phải:

1. Kiểm tra môi trường tối thiểu nếu dự án có quy định phiên bản.
2. Tạo hoặc cập nhật bảng bằng `dbDelta()`.
3. Tạo capability cần thiết.
4. Lưu schema version trong WordPress options.
5. Không tự tạo business data cố định.

Option đề xuất:

```text
mini_assessment_db_version = 1.0.0
```

Không nên chỉ chạy migration khi activate. Khi plugin được cập nhật mà không
deactivate/reactivate, bootstrap phải so sánh version code với version trong option và
chạy migration còn thiếu theo thứ tự.

### 6.4. Deactivation và uninstall

- Deactivation không xóa bảng hoặc business data.
- Deactivation chỉ dọn scheduled hook/transient nếu plugin có tạo.
- Việc xóa dữ liệu khi uninstall phải là quyết định minh bạch.
- Nếu hỗ trợ xóa, dùng `uninstall.php`, kiểm tra `WP_UNINSTALL_PLUGIN` và có option
  `delete_data_on_uninstall`.
- Mặc định giữ dữ liệu là lựa chọn an toàn cho bài test.

---

## 7. REST API contract chi tiết

Base URL:

```text
/wp-json/assessment/v1
```

### 7.1. Danh sách Assessment

```http
GET /wp-json/assessment/v1/assessments
```

Query parameters tối thiểu:

| Tham số | Mặc định | Validation |
| --- | ---: | --- |
| `page` | `1` | Integer, tối thiểu 1 |
| `per_page` | `10` | Integer, 1–100 |
| `status` | Theo quyền user | Allow-list trạng thái |

Có thể bổ sung `search`, `orderby`, `order`, nhưng `orderby` và `order` phải qua
allow-list, không ghép trực tiếp input vào SQL.

Response mẫu:

```json
{
  "data": [
    {
      "id": 12,
      "title": "PHP Assessment",
      "description": "Basic PHP knowledge",
      "status": "published",
      "created_at": "2026-08-14T03:00:00Z",
      "updated_at": "2026-08-14T03:00:00Z"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 24,
    "total_pages": 3
  }
}
```

Có thể đồng thời trả `X-WP-Total` và `X-WP-TotalPages` để gần với convention của
WordPress REST API.

### 7.2. Tạo Assessment

```http
POST /wp-json/assessment/v1/assessments
```

Permission: authenticated + capability tạo/sửa Assessment.

Request mẫu:

```json
{
  "title": "WordPress Fundamentals",
  "description": "Assessment for junior developers",
  "status": "draft"
}
```

Thành công trả `201 Created`. Title bắt buộc và không được rỗng sau khi trim/sanitize.

### 7.3. Chi tiết Assessment

```http
GET /wp-json/assessment/v1/assessments/{id}
```

- `200 OK` khi tồn tại và user được phép xem.
- `404 Not Found` khi không tồn tại.
- Không để anonymous dùng ID trực tiếp để xem Assessment draft nếu thiết kế public
  chỉ cho phép `published`.

### 7.4. Cập nhật Assessment

```http
PUT   /wp-json/assessment/v1/assessments/{id}
PATCH /wp-json/assessment/v1/assessments/{id}
```

- `PATCH` cập nhật một phần.
- `PUT` theo REST chuẩn là thay thế representation đầy đủ.
- Nếu implementation dùng cùng partial-update semantics cho cả hai method, phải ghi
  rõ trong API documentation.
- Không dùng default value để ghi đè field mà client không gửi trong PATCH.

### 7.5. Xóa Assessment

```http
DELETE /wp-json/assessment/v1/assessments/{id}
```

- `200 OK` nếu trả body xác nhận.
- Hoặc `204 No Content` nếu không trả body.
- `404` nếu resource không tồn tại.
- `409` nếu chọn chính sách restrict và vẫn còn Question.

### 7.6. Lấy Questions của Assessment

```http
GET /wp-json/assessment/v1/assessments/{id}/questions
```

Thứ tự query phải ổn định:

```sql
ORDER BY sort_order ASC, id ASC
```

`id` là tie-breaker khi nhiều Question có cùng `sort_order`.

### 7.7. Tạo Question

```http
POST /wp-json/assessment/v1/questions
```

Request mẫu:

```json
{
  "assessment_id": 12,
  "content": "What is a WordPress hook?",
  "sort_order": 1,
  "status": "active"
}
```

Backend phải kiểm tra Assessment cha tồn tại trước khi insert.

### 7.8. Lấy Answers của Question

```http
GET /wp-json/assessment/v1/questions/{id}/answers
```

Sắp xếp theo:

```sql
ORDER BY sort_order ASC, id ASC
```

`score` có thể làm lộ đáp án đúng. Public response không nên trả `score`, trừ khi đề
bài được xác nhận là công cụ quản trị hoặc score không mang ý nghĩa đúng/sai. User có
capability quản trị có thể nhận representation đầy đủ hơn.

### 7.9. Tạo Answer

```http
POST /wp-json/assessment/v1/answers
```

Request mẫu:

```json
{
  "question_id": 33,
  "content": "A callback registration mechanism",
  "score": 1,
  "sort_order": 1
}
```

Backend phải kiểm tra Question cha tồn tại và validate phạm vi `score`.

### 7.10. Endpoint ngoài phạm vi bắt buộc

Đề không bắt buộc update/delete Question và Answer. Các endpoint sau chỉ nên làm sau
khi toàn bộ contract bắt buộc đã hoàn thành và được test:

```text
GET/PATCH/DELETE /questions/{id}
GET/PATCH/DELETE /answers/{id}
```

### 7.11. Chuẩn response lỗi

Nên dùng `WP_Error` và một cấu trúc ổn định:

```json
{
  "code": "assessment_validation_failed",
  "message": "The assessment data is invalid.",
  "data": {
    "status": 422,
    "fields": {
      "title": "Title is required."
    }
  }
}
```

| Trường hợp | HTTP status |
| --- | ---: |
| GET/PATCH/DELETE thành công và có body | 200 |
| Tạo resource thành công | 201 |
| Xóa thành công, không có body | 204 |
| Request sai kiểu/cú pháp | 400 |
| Chưa xác thực | 401 |
| Đã xác thực nhưng thiếu quyền | 403 |
| Không tìm thấy resource | 404 |
| Xung đột integrity/state | 409 |
| Sai quy tắc nghiệp vụ | 422 |
| Lỗi hệ thống không dự kiến | 500 |

---

## 8. Authentication và authorization

### 8.1. Phân biệt hai khái niệm

- **Authentication:** xác định người gọi là ai.
- **Authorization:** xác định người đó được phép thực hiện hành động nào.

Chỉ kiểm tra `is_user_logged_in()` là chưa đáp ứng yêu cầu authorization. Endpoint
ghi/sửa/xóa phải kiểm tra capability trong `permission_callback`.

### 8.2. Capability đề xuất

Phương án có chất lượng tốt là capability riêng:

```text
read_assessments
edit_assessments
publish_assessments
delete_assessments
```

Khi activate, gán capability thích hợp cho administrator. Nếu giới hạn thời gian, có
thể dùng `manage_options`, nhưng README phải nêu rõ chỉ administrator được thao tác.

### 8.3. Cơ chế xác thực cho SPA

Phương án mặc định đề xuất: WordPress cookie authentication kết hợp REST nonce khi
SPA chạy cùng origin hoặc được WordPress bootstrap.

```http
X-WP-Nonce: <nonce>
```

Nếu SPA chạy khác origin:

- Phải document cơ chế đăng nhập.
- Phải cấu hình CORS theo allow-list.
- Phải xử lý cookie `SameSite`, HTTPS và credential đúng cách.
- Không nhúng Application Password hoặc secret vào bundle React.
- Không tự viết JWT implementation trong thời gian ngắn nếu không có yêu cầu rõ.

### 8.4. Kết quả permission mong đợi

- Anonymous gọi endpoint ghi: `401 Unauthorized`.
- User đã đăng nhập nhưng thiếu capability: `403 Forbidden`.
- User có capability: request được chuyển sang validation/business logic.

---

## 9. Security checklist chuyên sâu

### 9.1. Validate và sanitize

Validation phải thực hiện tại backend, dù React đã validate trước đó.

| Field | Validation | Sanitization gợi ý |
| --- | --- | --- |
| `id` | Integer dương | `absint()` và kiểm tra > 0 |
| `title` | Bắt buộc, giới hạn độ dài | `sanitize_text_field()` |
| `description` | Optional, giới hạn hợp lý | `sanitize_textarea_field()` hoặc `wp_kses_post()` |
| `content` | Bắt buộc, không rỗng | Chọn plain text hoặc HTML policy rõ ràng |
| `status` | Thuộc allow-list | `sanitize_key()` sau đó validate allow-list |
| `sort_order` | Integer không âm | Cast/validate integer |
| `score` | Numeric, trong miền nghiệp vụ | Chuẩn hóa decimal và kiểm tra range |
| `page` | Integer >= 1 | `absint()` và fallback |
| `per_page` | Integer 1–100 | Clamp/validate |

Sanitize không thay thế validation. Ví dụ, sanitize một `status` lạ không khiến nó
trở thành trạng thái hợp lệ.

### 9.2. Chống SQL injection

- Dùng `$wpdb->insert()` và `$wpdb->update()` với format rõ ràng khi phù hợp.
- Dùng `$wpdb->prepare()` cho mọi giá trị động trong raw query.
- Ép kiểu ID/pagination nhưng vẫn dùng prepared query khi đưa vào SQL.
- `ORDER BY`, tên cột và hướng sắp xếp phải chọn từ allow-list.
- Không nối `$_GET`, `$_POST`, REST param hoặc JSON input trực tiếp vào SQL.

Ví dụ nguy hiểm:

```php
$sql .= " ORDER BY {$request['orderby']}";
```

Giải pháp là ánh xạ input sang một giá trị nội bộ đã biết trước.

### 9.3. Escape output

React escape text khi render bằng expression:

```jsx
<div>{question.content}</div>
```

Tránh `dangerouslySetInnerHTML`. Nếu nghiệp vụ cho phép HTML:

1. Backend lọc qua policy HTML rõ ràng như `wp_kses_post()`.
2. Frontend sanitize trước khi render HTML.
3. Test payload XSS.

### 9.4. Không lộ thông tin nhạy cảm

Không trả hoặc ghi log:

- Authorization header.
- Password/Application Password.
- Cookie hoặc nonce.
- Raw SQL và database credentials.
- Stack trace/file path trong production response.
- Toàn bộ request body nếu có khả năng chứa dữ liệu nhạy cảm.

### 9.5. CORS

Nếu frontend khác origin, origin hợp lệ phải lấy từ config/option. Không dùng wildcard
`*` khi request gửi cookie/credential.

### 9.6. Data exposure

- Anonymous chỉ xem Assessment `published`.
- Không trả `score` trong public Answer representation nếu score có thể lộ đáp án.
- Không dựa riêng vào việc “ẩn nút” trên React; backend vẫn kiểm tra permission.

---

## 10. Logging và observability

Logger cơ bản nên ghi:

- Action, ví dụ `create_assessment`.
- Resource ID nếu đã có.
- WordPress user ID.
- Kết quả success/failure.
- Error code nội bộ.
- Timestamp và request correlation ID nếu triển khai được.

Ví dụ:

```text
[mini-assessment] action=create_assessment user_id=2 result=success assessment_id=15
```

Chi tiết debug chỉ nên ghi khi `WP_DEBUG` bật. Logging thất bại không được làm hỏng
response chính. Client nhận thông báo an toàn, còn chi tiết kỹ thuật chỉ nằm ở server
log.

---

## 11. Thiết kế React SPA

### 11.1. Cấu trúc đề xuất

```text
frontend/
├── src/
│   ├── api/
│   │   ├── client.js
│   │   ├── assessments.js
│   │   ├── questions.js
│   │   └── answers.js
│   ├── components/
│   │   ├── AssessmentCard.jsx
│   │   ├── QuestionList.jsx
│   │   ├── QuestionItem.jsx
│   │   ├── AnswerList.jsx
│   │   ├── LoadingState.jsx
│   │   ├── EmptyState.jsx
│   │   └── ErrorState.jsx
│   ├── pages/
│   │   ├── AssessmentListPage.jsx
│   │   ├── AssessmentDetailPage.jsx
│   │   └── AssessmentCreatePage.jsx
│   ├── config/
│   ├── App.jsx
│   └── main.jsx
├── .env.example
├── package.json
└── README.md
```

### 11.2. API client

Component không nên tự ghép URL và gọi `fetch()` rải rác. API client chịu trách nhiệm:

- Base URL từ environment/config.
- JSON encode/decode.
- Nonce/auth headers.
- Chuẩn hóa HTTP error.
- Abort/cancel request khi component unmount nếu cần.

API URL không được hard-code. Có thể dùng:

```text
VITE_API_BASE_URL=https://example.test/wp-json/assessment/v1
```

Chỉ commit `.env.example`, không commit `.env` chứa thông tin môi trường.

### 11.3. Assessment List Page

Phải có bốn trạng thái độc lập:

1. Loading.
2. Success có dữ liệu.
3. Success nhưng danh sách rỗng.
4. Error và khả năng thử lại.

Pagination phải dựa trên metadata backend, không tự phỏng đoán số trang.

### 11.4. Assessment Detail Page

Luồng dữ liệu:

1. Lấy Assessment theo ID.
2. Lấy Questions theo Assessment.
3. Hiển thị theo `sort_order`.
4. Lấy Answers của từng Question.
5. Hiển thị trạng thái lỗi/loading ở cấp phù hợp.

Nếu một request Answer lỗi, không nên làm toàn bộ trang trắng; có thể hiển thị lỗi
cục bộ tại Question tương ứng.

### 11.5. Form tạo dữ liệu

Đề chỉ bắt buộc form tạo Assessment hoặc Question ở mức đơn giản. Nên ưu tiên form
Assessment để hoàn chỉnh nhanh:

- Title.
- Description.
- Status.
- Disable submit khi đang gửi.
- Client validation để cải thiện UX.
- Hiển thị field errors từ backend `422`.
- Xử lý `401` và `403` rõ ràng.

Client validation không bao giờ thay thế server validation.

### 11.6. Xử lý lỗi bắt buộc

| HTTP | Hành vi UI đề xuất |
| --- | --- |
| 401 | Thông báo cần đăng nhập hoặc chuyển tới login |
| 403 | Thông báo không có quyền thao tác |
| 404 | Trang/resource không tồn tại hoặc đã bị xóa |
| 422 | Ánh xạ lỗi về từng field của form |
| 500 | Thông báo lỗi hệ thống và nút thử lại |

### 11.7. Rủi ro N+1 request

Với 20 Questions, thiết kế API bắt buộc có thể tạo 22 request: một Assessment, một
Question list và 20 Answer lists. Phương án xử lý theo thứ tự ưu tiên:

1. Đảm bảo endpoint bắt buộc hoạt động đúng.
2. Gọi Answer song song có giới hạn hợp lý.
3. Nếu còn thời gian, bổ sung tùy chọn tương thích ngược:

```http
GET /assessments/{id}/questions?include=answers
```

Không thay endpoint bắt buộc bằng một endpoint riêng không có trong contract.

---

## 12. Cấu hình, dữ liệu và secret

“Không hard-code business data” được hiểu là:

- Không nhúng Assessment/Question/Answer mẫu trực tiếp trong source để UI hoạt động.
- API base URL lấy từ environment/config.
- Allowed frontend origins lấy từ option/config.
- Page size mặc định/tối đa có constant hoặc filter/option rõ ràng.
- Status definitions được tập trung, không lặp string rải rác.
- Không đưa password, token, nonce hoặc credential vào Git.

Nếu cần dữ liệu demo, cung cấp script/fixture hoặc hướng dẫn tạo qua API; không để
frontend phụ thuộc vào dữ liệu đó.

---

## 13. Chiến lược kiểm thử

### 13.1. Backend critical tests

- [ ] Activate plugin trên WordPress sạch tạo đúng ba bảng.
- [ ] Activate/chạy installer lần hai không lỗi và không mất dữ liệu.
- [ ] Migration từ version cũ chạy đúng.
- [ ] Deactivate không xóa business data.
- [ ] GET list trả pagination chính xác.
- [ ] `page < 1`, `per_page < 1` và `per_page > max` được xử lý.
- [ ] Anonymous chỉ thấy dữ liệu public theo thiết kế.
- [ ] Anonymous gọi endpoint ghi nhận 401.
- [ ] User thiếu capability nhận 403.
- [ ] Assessment không tồn tại trả 404.
- [ ] Title/content rỗng nhận validation error.
- [ ] Status ngoài allow-list bị từ chối.
- [ ] Không tạo Question với Assessment không tồn tại.
- [ ] Không tạo Answer với Question không tồn tại.
- [ ] Question/Answer sắp xếp ổn định theo `sort_order`, rồi `id`.
- [ ] Delete không để lại orphan records.
- [ ] Payload SQL injection không thay đổi query.
- [ ] Payload XSS không được render thành script thực thi.
- [ ] Public response không làm lộ score theo security decision.
- [ ] Lỗi database không làm lộ SQL/stack trace cho client.

### 13.2. Frontend critical tests

- [ ] Assessment List có loading state.
- [ ] Danh sách rỗng có empty state.
- [ ] Network/server error có error state và retry.
- [ ] Pagination điều hướng đúng.
- [ ] Detail hiển thị Question theo thứ tự.
- [ ] Mỗi Question hiển thị Answer.
- [ ] Question không có Answer không làm UI crash.
- [ ] Form submit thành công và cập nhật UI hợp lý.
- [ ] 422 hiển thị field errors.
- [ ] 401/403/404/500 có thông báo riêng.
- [ ] API base URL không hard-code.
- [ ] Không dùng `dangerouslySetInnerHTML` không kiểm soát.
- [ ] Production build hoàn thành không lỗi.

### 13.3. Kiểm thử đóng gói

- [ ] ZIP chứa đúng một thư mục plugin ở cấp root.
- [ ] Không chứa `.git`, `node_modules`, test artifacts, log hoặc secret.
- [ ] Cài ZIP qua WordPress Admin thành công.
- [ ] Activate/deactivate không phát sinh warning/fatal error.
- [ ] Frontend chạy được theo đúng README trên môi trường mới.

---

## 14. Definition of Done

Bài chỉ được coi là hoàn thành khi đồng thời thỏa mãn:

### Plugin

- [ ] Có source plugin hoặc `plugin-assessment.zip` cài được.
- [ ] Activation/deactivation sạch, không lỗi PHP.
- [ ] Custom tables và indexes đúng yêu cầu.
- [ ] Có schema version và ít nhất cơ chế migration đơn giản.
- [ ] Không viết cứng database prefix.

### API

- [ ] Đủ tất cả endpoint bắt buộc trong namespace `assessment/v1`.
- [ ] List Assessment có pagination và metadata.
- [ ] Input được validate/sanitize tại backend.
- [ ] Endpoint ghi có authentication và capability check.
- [ ] Query động an toàn trước SQL injection.
- [ ] Error code/message/status nhất quán.
- [ ] Có logging cơ bản và không log secret.

### React

- [ ] Có Assessment List và Assessment Detail.
- [ ] Hiển thị Questions và Answers theo thứ tự.
- [ ] Có ít nhất một form tạo Assessment hoặc Question.
- [ ] API service tách khỏi component.
- [ ] Có loading/error/empty state.
- [ ] Xử lý ít nhất 401/403/404/422/500.
- [ ] Không hard-code dữ liệu nghiệp vụ hoặc API URL.

### Bàn giao

- [ ] README mô tả cài đặt backend/frontend.
- [ ] README mô tả schema, indexes và migration.
- [ ] Có API docs và request/response examples.
- [ ] Có assumptions/design decisions.
- [ ] Có test instructions và known limitations.
- [ ] Có AI usage disclosure nếu dùng AI.
- [ ] Git history rõ ràng và không có secret.
- [ ] Kiểm tra lại toàn bộ file trước khi gửi Group Zalo cho HR.

---

## 15. Kế hoạch thực hiện theo thời hạn HR

### Ngày 1 — Nền tảng và backend cốt lõi

Mục tiêu: có vertical slice backend chạy được.

1. Chốt assumptions và security decisions.
2. Scaffold plugin và cấu trúc class.
3. Tạo schema, indexes, activation và migration version.
4. Thêm capability và permission policy.
5. Hoàn thành Assessment CRUD.
6. Test activate, GET list/detail và POST.

### Ngày 2 — API còn lại và React SPA

Mục tiêu: hoàn thành toàn bộ chức năng bắt buộc.

1. Hoàn thành Question/Answer endpoints.
2. Hoàn thành validation, error mapping và logging.
3. Tạo React API client.
4. Xây Assessment List và Detail.
5. Xây form tạo Assessment hoặc Question.
6. Thêm loading/error/empty state.
7. Kiểm tra các mã lỗi bắt buộc.

### Ngày 3 — Quality gate và bàn giao

Mục tiêu: không thêm tính năng lớn; chỉ hoàn thiện chất lượng.

1. Chạy backend/frontend test checklist.
2. Rà soát permission, SQL query và data exposure.
3. Cài thử plugin từ ZIP trên môi trường sạch.
4. Chạy production build frontend.
5. Hoàn thiện README, schema notes và API examples.
6. Ghi AI disclosure.
7. Kiểm tra Git history và secret.
8. Chuẩn bị gói bàn giao và gửi trước 12:00 ngày 16/08/2026.

Nên đặt hạn nội bộ sớm hơn hạn HR tối thiểu 60–90 phút để có thời gian xử lý lỗi đóng
gói hoặc upload.

---

## 16. Áp dụng cấu trúc Git hiện có

Repository đã được cấu trúc Git và có quy trình riêng tại
[GIT_WORKFLOW.md](../GIT_WORKFLOW.md). Quá trình thực hiện bài test **không thiết lập lại
Gitflow, không đổi tên các nhánh môi trường và không tạo một quy trình Git mới**.

Mọi thay đổi phải tuân thủ cấu trúc hiện có:

```text
dev -> feat/assessment-plugin -> dev -> staging -> prod
```

Các commit triển khai nên nhỏ, có mục đích rõ ràng và tuân thủ Conventional Commits
đã được dự án quy định:

```text
chore(plugin): scaffold assessment plugin #<issue-id>
feat(database): add assessment schema and migrations #<issue-id>
feat(api): implement assessment REST CRUD #<issue-id>
feat(api): add question and answer endpoints #<issue-id>
feat(security): add capabilities and validation #<issue-id>
feat(frontend): build assessment list and detail #<issue-id>
feat(frontend): add assessment creation form #<issue-id>
test(api): cover permissions and validation #<issue-id>
docs(project): add setup and API documentation #<issue-id>
```

Không commit `.env`, credential, `node_modules`, log, IDE cache hoặc ZIP tạm chưa kiểm
tra. Quy định issue ID, pull request, hướng merge và đồng bộ `dev`, `staging`, `prod`
được lấy từ tài liệu Git hiện có; tài liệu phân tích này không thay thế
`GIT_WORKFLOW.md`.

---

## 17. Nội dung README bắt buộc khi bàn giao

README cuối cùng nên có:

1. Tổng quan và kiến trúc.
2. Yêu cầu phiên bản PHP, WordPress, database, Node/npm.
3. Cách cài plugin từ source và ZIP.
4. Cách activate/deactivate/uninstall.
5. Cách cấu hình authentication và CORS.
6. Database schema, index và integrity policy.
7. Schema version và migration notes.
8. Danh sách endpoint và quyền truy cập.
9. Request/response/error examples.
10. Cách cấu hình và chạy React SPA.
11. Cách build production.
12. Cách chạy test và kiểm thử thủ công.
13. Assumptions và known limitations.
14. Delete/data retention policy.
15. AI usage disclosure.

Ví dụ AI disclosure phù hợp:

```text
AI tool: OpenAI Codex
Usage: phân tích yêu cầu, review security checklist và hỗ trợ tạo test cases.
Example prompt: "Review WordPress REST routes for permission and SQL injection risks."
Human review: tự kiểm tra lại migration, permission callbacks, SQL queries, API
responses, frontend behavior và toàn bộ mã nguồn trước khi bàn giao.
```

Phần disclosure phải phản ánh đúng quá trình thực tế, không sao chép ví dụ nếu không
đúng với cách sử dụng AI trong dự án.

---

## 18. Rủi ro và biện pháp giảm thiểu

| Rủi ro | Mức độ | Biện pháp |
| --- | --- | --- |
| Viết cứng prefix `wp_` | Cao | Luôn lấy `$wpdb->prefix` |
| Route ghi public | Nghiêm trọng | Permission callback và capability test |
| Chỉ validate trên React | Cao | Server-side validation cho mọi input |
| SQL injection qua filter/order | Nghiêm trọng | Prepare values và allow-list identifier |
| Orphan Question/Answer | Cao | Application integrity + transaction/delete policy |
| Lộ score/đáp án | Cao | Public representation không chứa score |
| SPA không auth được | Cao | Chốt same-origin/cross-origin sớm, document nonce |
| N+1 requests | Trung bình | Hoàn thành contract trước, sau đó `include=answers` |
| Migration chỉ chạy khi activation | Cao | Version check trong plugin bootstrap |
| Plugin ZIP không cài được | Cao | Test ZIP trên WordPress sạch |
| Hard-code API URL | Trung bình | Environment/config và `.env.example` |
| Commit secret | Nghiêm trọng | `.gitignore`, secret scan và review Git diff |
| Quá phạm vi trong 03 ngày | Cao | Freeze scope sau khi đủ acceptance criteria |
| README thiếu bước auth | Cao | Test hướng dẫn từ góc nhìn reviewer mới |

---

## 19. Điểm cộng sau khi hoàn thành phần bắt buộc

Chỉ triển khai nếu Definition of Done đã đạt:

- Capability riêng thay vì chỉ dùng `manage_options`.
- Transaction cho cascade delete.
- Endpoint `include=answers` tránh N+1.
- Automated tests cho REST permissions và validation.
- Postman collection hoặc OpenAPI document.
- Docker/`wp-env` giúp reviewer khởi chạy nhanh.
- CI chạy lint, test và frontend build.
- Accessibility cơ bản cho form và error state.
- Option kiểm soát xóa dữ liệu khi uninstall.

Không nên ưu tiên trước core requirements:

- UI phức tạp.
- Redux hoặc state framework lớn cho ứng dụng nhỏ.
- JWT tự viết.
- Admin dashboard đầy đủ.
- Hệ thống attempt/submission hoàn chỉnh.
- CRUD mở rộng cho Question/Answer khi endpoint bắt buộc chưa ổn định.

---

## 20. Các quyết định cần chốt trước khi code

- [x] Public chỉ nhìn thấy Assessment `published` — technical lead đã xác nhận.
- [ ] Public Answer response có loại bỏ `score` không?
- [ ] Trạng thái hợp lệ của Assessment và Question là gì?
- [ ] `score` cho phép số âm, số lẻ và tối đa bao nhiêu?
- [ ] DELETE dùng cascade, restrict hay soft delete?
- [ ] PUT có full replacement hay dùng partial-update semantics?
- [x] Production SPA được đóng gói cùng plugin và chạy same-origin; Vite chỉ dùng khi phát triển.
- [x] Production dùng WordPress cookie + REST nonce để không lưu secret trong bundle.
- [ ] Có xóa dữ liệu khi uninstall không?
- [ ] HTML có được phép trong description/content không?
- [ ] Mức pagination mặc định và tối đa là bao nhiêu?
- [ ] Có triển khai `include=answers` sau contract bắt buộc không?

Các lựa chọn này phải xuất hiện trong README dưới mục “Assumptions and design
decisions” để reviewer hiểu hành vi hệ thống là có chủ đích.

---

## 21. Kết luận đánh giá

Bài test có độ khó trung cấp và tập trung mạnh vào chất lượng backend. Sản phẩm tốt
không cần nhiều tính năng ngoài đề, nhưng phải chứng minh được:

- Plugin có vòng đời và migration đúng.
- Database có index và integrity rõ ràng.
- API đúng contract, có pagination và error semantics nhất quán.
- Authentication/authorization thực sự được kiểm tra phía server.
- Không có đường SQL injection, XSS hiển nhiên hoặc secret trong source.
- React tách API service, hiển thị đủ trạng thái và không dùng dữ liệu hard-code.
- Gói bàn giao cài được, chạy được và có tài liệu để reviewer tái hiện.

Chiến lược tối ưu là hoàn thành một vertical slice chạy được càng sớm càng tốt, sau
đó tăng độ chắc chắn bằng test, security review, tài liệu và kiểm tra lại gói ZIP. Đây
là cách bám sát nhất các tiêu chí của đề và yêu cầu HR về việc hoàn thành đầy đủ, đúng
thời hạn, tự kiểm tra nội dung trước khi gửi.

---

## 22. Phân loại phạm vi theo mức độ ưu tiên

Phân loại này giúp tránh dùng thời gian cho tính năng phụ trước khi đạt yêu cầu tuyển
dụng bắt buộc.

### 22.1. Must have — bắt buộc phải hoàn thành

- Plugin có thể cài từ ZIP, activate và deactivate không lỗi.
- Ba custom tables, đúng columns bắt buộc và có index cho filter/join.
- Schema version và cơ chế nâng cấp schema đơn giản.
- Namespace REST API chính xác là `assessment/v1`.
- Đủ tám route/method được liệt kê trong đề.
- Assessment list có pagination.
- Backend validation, sanitization và permission callback.
- SQL an toàn với `$wpdb` và prepared query.
- Error response và logging cơ bản.
- React List, Detail, Questions, Answers và một create form.
- React có loading, error, empty state và xử lý năm nhóm HTTP error.
- README, schema/migration notes, API examples và cách chạy frontend.
- Source/ZIP, Git history và AI disclosure.

Thiếu một mục Must have là thiếu trực tiếp yêu cầu đề bài, dù phần còn lại hoạt động
tốt.

### 22.2. Should have — nên hoàn thành để bài có chất lượng tốt

- Capability riêng thay cho dùng chung `manage_options`.
- Public visibility policy cho `published`/`draft`.
- Ẩn `score` khỏi public response.
- Transaction khi xóa dữ liệu nhiều cấp.
- Automated tests cho permission, validation và integrity.
- `.env.example` và cấu hình CORS rõ ràng.
- Error contract dùng code ổn định, không phụ thuộc message.
- Test ZIP trên một WordPress installation sạch.

### 22.3. Could have — điểm cộng nếu còn thời gian

- `include=answers` để giảm N+1 request.
- OpenAPI/Postman collection.
- CI lint/test/build.
- Docker hoặc `wp-env`.
- Update/delete cho Question và Answer.
- Accessibility và responsive UI tốt hơn mức tối thiểu.

### 22.4. Won't have trong phiên bản bài test

- Hệ thống đăng ký/đăng nhập mới thay thế WordPress.
- Attempt, response, submit và chấm tổng điểm hoàn chỉnh.
- Analytics/reporting.
- Import/export hàng loạt.
- Drag-and-drop question builder.
- Multi-language content workflow.
- Một WordPress admin dashboard đầy đủ.

Danh sách Won't have không có nghĩa là các tính năng không có giá trị; chúng được loại
khỏi phiên bản hiện tại để bảo vệ chất lượng phần bắt buộc trong thời gian 03 ngày.

---

## 23. Đặc tả field và quy tắc validation

Các giới hạn dưới đây là thiết kế đề xuất. Nếu implementation chọn giá trị khác, phải
đồng bộ giữa database, REST schema, frontend và README.

### 23.1. Assessment fields

| Field | Request | Response | Quy tắc đề xuất | Lỗi |
| --- | --- | --- | --- | --- |
| `id` | Không cho phép khi tạo | Luôn có | Integer dương, server sinh | 400 nếu ID path sai |
| `title` | Bắt buộc khi POST | Luôn có | Trim; 1–255 ký tự; plain text | 422 khi rỗng/quá dài |
| `description` | Optional | Có thể `null`/rỗng | Tối đa hợp lý, ví dụ 10.000 ký tự; policy HTML rõ ràng | 422 khi quá giới hạn |
| `status` | Optional khi POST | Luôn có | `draft`, `published`, `archived`; default `draft` | 422 khi ngoài allow-list |
| `created_at` | Read-only | Luôn có | Server sinh, ISO 8601 UTC | Bỏ qua hoặc 400 nếu client gửi theo policy |
| `updated_at` | Read-only | Luôn có | Server cập nhật, ISO 8601 UTC | Bỏ qua hoặc 400 nếu client gửi theo policy |

Không dùng `empty()` để kiểm tra mọi field vì giá trị hợp lệ như chuỗi `"0"` có thể bị
coi là rỗng. Cần kiểm tra theo kiểu dữ liệu và quy tắc cụ thể.

### 23.2. Question fields

| Field | Request | Response | Quy tắc đề xuất | Lỗi |
| --- | --- | --- | --- | --- |
| `id` | Read-only | Luôn có | Integer dương | 400 nếu path ID sai |
| `assessment_id` | Bắt buộc khi tạo | Luôn có | Integer dương; parent tồn tại | 404 nếu parent không tồn tại |
| `content` | Bắt buộc | Luôn có | Không rỗng; giới hạn, ví dụ 20.000 ký tự | 422 |
| `sort_order` | Optional | Luôn có | Integer từ 0 đến giới hạn INT | 422 |
| `status` | Optional | Luôn có | `active`, `inactive`; default `active` | 422 |
| `created_at` | Read-only | Luôn có | Server sinh | 400/ignore theo policy |
| `updated_at` | Read-only | Luôn có | Server sinh/cập nhật | 400/ignore theo policy |

### 23.3. Answer fields

| Field | Request | Response | Quy tắc đề xuất | Lỗi |
| --- | --- | --- | --- | --- |
| `id` | Read-only | Luôn có | Integer dương | 400 nếu path ID sai |
| `question_id` | Bắt buộc khi tạo | Luôn có | Integer dương; parent tồn tại | 404 nếu parent không tồn tại |
| `content` | Bắt buộc | Luôn có | Không rỗng; giới hạn hợp lý | 422 |
| `score` | Optional hoặc bắt buộc theo quyết định | Chỉ trả khi được phép | Decimal hợp lệ; range phải chốt | 422 |
| `sort_order` | Optional | Luôn có | Integer không âm | 422 |
| `created_at` | Read-only | Luôn có | Server sinh | 400/ignore theo policy |
| `updated_at` | Read-only | Luôn có | Server sinh/cập nhật | 400/ignore theo policy |

### 23.4. Quy tắc PATCH

- Phải có ít nhất một field có thể cập nhật.
- Phân biệt field không được gửi với field được gửi giá trị `null`.
- Không cho cập nhật `id`, `created_at` hoặc `updated_at` trực tiếp.
- Chỉ truyền field thực sự có mặt xuống `$wpdb->update()`.
- Nếu không có dữ liệu thay đổi, có thể trả resource hiện tại với `200`, không coi là
  database failure chỉ vì số affected rows bằng 0.

Điểm cuối rất quan trọng: `$wpdb->update()` trả `0` khi giá trị mới giống giá trị cũ;
`0` không đồng nghĩa với `false`.

---

## 24. Ma trận quyền và khả năng quan sát dữ liệu

Vai trò trong bảng là mô hình logic. Implementation có thể ánh xạ chúng vào WordPress
roles/capabilities cụ thể.

| Endpoint | Anonymous | Authenticated không có quyền | Editor/Manager có capability | Administrator |
| --- | --- | --- | --- | --- |
| `GET /assessments` | Chỉ `published` | Chỉ `published` | Xem theo capability/filter | Đầy đủ |
| `POST /assessments` | 401 | 403 | Cho phép | Cho phép |
| `GET /assessments/{id}` | Chỉ `published` | Chỉ `published` | Xem draft nếu có quyền | Đầy đủ |
| `PUT/PATCH /assessments/{id}` | 401 | 403 | Cho phép theo capability | Cho phép |
| `DELETE /assessments/{id}` | 401 | 403 | Cho phép nếu có delete capability | Cho phép |
| `GET /assessments/{id}/questions` | Chỉ dữ liệu public | Chỉ dữ liệu public | Xem theo quyền Assessment cha | Đầy đủ |
| `POST /questions` | 401 | 403 | Cho phép | Cho phép |
| `GET /questions/{id}/answers` | Không có `score` | Không có `score` | Có `score` nếu có quyền | Đầy đủ |
| `POST /answers` | 401 | 403 | Cho phép | Cho phép |

### 24.1. Chống IDOR

Kiểm tra capability chung chưa đủ trong mọi mô hình. Backend còn phải kiểm tra quyền
trên resource cha:

- Question chỉ được tạo trong Assessment mà user có quyền sửa.
- Answer chỉ được tạo trong Question có Assessment cha mà user có quyền sửa.
- Không cho lấy Questions của một draft Assessment bằng cách đoán ID.
- Không cho lấy Answers/score bằng cách gọi trực tiếp Question ID nếu Assessment cha
  không public.

Đây là phòng chống Insecure Direct Object Reference (IDOR) ở cấp quan hệ dữ liệu.

### 24.2. Tránh phân biệt tài nguyên bí mật qua response

Với draft resource, có thể trả `404` cho anonymous thay vì `403` để không xác nhận
rằng resource bí mật tồn tại. Policy phải nhất quán giữa Assessment, Question và
Answer.

---

## 25. Acceptance scenarios theo Given–When–Then

Các scenario này có thể chuyển trực tiếp thành integration tests hoặc checklist demo.

### 25.1. Plugin lifecycle

#### AC-PLG-01 — Cài đặt lần đầu

- **Given** một WordPress site sạch và database user có quyền tạo bảng.
- **When** reviewer cài ZIP và activate plugin.
- **Then** plugin activate không lỗi, tạo đủ ba bảng/index và lưu schema version.

#### AC-PLG-02 — Activation lặp lại

- **Given** plugin đã được cài và có dữ liệu.
- **When** plugin được deactivate rồi activate lại.
- **Then** dữ liệu còn nguyên, schema không bị nhân đôi và không phát sinh warning.

#### AC-PLG-03 — Migration

- **Given** option database version thấp hơn version trong code.
- **When** plugin bootstrap hoặc installer chạy.
- **Then** chỉ migration còn thiếu được chạy theo thứ tự và version chỉ cập nhật sau
  khi migration thành công.

### 25.2. Assessment API

#### AC-AST-01 — Public list

- **Given** database có draft và published Assessments.
- **When** anonymous gọi `GET /assessments?page=1&per_page=10`.
- **Then** response 200, chỉ có published records, có metadata và không quá 10 items.

#### AC-AST-02 — Pagination boundary

- **Given** có nhiều hơn 100 Assessments.
- **When** client yêu cầu `per_page=10000`.
- **Then** request bị từ chối hoặc được giới hạn theo policy; server không tải 10.000
  records.

#### AC-AST-03 — Tạo không xác thực

- **Given** request không có WordPress authentication hợp lệ.
- **When** gọi `POST /assessments` với payload đúng.
- **Then** nhận 401 và database không thay đổi.

#### AC-AST-04 — User thiếu capability

- **Given** user đăng nhập nhưng không có quyền tạo Assessment.
- **When** gọi `POST /assessments`.
- **Then** nhận 403 và database không thay đổi.

#### AC-AST-05 — Validation

- **Given** user có quyền.
- **When** gửi title chỉ gồm khoảng trắng hoặc status không hợp lệ.
- **Then** nhận 422 với field error ổn định và không insert partial data.

#### AC-AST-06 — Idempotent-like update

- **Given** Assessment có title `PHP Basics`.
- **When** PATCH title bằng đúng `PHP Basics`.
- **Then** request không bị báo 500 chỉ vì affected rows bằng 0.

#### AC-AST-07 — Xóa cây dữ liệu

- **Given** Assessment có Questions và Answers.
- **When** user có quyền xóa Assessment.
- **Then** operation thành công theo policy và không còn orphan record.

#### AC-AST-08 — Rollback khi xóa lỗi

- **Given** một bước trong chuỗi cascade delete thất bại.
- **When** transaction được thực thi.
- **Then** toàn bộ thay đổi rollback và API trả lỗi an toàn.

### 25.3. Question và Answer API

#### AC-QST-01 — Parent không tồn tại

- **Given** không có Assessment ID 999999.
- **When** tạo Question với `assessment_id=999999`.
- **Then** nhận 404, không có Question orphan.

#### AC-QST-02 — Thứ tự ổn định

- **Given** hai Questions có cùng `sort_order`.
- **When** lấy Question list nhiều lần.
- **Then** thứ tự vẫn ổn định nhờ tie-breaker `id`.

#### AC-ANS-01 — Không lộ score

- **Given** một Answer có score khác 0.
- **When** anonymous lấy Answer list.
- **Then** response không chứa score theo public policy.

#### AC-ANS-02 — Parent-chain authorization

- **Given** Question thuộc draft Assessment mà user không được xem.
- **When** user gọi trực tiếp `/questions/{id}/answers`.
- **Then** backend không trả dữ liệu của Question đó.

### 25.4. React SPA

#### AC-UI-01 — Loading và success

- **Given** API phản hồi có độ trễ.
- **When** mở Assessment List.
- **Then** loading state xuất hiện trước khi list được render.

#### AC-UI-02 — Empty state

- **Given** API trả `data: []` hợp lệ.
- **When** list load xong.
- **Then** UI hiển thị empty state, không coi đây là lỗi.

#### AC-UI-03 — Field validation

- **Given** backend trả 422 với `data.fields.title`.
- **When** user submit form.
- **Then** lỗi hiển thị gần trường title và dữ liệu đã nhập không bị mất.

#### AC-UI-04 — Permission failure

- **Given** session hết hạn hoặc user thiếu quyền.
- **When** submit create form.
- **Then** 401 và 403 có thông báo khác nhau, không hiện thông báo 500 chung chung.

#### AC-UI-05 — Partial Answer failure

- **Given** một trong nhiều Answer requests thất bại.
- **When** Assessment Detail load.
- **Then** các Questions/Answers khác vẫn hiển thị và vị trí lỗi có retry/error state.

---

## 26. Phân tích migration, concurrency và failure modes

### 26.1. Migration state machine

```text
Read installed DB version
        |
        v
Is installed version < code version? -- No --> Continue plugin bootstrap
        |
       Yes
        |
        v
Run missing migrations in ascending order
        |
        +-- Failure --> Log safely, keep old version, surface admin notice
        |
        v
Persist new version only after success
        |
        v
Continue plugin bootstrap
```

Không cập nhật option version trước khi schema operation thành công. Nếu làm ngược,
plugin có thể tin rằng migration đã hoàn tất trong khi database vẫn ở trạng thái cũ.

### 26.2. Rủi ro hai request cùng chạy migration

Trong một site có traffic, hai request có thể cùng phát hiện version cũ. Biện pháp ở
mức bài test:

- Migration phải có tính lặp lại an toàn (idempotent) khi có thể.
- Dùng `dbDelta()` cho create/alter được hỗ trợ.
- Re-check version trước khi chạy.
- Có thể dùng transient/option lock ngắn hạn nếu implementation đủ thời gian.
- Không coi lock là tuyệt đối nếu không có atomic primitive; document giới hạn.

### 26.3. Concurrent sort order

Hai user có thể cùng tạo Question với một `sort_order`. Schema hiện không yêu cầu
unique constraint, nên đây không phải lỗi integrity. Query dùng `id` làm tie-breaker
để kết quả vẫn deterministic. Reordering tự động là tính năng ngoài phạm vi.

### 26.4. Lost update

Hai user có thể đọc cùng một Assessment rồi cập nhật nối tiếp, khiến thay đổi sau ghi
đè thay đổi trước. Đề không yêu cầu optimistic locking. Có thể ghi đây là known
limitation; giải pháp tương lai là dùng version field hoặc `updated_at` làm precondition
với `If-Unmodified-Since`/ETag.

### 26.5. Database error mapping

- Repository trả kết quả hoặc lỗi nội bộ có kiểu rõ ràng.
- Service quyết định rollback/business behavior.
- Controller chuyển lỗi sang `WP_Error` và HTTP status.
- Client không nhận `$wpdb->last_error` trực tiếp.
- Log có context nhưng không chứa credential hoặc raw sensitive payload.

### 26.6. Delete transaction caveat

Transaction chỉ thực sự bảo vệ dữ liệu nếu các bảng dùng storage engine có transaction
support, thường là InnoDB. Installer nên tạo bảng tương thích với charset/collation của
WordPress qua `$wpdb->get_charset_collate()`. README cần ghi assumption về engine nếu
dựa vào transaction.

---

## 27. Phân tích hiệu năng và khả năng mở rộng

### 27.1. Query list Assessment

Một page list thường cần hai query:

1. Query lấy records với `LIMIT/OFFSET`.
2. Query count tổng records để tính pagination.

Không dùng `SELECT *` nếu response chỉ cần một tập columns xác định. Search bằng
`LIKE '%term%'` có thể không dùng index tốt; chấp nhận được ở bài test nhỏ nhưng nên
ghi nhận nếu mở rộng dữ liệu lớn.

### 27.2. Offset pagination

Offset pagination đáp ứng đề và dễ sử dụng. Khi page rất sâu, database phải bỏ qua
nhiều rows. Cursor pagination là hướng mở rộng, nhưng không cần triển khai trong bài
test vì contract yêu cầu pagination chứ không yêu cầu dữ liệu quy mô lớn.

### 27.3. Question/Answer query

Các composite indexes `(assessment_id, sort_order)` và `(question_id, sort_order)`
phù hợp với access pattern chính. Index riêng chỉ trên `sort_order` không hữu ích bằng
vì các query luôn lọc theo parent trước.

### 27.4. API response size

- Giới hạn `per_page`.
- Không trả field nội bộ không cần thiết.
- Không embed toàn bộ cây dữ liệu mặc định nếu có thể rất lớn.
- Nếu hỗ trợ `include=answers`, document trade-off response size.
- Có thể dùng REST `_fields` hoặc representation riêng trong tương lai.

### 27.5. Frontend request management

- Hủy request cũ khi user chuyển trang nhanh.
- Tránh update state sau khi component unmount.
- Không retry vô hạn với 401/403/422.
- Chỉ retry có giới hạn với lỗi network/5xx phù hợp.
- Cache ngắn hạn có thể là điểm cộng, nhưng phải invalidate sau create/update/delete.

---

## 28. Checklist demo và kịch bản trình bày bài

Nếu HR hoặc technical reviewer yêu cầu demo, nên trình bày theo thứ tự sau trong
10–15 phút:

1. Giới thiệu kiến trúc WordPress REST API + React SPA.
2. Cài/activate plugin và chỉ ra schema version.
3. Trình bày ba bảng và indexes chính.
4. Gọi public Assessment list và minh họa pagination.
5. Thử POST không auth để chứng minh 401.
6. Thử user thiếu quyền để chứng minh 403.
7. Tạo Assessment hợp lệ và payload sai để chứng minh 201/422.
8. Tạo Question/Answer và mở trang Detail.
9. Minh họa loading/empty/error state trên React.
10. Chỉ ra prepared query, permission callback và public score policy trong code.
11. Trình bày migration/delete integrity decision.
12. Kết thúc bằng test results, known limitations và AI disclosure.

Trước khi gửi hoặc demo:

- [ ] Dùng một máy/môi trường sạch để chạy theo README.
- [ ] Không dùng credential cá nhân trong ví dụ.
- [ ] Xóa dữ liệu/log nhạy cảm khỏi ảnh và terminal history hiển thị.
- [ ] Chuẩn bị một bộ demo data nhỏ, tạo bằng API hoặc fixture được document.
- [ ] Kiểm tra tất cả URL trong README.
- [ ] Ghi chính xác commit/tag dùng để bàn giao.
- [ ] Tạo checksum cho ZIP nếu muốn giúp reviewer xác minh artifact.
- [ ] Gửi sớm hơn deadline và lưu lại bằng chứng thời gian gửi.

---

## 29. Kết luận mở rộng

Ở mức phân tích sâu, giá trị lớn nhất của bài test không nằm ở số endpoint mà ở cách
các lớp bảo vệ nhau:

```text
React validation
    -> REST schema validation
        -> permission/resource authorization
            -> service business rules
                -> repository prepared queries
                    -> database indexes/integrity policy
```

Không lớp nào thay thế hoàn toàn lớp khác. React validation cải thiện trải nghiệm nhưng
không bảo vệ server; sanitize không thay thế allow-list; authentication không thay thế
authorization; prepared query không tự bảo đảm integrity; README không bù được một ZIP
không cài được. Bài làm đạt chất lượng cao khi toàn bộ chuỗi trên nhất quán, có bằng
chứng kiểm thử và có thể được một reviewer mới tái hiện trước thời hạn HR quy định.
