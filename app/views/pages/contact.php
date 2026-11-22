<?php
/** Contact (ported from src/app/contact/*). Submissions go to /contact/submit and are stored as leads. */
$d = Content::doc('contact');
$site = Content::doc('site');
$c = $site->at('contact');
$info = $d->at('info');
$fp = $d->at('form_panel');
$f = $d->at('form');
$map = $d->at('map');
$qk = $d->at('quick');
$field = 'w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/30';
$sent = isset($_GET['sent']);
$failed = isset($_GET['error']);
$address = $c->str('address_title') . ', ' . implode(' ', $c->lines('address_lines'));
$formToken = form_token();
$interest = trim((string)($_GET['interest'] ?? ''));
$prefillMessage = $interest !== '' ? "I'm interested in your " . mb_substr($interest, 0, 120) . ' technology. Please share more details.' : '';
$fieldEdit = function (string $key, string $name, array $extra = []) use ($d) {
    return $d->edit(["form.{$key}_label" => ['text', 'Label'], "form.{$key}_placeholder" => ['text', 'Placeholder']]
        + $extra + ["form.err_{$key}" => ['text', 'Error message']], $name);
};
// Validation messages for the browser check (same rules as app/lib/validate.php).
$formMessages = [];
foreach (array_keys(LeadCheck::MESSAGES) as $key) {
    $formMessages[$key] = LeadCheck::message($key);
}
$formMessages['summary'] = $f->str('err_summary') ?: 'Please correct the highlighted fields.';
?>
<?php if ($d->shown('hero')) echo page_hero($d->at('hero'), '/contact/'); ?>

