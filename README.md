# بطاقات اللغات (Language Cards / Flashcards)

تطبيق ويب لإنشاء وإدارة **بطاقات تعليمية (Flashcards)** منظمة هرميًا: لغة → مستوى → مجموعة (Deck) → تصنيفات → بطاقات. الواجهة بالعربية واتجاه **RTL**، مع إمكانية **طباعة** البطاقات على ورق A4–A0 بخيارات تخطيط افتراضية أو مخصصة.

---

## المتطلبات

| الأداة | الإصدار المقترح |
|--------|-------------------|
| PHP | ‎^8.2 |
| Composer | حديث |
| Node.js | لدعم Vite 7 و npm |
| قاعدة بيانات | SQLite (افتراضي) أو MySQL/MariaDB/PostgreSQL عبر `.env` |

---

## التثبيت السريع (جلسة واحدة)

من جذر المشروع:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

أنشئ ملف SQLite إن لم يكن موجودًا:

```bash
# إذا استخدمت SQLite في .env (DB_CONNECTION=sqlite)
New-Item -ItemType File -Path database\database.sqlite -Force
```

ثم:

```bash
php artisan migrate
npm install
npm run build
php artisan storage:link
```

أمر **`storage:link`** ضروري لعرض صور أيقونات البطاقات المرفوعة (`storage/app/public` ← `public/storage`).

### تشغيل بيئة التطوير

**طريقة 1 — سكربت Composer (يُشغّل الخادم، الطابور، السجلات، و Vite معًا):**

```bash
composer run dev
```

**طريقة 2 — يدويًا في نوافذ طرفية منفصلة:**

```bash
php artisan serve
npm run dev
```

افتح المتصفح على العنوان الذي يعرضه `serve` (غالبًا `http://127.0.0.1:8000`).

> الجذر `/` يعيد التوجيه إلى قائمة اللغات `languages`.

### إعداد تلقائي (بديل)

يوفّر المشروع سكربت Composer:

```bash
composer run setup
```

ينفّذ: `composer install`، نسخ `.env` إن لزم، `key:generate`، `migrate`، `npm install`، `npm run build`. قد تحتاج بعده إلى `php artisan storage:link` وإنشاء ملف SQLite يدويًا إذا لم يكن موجودًا.

---

## ملف البيئة `.env`

أهم المتغيرات:

| المتغير | المعنى |
|---------|--------|
| `APP_NAME`, `APP_URL` | اسم التطبيق والرابط الأساسي |
| `APP_DEBUG` | في الإنتاج يجب أن يكون `false` |
| `DB_CONNECTION` | مثل `sqlite` أو `mysql` |
| `DB_DATABASE` | لـ SQLite غالبًا المسار الكامل لملف `.sqlite` |
| `SESSION_DRIVER` | الافتراضي في المثال: `database` |
| `CACHE_STORE`, `QUEUE_CONNECTION` | في المثال: `database` |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD` | حساب المدير الوحيد (صلاحيات كاملة). الزائر يتصفّح ويطبع فقط |

نسخ `.env.example` إلى `.env` ثم عدّل حسب بيئتك.

---

## البنية الهرمية للبيانات

```
Language (لغة)
 └── Level (مستوى)
      └── Deck (مجموعة بطاقات)
           └── Category (تصنيف داخل المجموعة)
                └── Card (بطاقة)
