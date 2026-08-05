(() => {
  'use strict';
  document.documentElement.classList.add('nexor-js');
  const cfg = window.NexorSettings || {};
  const fallbackServices = [
    { label: 'Ремонт квартир под ключ', url: '/remont-kvartir-pod-klyuch/' },
    { label: 'Капитальный ремонт', url: '/capital-remont/' },
    { label: 'Дизайнерский ремонт', url: '/design-remont/' },
    { label: 'Ремонт в новостройке', url: '/remont-v-novostroyke/' },
    { label: 'Косметический ремонт', url: '/cosmetic-remont/' },
    { label: 'Ремонт домов под ключ', url: '/remont-domov-pod-klyuch/' },
  ];
  const fallbackItems = [
    { label: 'Калькулятор', url: '/#calculator' },
    { label: 'Проекты', url: '/projects/' },
    { label: 'О компании', url: '/#about-company-nexor' },
    { label: 'FAQ', url: '/#faq' },
  ];
  const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
  const lock = value => document.documentElement.classList.toggle('nexor-lock', value);

  function setupNavigation() {
    const navigation = cfg.navigation || {},
      primary = navigation.primary || {},
      mobile = navigation.mobile || primary;
    const searchAction = cfg.enhancements?.searchUrl || '/';
    const searchValue = new URLSearchParams(location.search).get('s') || '';
    const searchForm = `<form class="nexor-search" role="search" method="get" action="${esc(searchAction)}"><label><span class="nexor-sr-only">Поиск по сайту</span><input type="search" name="s" value="${esc(searchValue)}" placeholder="Поиск" autocomplete="off"></label><button class="nexor-search__submit" type="submit" aria-label="Найти"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></button></form>`;
    const primaryServices = primary.services?.length ? primary.services : fallbackServices;
    const mobileServices = mobile.services?.length ? mobile.services : primaryServices;
    const primaryItems = [...(primary.items?.length ? primary.items : fallbackItems), ...(navigation.sectionLinks || [])];
    const mobileItems = [...(mobile.items?.length ? mobile.items : primaryItems), ...(navigation.sectionLinks || [])].filter(
      (item, index, list) => list.findIndex(other => other.url === item.url) === index,
    );
    const links = items => items.map(item => `<a href="${esc(item.url)}">${esc(item.label)}</a>`).join('');
    const mobileTrigger = document.querySelector('header .lucide-menu')?.closest('button');
    mobileTrigger?.classList.add('nexor-mobile-trigger');
    let closeMobile = () => {};
    if (mobileTrigger) {
      mobileTrigger.setAttribute('aria-label', 'Открыть меню');
      mobileTrigger.setAttribute('aria-expanded', 'false');
      mobileTrigger.setAttribute('aria-controls', 'nexor-mobile-navigation');
      const panel = document.createElement('div');
      panel.id = 'nexor-mobile-navigation';
      panel.className = 'nexor-mobile-menu';
      panel.hidden = true;
      panel.setAttribute('role', 'dialog');
      panel.setAttribute('aria-modal', 'true');
      panel.setAttribute('aria-label', 'Меню сайта');
      panel.innerHTML = `<div class="nexor-mobile-menu__top"><strong>Nexor</strong><button type="button" class="nexor-mobile-menu__close" aria-label="Закрыть меню">×</button></div>${searchForm}<nav aria-label="Мобильная навигация"><button type="button" class="nexor-mobile-menu__toggle" aria-expanded="false" aria-controls="nexor-mobile-services">Услуги</button><div id="nexor-mobile-services" class="nexor-mobile-menu__services" hidden>${links(mobileServices)}</div>${links(mobileItems)}<a href="tel:+79260832324">+7 (926) 083-23-24</a></nav>`;
      document.body.append(panel);
      closeMobile = (restore = true) => {
        if (panel.hidden) return;
        panel.hidden = true;
        lock(false);
        mobileTrigger.setAttribute('aria-expanded', 'false');
        if (restore) mobileTrigger.focus();
      };
      mobileTrigger.addEventListener('click', () => {
        panel.hidden = false;
        lock(true);
        mobileTrigger.setAttribute('aria-expanded', 'true');
        panel.querySelector('.nexor-mobile-menu__close').focus();
      });
      panel.querySelector('.nexor-mobile-menu__close').addEventListener('click', () => closeMobile());
      panel.querySelectorAll('a').forEach(a => a.addEventListener('click', () => closeMobile(false)));
      panel.querySelector('.nexor-mobile-menu__toggle').addEventListener('click', e => {
        const box = panel.querySelector('.nexor-mobile-menu__services');
        box.hidden = !box.hidden;
        e.currentTarget.setAttribute('aria-expanded', String(!box.hidden));
      });
      panel.addEventListener('keydown', e => {
        if (e.key !== 'Tab') return;
        const focusable = [...panel.querySelectorAll('a[href],button:not([disabled])')].filter(el => !el.closest('[hidden]'));
        const first = focusable[0],
          last = focusable.at(-1);
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      });
    }
    document.querySelectorAll('.nexor-search input[type="search"]').forEach(input =>
      input.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
          e.preventDefault();
          input.value = '';
          input.blur();
        }
      }),
    );
    const serviceButton = [...document.querySelectorAll('header button')].find(b => b.textContent.trim().startsWith('Услуги'));
    let closeMega = () => {};
    if (serviceButton) {
      const desktopNav = serviceButton.closest('nav[aria-label="Main"]')?.parentElement;
      if (desktopNav) {
        desktopNav.classList.add('nexor-desktop-nav');
        desktopNav.nextElementSibling?.classList.add('nexor-desktop-contact');
        const template = desktopNav.querySelector(':scope > a');
        desktopNav.querySelectorAll(':scope > a').forEach(a => a.remove());
        primaryItems.forEach(item => {
          const a = document.createElement('a');
          a.href = item.url;
          a.textContent = item.label;
          a.className = template?.className || 'text-sm font-medium text-muted-foreground hover:text-foreground';
          desktopNav.append(a);
        });
        desktopNav.insertAdjacentHTML('beforeend', searchForm);
      }
      const mega = document.createElement('nav');
      mega.id = 'nexor-services-menu';
      mega.className = 'nexor-mega';
      mega.hidden = true;
      mega.setAttribute('aria-label', 'Услуги');
      mega.innerHTML = links(primaryServices);
      document.body.append(mega);
      serviceButton.setAttribute('type', 'button');
      serviceButton.setAttribute('aria-haspopup', 'true');
      serviceButton.setAttribute('aria-controls', mega.id);
      serviceButton.setAttribute('aria-expanded', 'false');
      const position = () => {
        const r = serviceButton.getBoundingClientRect();
        mega.style.top = `${Math.round(r.bottom + 12)}px`;
        mega.style.left = `${Math.min(Math.max(20, Math.round(r.left)), Math.max(20, innerWidth - mega.offsetWidth - 20))}px`;
      };
      const open = (focus = false) => {
        mega.hidden = false;
        position();
        serviceButton.setAttribute('aria-expanded', 'true');
        if (focus) mega.querySelector('a')?.focus();
      };
      closeMega = (restore = false) => {
        if (mega.hidden) return;
        mega.hidden = true;
        serviceButton.setAttribute('aria-expanded', 'false');
        if (restore) serviceButton.focus();
      };
      serviceButton.addEventListener('click', e => {
        e.stopPropagation();
        mega.hidden ? open(false) : closeMega(false);
      });
      serviceButton.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          open(true);
        }
      });
      mega.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
          e.preventDefault();
          closeMega(true);
        }
      });
      document.addEventListener('click', e => {
        if (!mega.contains(e.target) && e.target !== serviceButton) closeMega(false);
      });
      window.addEventListener('resize', () => {
        if (!mega.hidden) position();
      });
    }
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        closeMega(true);
        closeMobile(true);
      }
    });
  }

  function formMarkup(source, project = '', context = {}) {
    const projectFields =
      source === 'Проект'
        ? `<label>Тип объекта<select name="object_type" required><option value="">Выберите</option><option>Квартира</option><option>Дом</option></select></label><label>Площадь<input name="area" inputmode="decimal" required></label><label>Тип ремонта<select name="repair_type" required><option>Капитальный</option><option>Дизайнерский</option><option>Косметический</option></select></label>`
        : `<label>Адрес или район<input name="address" autocomplete="street-address"></label>`;
    const contextFields = Object.entries(context)
      .filter(([key]) => ['additional_service_id', 'promotion_id', 'price_row_id'].includes(key))
      .map(([key, value]) => `<input type="hidden" name="${key}" value="${esc(value)}">`)
      .join('');
    return `<form class="nexor-form" data-source="${esc(source)}"><input type="text" name="website" class="nexor-hp" tabindex="-1" autocomplete="off"><input type="hidden" name="project" value="${esc(project)}">${contextFields}<label>Имя<input name="name" autocomplete="name" required maxlength="80"></label><label>Телефон<input name="phone" type="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required></label>${projectFields}<button type="submit">Отправить заявку</button><p class="nexor-form__legal">Нажимая кнопку, вы соглашаетесь с <a href="${cfg.privacy}">политикой конфиденциальности</a> и <a href="${cfg.consent}">обработкой персональных данных</a>.</p><p class="nexor-form__status" role="status" aria-live="polite"></p></form>`;
  }
  function openForm(source = 'Запись на замер', project = '', context = {}) {
    let modal = document.querySelector('.nexor-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.className = 'nexor-modal';
      modal.hidden = true;
      modal.setAttribute('role', 'dialog');
      modal.setAttribute('aria-modal', 'true');
      modal.innerHTML =
        '<div class="nexor-modal__panel"><button class="nexor-modal__close" aria-label="Закрыть">×</button><h2 class="heading-card mb-5">Оставить заявку</h2><div class="nexor-modal__body"></div></div>';
      document.body.append(modal);
      modal.addEventListener('click', e => {
        if (e.target === modal || e.target.closest('.nexor-modal__close')) closeModal();
      });
    }
    modal.querySelector('.nexor-modal__body').innerHTML = formMarkup(source, project, context);
    modal.hidden = false;
    lock(true);
    modal.querySelector('input:not([type=hidden]):not(.nexor-hp)')?.focus();
  }
  function closeModal() {
    const m = document.querySelector('.nexor-modal');
    if (m) m.hidden = true;
    lock(false);
  }
  async function submitLead(form, extra = {}) {
    const status = form.querySelector('.nexor-form__status');
    const button = form.querySelector('button[type=submit]');
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    status.classList.remove('is-error');
    status.textContent = 'Отправляем…';
    const body = Object.fromEntries(new FormData(form).entries());
    Object.assign(body, extra, { source: form.dataset.source });
    try {
      const res = await fetch(cfg.restUrl + 'lead', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce }, body: JSON.stringify(body) });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Не удалось отправить заявку');
      document.dispatchEvent(new CustomEvent('nexor:lead-success', { detail: { uuid: data.uuid || '' } }));
      location.href = data.redirect || cfg.thankYou;
    } catch (error) {
      status.classList.add('is-error');
      status.textContent = error.message;
      button.disabled = false;
      form.removeAttribute('aria-busy');
    }
  }
  function setupForms() {
    document.addEventListener('submit', e => {
      if (e.target.matches('.nexor-form')) {
        e.preventDefault();
        submitLead(e.target);
      }
    });
    document.addEventListener('click', e => {
      const trigger = e.target.closest('[data-nexor-context-type],[data-nexor-open-form]');
      if (!trigger) return;
      e.preventDefault();
      const type = trigger.dataset.nexorContextType,
        id = trigger.dataset.nexorContextId;
      const key = type === 'additional' ? 'additional_service_id' : type === 'promotion' ? 'promotion_id' : type === 'price' ? 'price_row_id' : '';
      openForm(type === 'promotion' ? 'Акция' : type === 'additional' ? 'Дополнительная услуга' : 'Услуга', '', key ? { [key]: id } : {});
    });
    document.querySelectorAll('button,a').forEach(el => {
      if (el.matches('[data-nexor-context-type],[data-nexor-open-form]')) return;
      const text = el.textContent.trim().toLowerCase();
      const measurement = text === 'записаться' || text.includes('записаться на замер') || text.includes('получить смету после замера');
      const project = text.includes('рассчитать') || text.includes('получить расч') || text.includes('узнать стоимость') || text.includes('обсудить ваш проект') || text.includes('обсудить ремонт');
      if (measurement) {
        el.addEventListener('click', e => {
          e.preventDefault();
          openForm();
        });
      } else if (project && !el.closest('#calculator')) {
        el.addEventListener('click', e => {
          e.preventDefault();
          openForm('Проект', document.querySelector('h1')?.textContent.trim() || '');
        });
      }
    });
  }

  function setupRevealAnimations() {
    const items = [...document.querySelectorAll('.nexor-reveal')];
    if (!items.length) return;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
      items.forEach(item => item.classList.add('is-visible'));
      return;
    }
    const observer = new IntersectionObserver(
      entries =>
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }),
      { rootMargin: '0px 0px -10% 0px', threshold: 0.08 },
    );
    items.forEach((item, index) => {
      item.style.setProperty('--reveal-delay', `${Math.min(index % 5, 4) * 55}ms`);
      observer.observe(item);
    });
  }
  function setupFaq() {
    document.querySelectorAll('#faq button').forEach(btn =>
      btn.addEventListener('click', () => {
        const card = btn.parentElement,
          answer = btn.nextElementSibling;
        if (!answer) return;
        const open = !answer.classList.contains('max-h-0');
        document.querySelectorAll('#faq button + div').forEach(x => {
          x.classList.remove('max-h-96');
          x.classList.add('max-h-0');
        });
        if (!open) {
          answer.classList.remove('max-h-0');
          answer.classList.add('max-h-96');
        }
      }),
    );
  }
  function setupBudgetAccordions() {
    document.querySelectorAll('.nexor-budget__toggle').forEach(button =>
      button.addEventListener('click', () => {
        if (!matchMedia('(max-width: 767px)').matches) return;
        const open = button.getAttribute('aria-expanded') === 'true';
        document.querySelectorAll('.nexor-budget__toggle').forEach(item => item.setAttribute('aria-expanded', 'false'));
        button.setAttribute('aria-expanded', String(!open));
      }),
    );
  }
  function setupTimeline() {
    document.querySelectorAll('.nexor-timeline-section').forEach(section =>
      section.querySelectorAll('[data-timeline-mode]').forEach(button =>
        button.addEventListener('click', () => {
          section.dataset.timelineActive = button.dataset.timelineMode;
          section.querySelectorAll('[data-timeline-mode]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        }),
      ),
    );
  }
  function setupSystemBlueprint() {
    const section = document.querySelector('#nexor-system');
    const grid = section?.querySelector('[class*="grid-cols-1"]');
    const cards = grid ? [...grid.children] : [];
    if (!section || cards.length < 4) return;
    grid.classList.add('nexor-blueprint-grid');
    cards.forEach((card, index) => {
      card.classList.add('nexor-blueprint-point', 'nexor-reveal');
      card.tabIndex = 0;
      card.setAttribute('role', 'button');
      card.setAttribute('aria-pressed', String(index === 0));
      card.addEventListener('click', () => cards.forEach(item => item.setAttribute('aria-pressed', String(item === card))));
      card.addEventListener('keydown', event => {
        if (!['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        card.click();
      });
    });
  }
  function setupProcessSteps() {
    const section = document.querySelector('#work-stages');
    const source = section?.querySelector('.grid-cols-5');
    const items = source ? [...source.children] : [];
    if (!section || items.length !== 5) return;
    const data = items.map((item, index) => ({
      number: String(index + 1).padStart(2, '0'),
      title: item.querySelector('h3')?.textContent.trim() || '',
      description: item.querySelector('p')?.textContent.trim() || '',
    }));
    const assetRoot = new URL('.', document.querySelector('img[src*="/wp-content/themes/nexor/assets/"]')?.src || location.href).href;
    const images = [
      'design-materials-samples-BJsw3pIb.webp',
      'remont-domov-engineering-BF5F-lJ4.webp',
      'calculator-section-interior.webp',
      'design-quality-control-DaTkqu7m.webp',
      'projects-showcase-interior-tGz2B9H-.webp',
    ];
    const experience = document.createElement('div');
    experience.className = 'nexor-process-experience nexor-reveal';
    experience.innerHTML = `<div class="nexor-process-experience__stage" tabindex="0" aria-label="Этапы работы Nexor"><div class="nexor-process-experience__copy"><p class="nexor-process-experience__eyebrow">Этап <strong data-stage-number>01</strong> из 05</p><h3 data-stage-title></h3><p data-stage-description></p><div class="nexor-process-experience__arrows"><button type="button" data-stage-prev aria-label="Предыдущий этап">&#8592;</button><button type="button" data-stage-next aria-label="Следующий этап">&#8594;</button></div></div><figure><img data-stage-image alt="" loading="lazy" width="1100" height="760"><figcaption>Система работы Nexor</figcaption></figure></div><div class="nexor-process-experience__nav" role="tablist" aria-label="Этапы ремонта">${data.map((stage, index) => `<button type="button" role="tab" data-stage-index="${index}" aria-selected="${index === 0}"><span>${stage.number}</span><strong>${esc(stage.title)}</strong></button>`).join('')}</div>`;
    source.parentElement.replaceWith(experience);
    const image = experience.querySelector('[data-stage-image]'),
      title = experience.querySelector('[data-stage-title]'),
      description = experience.querySelector('[data-stage-description]'),
      number = experience.querySelector('[data-stage-number]'),
      tabs = [...experience.querySelectorAll('[data-stage-index]')];
    let active = 0,
      startX = null;
    const render = index => {
      active = (index + data.length) % data.length;
      const stage = data[active];
      number.textContent = stage.number;
      title.textContent = stage.title;
      description.textContent = stage.description;
      image.src = new URL(images[active], assetRoot).href;
      image.alt = `${stage.title} — этап работы Nexor`;
      tabs.forEach((tab, tabIndex) => {
        tab.setAttribute('aria-selected', String(tabIndex === active));
        tab.tabIndex = tabIndex === active ? 0 : -1;
      });
      experience.style.setProperty('--stage-progress', `${(active / (data.length - 1)) * 100}%`);
    };
    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => render(index));
      tab.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? data.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + data.length) % data.length;
        render(next);
        tabs[next].focus();
      });
    });
    experience.querySelector('[data-stage-prev]').addEventListener('click', () => render(active - 1));
    experience.querySelector('[data-stage-next]').addEventListener('click', () => render(active + 1));
    const stage = experience.querySelector('.nexor-process-experience__stage');
    stage.addEventListener('pointerdown', event => {
      startX = event.clientX;
      stage.setPointerCapture?.(event.pointerId);
    });
    stage.addEventListener('pointerup', event => {
      if (startX === null) return;
      const delta = event.clientX - startX;
      startX = null;
      if (Math.abs(delta) > 45) render(active + (delta < 0 ? 1 : -1));
    });
    stage.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault();
      render(active + (event.key === 'ArrowRight' ? 1 : -1));
    });
    render(0);
  }
  function setupBeforeAfter() {
    const heading = [...document.querySelectorAll('main h2')].find(el => el.textContent.includes('сравните результат до и после'));
    const section = heading?.closest('section');
    const stage = section ? [...section.querySelectorAll('div')].find(el => el.classList.contains('cursor-ew-resize') && el.querySelector(':scope > [style*="clip-path"]')) : null;
    const before = stage?.querySelector(':scope > [style*="clip-path"]');
    const divider = stage ? [...stage.children].find(el => el.style.left && el.querySelector('.cursor-ew-resize')) : null;
    if (!stage || !before || !divider) return;
    section.classList.add('nexor-before-after-section');
    stage.classList.add('nexor-before-after');
    stage.tabIndex = 0;
    stage.setAttribute('role', 'slider');
    stage.setAttribute('aria-label', 'Сравнение результата до и после');
    stage.setAttribute('aria-valuemin', '0');
    stage.setAttribute('aria-valuemax', '100');
    const afterImage = [...stage.children].map(el => el.querySelector(':scope > img')).find(Boolean),
      beforeImage = before.querySelector('img');
    const details = stage.nextElementSibling,
      title = details?.querySelector('p:first-child'),
      description = details?.querySelector('p:nth-child(2)');
    const projects = {
      'project1-after-ByVdkCUc.webp': {
        before: 'project1-before-C7cKpHe8.webp',
        title: 'Гостиная в загородном доме · комплексный ремонт под ключ · ~200 м²',
        description:
          'Выполнили полный комплекс ремонтных работ в гостиной загородного дома: подготовка основания, отделка стен и потолка, монтаж освещения и чистовая отделка. Итог — светлое, аккуратное пространство под жизнь.',
        beforeAlt: 'Гостиная в загородном доме до ремонта — черновая отделка',
        afterAlt: 'Гостиная в загородном доме после ремонта — готовый интерьер',
      },
      'project2-after-CSGa2JQV.webp': {
        before: 'project2-before-DSft2Fs8.webp',
        title: 'Ванная комната в частном доме · дизайнерский ремонт',
        description: 'Показываем помещение до начала работ и готовый интерьер после полной отделки и установки сантехники.',
        beforeAlt: 'Ванная комната в частном доме до ремонта — черновое помещение',
        afterAlt: 'Ванная комната в частном доме после ремонта — готовый дизайнерский интерьер',
      },
      'project3-after-BSOUOgwz.webp': {
        before: 'project3-before-BAJg1JlV.webp',
        title: 'Кухня в частном доме · ремонт под ключ',
        description: 'Сравните исходное состояние помещения и результат после выполнения отделочных и монтажных работ.',
        beforeAlt: 'Кухня в частном доме до ремонта',
        afterAlt: 'Кухня в частном доме после ремонта — готовый интерьер',
      },
      'project4-after-zCG3XS9L.webp': {
        before: 'project4-before-BIKMWWXH.webp',
        title: 'Санузел · капитальный ремонт под ключ',
        description: 'Обновили отделку, сантехнику, освещение и организовали современную душевую зону.',
        beforeAlt: 'Санузел до ремонта — старая отделка',
        afterAlt: 'Санузел после ремонта под ключ — современная ванная комната',
      },
      'project5-bedroom-after-BoyIMRH2.webp': {
        before: 'project5-bedroom-before--dXBd2jU.webp',
        title: 'Спальня · дизайнерский ремонт под ключ',
        description: 'Преобразили исходное помещение в современную спальню с продуманным освещением и отделкой.',
        beforeAlt: 'Спальня до ремонта — исходное состояние',
        afterAlt: 'Спальня после ремонта под ключ — современный дизайнерский интерьер',
      },
    };
    const projectAliases = {
      'project1-after.webp': 'project1-after-ByVdkCUc.webp',
      'project2-after.webp': 'project2-after-CSGa2JQV.webp',
      'project3-after.webp': 'project3-after-BSOUOgwz.webp',
      'project4-after.webp': 'project4-after-zCG3XS9L.webp',
      'project5-bedroom-after.webp': 'project5-bedroom-after-BoyIMRH2.webp',
    };
    let value = 50,
      dragging = false,
      moved = false;
    const render = next => {
      value = Math.min(100, Math.max(0, Math.round(next)));
      before.style.clipPath = `inset(0 ${100 - value}% 0 0)`;
      divider.style.left = `${value}%`;
      stage.setAttribute('aria-valuenow', String(value));
      stage.setAttribute('aria-valuetext', `${value}% изображения до ремонта`);
    };
    const fromPointer = e => {
      const rect = stage.getBoundingClientRect();
      render(((e.clientX - rect.left) / rect.width) * 100);
    };
    stage.addEventListener('pointerdown', e => {
      if (e.button !== undefined && e.button !== 0) return;
      dragging = true;
      moved = false;
      stage.setPointerCapture?.(e.pointerId);
      fromPointer(e);
      stage.focus({ preventScroll: true });
      e.preventDefault();
    });
    stage.addEventListener('pointermove', e => {
      if (!dragging) return;
      moved = true;
      fromPointer(e);
      e.preventDefault();
    });
    const stop = e => {
      if (!dragging) return;
      dragging = false;
      stage.releasePointerCapture?.(e.pointerId);
    };
    stage.addEventListener('pointerup', stop);
    stage.addEventListener('pointercancel', stop);
    stage.addEventListener(
      'click',
      e => {
        if (moved) {
          e.preventDefault();
          e.stopPropagation();
          moved = false;
        }
      },
      true,
    );
    stage.addEventListener('keydown', e => {
      let next = value;
      if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') next -= 5;
      else if (e.key === 'ArrowRight' || e.key === 'ArrowUp') next += 5;
      else if (e.key === 'Home') next = 0;
      else if (e.key === 'End') next = 100;
      else return;
      e.preventDefault();
      render(next);
    });
    const thumbnails = [...section.querySelectorAll('button')].filter(button => button.querySelector('img'));
    const thumbsRow = thumbnails[0]?.parentElement;
    let counter = null,
      activeProject = 0;
    if (thumbsRow) {
      const controls = document.createElement('div');
      controls.className = 'nexor-before-after__controls';
      controls.innerHTML =
        '<button type="button" data-project-prev aria-label="Предыдущий проект">&#8592;</button><span><strong data-project-current>01</strong> / ' +
        String(thumbnails.length).padStart(2, '0') +
        '</span><button type="button" data-project-next aria-label="Следующий проект">&#8594;</button>';
      thumbsRow.insertAdjacentElement('afterend', controls);
      counter = controls.querySelector('[data-project-current]');
      controls
        .querySelector('[data-project-prev]')
        .addEventListener('click', () => selectProject(thumbnails[(activeProject - 1 + thumbnails.length) % thumbnails.length], (activeProject - 1 + thumbnails.length) % thumbnails.length, false));
      controls.querySelector('[data-project-next]').addEventListener('click', () => selectProject(thumbnails[(activeProject + 1) % thumbnails.length], (activeProject + 1) % thumbnails.length, false));
    }
    const selectProject = (button, index, moveFocus = true) => {
      const thumbnail = button.querySelector('img'),
        filename = new URL(thumbnail.currentSrc || thumbnail.src, location.href).pathname.split('/').pop(),
        project = projects[projectAliases[filename] || filename];
      if (!project || !afterImage || !beforeImage) return;
      activeProject = index;
      const base = new URL('.', thumbnail.currentSrc || thumbnail.src),
        beforeFilename = projectAliases[filename] ? filename.replace('-after.webp', '-before.webp') : project.before;
      afterImage.src = new URL(filename, base).href;
      beforeImage.src = new URL(beforeFilename, base).href;
      afterImage.alt = project.afterAlt;
      beforeImage.alt = project.beforeAlt;
      if (title) title.textContent = project.title;
      if (description) description.textContent = project.description;
      if (counter) counter.textContent = String(index + 1).padStart(2, '0');
      thumbnails.forEach(item => {
        const active = item === button;
        item.classList.toggle('scale-105', active);
        item.classList.toggle('shadow-[0_10px_25px_rgba(0,0,0,0.08)]', active);
        item.classList.toggle('opacity-60', !active);
        item.setAttribute('aria-pressed', String(active));
        item.tabIndex = active ? 0 : -1;
      });
      stage.setAttribute('aria-label', `Сравнение до и после: ${project.title}`);
      render(50);
      if (moveFocus) button.focus({ preventScroll: true });
    };
    thumbnails.forEach((button, index) => {
      button.type = 'button';
      button.classList.add('nexor-before-after-thumb');
      button.setAttribute('aria-label', `Показать проект ${index + 1} в слайдере`);
      button.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        selectProject(button, index);
      });
      button.addEventListener('keydown', e => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
        e.preventDefault();
        const next = e.key === 'Home' ? 0 : e.key === 'End' ? thumbnails.length - 1 : (index + (e.key === 'ArrowRight' ? 1 : -1) + thumbnails.length) % thumbnails.length;
        selectProject(thumbnails[next], next);
      });
    });
    if (thumbnails.length) selectProject(thumbnails[0], 0, false);
    render(value);
  }
  function setupLightbox() {
    const box = document.createElement('div');
    box.className = 'nexor-lightbox';
    box.hidden = true;
    box.innerHTML = '<button aria-label="Закрыть">×</button><img alt="">';
    document.body.append(box);
    document.querySelectorAll('main img').forEach(img => {
      if (img.closest('#calculator,.nexor-before-after-section,.nexor-service-desk,.nexor-process-experience')) return;
      img.style.cursor = 'zoom-in';
      img.addEventListener('click', () => {
        box.querySelector('img').src = img.currentSrc || img.src;
        box.querySelector('img').alt = img.alt;
        box.hidden = false;
        lock(true);
      });
    });
    box.addEventListener('click', e => {
      if (e.target === box || e.target.tagName === 'BUTTON') {
        box.hidden = true;
        lock(false);
      }
    });
  }
  function setupProjectFilters() {
    if (!location.pathname.replace(/\/$/, '').endsWith('/projects')) return;
    const allButtons = [...document.querySelectorAll('main button')];
    const allChoices = allButtons.filter(b => b.textContent.trim() === 'Все');
    const typeButtons = [allChoices[0], ...allButtons.filter(b => ['Квартиры', 'Частные дома'].includes(b.textContent.trim()))].filter(Boolean);
    const areaButtons = [allChoices[1], ...allButtons.filter(b => /^(до 50 м²|50–80 м²|80–120 м²|более 120 м²)$/.test(b.textContent.trim()))].filter(Boolean);
    const showMore = allButtons.find(b => b.textContent.trim().toLowerCase().includes('показать ещё'));
    const cards = [...document.querySelectorAll('main a[href*="/projects/"]')].filter(a => a.querySelector('article img'));
    let type = 'все',
      area = 'все',
      expanded = false;
    const areaMatches = (value, filter) =>
      filter === 'все' ||
      (filter === 'до 50 м²' && value < 50) ||
      (filter === '50–80 м²' && value >= 50 && value < 80) ||
      (filter === '80–120 м²' && value >= 80 && value <= 120) ||
      (filter === 'более 120 м²' && value > 120);
    const render = () => {
      const filtered = type !== 'все' || area !== 'все';
      let visible = 0,
        total = 0;
      cards.forEach(card => {
        const text = card.textContent.toLowerCase(),
          matchArea = text.match(/(\d+(?:[,.]\d+)?)\s*м²/),
          value = Number((matchArea?.[1] || '0').replace(',', '.'));
        const matchType = type === 'все' || (type === 'квартиры' && text.includes('квартир')) || (type === 'частные дома' && text.includes('дом'));
        const match = matchType && areaMatches(value, area);
        if (match) total++;
        const show = match && (filtered || expanded || visible < 6);
        card.classList.toggle('nexor-filter-hidden', !show);
        if (match) visible++;
      });
      if (showMore) showMore.hidden = filtered || expanded || total <= 6;
      typeButtons.forEach(b => b.setAttribute('aria-pressed', String(b.textContent.trim().toLowerCase() === type)));
      areaButtons.forEach(b => b.setAttribute('aria-pressed', String(b.textContent.trim().toLowerCase() === area)));
    };
    typeButtons.forEach(b =>
      b.addEventListener('click', () => {
        type = b.textContent.trim().toLowerCase();
        expanded = false;
        render();
      }),
    );
    areaButtons.forEach(b =>
      b.addEventListener('click', () => {
        area = b.textContent.trim().toLowerCase();
        expanded = false;
        render();
      }),
    );
    showMore?.addEventListener('click', () => {
      expanded = true;
      render();
    });
    render();
  }

  function setupAdditionalServices() {
    document.querySelectorAll('.nexor-service-desk').forEach(desk => {
      const hotspots = [...desk.querySelectorAll('[data-service-panel]')],
        panels = [...desk.querySelectorAll('.nexor-service-panel')],
        drawer = desk.querySelector('.nexor-service-desk__drawer');
      if (!hotspots.length || !drawer) return;
      const open = id => {
        hotspots.forEach(button => button.setAttribute('aria-expanded', String(button.dataset.servicePanel === id)));
        panels.forEach(panel => panel.classList.toggle('is-active', panel.id === id));
        drawer.classList.add('is-open');
      };
      hotspots.forEach(button => {
        button.addEventListener('click', () => open(button.dataset.servicePanel));
        button.addEventListener('keydown', event => {
          if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
          event.preventDefault();
          const index = hotspots.indexOf(button),
            next = event.key === 'Home' ? 0 : event.key === 'End' ? hotspots.length - 1 : (index + (['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1) + hotspots.length) % hotspots.length;
          hotspots[next].focus();
          open(hotspots[next].dataset.servicePanel);
        });
      });
      panels.forEach(panel =>
        panel.querySelector('.nexor-service-panel__close')?.addEventListener('click', () => {
          drawer.classList.remove('is-open');
          hotspots.forEach(button => button.setAttribute('aria-expanded', 'false'));
        }),
      );
      open(hotspots[0].dataset.servicePanel);
      if (matchMedia('(max-width: 767px)').matches) drawer.classList.remove('is-open');
      try {
        if (!localStorage.getItem('nexor_service_desk_seen_v1')) {
          desk.classList.add('is-guided');
          localStorage.setItem('nexor_service_desk_seen_v1', '1');
          setTimeout(() => desk.classList.remove('is-guided'), 5500);
        }
      } catch {}
    });
  }

  function setupBonusDetails() {
    const triggers = [...document.querySelectorAll('[data-nexor-bonus-details]')];
    if (!triggers.length) return;
    const modal = document.createElement('div');
    modal.className = 'nexor-bonus-modal';
    modal.hidden = true;
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'nexor-bonus-modal-title');
    modal.innerHTML =
      '<div class="nexor-bonus-modal__panel"><button type="button" class="nexor-bonus-modal__close" aria-label="Закрыть">&#215;</button><p class="nexor-bonus-modal__eyebrow">Бонус для клиента</p><h2 id="nexor-bonus-modal-title"></h2><p data-bonus-modal-details></p><button type="button" class="nexor-bonus-modal__cta">Узнать условия</button></div>';
    document.body.append(modal);
    let lastFocus = null;
    const close = () => {
      if (modal.hidden) return;
      modal.hidden = true;
      lock(false);
      lastFocus?.focus?.();
    };
    triggers.forEach(trigger =>
      trigger.addEventListener('click', () => {
        lastFocus = trigger;
        modal.querySelector('h2').textContent = trigger.dataset.bonusTitle || '';
        modal.querySelector('[data-bonus-modal-details]').textContent = trigger.dataset.bonusDetails || '';
        const cta = modal.querySelector('.nexor-bonus-modal__cta');
        cta.textContent = trigger.dataset.bonusCta || 'Узнать условия';
        cta.dataset.nexorContextType = 'promotion';
        cta.dataset.nexorContextId = trigger.dataset.bonusId || '';
        modal.hidden = false;
        lock(true);
        modal.querySelector('.nexor-bonus-modal__close').focus();
      }),
    );
    modal.addEventListener('click', event => {
      if (event.target === modal || event.target.closest('.nexor-bonus-modal__close')) close();
    });
    modal.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
      }
      if (event.key === 'Tab') {
        const focusable = [...modal.querySelectorAll('button:not([disabled]),a[href]')],
          first = focusable[0],
          last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });
  }

  const quizSteps = [
    {
      key: 'propertyType',
      title: 'Какой у вас объект?',
      options: [
        ['new-apartment', 'Квартира в новостройке'],
        ['secondary-apartment', 'Квартира во вторичке'],
        ['house', 'Дом или таунхаус'],
        ['undecided', 'Пока выбираю'],
      ],
    },
    { key: 'area', title: 'Площадь объекта', dynamic: true },
    {
      key: 'repairFormat',
      title: 'Формат ремонта',
      options: [
        ['cosmetic', 'Косметический'],
        ['capital', 'Капитальный'],
        ['designer', 'Дизайнерский'],
        ['consultation', 'Нужна консультация'],
      ],
    },
    {
      key: 'currentState',
      title: 'Текущее состояние',
      options: [
        ['no-finish', 'Без отделки'],
        ['old-repair', 'Старый ремонт'],
        ['partial', 'Частично готов'],
      ],
    },
    {
      key: 'designProject',
      title: 'Есть дизайн-проект?',
      options: [
        ['have', 'Уже есть'],
        ['yes', 'Да, нужен'],
        ['not-needed', 'Не нужен'],
      ],
    },
    {
      key: 'timeline',
      title: 'Когда планируете начать?',
      options: [
        ['month', 'В ближайший месяц'],
        ['2-3-months', 'В течение 2–3 месяцев'],
        ['planning', 'Пока планирую'],
      ],
    },
    {
      key: 'priorities',
      title: 'Выберите два приоритета',
      multiple: true,
      options: [
        ['fixed-estimate', 'Фиксированная смета'],
        ['deadlines', 'Соблюдение сроков'],
        ['transparency', 'Прозрачный процесс'],
        ['minimal-involvement', 'Минимальное вовлечение'],
        ['quality-materials', 'Качественные материалы'],
        ['contract-work', 'Работа по договору'],
      ],
    },
  ];
  const areaOptions = a =>
    a.propertyType === 'house'
      ? [
          ['up-to-120', 'до 120 м²'],
          ['120-200', '120–200 м²'],
          ['200-350', '200–350 м²'],
          ['over-350', 'более 350 м²'],
        ]
      : [
          ['up-to-40', 'до 40 м²'],
          ['40-60', '40–60 м²'],
          ['60-90', '60–90 м²'],
          ['90-120', '90–120 м²'],
          ['over-120', 'более 120 м²'],
        ];
  function setupCalculator() {
    const old = document.querySelector('#calculator');
    if (!old) return;
    const section = document.createElement('section');
    section.id = 'calculator';
    section.className = 'section-padding bg-background';
    section.innerHTML = '<div class="container-nexor"><div class="nexor-calculator"></div></div>';
    old.replaceWith(section);
    const root = section.querySelector('.nexor-calculator');
    let step = -1;
    const answers = { priorities: [] };
    const render = () => {
      if (step < 0) {
        root.innerHTML =
          '<p class="text-sm text-muted-foreground mb-3">Расчёт стоимости</p><h2 class="heading-section mb-5">Рассчитайте ориентировочную стоимость ремонта</h2><p class="text-body text-muted-foreground mb-7">Ответьте на 7 коротких вопросов и получите ориентир по бюджету.</p><button type="button" class="nexor-calculator__button" data-next>Рассчитать стоимость</button>';
        root.querySelector('[data-next]').onclick = () => {
          step = 0;
          render();
        };
        return;
      }
      const s = quizSteps[step];
      const options = s.dynamic ? areaOptions(answers) : s.options;
      root.innerHTML = `<p>Шаг ${step + 1} из 7</p><div class="nexor-calculator__progress"><span style="width:${((step + 1) / 7) * 100}%"></span></div><h2 class="heading-card">${s.title}</h2><div class="nexor-calculator__options ${s.multiple ? 'nexor-calculator__priorities' : ''}">${options.map(o => `<button type="button" class="nexor-calculator__option ${(s.multiple ? answers.priorities.includes(o[0]) : answers[s.key] === o[0]) ? 'is-selected' : ''}" data-value="${o[0]}" aria-pressed="${s.multiple && answers.priorities.includes(o[0]) ? 'true' : 'false'}"><strong>${o[1]}</strong></button>`).join('')}</div>${s.multiple ? '<p class="nexor-calculator__hint" data-priority-hint>Выберите ещё два приоритета</p>' : ''}<div class="nexor-calculator__nav"><button type="button" class="nexor-calculator__button nexor-calculator__back" data-back>Назад</button>${s.multiple ? '<button type="button" class="nexor-calculator__button" data-result disabled>Показать расчёт</button>' : ''}</div>`;
      root.querySelector('[data-back]').onclick = () => {
        step = Math.max(-1, step - 1);
        render();
      };
      const result = root.querySelector('[data-result]'),
        hint = root.querySelector('[data-priority-hint]');
      const syncPriorities = () => {
        root.querySelectorAll('[data-value]').forEach(btn => {
          const selected = answers.priorities.includes(btn.dataset.value);
          btn.classList.toggle('is-selected', selected);
          btn.setAttribute('aria-pressed', String(selected));
        });
        if (result) result.disabled = answers.priorities.length !== 2;
        if (hint) {
          const left = 2 - answers.priorities.length;
          hint.textContent = left > 0 ? `Выберите ещё ${left === 1 ? 'один приоритет' : 'два приоритета'}` : 'Готово — можно показать расчёт';
        }
      };
      root.querySelectorAll('[data-value]').forEach(
        btn =>
          (btn.onclick = () => {
            if (s.multiple) {
              const v = btn.dataset.value,
                i = answers.priorities.indexOf(v);
              if (i >= 0) answers.priorities.splice(i, 1);
              else if (answers.priorities.length < 2) answers.priorities.push(v);
              syncPriorities();
            } else {
              answers[s.key] = btn.dataset.value;
              step++;
              render();
            }
          }),
      );
      if (result)
        result.onclick = () => {
          if (answers.priorities.length === 2) calculate();
        };
      syncPriorities();
    };
    const calculate = async () => {
      root.innerHTML = '<p class="text-center py-12">Считаем ориентировочную стоимость…</p>';
      try {
        const res = await fetch(cfg.restUrl + 'calculate', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce }, body: JSON.stringify(answers) });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Не удалось выполнить расчёт');
        root.innerHTML = `<h2 class="heading-card">Ориентировочная стоимость ремонта</h2><div class="nexor-calculator__result">${data.formatted}</div><p class="text-muted-foreground">Финальную стоимость определим после осмотра объекта и уточнения состава работ.</p><div class="mt-6"><button type="button" class="nexor-calculator__button" data-lead>Уточнить стоимость</button> <button type="button" class="nexor-calculator__button nexor-calculator__back" data-restart>Рассчитать ещё раз</button></div>`;
        root.querySelector('[data-lead]').onclick = () => openForm('Калькулятор', JSON.stringify({ ...answers, range: data.formatted }));
        root.querySelector('[data-restart]').onclick = () => {
          step = -1;
          Object.keys(answers).forEach(k => delete answers[k]);
          answers.priorities = [];
          render();
        };
      } catch (e) {
        root.innerHTML = '<p class="is-error">Не удалось рассчитать стоимость. Попробуйте ещё раз.</p><button type="button" class="nexor-calculator__button" data-retry>Повторить</button>';
        root.querySelector('[data-retry]').onclick = render;
      }
    };
    render();
  }
  function setupVideoFacades() {
    document.querySelectorAll('.nexor-video-facade[data-video-url]').forEach(button =>
      button.addEventListener('click', () => {
        const url = button.dataset.videoUrl;
        if (!/^https:\/\//i.test(url)) return;
        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.title = 'Видео Nexor';
        iframe.allow = 'fullscreen; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.loading = 'lazy';
        button.replaceWith(iframe);
      }),
    );
  }
  function setupBonusCountdown() {
    document.querySelectorAll('[data-nexor-deadline]').forEach(banner => {
      const deadline = Date.parse(banner.dataset.nexorDeadline || '');
      if (!Number.isFinite(deadline)) return;
      const fields = {
        days: banner.querySelector('[data-days]'),
        hours: banner.querySelector('[data-hours]'),
        minutes: banner.querySelector('[data-minutes]'),
        seconds: banner.querySelector('[data-seconds]'),
      };
      let timer = 0;
      const render = () => {
        const left = Math.max(0, deadline - Date.now());
        if (!left) {
          clearInterval(timer);
          banner.remove();
          return;
        }
        const total = Math.floor(left / 1000),
          days = Math.floor(total / 86400),
          hours = Math.floor((total % 86400) / 3600),
          minutes = Math.floor((total % 3600) / 60),
          seconds = total % 60;
        fields.days.textContent = String(days).padStart(2, '0');
        fields.hours.textContent = String(hours).padStart(2, '0');
        fields.minutes.textContent = String(minutes).padStart(2, '0');
        fields.seconds.textContent = String(seconds).padStart(2, '0');
      };
      render();
      timer = setInterval(render, 1000);
    });
  }
  function setupExitIntent() {
    const config = cfg.enhancements?.exitIntent || {};
    const key = `nexor_exit_${config.storage_version || '1'}`;
    let memory = '';
    const read = () => {
      try {
        return localStorage.getItem(key) || '';
      } catch {
        return (document.cookie.match(new RegExp('(?:^|; )' + key + '=([^;]*)')) || [])[1] || memory;
      }
    };
    const write = value => {
      memory = value;
      try {
        localStorage.setItem(key, value);
      } catch {
        document.cookie = `${key}=${encodeURIComponent(value)};path=/;max-age=${Math.max(86400, Number(config.suppression_days || 7) * 86400)};SameSite=Lax`;
      }
    };
    document.addEventListener('nexor:lead-success', () => write('lead'));
    if (!config.enabled) return;
    const blocked = () => {
      const value = decodeURIComponent(read());
      if (value === 'lead') return true;
      const until = Number(value || 0);
      return until > Date.now();
    };
    let shown = false,
      lastFocus = null;
    const dialog = document.createElement('div');
    dialog.className = 'nexor-exit';
    dialog.hidden = true;
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'nexor-exit-title');
    dialog.innerHTML = `<div class="nexor-exit__panel"><button type="button" class="nexor-exit__close" aria-label="Закрыть">×</button><h2 id="nexor-exit-title">${esc(config.heading)}</h2><p>${esc(config.body)}</p><p class="nexor-exit__offer">${esc(config.offer_text)}</p><button type="button" class="nexor-exit__cta">${esc(config.cta_label || 'Получить консультацию')}</button></div>`;
    document.body.append(dialog);
    const close = (suppress = true) => {
      if (dialog.hidden) return;
      dialog.hidden = true;
      lock(false);
      if (suppress) write(String(Date.now() + Number(config.suppression_days || 7) * 86400000));
      lastFocus?.focus?.();
    };
    const conflicts = () =>
      document.querySelector('.nexor-modal:not([hidden]),.nexor-lightbox:not([hidden]),.nexor-mobile-menu:not([hidden]),.nexor-mega:not([hidden]),.nexor-form[aria-busy="true"]') ||
      document.activeElement?.matches('input,textarea,select') ||
      document.querySelector('#calculator:hover,#calculator:focus-within');
    const open = () => {
      if (shown || blocked() || conflicts()) return;
      shown = true;
      lastFocus = document.activeElement;
      dialog.hidden = false;
      lock(true);
      dialog.querySelector('.nexor-exit__close').focus();
    };
    const tryOpen = () => {
      if (shown || blocked()) return;
      if (conflicts()) {
        setTimeout(tryOpen, 1000);
        return;
      }
      open();
    };
    setTimeout(tryOpen, Math.max(5, Number(config.minimum_delay_seconds || 20)) * 1000);
    dialog.addEventListener('click', e => {
      if (e.target === dialog || e.target.closest('.nexor-exit__close')) close(true);
      if (e.target.closest('.nexor-exit__cta')) {
        close(true);
        openForm('Exit intent');
      }
    });
    dialog.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        e.preventDefault();
        close(true);
      }
      if (e.key === 'Tab') {
        const f = [...dialog.querySelectorAll('button,a[href],input')].filter(x => !x.disabled),
          first = f[0],
          last = f.at(-1);
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    });
  }
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeModal();
      document.querySelector('.nexor-lightbox')?.setAttribute('hidden', '');
      lock(false);
    }
  });
  document.addEventListener('DOMContentLoaded', () => {
    setupNavigation();
    setupForms();
    setupFaq();
    setupBudgetAccordions();
    setupTimeline();
    setupSystemBlueprint();
    setupProjectFilters();
    setupCalculator();
    setupProcessSteps();
    setupBeforeAfter();
    setupAdditionalServices();
    setupBonusDetails();
    setupLightbox();
    setupVideoFacades();
    setupBonusCountdown();
    setupRevealAnimations();
    setupExitIntent();
  });
})();
