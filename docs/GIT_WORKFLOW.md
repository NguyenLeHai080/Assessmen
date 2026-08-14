# Quy trình Git của dự án

## 1. Các nhánh chính

| Nhánh | Mục đích | Cho phép push trực tiếp |
| --- | --- | --- |
| `prod` | Production | Không |
| `staging` | QA và demo | Không |
| `dev` | Tích hợp tính năng | Không khuyến khích; ưu tiên PR |

Luồng phát hành chuẩn:

```text
feat/* -> dev -> staging -> prod
```

Luồng sửa lỗi khẩn cấp:

```text
prod -> hotfix/* -> prod -> staging -> dev
```

Không merge ngược `staging` vào `dev` như một cách phát triển tính năng. Mọi thay
đổi phải bắt nguồn từ `dev`, ngoại trừ hotfix bắt nguồn từ `prod`.

## 2. Phát triển tính năng

```bash
git switch dev
git pull --ff-only origin dev
git switch -c feat/homepage

# Sau khi chỉnh sửa
git add .
git commit -m "feat(homepage): thêm giao diện trang chủ #123"
git push -u origin feat/homepage
```

Tạo PR từ `feat/homepage` vào `dev`, yêu cầu review và chờ kiểm tra tự động thành
công trước khi merge. Khi một nhóm tính năng ổn định, tạo lần lượt PR `dev` vào
`staging`, sau đó `staging` vào `prod`.

## 3. Hotfix

```bash
git switch prod
git pull --ff-only origin prod
git switch -c hotfix/fix-login-error

# Sau khi sửa và kiểm thử
git add .
git commit -m "fix(login): xử lý lỗi đăng nhập #456"
git push -u origin hotfix/fix-login-error
```

Tạo PR từ hotfix vào `prod`. Sau khi phát hành, tạo PR đồng bộ `prod` vào
`staging`, rồi `staging` vào `dev`. Giải quyết xung đột ở từng PR và kiểm thử lại
trước khi merge.

## 4. Conventional Commits

Cấu trúc:

```text
<type>[optional scope]: <description> #<issue-id>

[optional body]

[optional footer]
```

Các type được chấp nhận:

- `feat`: thêm tính năng.
- `fix`: sửa lỗi.
- `refactor`: thay đổi cấu trúc nhưng không đổi hành vi mong muốn.
- `docs`: tài liệu.
- `chore`: công việc bảo trì không liên quan trực tiếp đến chức năng.
- `style`: định dạng hoặc giao diện không đổi logic.
- `perf`: cải thiện hiệu năng.
- `vendor`: cập nhật dependency/package.
- `test`: thêm hoặc sửa kiểm thử.
- `ci`: thay đổi pipeline CI/CD.
- `build`: thay đổi hệ thống build.
- `revert`: hoàn tác commit.

Quy tắc tiêu đề:

- Viết rõ mục đích, ưu tiên không quá 50 ký tự khi có thể.
- Không kết thúc bằng dấu chấm.
- Dùng nhất quán một ngôn ngữ trong phần mô tả.
- Mọi commit nghiệp vụ phải có issue ID dạng `#123`.
- Commit merge/revert tự sinh và các thay đổi quản trị đặc biệt có thể được miễn.

Ví dụ đúng:

```text
feat(homepage): thêm giao diện trang chủ #123
fix(login): xử lý ký tự không hợp lệ #456
docs(git): bổ sung hướng dẫn hotfix #789
vendor(redis): cập nhật Redis lên phiên bản 8 #810
```

Ví dụ sai:

```text
hihi
feat: fix bug logout
feat(logout): Fix bug ở chỗ tính năng đăng xuất
```

## 5. Review và đồng bộ

- Không tự approve PR của chính mình nếu team có reviewer khác.
- PR phải liên kết issue, mô tả cách kiểm thử và phạm vi ảnh hưởng.
- Không merge khi CI thất bại hoặc còn review yêu cầu thay đổi.
- Sau hotfix, kiểm tra commit đã xuất hiện trên cả `prod`, `staging` và `dev`.
- Luôn chạy `git pull --ff-only` trước khi tạo nhánh mới.
