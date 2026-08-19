# AGENTS.md — Nexor WordPress

Документация для AI-агентов и разработчиков, работающих с репозиторием **nexorpro** — нативного WordPress-сайта [nexorpro.ru](https://nexorpro.ru) (компания Nexor, ремонт квартир и домов под ключ в Москве и МО).

**Текущая версия релиза:** `1.6.0` (`VERSION`, `package.json`, `style.css`, `NEXOR_THEME_VERSION`).  
**Схема enhancements:** `1.7.2` (`Nexor_Enhancements::VERSION`) — не путать с версией релиза.

---

## Что это за проект

Сайт мигрирован с React SPA на **native WordPress** (тема + плагин). Сохранены:

- все публичные URL и SEO-метаданные;
- калькулятор стоимости (серверная перепроверка формулы);
- формы заявок и интеграция с Telegram;
- CPT «Проекты» с галереями;
- Schema.org, sitemap, robots.

Контент страниц хранится как **HTML-разметка** (миграция из React). Шапка и часть секций главной вынесены в PHP `template-parts/`. Остальные динамические блоки (цены, бонусы, popup, этапы) генерируются плагином и встраиваются в HTML через фильтр `nexor_migrated_content`.

---

## Архитектура

```
┌─────────────────────────────────────────────────────────────┐
│  WordPress (PHP 8.2+, WP 6.6+)                              │
├─────────────────────────────────────────────────────────────┤
│  Тема nexor          │  Плагин nexor-core                   │
│  ─────────────────   │  ─────────────────                   │
│  Шаблоны PHP         │  CPT: nexor_project, nexor_lead       │
│  template-parts/     │  REST: /nexor/v1/lead, /calculate     │
│  Статический HTML    │  Настройки, SEO, Telegram, Schema     │
│  nexor.css / nexor.js│  Nexor_Enhancements (секции главной)  │
│  GSAP vendor         │                                       │
└─────────────────────────────────────────────────────────────┘
```

### Разделение ответственности

| Компонент | Путь | За что отвечает |
|-----------|------|-----------------|
| **Тема** | `package/wp-content/themes/nexor/` | Шаблоны, `template-parts/`, рендер мигрированного HTML, SEO в `<head>`, навигация, фронтенд JS/CSS, GSAP |
| **Плагин** | `package/wp-content/plugins/nexor-core/` | Бизнес-логика: заявки, калькулятор (REST), проекты, админка, инъекция секций |
| **Контент** | `package/wp-content/themes/nexor/content/*.html` | Исходная HTML-разметка страниц (seed при активации). Шапка, услуги, проекты и калькулятор главной сюда больше не входят |
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
│       │   ├── assets/       ← nexor.js, nexor.css, index-*.css, webp, vendor/gsap
│       │   ├── content/      ← *.html + metadata.json (seed)
│       │   ├── template-parts/
│       │   │   ├── site-header.php
│       │   │   ├── home-services-section.php
│       │   │   ├── home-projects-section.php
│       │   │   ├── home-calculator-section.php
│       │   │   └── home-budget-section.php
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
2. Вырезается встроенный `<header>` (`nexor_strip_embedded_header`) — шапка рендерится из `template-parts/site-header.php`;
3. Применяется фильтр `nexor_content_replacements` (телефон, email, соцсети из настроек);
4. Применяется фильтр `nexor_migrated_content` (инъекция секций из `Nexor_Enhancements`);
5. Подставляются `{{THEME_URI}}` и `url(/assets/...)` → пути темы.

**Не ломайте** существующие CSS-классы и `id` якорей в HTML главной и страниц услуг — PHP ищет их для вставки блоков (`#calculator`, `#cases`, `#about-company-nexor`, `#faq`, «Ремонт без неприятных сюрпризов»).

Уже сохранённый HTML в БД может содержать старые копии вынесенных секций (`#cases`, `#calculator`, `#budget-control`, пятишаговый блок этапов). Плагин **вырезает или заменяет** их при рендере — править нужно template-part / enhancements, а не устаревший `post_content`.

### 2. Template parts главной

Вынесенные секции рендерятся темой, плагин только собирает данные и вставляет HTML:

| Секция | Якорь | Template part | Рендер-хелпер темы | Данные |
|--------|-------|---------------|--------------------|--------|
| Шапка сайта | — | `site-header.php` | `header.php` → `get_template_part` | `nexor_contact_settings()`, `nexor_navigation_payload()` |
| Основные услуги | `#main-services` | `home-services-section.php` | `nexor_render_home_services_section()` | option `nexor_home_services` |
| Реализованные проекты | `#cases` | `home-projects-section.php` | `nexor_render_home_projects_section()` | option `nexor_home_projects` + CPT |
| Калькулятор | `#calculator` | `home-calculator-section.php` | `nexor_render_home_calculator_section()` | статичный intro; квиз гидрирует `nexor.js` |
| Смета | `#budget-control` | `home-budget-section.php` | `nexor_render_home_budget_section()` | option `nexor_budget_control` |

Новую editorial-секцию главной добавляй так же: template-part + `nexor_render_home_*()` в `functions.php` + вызов из `inject_frontend_content()`.

### 3. Nexor_Enhancements (динамические секции)

Класс `Nexor_Enhancements` в `class-nexor-enhancements.php`:

- Хранит настройки в отдельных WP options (`nexor_home_prices`, `nexor_promotions`, `nexor_home_services`, `nexor_home_projects`, `nexor_home_stages`, …);
- На главной **вставляет** секции в порядке (unit-тест проверяет): услуги → проекты → калькулятор → смета → цены → сроки → система Nexor → этапы → до/после → доп. услуги → бонусы → о компании;
- Собирает hero (`compose_home_hero()`, `hero_promotion()`);
- Секция этапов (`#stages`) — интерактивная карточка с круговым ползунком (GSAP Draggable + InertiaPlugin). Старый блок «Как мы делаем ремонт предсказуемым» (`nexor-process-section` / `#work-stages`) и мигрированные пять шагов **удалены** и вырезаются при рендере;
- На страницах услуг добавляет hero-shell, trust-блок, мета «Структура услуги»;
- На страницах проектов — блок «Связанные услуги».

Пустая или невалидная секция **полностью скрывается** (вместе с пунктом меню). Косметический ремонт (`cosmetic-remont`) на главной в карточках услуг не показывается.

Порядок секций на главной (якоря):

`#main-services` → `#cases` → `#calculator` → `#budget-control` → `#prices` → `#repair-timeline` → система Nexor → `#stages` → `#before-after` → `#additional-services` → `#promotions` → `#about-company-nexor` → `#faq`.

### 4. REST API

| Endpoint | Метод | Назначение |
|----------|-------|------------|
| `/wp-json/nexor/v1/lead` | POST | Создание заявки → CPT `nexor_lead` + Telegram |
| `/wp-json/nexor/v1/calculate` | POST | Расчёт калькулятора (серверная валидация) |

Фронтенд получает `restUrl` и `nonce` через `wp_localize_script` → объект `NexorSettings` в `nexor.js`.

Защита: WP REST nonce, проверка Origin, rate limit 5 запросов / 15 мин / IP, honeypot-поле `website`.

### 5. Custom Post Types

| CPT | Slug rewrite | Описание |
|-----|--------------|----------|
| `nexor_project` | `/projects/{slug}/` | Кейсы ремонта (галерея, мета-поля) |
| `nexor_lead` | — (private) | Заявки с сайта |

Таксономии `nexor_repair_type`, `nexor_property_type` — только для админки, публичные архивы редиректятся на `/projects/`.

### 6. Страницы услуг (фиксированные slug)

**Не менять slug** этих страниц:

- `remont-kvartir-pod-klyuch`
- `capital-remont`
- `design-remont`
- `remont-v-novostroyke`
- `cosmetic-remont` (не выводится на главной)
- `remont-domov-pod-klyuch`

Мета-поля услуг: `_nexor_service_summary`, `_nexor_service_composition`, `_nexor_service_faq` (формат `Вопрос | Ответ`), `_nexor_service_related_project_ids` и др.

### 7. Фронтенд JS (`nexor.js`)

Vanilla JS (~1400 строк), без bundler. GSAP подключается отдельно из `assets/vendor/gsap/minified/` (`gsap`, `ScrollTrigger`, `Draggable`, `InertiaPlugin`). Отвечает за:

- desktop/mobile навигацию и поиск (меню берётся из `NexorSettings.navigation`);
- калькулятор: гидрирует `#calculator .nexor-calculator` (7 шагов, POST на REST);
- модальные формы заявок с контекстом (бонус, доп. услуга, цена);
- popup с задержкой (`exitIntent` из настроек);
- слайдер ДО/ПОСЛЕ, countdown бонусов, IntersectionObserver / reveal-анимации;
- карточку этапов: круговой ползунок, пагинация, клавиатура, `prefers-reduced-motion`;
- accessibility: focus trap, Escape, `prefers-reduced-motion`.

При изменении API настроек обновляйте и PHP (`frontend_config()`), и JS.

### 8. Стили

- `assets/index-*.css` — скомпилированный Tailwind/design-system из миграции (hash в имени файла);
- `assets/nexor.css` — дополнения темы (enhancements, service pages, template-parts главной);
- `style.css` — только метаданные темы для WordPress.

---

## Где что менять

| Задача | Где править |
|--------|-------------|
| Шапка сайта | `template-parts/site-header.php` + `nexor_contact_settings()` / меню WP |
| Карточки услуг на главной | `template-parts/home-services-section.php` + option `nexor_home_services` |
| Карточки проектов на главной | `template-parts/home-projects-section.php` + option `nexor_home_projects` |
| Оболочка калькулятора (`#calculator`) | `template-parts/home-calculator-section.php` |
| Секция «Как мы держим смету» (`#budget-control`) | `template-parts/home-budget-section.php` + option `nexor_budget_control` |
| Квиз калькулятора / формула | `nexor.js` + REST `/calculate` в `nexor-core.php` (ставки — **Настройки → Nexor**) |
| Вёрстка/стили секций enhancements | `class-nexor-enhancements.php` (HTML) + `nexor.css` |
| Этапы (`#stages`, ползунок) | HTML в enhancements + CSS + GSAP-логика в `nexor.js` |
| Формы заявок, popup | `nexor.js` + REST `/lead` в `nexor-core.php` |
| SEO title/description/canonical | мета `_nexor_seo_*` или `page_seo_defaults()` в плагине |
| Контакты, ставки калькулятора | **Настройки → Nexor** (`nexor_settings` option) |
| Блоки главной (цены, бонусы, popup, услуги, проекты) | **Настройки → Nexor** (секции enhancements) |
| Hero и оставшийся статический HTML главной | контент страницы «Главная» в WP или `content/home.html` + `compose_home_hero()` |
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

Проверяет: seed акций, stable ID заявок, политику поиска, порядок секций на главной, UTF-8 в доп. услугах, инъекцию калькулятора из template-part.

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
- При правках HTML главной и template-parts — сохранять якоря (`#calculator`, `#cases`, `#main-services`, `#about-company-nexor`, `#faq`, `#stages`).
- Использовать существующие CSS-классы (`container-nexor`, `heading-section`, `nexor-*`).
- Соблюдать семантичность вёрстки (см. ниже).
- В CSS — пустая строка между правилами/селекторами (см. ниже).
- Санитизировать пользовательский ввод через WP API (`sanitize_text_field`, `esc_html`, …).
- Проверять `npm run test:unit` после изменений в `class-nexor-enhancements.php`.
- Сверяться с `docs/ADMIN-GUIDE.md` при изменении админ-полей.
- Обновлять `docs/CHANGELOG.md` и `VERSION` при релизных изменениях (если задача это подразумевает).

### CSS: отступы между селекторами

В `nexor.css` и других таблицах стилей темы/плагина:

- Между соседними правилами (блоками `селектор { … }`) всегда оставлять **одну пустую строку**.
- Не склеивать правила вплотную — это ухудшает diff и читаемость.
- Группу связанных селекторов (например, `.nexor-home-hero` и его `__*` элементы) тоже разделять пустой строкой между правилами; комментарий-секцию отделять пустой строкой сверху.
- Не раздувать файл лишними пустыми строками внутри одного `{ … }` и не делать по две+ пустых строки между правилами без нужды.

Пример:

```css
.nexor-home-hero h1 {
  font-size: 46px;
}

.nexor-home-hero__sub {
  font-size: 30px;
}
```

### CSS: без избыточных свойств

Не задавай свойства, которые **ничего не меняют** — они только шумят в diff и вводят в заблуждение.

- Не дублируй значения по умолчанию: `grid-row: 1; grid-column: 1` у первого ребёнка grid, `flex-shrink: 0` там, где shrink и так не сработает, `overflow: visible` без причины и т.п.
- Явное grid/flex-позиционирование пиши **только** когда оно реально переопределяет auto-placement (например, `stat-note` во 2-й колонке, а не под цифрой).
- Перед коммитом проверяй: если убрать свойство в DevTools и визуально/поведенчески ничего не меняется — свойство не добавляй и убирай существующее.

### CSS: `.container-nexor`

`.container-nexor` — только ограничивающий контейнер:

- центрирование (`margin-inline: auto`);
- ограничение ширины (`width` / `max-width`).

**Нельзя** навешивать на `.container-nexor` (в т.ч. через `.section > .container-nexor`) grid/flex-раскладку, `gap`, `padding`, `min-height`, позиционирование контента и прочие layout-стили секции.

Если секции нужен grid/flex — добавляй **внутреннюю обёртку** (например `.nexor-home-hero__layout`) и пиши layout-стили на неё.

```html
<section class="nexor-home-hero">
  <div class="container-nexor">
    <div class="nexor-home-hero__layout">
      <!-- promo / main / features -->
    </div>
  </div>
</section>
```

### CSS: responsive — без фиксированных размеров

Верстка должна быть отзывчивой. По возможности **избегай фиксированных `width` / `height` / `min-height` / `max-height` в px** для блоков контента и карточек.

- Размер пусть задаёт контент, flex/grid, `clamp()`, `%`, `svh`/`dvh`, `minmax(0, 1fr)`.
- Фиксированные размеры допустимы точечно: иконки, декоративные линии, focus-ring, мелкие UI-ассеты — не для секций и карточек, которые должны перестраиваться по viewport.
- Не «выпирать» карточку через `min-height: 460px` и не обрезать через `max-height: 560px` — это ломает mobile/tablet.

### Семантичность вёрстки

HTML — не только визуал. Разметка должна отражать смысл контента для SEO, a11y и поддержки.

- Один `<h1>` на страницу; подзаголовок — отдельный элемент (`<p>`, `<p class="…__sub">`), **не** внутри `<h1>`.
- Иерархия заголовков без пропусков уровней (`h1` → `h2` → `h3`); не использовать заголовки только ради стиля.
- Кнопки действий — `<button type="button">`; навигация и переходы — `<a href="…">`. Не подменять одно другим ради CSS.
- Списки преимуществ/пунктов — `<ul>`/`<ol>` + `<li>` (или явная группа с `aria-label`), не «голые» `<div>` без роли, если это действительно список.
- Секции страницы — `<section>` / `<aside>` с понятным заголовком или `aria-label`; декоративные обёртки — `<div>`.
- Таймеры, статус и live-области помечать ARIA (`role="timer"`, `aria-live` и т.п.) без дублирования скрытого мусорного текста в заголовках.
- Не класть блочный маркетинговый текст, CTA и метаданные внутрь заголовков «чтобы стили совпали» — стилизовать соседние элементы через CSS/grid.
- При правках hero и enhancements (`home.html`, `compose_home_hero()`, `hero_promotion()`, template-parts главной) сохранять эту семантику, даже если референс рисует всё одним визуальным блоком.

### Не делать

- Не коммитить секреты (`.env`, `wp-config.php`, токены Telegram).
- Не превращать `.container-nexor` в layout-обёртку секции (grid/flex/gap/padding/min-height) — только ширина и центрирование; layout — во внутренней обёртке.
- Не менять формулу калькулятора без явного запроса (ставки в настройках — OK).
- Не включать акции/popup на production без согласования (`enabled=0` по умолчанию для popup и prices/video).
- Не перезаписывать SEO существующих страниц при повторной активации плагина.
- Не добавлять произвольный HTML в options enhancements — только текст/числа/URL/списки.
- Не удалять `MANIFEST.sha256` entries без пересборки манифеста.
- Не форс-пушить в main без явного запроса.
- Не жертвовать семантикой ради пиксель-перфекта: сначала корректные теги, потом внешний вид через CSS.

### Версии в коде

В проекте несколько констант версий — при релизе синхронизируйте:

- `VERSION` (корень)
- `package.json` → `version`
- `style.css` → `Version`
- `NEXOR_THEME_VERSION` в `functions.php`
- `Nexor_Enhancements::VERSION` в `class-nexor-enhancements.php` — это **схема options** (сейчас `1.7.2`), её можно поднимать отдельно от релиза темы
- `Nexor_Core::VERSION` в `nexor-core.php` (может отставать — это версия плагина WP)

---

## Типичные сценарии

### Добавить секцию на главную

Editorial-секции вроде услуг/проектов/калькулятора:

1. Template-part `template-parts/home-{name}-section.php`
2. Хелпер `nexor_render_home_{name}_section()` в `functions.php`
3. Сбор данных + вызов из `inject_frontend_content()` (сохранить порядок — unit-тест проверяет)
4. Стили в `nexor.css`, интерактив в `nexor.js` при необходимости
5. Если секция управляется из админки — option + sanitize + `render_*_admin()`
6. Обновить `enhancements-unit.php` (в тестах заглушить рендер-хелпер) и при необходимости audit-скрипт

Секции только из options (цены, этапы, бонусы) — по-прежнему HTML в `class-nexor-enhancements.php` без template-part.

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
