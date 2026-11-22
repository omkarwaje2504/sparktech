<?php /** Not found page. */ ?>
<section class="relative overflow-hidden bg-gray-950 text-white">
  <div class="pointer-events-none absolute -left-20 top-1/3 h-72 w-72 rounded-full bg-sky-500/15 blur-[120px]"></div>
  <div class="pointer-events-none absolute -right-16 bottom-0 h-72 w-72 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="container relative mx-auto flex min-h-[70vh] flex-col items-center justify-center px-4 pb-16 pt-40 text-center sm:px-6 lg:px-8">
    <?= eyebrow_chip('Error 404', 'dark', true, 'mb-5') ?>
    <h1 class="text-4xl font-bold md:text-6xl">Page not <span class="bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent">found</span></h1>
    <p class="mt-4 max-w-xl text-gray-300">The page you are looking for may have moved. Try one of these instead:</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <a href="<?= e(url('/')) ?>" class="rounded-full bg-sky-500 px-7 py-4 text-xs font-semibold uppercase tracking-[0.12em] text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400">Home</a>
      <a href="<?= e(url('/services/')) ?>" class="rounded-full border border-white/25 bg-white/5 px-7 py-4 text-xs font-semibold uppercase tracking-[0.12em] text-white transition hover:border-white/50 hover:bg-white/10">Services</a>
      <a href="<?= e(url('/contact/')) ?>" class="rounded-full border border-white/25 bg-white/5 px-7 py-4 text-xs font-semibold uppercase tracking-[0.12em] text-white transition hover:border-white/50 hover:bg-white/10">Contact</a>
    </div>
  </div>
</section>
