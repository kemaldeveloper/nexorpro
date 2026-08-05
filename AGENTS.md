# AGENTS.md — Nexor WordPress

Документация для AI-агентов и разработчиков, работающих с репозиторием **nexorpro** — нативного WordPress-сайта [nexorpro.ru](https://nexorpro.ru) (компания Nexor, ремонт квартир и домов под ключ в Москве и МО).

**Текущая версия:** `1.6.0` (см. `VERSION`, `package.json`, `style.css`).

---

## Что это за проект

Сайт мигрирован с React SPA на **native WordPress** (тема + плагин). Сохранены:

- все публичные URL и SEO-метаданные;
- калькулятор стоимости (серверная перепроверка формулы);
- формы заявок и интеграция с Telegram;
- CPT «Проекты» с галереями;
- Schema.org, sitemap, robots.

Контент страниц хранится как **HTML-разметка** (миграция из React), а динамические блоки (цены, бонусы, popup, секции главной) генерируются PHP-плагином и встраиваются в HTML через фильтры.

---

## Архитектура

```
┌─────────────────────────────────────────────────────────────┐
│  WordPress (PHP 8.2+, WP 6.6+)                              │
├─────────────────────────────────────────────────────────────┤
│  Тема nexor          │  Плагин nexor-core                   │
│  ─────────────────   │  ─────────────────                   │
│  Шаблоны PHP         │  CPT: nexor_project, nexor_lead       │
│  Статический HTML    │  REST: /nexor/v1/lead, /calculate     │
│  nexor.css / nexor.js│  Настройки, SEO, Telegram, Schema     │
│  Ассеты (webp, CSS)  │  Nexor_Enhancements (секции главной)  │
└─────────────────────────────────────────────────────────────┘
```

### Разделение ответственности

| Компонент | Путь | За что отвечает |
|-----------|------|-----------------|
| **Тема** | `package/wp-content/themes/nexor/` | Шаблоны, рендер мигрированного HTML, SEO в `<head>`, навигация, фронтенд JS/CSS |
| **Плагин** | `package/wp-content/plugins/nexor-core/` | Бизнес-логика: заявки, калькулятор, проекты, админка, инъекция секций |
| **Контент** | `package/wp-content/themes/nexor/content/*.html` | Исходная HTML-разметка страниц (seed при активации) |
| **Деплой** | `package/deploy/` | Docker Swarm stack, wp-config, Traefik, php.ini |
| **Тесты** | `tests/` | PHP unit + Chromium CDP аудиты production/local |
| **Инструменты** | `tools/` | Сборка контента из React dist, генерация project-data |

---

## Структура репозитория

```
nexorpro/
├── AGENTS.md                 ← этот файл
├── VERSION                   ← версия релиза
├── package.json              ← npm-скрипты (unit-тесты, проверка JS)
├── docker-compose.yml        ← локальный WordPress (порт 8080)
├── MANIFEST.sha256           ← контрольные суммы файлов релиза
├── docs/
│   ├── ADMIN-GUIDE.md        ← инструкция для администратора WP
│   └── CHANGELOG.md          ← история версий
├── package/
│   ├── deploy/               ← production-конфигурация
│   └── wp-content/
│       ├── themes/nexor/
│       │   ├── assets/       ← nexor.js, nexor.css, index-*.css, webp
│       │   ├── content/      ← *.html + metadata.json
│       │   ├── functions.php
│       │   ├── header.php, footer.php, index.php, page.php, ...
│       │   └── style.css
│       └── plugins/nexor-core/
│           ├── nexor-core.php
│           ├── class-nexor-enhancements.php
│           ├── project-data.json
│           └── asset-media-map.json
├── tests/                    ← аудиты и unit-тесты
└── tools/                    ← build-content.mjs, generate-project-data.mjs
```

---

## Локальная разработка

### Требования

- Docker + Docker Compose
- Node.js (для `npm run check:js` и production-аудитов)
- PHP CLI (для `npm run test:unit`)

### Запуск

```bash
docker compose up -d
```

После старта `wp-init` автоматически:

1. Устанавливает WordPress;
2. Активирует тему `nexor` и плагин `nexor-core`;
3. Настраивает ЧПУ `/%postname%/`.

**Сайт:** http://localhost:8080  
**Админка:** http://localhost:8080/wp-admin — `admin` / `admin`

Тема и плагин смонтированы как volume — изменения в `package/wp-content/` видны сразу без пересборки образа.

### Полезные npm-скрипты

```bash
npm run test:unit    # PHP unit-тест enhancements (без WordPress)
npm run check:js     # синтаксическая проверка всех .js/.mjs
```

### WP-CLI (внутри контейнера)

```bash
docker compose exec wordpress wp --allow-root <command>
```

| Команда | Назначение |
|---------|------------|
| `wp nexor seed` | Импорт HTML-контента из `content/` (идемпотентно для новых страниц) |
| `wp nexor migrate-media` | Замена `{{THEME_URI}}/assets/...` на URL из Media Library |
| `wp nexor migrate-projects` | Заполнение мета-полей проектов из `project-data.json` |
| `wp nexor enhancements-diagnostic` | JSON-диагностика секций (без изменения данных, без секретов) |

---

## Ключевые концепции

### 1. Мигрированный контент

Страницы рендерятся через `nexor_render_migrated_content()` в `functions.php`:

1. Берётся `post_content` (HTML из редактора WP или seed);
2. Применяется фильтр `nexor_content_replacements` (телефон, email, соцсети из настроек);
3. Применяется фильтр `nexor_migrated_content` (инъекция секций из `Nexor_Enhancements`);
4. Подставляются `{{THEME_URI}}` и `url(/assets/...)` → пути темы.

**Не ломайте** существующие CSS-классы и `id` якорей в HTML главной и страниц услуг — PHP ищет их для вставки блоков.

### 2. Nexor_Enhancements (динамические секции)

Класс `Nexor_Enhancements` в `class-nexor-enhancements.php`:

- Хранит настройки в отдельных WP options (`nexor_home_prices`, `nexor_promotions`, …);
- На главной **вставляет** секции: услуги, сроки, смета, цены, видео, доп. услуги, бонусы;
- На страницах услуг добавляет hero-shell, trust-блок, мета «Структура услуги»;
- На страницах проектов — блок «Связанные услуги».

Пустая или невалидная секция **полностью скрывается** (вместе с пунктом меню).

### 3. REST API

| Endpoint | Метод | Назначение |
|----------|-------|------------|
| `/wp-json/nexor/v1/lead` | POST | Создание заявки → CPT `nexor_lead` + Telegram |
| `/wp-json/nexor/v1/calculate` | POST | Расчёт калькулятора (серверная валидация) |

Фронтенд получает `restUrl` и `nonce` через `wp_localize_script` → объект `NexorSettings` в `nexor.js`.

Защита: WP REST nonce, проверка Origin, rate limit 5 запросов / 15 мин / IP, honeypot-поле `website`.

### 4. Custom Post Types

| CPT | Slug rewrite | Описание |
|-----|--------------|----------|
| `nexor_project` | `/projects/{slug}/` | Кейсы ремонта (галерея, мета-поля) |
| `nexor_lead` | — (private) | Заявки с сайта |

Таксономии `nexor_repair_type`, `nexor_property_type` — только для админки, публичные архивы редиректятся на `/projects/`.

### 5. Страницы услуг (фиксированные slug)

**Не менять slug** этих страниц:

- `remont-kvartir-pod-klyuch`
- `capital-remont`
- `design-remont`
- `remont-v-novostroyke`
- `cosmetic-remont` (не выводится на главной)
- `remont-domov-pod-klyuch`

Мета-поля услуг: `_nexor_service_summary`, `_nexor_service_composition`, `_nexor_service_faq` (формат `Вопрос | Ответ`), `_nexor_service_related_project_ids` и др.

### 6. Фронтенд JS (`nexor.js`)

Vanilla JS (~1100 строк), без bundler. Отвечает за:

- desktop/mobile навигацию и поиск;
- калькулятор (7 шагов, POST на REST);
- модальные формы заявок с контекстом (бонус, доп. услуга, цена);
- popup с задержкой (`exitIntent` из настроек);
- слайдер ДО/ПОСЛЕ, countdown бонусов, IntersectionObserver-анимации;
- accessibility: focus trap, Escape, `prefers-reduced-motion`.

При изменении API настроек обновляйте и PHP (`frontend_config()`), и JS.

### 7. Стили

- `assets/index-*.css` — скомпилированный Tailwind/design-system из миграции (hash в имени файла);
- `assets/nexor.css` — дополнения темы (секции enhancements, service pages 1.6);
- `style.css` — только метаданные темы для WordPress.

---

## Где что менять

| Задача | Где править |
|--------|-------------|
| Вёрстка/стили секций enhancements | `class-nexor-enhancements.php` (HTML) + `nexor.css` |
| Поведение калькулятора, форм, popup | `nexor.js` + REST в `nexor-core.php` |
| SEO title/description/canonical | мета `_nexor_seo_*` или `page_seo_defaults()` в плагине |
| Контакты, ставки калькулятора | **Настройки → Nexor** (`nexor_settings` option) |
| Блоки главной (цены, бонусы, popup) | **Настройки → Nexor** (секции enhancements) |
| Hero/статический HTML главной | контент страницы «Главная» в WP или `content/home.html` |
| Новый проект | **Проекты** в админке + медиафайлы |
| Импорт HTML из React-сборки | `tools/build-content.mjs` → `content/*.html` → `wp nexor seed` |
| Данные проектов (bulk) | `tools/generate-project-data.mjs` → `project-data.json` → `wp nexor migrate-projects` |

---

## Тестирование

### Unit (быстро, без Docker)

```bash
npm run test:unit
# или
php tests/enhancements-unit.php
```

Проверяет: seed акций, stable ID заявок, политику поиска, порядок секций на главной, UTF-8 в доп. услугах.

### Production-аудиты (Chromium CDP)

Скрипты в `tests/*-audit.mjs` и `tests/*-evidence.mjs` запускают headless Chromium через WebSocket DevTools Protocol. Большинство таргетят **production** (`nexorpro.ru`).

Типичные проверки:

- layout на 1440×900, 390×844;
- якоря и навигация;
- калькулятор end-to-end;
- popup desktop/mobile;
- crawl ресурсов (в т.ч. inline `background-image`);
- скриншоты секций для release evidence.

Артефакты сохраняются в `artifacts/`.

### PHP-фикстуры

- `tests/render-homepage-redesign-fixture.php` — рендер главной
- `tests/homepage-popup-fixture.php` — popup
- `tests/configure-homepage-options.php` — настройка options для тестов

---

## Production-деплой

- **Stack:** Docker Swarm (`package/deploy/stack.yml`)
- **Reverse proxy:** Traefik (`package/deploy/traefik-production.yml`) → `nexorpro.ru`
- **Secrets:** `nexor_db_password`, `nexor_wp_secret`, `nexor_telegram_token`, `nexor_telegram_chat_id`
- **Volumes:** тема и плагин read-only из `/opt/nexor-wordpress/current/`, uploads в shared volume
- **wp-config:** `package/deploy/wp-config.example.php` — Telegram token **только** в конфиге/secrets, не в БД

Telegram:

```php
define('NEXOR_TELEGRAM_BOT_TOKEN', ...);
define('NEXOR_TELEGRAM_CHAT_ID', ...);
```

Chat ID также можно задать в **Настройки → Nexor** (fallback).

---

## Правила для агентов

### Делать

- Минимальный diff; не трогать несвязанный код.
- Сохранять slug услуг, URL проектов, SEO-метаданные существующих страниц.
- При правках HTML главной — сохранять якоря (`#calculator`, `#cases`, `#about-company-nexor`, `#faq`).
- Использовать существующие CSS-классы (`container-nexor`, `heading-section`, `nexor-*`).
- Санитизировать пользовательский ввод через WP API (`sanitize_text_field`, `esc_html`, …).
- Проверять `npm run test:unit` после изменений в `class-nexor-enhancements.php`.
- Сверяться с `docs/ADMIN-GUIDE.md` при изменении админ-полей.
- Обновлять `docs/CHANGELOG.md` и `VERSION` при релизных изменениях (если задача это подразумевает).

### Не делать

- Не коммитить секреты (`.env`, `wp-config.php`, токены Telegram).
- Не менять формулу калькулятора без явного запроса (ставки в настройках — OK).
- Не включать акции/popup на production без согласования (`enabled=0` по умолчанию для popup и prices/video).
- Не перезаписывать SEO существующих страниц при повторной активации плагина.
- Не добавлять произвольный HTML в options enhancements — только текст/числа/URL/списки.
- Не удалять `MANIFEST.sha256` entries без пересборки манифеста.
- Не форс-пушить в main без явного запроса.

### Версии в коде

В проекте несколько констант версий — при релизе синхронизируйте:

- `VERSION` (корень)
- `package.json` → `version`
- `style.css` → `Version`
- `NEXOR_THEME_VERSION` в `functions.php`
- `Nexor_Enhancements::VERSION` в `class-nexor-enhancements.php`
- `Nexor_Core::VERSION` в `nexor-core.php` (может отставать — это версия плагина WP)

---

## Типичные сценарии

### Добавить секцию на главную

1. Option + sanitize + render-метод в `class-nexor-enhancements.php`
2. Вставка в `inject_frontend_content()` в нужном месте (сохранить порядок секций — unit-тест проверяет)
3. Стили в `nexor.css`, интерактив в `nexor.js` при необходимости
4. Поля админки в `render_*_admin()`
5. Обновить `enhancements-unit.php` и при необходимости audit-скрипт

### Изменить страницу услуги

1. Контент: редактор WP или `content/{slug}.html`
2. Мета: блок «Структура услуги и перелинковка» в админке
3. Оболочка hero/standards: `service_page_shell()` в enhancements
4. Аудит: `tests/service-pages-production-audit.mjs`

### Отладка заявок

1. **Заявки** в админке WP — статус `telegram_sent` / `telegram_error`
2. Кнопка «Повторно отправить в Telegram»
3. `error_log` при ошибке Telegram (без секретов в UI)
4. Проверить `NEXOR_TELEGRAM_*` в wp-config

### Диагностика enhancements

```bash
docker compose exec wordpress wp nexor enhancements-diagnostic --allow-root
```

---

## Связанная документация

- [docs/ADMIN-GUIDE.md](docs/ADMIN-GUIDE.md) — для администратора WordPress
- [docs/CHANGELOG.md](docs/CHANGELOG.md) — история релизов
- [.cursor/rules/](.cursor/rules/) — правила Cursor (если добавлены)

---

## Краткий чеклист перед PR

- [ ] `npm run test:unit` — зелёный
- [ ] `npm run check:js` — без синтax-ошибок
- [ ] Slug услуг и URL не изменены
- [ ] Нет секретов в diff
- [ ] При UI-изменениях — responsive (1440 / 390) учтён
- [ ] CHANGELOG обновлён (для user-facing изменений)
