const menu = document.querySelector('.mobile-nav');

if (menu) {
  const trigger = menu.querySelector('summary');
  menu.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !menu.open) return;
    menu.open = false;
    trigger.focus();
  });

  menu.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', () => {
      const destination = document.getElementById(link.hash.slice(1));
      menu.open = false;
      destination?.focus({ preventScroll: true });
    });
  });

  document.addEventListener('click', (event) => {
    if (menu.open && !menu.contains(event.target)) menu.open = false;
  });
}

if ('IntersectionObserver' in window && matchMedia('(prefers-reduced-motion: no-preference)').matches) {
  // Figures are visible by default. Only animate the ones approached on scroll.
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(({ target, isIntersecting }) => {
      if (!isIntersecting) return;
      target.classList.add('is-revealed');
      observer.unobserve(target);
    });
  }, { threshold: .18 });

  document.querySelectorAll('[data-reveal]').forEach((figure) => observer.observe(figure));
}