```

- **اللغة**: اسم، رمز، لون، ترتيب.
- **المستوى**: تابع للغة، اسم، عنوان، ترتيب.
- **المجموعة**: تابع للمستوى، اسم، وصف، لون مميز (hex).
- **التصنيف**: داخل مجموعة؛ البطاقات مرتبطة بتصنيف. عند إضافة بطاقة من شاشة المجموعة يُنشأ/يُستخدم تصنيف افتراضي (`firstOrCreate` بالموضع 1) إن لزم.
- **البطاقة**: كلمة، خلفية الواجهة (`front_bg_type`: لون أو صورة)، قيمة الخلفية، معاني إنجليزي/عربي، شرح، أيقونة نصية أو صورة (`icon_image_path`)، أعلام إظهار لكل حقل، وترتيب `position`.

---

## المسارات الرئيسية (Routes)

| الوظيفة | مسار تقريبي |
|---------|-------------|
| الصفحة الرئيسية | `GET /` → إعادة توجيه إلى `languages.index` |
| CRUD اللغات | `languages` (موارد Laravel) |
| CRUD المستويات | `levels` |
| قائمة المجموعات (متدرجة: لغة ثم مستوى) | `GET /decks` مع معاملات `language_id`, `level_id` |
| CRUD المجموعات | `decks` |
| خيارات الطباعة | `GET decks/{deck}/print/options` |
| معاينة/طباعة | `GET decks/{deck}/print` |
| إعادة ترتيب البطاقات | `GET/POST decks/{deck}/cards/reorder` |
| إنشاء بطاقة ضمن مجموعة | `GET decks/{deck}/cards/create` و `POST decks/{deck}/cards` |
| تصنيفات ضمن مجموعة | `decks.categories` (shallow، بدون index/show) |
| بطاقات ضمن تصنيف | `categories.cards` (shallow، بدون index/show) |

---

## ميزات البطاقات والطباعة

- **واجهة البطاقة**: لون hex أو تدرج CSS (مع فلترة أمان في `sanitizeFrontBgValue`)، أو صورة عبر `front_bg_value` عند النوع `image`.
- **الظهر**: معاني، شرح، أيقونة emoji أو **صورة مرفوعة** (تُخزن تحت `storage/app/public/card-icons`).
- **الطباعة** (`DeckController`):
  - **وضع افتراضي** (`mode=default`): أحجام ورق A4–A0 مع شبكة أعمدة/صفوف محددة لكل حجم؛ حجم البطاقة ثابت نسبيًا للنموذج الأصلي A4 3×3.
  - **وضع مخصص** (`mode=custom`): صفوف وأعمدة، حشو الصفحة بالمليمتر، الفجوات بين البطاقات، سمك ونمط إطار البطاقة ضمن حدود `CUSTOM_LIMITS`.

---

## الواجهة والأصول الأمامية

- **Blade** للقوالب؛ التخطيط في `resources/views/layouts/app.blade.php` (عربي، RTL، خط Cairo).
- **Tailwind CSS v4** عبر `@tailwindcss/vite`.
- **Vite 7** لبناء `resources/css/app.css` و `resources/js/app.js`.

أوامر npm:

```bash
npm run dev    # تطوير مع HMR
npm run build  # بناء للإنتاج
```

---

## التخزين والملفات العامة

- رفع أيقونات البطاقات يعتمد على قرص `public`؛ المسارات تُحفظ في `icon_image_path`.
- نفّذ `php artisan storage:link` على كل بيئة جديدة حتى تُعرض الصور من `public/storage`.

---

## الطابور والجلسات

`composer run dev` يشغّل `queue:listen` و **Pail** للسجلات. للتطوير البسيط يكفي `php artisan serve` و `npm run dev`. الجلسات والكاش والطابور في `.env.example` مضبوطة على `database`؛ بعد `migrate` تُنشأ الجداول اللازمة.

---

## الاختبارات

```bash
composer test
# أو
php artisan test
```

---

## فحص الصحة (Health)

مسار Laravel: `GET /up`.

---

## المرجع الرسمي لمصطلحات الذكاء الاصطناعي

عند استخدام/صياغة/تعريب مصطلحات الذكاء الاصطناعي في هذا المشروع، **المرجع الرسمي** هو توثيق Oxford / OED:

- `https://www.oed.com/?tl=true`

---

## الأمان والاستخدام

- **الزائر** يتصفّح اللغات والمستويات والمجموعات والبطاقات و**يطبع** بدون تسجيل دخول.
- **المدير** يسجّل الدخول من `/login` باستخدام `ADMIN_USERNAME` و `ADMIN_PASSWORD` في `.env` للحصول على صلاحيات الإنشاء والتعديل والحذف.
- اضبط كلمة مرور قوية في `.env` على الخادم ولا ترفع ملف `.env` إلى Git.
- التحقق من خلفيات البطاقات يقلل حقن CSS/XSS غير الآمن.

---

## هيكل المجلدات المهم

| المسار | المحتوى |
|--------|---------|
| `app/Http/Controllers/` | المتحكمات |
| `app/Models/` | نماذج Eloquent |
| `database/migrations/` | مخطط قاعدة البيانات |
| `resources/views/` | قوالب Blade ومكوّنات البطاقة |
| `routes/web.php` | المسارات |
| `public/` | `index.php` والأصول العامة |

---

## الترخيص

إطار Laravel مرخّص تحت [MIT](https://opensource.org/licenses/MIT).

---

## ملخص سريع للتشغيل (Windows / PowerShell)

```powershell
composer install; Copy-Item .env.example .env; php artisan key:generate
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan migrate
npm install; npm run build
php artisan storage:link
composer run dev
```

ثم زُر `http://127.0.0.1:8000` (أو المنفذ الذي يظهر في الطرفية).
