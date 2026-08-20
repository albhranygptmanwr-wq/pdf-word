# PDF TO WORD Converter

تطبيق ويب متكامل لتحويل ملفات PDF إلى Word (docx) مبني باستخدام PHP، MySQL، و Javascript.

## المتطلبات
- خادم محلي XAMPP (Apache + MySQL)
- PHP 8.1 أو أحدث
- Composer لإدارة حزم PHP

## خطوات التثبيت

1. **نقل الملفات:**
   قم بنسخ مجلد `pdf-word` إلى مسار `htdocs` داخل XAMPP:
   `C:\xampp\htdocs\pdf-word`

2. **قاعدة البيانات:**
   - افتح http://localhost/phpmyadmin
   - قم بإنشاء قاعدة بيانات باسم `pdf_word` بالترميز `utf8mb4_unicode_ci`.
   - قم باستيراد الملف `database.sql` الموجود في جذر المشروع لإنشاء الجداول.

3. **تثبيت مكتبات Composer:**
   افتح موجه الأوامر (CMD) في مسار المشروع وقم بتشغيل:
   ```cmd
   cd C:\xampp\htdocs\pdf-word
   composer install