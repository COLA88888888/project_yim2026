# ເອກະສານໂຄງສ້າງຖານຂໍ້ມູນ (Database Schema Documentation)

ຖານຂໍ້ມູນຂອງລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ (`db_gym2026`) ປະກອບມີທັງໝົດ **16 ຕາຕະລາງ (Tables)** ດັ່ງລາຍລະອຽດລຸ່ມນີ້:

---

## 1. ຕາຕະລາງ `users` (ຂໍ້ມູນຜູ້ໃຊ້ງານ/ພະນັກງານ)
ເກັບຂໍ້ມູນບັນຊີຜູ້ໃຊ້, ລະຫັດຜ່ານ ແລະ ສິດທິການເຂົ້າເຖິງລະບົບ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `user_id` | `varchar(50)` | Primary Key, Not Null | ລະຫັດຜູ້ໃຊ້ (ຕົວຢ່າງ: U001, U002) |
| `fname` | `varchar(50)` | Default NULL | ຊື່ແທ້ |
| `lname` | `varchar(50)` | Default NULL | ນາມສະກຸນ |
| `gender` | `varchar(10)` | Default NULL | ເພດ (ຊາຍ / ຍິງ) |
| `dob` | `date` | Default NULL | ວັນເດືອນປີເກີດ |
| `tel` | `varchar(20)` | Default NULL | ເບີໂທລະສັບ |
| `address` | `text` | Default NULL | ທີ່ຢູ່ປະຈຸບັນ |
| `status` | `varchar(20)` | Default NULL | ສະຖານະ/ຕຳແໜ່ງ (ຜູ້ບໍລິຫານ / ພະນັກງານ) |
| `username` | `varchar(50)` | Unique, Default NULL | ຊື່ເຂົ້າໃຊ້ລະບົບ (Username) |
| `password` | `varchar(255)` | Default NULL | ລະຫັດຜ່ານ (ແຮຊດ້ວຍ bcrypt) |
| `permissions` | `text` | Default NULL | ສິດທິການເຂົ້າເຖິງໃນຮູບແບບ JSON |
| `profile_img` | `varchar(100)` | Default 'default.png' | ຊື່ໄຟລ໌ຮູບໂປຣໄຟລ໌ |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີເວລາທີ່ສ້າງບັນຊີ |
| `remark` | `varchar(100)` | Default NULL | ໝາຍເຫດ |

---

## 2. ຕາຕະລາງ `members` (ຂໍ້ມູນສະມາຊິກຍິມ)
ເກັບລາຍລະອຽດຂອງສະມາຊິກທີ່ລົງທະບຽນເຂົ້າໃຊ້ບໍລິການຍິມ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `member_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ພາຍໃນ (Auto run) |
| `member_code` | `varchar(50)` | Unique, Not Null | ລະຫັດບັດສະມາຊິກ (ຕົວຢ່າງ: GYM26060001) |
| `fname` | `varchar(50)` | Not Null | ຊື່ແທ້ |
| `lname` | `varchar(50)` | Not Null | ນາມສະກຸນ |
| `gender` | `varchar(10)` | Default NULL | ເພດ (ຊາຍ / ຍິງ) |
| `dob` | `date` | Default NULL | ວັນເກີດ |
| `tel` | `varchar(20)` | Default NULL | ເບີໂທລະສັບ |
| `address` | `text` | Default NULL | ທີ່ຢູ່ |
| `profile_img` | `varchar(100)` | Default 'default.png' | ຮູບພາບສະມາຊິກ |
| `status` | `varchar(20)` | Default 'Active' | ສະຖານະສະມາຊິກ (Active / Expired / Inactive) |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີສະໝັກທຳອິດ |

---

## 3. ຕາຕະລາງ `packages` (ຂໍ້ມູນແພັກເກດຍິມ)
ເກັບລາຍລະອຽດແພັກເກດຕ່າງໆ ເຊັ່ນ: 1 ເດືອນ, 3 ເດືອນ, 1 ປີ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `package_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ແພັກເກດ |
| `package_name` | `varchar(100)` | Not Null | ຊື່ແພັກເກດ (ຕົວຢ່າງ: 3 ເດືອນ) |
| `duration_days` | `int(11)` | Not Null | ຈຳນວນມື້ທີ່ຫຼິ້ນໄດ້ (ຕົວຢ່າງ: 90) |
| `price` | `decimal(12,2)` | Not Null | ລາຄາມາດຕະຖານຂອງແພັກເກດ |
| `description` | `text` | Default NULL | ລາຍລະອຽດແພັກເກດ |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີສ້າງແພັກເກດ |

