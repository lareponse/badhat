document.addEventListener('DOMContentLoaded', () => {
  document.querySelector('nav .nav-toggle').addEventListener('click', (e) => {
    console.log(document.querySelector('nav .nav-links'));
    document.querySelector('nav .nav-links').classList.toggle('active');
  });
});
