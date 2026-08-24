<?php

if (! defined('ABSPATH')) {
  exit;
}

$heading = trim((string) ($args['heading'] ?? 'Частые вопросы о ремонте'));
$intro = trim((string) ($args['intro'] ?? 'Отвечаем на вопросы, которые чаще всего задают перед началом ремонта. Всё фиксируем в договоре — без скрытых условий и неожиданных расходов.'));
$items = $args['items'] ?? array(
  array(
    'question' => 'Как формируется стоимость ремонта?',
    'answer' => 'Стоимость зависит от площади, состояния объекта, объёма работ и выбранных материалов. После замера мы составляем подробную смету по этапам и фиксируем её в договоре — вы заранее понимаете бюджет ремонта.',
  ),
  array(
    'question' => 'Можно ли заранее понять примерный бюджет?',
    'answer' => 'Да. По телефону или в мессенджере уточним основные параметры и дадим ориентир по стоимости. Точную смету готовим после замера.',
  ),
  array(
    'question' => 'Гарантируете ли вы соблюдение сроков?',
    'answer' => 'Да. Сроки фиксируются в договоре и контролируются прорабом и техническим надзором. Мы планируем работы по графику, чтобы исключить простои и затягивание ремонта.',
  ),
  array(
    'question' => 'Что входит в ремонт «под ключ»?',
    'answer' => 'Полный цикл работ: демонтаж, подготовка оснований, инженерные коммуникации, черновая и чистовая отделка, установка сантехники, электрики и дверей, финальная уборка. При необходимости подключаем дизайн-проект и комплектацию материалов.',
  ),
  array(
    'question' => 'Кто контролирует качество работ?',
    'answer' => 'За объект отвечает прораб, а ключевые этапы дополнительно проверяет внутренний контроль качества. Перед сдачей проводим финальную проверку — вы принимаете результат без недочётов.',
  ),
  array(
    'question' => 'Работаете ли вы с материалами заказчика?',
    'answer' => 'Да. Можем работать с вашими материалами или полностью взять закупку на себя. Помогаем подобрать решения под бюджет и привозим материалы точно к этапам работ.',
  ),
  array(
    'question' => 'Какую гарантию вы даёте?',
    'answer' => 'Гарантия фиксируется в договоре. Если возникает гарантийный случай по нашей вине — устраняем его бесплатно и в приоритетном порядке.',
  ),
  array(
    'question' => 'Как происходит оплата?',
    'answer' => 'Оплата поэтапная — вы оплачиваете только выполненные и принятые работы. Все этапы и суммы заранее прописываются в договоре.',
  ),
);

$items = array_values(array_filter($items, static function ($item): bool {
  return is_array($item)
    && trim((string) ($item['question'] ?? '')) !== ''
    && trim((string) ($item['answer'] ?? '')) !== '';
}));

if (! $heading || ! $items) {
  return;
}
?>
<section id="faq" class="nexor-faq-section" aria-labelledby="nexor-faq-heading">
  <div class="container-nexor">
    <div class="nexor-faq__layout nexor-reveal">
      <div class="nexor-faq__header">
        <h2 id="nexor-faq-heading" class="heading-section"><?php echo esc_html($heading); ?></h2>
        <?php if ($intro) : ?>
          <p class="nexor-faq__intro"><?php echo esc_html($intro); ?></p>
        <?php endif; ?>
      </div>
      <ul class="nexor-faq__list">
        <?php foreach ($items as $index => $item) : ?>
          <?php
          $open = 0 === $index;
          $question_id = 'nexor-faq-question-' . ($index + 1);
          $answer_id = 'nexor-faq-answer-' . ($index + 1);
          ?>
          <li class="nexor-faq__item">
            <button
              type="button"
              class="nexor-faq__trigger"
              id="<?php echo esc_attr($question_id); ?>"
              aria-expanded="<?php echo $open ? 'true' : 'false'; ?>"
              aria-controls="<?php echo esc_attr($answer_id); ?>"
            >
              <span class="nexor-faq__marker" aria-hidden="true"></span>
              <span class="nexor-faq__question"><?php echo esc_html((string) $item['question']); ?></span>
              <span class="nexor-faq__toggle" aria-hidden="true"></span>
            </button>
            <div
              class="nexor-faq__answer"
              id="<?php echo esc_attr($answer_id); ?>"
              role="region"
              aria-labelledby="<?php echo esc_attr($question_id); ?>"
            >
              <div class="nexor-faq__panel">
                <p><?php echo esc_html((string) $item['answer']); ?></p>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