---

## 4. ຕາຕະລາງ `memberships` (ປະຫວັດການສະໝັກແພັກເກດ & ຊຳລະເງິນ)
ເກັບປະຫວັດການລົງທະບຽນແພັກເກດຂອງສະມາຊິກ ແລະ ລາຍຮັບຈາກຄ່າສະໝັກ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `membership_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ການລົງທະບຽນ |
| `member_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `members.member_id` |
| `package_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `packages.package_id` |
| `start_date` | `date` | Not Null | ວັນທີເລີ່ມຕົ້ນແພັກເກດ |
| `end_date` | `date` | Not Null | ວັນທີໝົດອາຍຸແພັກເກດ |
| `price_paid` | `decimal(12,2)` | Not Null | ຈຳນວນເງິນທີ່ຈ່າຍຈິງ |
| `payment_method` | `varchar(50)` | Default 'ເງິນສົດ' | ວິທີຊຳລະ (ເງິນສົດ / ໂອນຜ່ານ QR) |
| `payment_status` | `varchar(20)` | Default 'Paid' | ສະຖານະການຈ່າຍ (Paid / Unpaid) |
| `status` | `varchar(20)` | Default 'Active' | ສະຖານະແພັກເກດ (Active / Expired) |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີເວລາທີ່ບັນທຶກລາຍການ |
| `user_id` | `varchar(50)` | Foreign Key, Default NULL | ພະນັກງານຜູ້ບັນທຶກ (`users.user_id`) |

---

## 5. ຕາຕະລາງ `checkins` (ປະຫວັດການສະແກນເຂົ້າຫຼິ້ນຍິມ)
ບັນທຶກເວລາທີ່ສະມາຊິກສະແກນບັດເຂົ້າໃຊ້ບໍລິການຍິມໃນແຕ່ລະວັນ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `checkin_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ການເຊັກອິນ |
| `member_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `members.member_id` |
| `checkin_time` | `datetime` | Default current_timestamp() | ວັນທີ ແລະ ເວລາທີ່ເຂົ້າໃຊ້ບໍລິການ |

---

## 6. ຕາຕະລາງ `daily_checkins` (ເຊັກອິນລູກຄ້າລາຍວັນ)
ເກັບລາຍຮັບ ແລະ ຂໍ້ມູນລູກຄ້າທົ່ວໄປທີ່ບໍ່ແມ່ນສະມາຊິກ ແຕ່ມາຊື້ປີ້ເຂົ້າຫຼິ້ນເປັນລາຍວັນ.

| ຊື່ຟີວ (Column) | ປະເພດ時計 (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ລາຍວັນ |
| `gender` | `varchar(20)` | Not Null | ເພດຂອງລູກຄ້າ (ຊາຍ / ຍິງ) |
| `price_paid` | `decimal(12,2)` | Not Null | ຄ່າບໍລິການລາຍວັນທີ່ຈ່າຍຈິງ |
| `payment_method` | `varchar(50)` | Not Null | ວິທີຊຳລະ (ເງິນສົດ / ເງິນໂອນ) |
| `checkin_date` | `date` | Not Null | ວັນທີທີ່ເຂົ້າຫຼິ້ນ |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີເວລາທີ່ບັນທຶກ |
| `user_id` | `varchar(50)` | Default NULL | ພະນັກງານຜູ້ບັນທຶກ (`users.user_id`) |

---

## 7. ຕາຕະລາງ `equipment` (ຂໍ້ມູນເຄື່ອງອອກກຳລັງກາຍ)
ບັນທຶກລາຍຊື່ເຄື່ອງມື, ອຸປະກອນອອກກຳລັງກາຍ ແລະ ສະພາບການໃຊ້ງານ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `equipment_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ອຸປະກອນ |
| `equipment_code` | `varchar(50)` | Unique, Not Null | ລະຫັດເຄື່ອງ (ຕົວຢ່າງ: EQ-001) |
| `equipment_name` | `varchar(100)` | Not Null | ຊື່ເຄື່ອງອອກກຳລັງກາຍ |
| `brand_model` | `varchar(100)` | Default NULL | ຍີ່ຫໍ້ ແລະ ລຸ້ນ |
| `quantity` | `int(11)` | Default 1 | ຈຳນວນເຄື່ອງ |
| `status` | `varchar(20)` | Default 'ດີ' | ສະພາບ (ດີ / ເພ / ຊຳລຸດ) |
| `purchase_date` | `date` | Default NULL | ວັນທີຊື້ເຂົ້າມາ |
| `price` | `decimal(12,2)` | Default 0.00 | ລາຄາທີ່ຊື້ມາ |
| `description` | `varchar(255)` | Default NULL | ຄຳອະທິບາຍເພີ່ມເຕີມ |
| `equipment_img` | `varchar(100)` | Default 'default_eq.png' | ຮູບພາບອຸປະກອນ |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີບັນທຶກລົງລະບົບ |

