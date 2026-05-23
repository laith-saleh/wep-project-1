window.addEventListener('scroll', () => {
    const nav = document.getElementById('siteNav');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 40);
});

const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.15 });
document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
