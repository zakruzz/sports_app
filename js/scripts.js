document.addEventListener('DOMContentLoaded', () => {
    gsap.from('h1', { duration: 1, y: -50, opacity: 0, ease: 'power2.out' });
    gsap.from('.card', { duration: 1, y: 50, opacity: 0, stagger: 0.2, ease: 'power2.out' });
});