---

## 8. ຕາຕະລາງ `lockers` (ຂໍ້ມູນລັອກເກີເກັບເຄື່ອງ)
ຈັດການສະຖານະການຈອງ ແລະ ນຳໃຊ້ຕູ້ລັອກເກີຂອງລູກຄ້າ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `locker_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ລັອກເກີ |
| `locker_code` | `varchar(50)` | Unique, Not Null | ເລກຕູ້ລັອກເກີ (ຕົວຢ່າງ: L-01) |
| `locker_floor` | `varchar(50)` | Default NULL | ສະຖານທີ່/ຊັ້ນວາງຕູ້ (ຕົວຢ່າງ: ຊັ້ນ 1) |
| `status` | `varchar(20)` | Default 'Available' | ສະຖານະຕູ້ (Available / Occupied / Broken) |
| `member_id` | `int(11)` | Default NULL | ສະມາຊິກທີ່ກຳລັງໃຊ້ຕູ້ (`members.member_id`) |
| `assigned_at` | `datetime` | Default NULL | ເວລາທີ່ເລີ່ມນຳໃຊ້ຕູ້ |
| `member_name` | `varchar(150)` | Default NULL | ຊື່ຜູ້ໃຊ້ຕູ້ (ໃຊ້ບັນທຶກກໍລະນີບໍ່ແມ່ນສະມາຊິກຖາວອນ) |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີສ້າງລັອກເກີ |

---

## 9. ຕາຕະລາງ `expenses` (ຂໍ້ມູນລາຍຈ່າຍທົ່ວໄປ)
ບັນທຶກລາຍຈ່າຍຕ່າງໆພາຍໃນຍິມ ເຊັ່ນ: ຄ່ານ້ຳ, ຄ່າໄຟ, ຄ່າສ້ອມແປງ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `expense_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ລາຍຈ່າຍ |
| `title` | `varchar(255)` | Not Null | ຫົວຂໍ້ລາຍຈ່າຍ (ຕົວຢ່າງ: ຈ່າຍຄ່າໄຟຟ້າປະຈຳເດືອນ) |
| `category` | `varchar(100)` | Not Null | ປະເພດລາຍຈ່າຍ |
| `amount` | `decimal(12,2)` | Not Null | ຈຳນວນເງິນລາຍຈ່າຍ |
| `expense_date` | `date` | Not Null | ວັນທີທີ່ຈ່າຍເງິນ |
| `notes` | `text` | Default NULL | ລາຍລະອຽດ/ໝາຍເຫດ |
| `user_id` | `varchar(50)` | Foreign Key, Default NULL | ພະນັກງານຜູ້ບັນທຶກ (`users.user_id`) |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີເວລາທີ່ບັນທຶກ |

