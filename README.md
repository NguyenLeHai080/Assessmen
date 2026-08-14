# Assessmen

Repository sử dụng mô hình Gitflow với ba nhánh môi trường:

- `prod`: mã nguồn đang chạy production.
- `staging`: kiểm thử QA và demo.
- `dev`: tích hợp các tính năng đang phát triển.

Nhánh công việc được tạo từ `dev` theo dạng `feat/<ten-tinh-nang>`. Bản sửa lỗi
khẩn cấp được tạo từ `prod` theo dạng `hotfix/<ten-loi>`.

Quy trình đầy đủ, quy ước commit và các lệnh mẫu nằm tại
[docs/GIT_WORKFLOW.md](docs/GIT_WORKFLOW.md).