<?php if ($d->shown('info')): ?>
<section class="bg-white py-10"<?= $d->section('info', 'Contact info cards') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="-mt-24 grid gap-4 sm:grid-cols-2 lg:-mt-32 lg:grid-cols-4 relative z-10" data-reveal-group>
      <?php
      $cards = [
          ['pin', 'office_title', null, $site->edit(['contact.address_title' => ['text', 'Address first line'], 'contact.address_lines' => ['lines', 'Address lines (one per line)'],
              'contact.city' => ['text', 'City (for Google)'], 'contact.state' => ['text', 'State (for Google)'], 'contact.postal_code' => ['text', 'PIN / postal code (for Google)']], 'Address'),
              '<p>' . $c->e('address_title') . '</p>' . implode('', array_map(function ($l) { return '<p>' . e($l) . '</p>'; }, $c->lines('address_lines')))],
          ['cog', 'phone_title', $c->str('phone_href'), $site->edit(['contact.phone_display' => ['text', 'Phone (shown)'], 'contact.phone_href' => ['url', 'Phone link (tel:+91...)']], 'Phone'), $c->v('phone_display')],
          ['engineering', 'email_title', $c->str('email_href'), $site->edit(['contact.email_display' => ['text', 'E-mail (shown)'], 'contact.email_href' => ['url', 'E-mail link (mailto:...)']], 'E-mail'), $c->v('email_display')],
          ['clock', 'hours_title', null, $site->edit(['contact.hours' => ['text', 'Working hours']], 'Working hours'), '<p>' . $c->v('hours') . '</p>'],
      ];
      foreach ($cards as $card): ?>
      <div data-reveal class="group relative overflow-hidden rounded-3xl border border-gray-200 bg-gray-50 p-6 text-center shadow-[0_20px_50px_rgba(15,23,42,0.08)] transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_40px_80px_rgba(15,23,42,0.12)]">
        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 to-lime-500 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[#3CAAE5] to-[#99C71E] text-white transition-transform duration-500 group-hover:scale-110"><?= glyph($card[0], 'h-7 w-7') ?></div>
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900"><?= $info->t($card[1], 'Card title') ?></h3>
        <div class="mt-3 space-y-0.5 text-sm leading-relaxed text-gray-500"<?= $card[3] ?>>
          <?php if ($card[2]): ?><a href="<?= e(url($card[2])) ?>" class="transition-colors hover:text-sky-600"><?= $card[4] ?></a><?php else: ?><?= $card[4] ?><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="bg-gray-50 py-10" id="enquiry">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid gap-8 lg:grid-cols-2">
      <div data-reveal="left">
        <div class="grid gap-6 overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.08)] md:grid-cols-[0.85fr_1.15fr]">
          <div class="bg-gray-950 p-6 text-white lg:p-8">
            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-300"><?= $fp->t('eyebrow', 'Eyebrow label') ?></p>
            <h2 class="mt-3 text-2xl font-bold"><?= $fp->t('title', 'Heading') ?></h2>
            <p class="mt-4 text-sm leading-relaxed text-gray-300"><?= $fp->ta('text', 'Text') ?></p>
            <ul class="mt-6 space-y-3"<?= $fp->edit(['tips' => ['lines', 'Tips (one per line)']], 'Tips') ?>>
              <?php foreach ($fp->lines('tips') as $tip): ?>
              <li class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm leading-6 text-gray-200"><span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-lime-400"></span><?= e($tip) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="p-6 lg:p-8">
            <div data-contact-success role="status" aria-live="polite" class="<?= $sent ? '' : 'hidden ' ?>py-12 text-center">
              <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-r from-[#3CAAE5] to-[#99C71E]" aria-hidden="true"><?= check_icon('h-10 w-10 text-white') ?></div>
              <h3 class="text-2xl font-bold text-gray-900"><?= $f->t('success_title', 'Success title') ?></h3>
              <p class="mt-2 text-gray-600"><?= $f->ta('success_text', 'Success text') ?></p>
              <a href="<?= e(url('/contact/#enquiry')) ?>" data-contact-again class="mt-6 inline-block text-sm font-semibold text-sky-600 hover:underline"><?= $f->t('again', '"Send another" link') ?></a>
            </div>
            <form data-contact-form method="post" action="<?= e(url('/contact/submit')) ?>" class="<?= $sent ? 'hidden ' : '' ?>space-y-5" novalidate
                  aria-label="<?= $fp->e('title') ?>" data-messages="<?= e(json_encode($formMessages, JSON_UNESCAPED_UNICODE)) ?>">
              <input type="hidden" name="_ft" value="<?= e($formToken) ?>">
              <input type="hidden" name="page" value="<?= e(current_path()) ?>">
              <input type="hidden" name="_vid" value="">
              <input type="hidden" name="_sid" value="">
              <input type="hidden" name="_h" value="">
              <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
              <p data-contact-error role="alert" class="<?= $failed ? '' : 'hidden ' ?>rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= $f->e('error') ?></p>
              <div class="grid gap-4 md:grid-cols-2">
                <div<?= $fieldEdit('name', 'Name field') ?>>
                  <label for="cf-name" class="mb-2 block text-sm font-medium text-gray-700"><?= $f->v('name_label') ?></label>
                  <input type="text" id="cf-name" name="name" required aria-required="true" aria-describedby="cf-name-err" minlength="2" maxlength="80" autocomplete="name" autocapitalize="words" spellcheck="false" class="<?= $field ?>" placeholder="<?= $f->e('name_placeholder') ?>">
                  <p id="cf-name-err" class="st-field-error" aria-live="polite"></p>
                </div>
                <div<?= $fieldEdit('email', 'E-mail field') ?>>
                  <label for="cf-email" class="mb-2 block text-sm font-medium text-gray-700"><?= $f->v('email_label') ?></label>
                  <input type="email" id="cf-email" name="email" required aria-required="true" aria-describedby="cf-email-err" maxlength="190" autocomplete="email" inputmode="email" autocapitalize="off" spellcheck="false" class="<?= $field ?>" placeholder="<?= $f->e('email_placeholder') ?>">
                  <p id="cf-email-err" class="st-field-error" aria-live="polite"></p>
                </div>
              </div>
              <div class="grid gap-4 md:grid-cols-2">
                <div<?= $fieldEdit('phone', 'Phone field') ?>>
                  <label for="cf-phone" class="mb-2 block text-sm font-medium text-gray-700"><?= $f->v('phone_label') ?></label>
                  <input type="tel" id="cf-phone" name="phone" required aria-required="true" aria-describedby="cf-phone-err" maxlength="26" autocomplete="tel" inputmode="tel" class="<?= $field ?>" placeholder="<?= $f->e('phone_placeholder') ?>">
                  <p id="cf-phone-err" class="st-field-error" aria-live="polite"></p>
                </div>
                <div<?= $fieldEdit('company', 'Company field') ?>>
                  <label for="cf-company" class="mb-2 block text-sm font-medium text-gray-700"><?= $f->v('company_label') ?></label>
                  <input type="text" id="cf-company" name="company" aria-describedby="cf-company-err" maxlength="120" autocomplete="organization" class="<?= $field ?>" placeholder="<?= $f->e('company_placeholder') ?>">
                  <p id="cf-company-err" class="st-field-error" aria-live="polite"></p>
                </div>
              </div>
              <div<?= $d->edit(['form.service_label' => ['text', 'Label'], 'form.service_placeholder' => ['text', 'First option text'], 'form.services' => ['lines', 'Service options (one per line)'], 'form.err_service' => ['text', 'Error message']], 'Service field') ?>>
                <label for="cf-service" class="mb-2 block text-sm font-medium text-gray-700"><?= $f->v('service_label') ?></label>
                <select id="cf-service" name="service" required aria-required="true" aria-describedby="cf-service-err" class="<?= $field ?>">
                  <option value=""><?= $f->e('service_placeholder') ?></option>
                  <?php foreach ($f->lines('services') as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?>
                </select>
                <p id="cf-service-err" class="st-field-error" aria-live="polite"></p>
              </div>
              <div<?= $fieldEdit('message', 'Message field', ['form.message_hint' => ['text', 'Hint under the box']]) ?>>
                <label for="cf-message" class="mb-2 block text-sm font-medium text-gray-700"><?= $f->v('message_label') ?></label>
                <textarea id="cf-message" name="message" required aria-required="true" aria-describedby="cf-message-hint cf-message-err" rows="5" minlength="<?= LeadCheck::MESSAGE_MIN ?>" maxlength="<?= LeadCheck::MESSAGE_MAX ?>" class="<?= $field ?> resize-none" placeholder="<?= $f->e('message_placeholder') ?>"><?= e($prefillMessage) ?></textarea>
                <div class="flex items-start justify-between gap-3">
                  <p id="cf-message-hint" class="mt-1.5 text-xs text-gray-400"><?= $f->v('message_hint') ?></p>
                  <p class="st-count" data-count-for="cf-message" aria-hidden="true"></p>
                </div>
                <p id="cf-message-err" class="st-field-error" aria-live="polite"></p>
              </div>
              <button type="submit" data-label-sending="<?= $f->e('sending') ?>"<?= $d->edit(['form.submit' => ['text', 'Button text'], 'form.sending' => ['text', 'Text while sending'], 'form.error' => ['textarea', 'Error message'], 'form.err_summary' => ['text', 'Message when fields need fixing']], 'Submit button') ?>
                      class="flex w-full items-center justify-center gap-3 rounded-full bg-sky-500 px-7 py-4 text-xs font-semibold uppercase tracking-[0.12em] text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-70">
                <span data-submit-label><?= $f->v('submit') ?></span><?= arrow_icon('h-4 w-4') ?>
              </button>
            </form>
          </div>
        </div>
      </div>

      <div data-reveal="right" class="space-y-6">
        <div class="h-80 overflow-hidden rounded-[2rem] border border-gray-200 shadow-[0_30px_80px_rgba(15,23,42,0.08)]"<?= $map->edit(['embed' => ['url', 'Google Maps embed URL (Share → Embed a map → copy the src="..." link)']], 'Map') ?>>
          <iframe src="<?= $map->url('embed') ?>" width="100%" height="100%" style="border:0" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map: <?= $site->e('brand.name') ?> office, <?= $c->e('city') ?>"<?= edit_mode() ? ' class="pointer-events-none"' : '' ?>></iframe>
        </div>
        <?php foreach ([['address_label', $address], ['primary_label', $c->str('phone_display') . ' | ' . $c->str('email_display')]] as $row): ?>
        <div class="flex items-start gap-4 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#3CAAE5] to-[#99C71E] text-white"><?= glyph('pin', 'h-6 w-6') ?></div>
          <div>
            <h4 class="font-bold text-gray-900"><?= $map->t($row[0], 'Label') ?></h4>
            <p class="mt-1 text-sm text-gray-600"><?= e($row[1]) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($d->shown('quick')): ?>
<section class="relative overflow-hidden bg-gray-950 py-10 text-white"<?= $d->section('quick', 'Quick connect') ?>>
  <div class="pointer-events-none absolute -left-24 top-0 h-96 w-96 rounded-full bg-sky-500/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-8"><?= section_heading($qk, 'dark') ?></div>
    <div class="flex flex-wrap justify-center gap-4" data-reveal-group<?= $qk->lst('channels', 'button') ?>>
      <?php foreach ($qk->each('channels') as $i => $ch): $ext = is_external_href($ch->str('href')); ?>
      <a data-reveal href="<?= $ch->url('href') ?>"<?= $ext ? ' target="_blank" rel="noopener noreferrer"' : '' ?><?= $qk->item($i) ?><?= $ch->edit(['label' => ['text', 'Button text'], 'href' => ['url', 'Link'], 'glyph' => ['icon', 'Icon']], 'Channel button') ?>
         class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/5 px-7 py-4 text-sm font-semibold text-white backdrop-blur-md transition hover:border-sky-400 hover:bg-white/10">
        <?= glyph($ch->str('glyph'), 'h-5 w-5 text-sky-300') ?><?= $ch->v('label') ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($d) ?>

<?php if ($d->shown('cta')) echo cta_band($d->at('cta')); ?>