---

## 10. ຕາຕະລາງ `product_categories` (ປະເພດສິນຄ້າໃນຮ້ານ)
ຈັດໝວດໝູ່ສິນຄ້າ ເຊັ່ນ: ເຄື່ອງດື່ມ, ອາຫານເສີມ, ອຸປະກອນເສີມ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `category_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ປະເພດສິນຄ້າ |
| `category_code` | `varchar(50)` | Unique, Not Null | ລະຫັດປະເພດສິນຄ້າ (ຕົວຢ່າງ: CAT001) |
| `category_name` | `varchar(100)` | Not Null | ຊື່ປະເພດສິນຄ້າ (ຕົວຢ່າງ: ເຄື່ອງດື່ມ) |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີສ້າງໝວດໝູ່ |

---

## 11. ຕາຕະລາງ `products` (ຂໍ້ມູນສິນຄ້າໃນສາງ)
ເກັບລາຍລະອຽດ, ລາຄາ ແລະ ຈຳນວນສິນຄ້າທີ່ວາງຂາຍໜ້າຮ້ານ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `product_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ສິນຄ້າ |
| `product_code` | `varchar(50)` | Unique, Not Null | ລະຫັດບາໂຄ້ດ (Barcode) |
| `product_name` | `varchar(100)` | Not Null | ຊື່ສິນຄ້າ |
| `category_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `product_categories.category_id` |
| `cost_price` | `decimal(12,2)` | Default 0.00 | ລາຄາຕົ້ນທຶນ |
| `sale_price` | `decimal(12,2)` | Not Null | ລາຄາຂາຍໜ້າຮ້ານ |
| `quantity` | `int(11)` | Default 0 | ຈຳນວນສິນຄ້າໃນຄັງປະຈຸບັນ |
| `unit` | `varchar(20)` | Default 'ຕຸກ/ປຸກ' | ຫົວໜ່ວຍສິນຄ້າ (ຕຸກ / ປຸກ / ອັນ) |
| `image` | `varchar(255)` | Default NULL | ຮູບພາບສິນຄ້າ |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີບັນທຶກສິນຄ້າ |

---

## 12. ຕາຕະລາງ `sales` (ລາຍການຂາຍສິນຄ້າ - POS)
ເກັບຫົວຂໍ້ການຂາຍແຕ່ລະບິນ (Transactions).

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `sale_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ການຂາຍ |
| `sale_code` | `varchar(50)` | Unique, Not Null | ເລກທີບິນຂາຍ (ຕົວຢ່າງ: SALE2606080001) |
| `sale_date` | `datetime` | Default current_timestamp() | ວັນທີເວລາທີ່ຂາຍສິນຄ້າ |
| `total_amount` | `decimal(12,2)` | Not Null | ຍອດລວມທັງໝົດຂອງບິນ |
| `payment_method` | `varchar(50)` | Default 'ເງິນສົດ' | ວິທີຊຳລະ (ເງິນສົດ / ເງິນໂອນ) |
| `user_id` | `varchar(50)` | Foreign Key, Default NULL | ພະນັກງານຂາຍ (`users.user_id`) |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີບັນທຶກລົງລະບົບ |

---

