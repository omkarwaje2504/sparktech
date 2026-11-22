<?php
/** Floating WhatsApp button (ported from src/components/WhatsAppFloat.tsx). @var Doc $site */
$wa = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $site->str('contact.whatsapp_number'))
    . '?text=' . rawurlencode($site->str('contact.whatsapp_message'));
?>
<div class="fixed bottom-10 right-6 z-50 flex items-center gap-3 group">
  <div aria-hidden="true" class="pointer-events-none hidden md:block opacity-0 translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 bg-gray-900 text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-[0_10px_30px_rgba(15,23,42,0.12)] border border-white/10 select-none whitespace-nowrap">
    <?= $site->e('contact.whatsapp_tooltip') ?>
  </div>
  <a href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp (opens in a new tab)"<?= $site->edit([
      'contact.whatsapp_number' => ['text', 'WhatsApp number (with country code)'],
      'contact.whatsapp_message' => ['textarea', 'Pre-filled message'],
      'contact.whatsapp_tooltip' => ['text', 'Tooltip text'],
  ], 'WhatsApp button') ?>
     class="relative flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-[0_12px_36px_rgba(16,185,129,0.35)] transition-all duration-500 hover:scale-110 hover:bg-emerald-400 hover:shadow-[0_16px_40px_rgba(16,185,129,0.5)] active:scale-95">
    <span aria-hidden="true" class="absolute inset-0 -z-10 rounded-full bg-emerald-500/40 animate-ping opacity-75 group-hover:bg-emerald-400/40"></span>
    <svg class="h-7 w-7 transition-transform duration-500 group-hover:rotate-[360deg]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="<?= ST_SOCIAL['whatsapp'][1] ?>"/></svg>
  </a>
</div>
