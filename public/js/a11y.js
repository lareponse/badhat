const menu = document.getElementById('accessibility-menu');
const toggleBtn = document.getElementById('accessibility-toggle');
const closeBtn = document.getElementById('close-menu');

// Open/Close menu
toggleBtn.addEventListener('click', () => {
  menu.classList.toggle('active');
  menu.setAttribute('aria-hidden', !menu.classList.contains('active'));
});
closeBtn.addEventListener('click', () => {
  menu.classList.remove('active');
  menu.setAttribute('aria-hidden', 'true');
});

// Accessibility actions
const actions = {
  increaseText: () =>
    (document.body.style.fontSize =
      document.body.style.fontSize === '120%' ? '100%' : '120%'),
  contrast: () => document.body.classList.toggle('high-contrast'),
  highlightLinks: () => document.body.classList.toggle('highlight-links'),
  dyslexiaFont: () => document.body.classList.toggle('dyslexia'),
  reset: () => {
    document.body.style.fontSize = '100%';
    document.body.classList.remove(
      'high-contrast',
      'highlight-links',
      'dyslexia'
    );
  },
};

document
  .querySelectorAll('#accessibility-menu [data-action]')
  .forEach((btn) => {
    btn.addEventListener('click', () => {
      const act = btn.dataset.action;
      if (actions[act]) actions[act]();
      localStorage.setItem(
        'accessibilitySettings',
        document.body.className + '|' + document.body.style.fontSize
      );
    });
  });

// Restore settings on load
window.addEventListener('load', () => {
  const saved = localStorage.getItem('accessibilitySettings');
  if (saved) {
    const [classes, size] = saved.split('|');
    document.body.className = classes;
    document.body.style.fontSize = size;
  }
});