## 13. ຕາຕະລາງ `sale_details` (ລາຍລະອຽດສິນຄ້າໃນບິນຂາຍ)
ເກັບລາຍລະອຽດສິນຄ້າແຕ່ລະລາຍການທີ່ຂາຍໃນບິນນັ້ນໆ (Sales details).

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `detail_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ລາຍລະອຽດການຂາຍ |
| `sale_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `sales.sale_id` (On Delete Cascade) |
| `product_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `products.product_id` |
| `quantity` | `int(11)` | Not Null | ຈຳນວນທີ່ຂາຍ |
| `price` | `decimal(12,2)` | Not Null | ລາຄາຂາຍຕໍ່ໜ່ວຍໃນເວລານັ້ນ |
| `subtotal` | `decimal(12,2)` | Not Null | ຍອດລວມຂອງລາຍການ (ລາຄາ x ຈຳນວນ) |

---

## 14. ຕາຕະລາງ `stock_in` (ການນຳເຂົ້າສິນຄ້າ)
ບັນທຶກການຮັບສິນຄ້າໃໝ່ເຂົ້າຄັງ/ສາງ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `stock_in_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ການນຳເຂົ້າ |
| `stock_in_date` | `datetime` | Default current_timestamp() | ວັນທີເວລານຳເຂົ້າ |
| `supplier` | `varchar(100)` | Default NULL | ບໍລິສັດ/ຮ້ານຄ້າທີ່ສະໜອງສິນຄ້າ |
| `total_amount` | `decimal(12,2)` | Default 0.00 | ຍອດລວມຕົ້ນທຶນທັງໝົດທີ່ນຳເຂົ້າ |
| `user_id` | `varchar(50)` | Foreign Key, Default NULL | ພະນັກງານຜູ້ບັນທຶກ (`users.user_id`) |
| `created_at` | `datetime` | Default current_timestamp() | ວັນທີເວລາທີ່ບັນທຶກ |

---

## 15. ຕາຕະລາງ `stock_in_details` (ລາຍລະອຽດສິນຄ້ານຳເຂົ້າ)
ເກັບລາຍລະອຽດສິນຄ້າ ແລະ ຕົ້ນທຶນຂອງແຕ່ລະລາຍການທີ່ນຳເຂົ້າ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `detail_id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ລາຍລະອຽດການນຳເຂົ້າ |
| `stock_in_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `stock_in.stock_in_id` (On Delete Cascade) |
| `product_id` | `int(11)` | Foreign Key, Not Null | ເຊື່ອມຫາ `products.product_id` |
| `quantity` | `int(11)` | Not Null | ຈຳນວນທີ່ນຳເຂົ້າ |
| `cost_price` | `decimal(12,2)` | Not Null | ລາຄາຕົ້ນທຶນຕໍ່ໜ່ວຍທີ່ຊື້ມາ |

---

## 16. ຕາຕະລາງ `system_settings` (ການຕັ້ງຄ່າຂໍ້ມູນຍິມ)
ເກັບຂໍ້ມູນທົ່ວໄປຂອງສະໂມສອນຍິມ/ຟິດເນັດ ເພື່ອນຳໄປໃຊ້ໃນຫົວຂໍ້ເວັບ, Sidebar, ແລະ ໃບບິນຮັບເງິນ.

| ຊື່ຟີວ (Column) | ປະເພດຂໍ້ມູນ (Type) | ຄຸນສົມບັດ (Attributes) | ອະທິບາຍ (Description) |
| :--- | :--- | :--- | :--- |
| `id` | `int(11)` | Primary Key, Auto Increment | ລະຫັດ ID ຕັ້ງຄ່າ |
| `gym_name` | `varchar(100)` | Not Null | ຊື່ຍິມ / ສະໂມສອນ (ຕົວຢ່າງ: GYM & FITNESS) |
| `tel` | `varchar(50)` | Default NULL | ເບີໂທລະສັບຕິດຕໍ່ |
| `address` | `text` | Default NULL | ທີ່ຢູ່ສະໂມສອນ |
| `logo_path` | `varchar(255)` | Default NULL | ເສັ້ນທາງເກັບໄຟລ໌ໂລໂກ້ຍິມ (Logo Path) |
| `updated_at` | `datetime` | Default current_timestamp() | ວັນທີເວລາທີ່ອັບເດດຫຼ້າສຸດ |
