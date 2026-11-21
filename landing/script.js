// Landing page statis untuk promosi / simulasi model bisnis TecFest 2026.
// Tidak ada fungsi checkout, pembayaran, atau redirection ke localhost.
const year = document.getElementById('year');
if (year) year.textContent = String(new Date().getFullYear());

const menu = document.querySelector('.mobile-nav');
if (menu) {
  menu.querySelectorAll('nav a').forEach((link) => {
    link.addEventListener('click', () => { menu.open = false; });
  });
}
