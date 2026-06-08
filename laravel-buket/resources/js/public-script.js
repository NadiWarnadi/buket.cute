import 'bootstrap';
// Script custom Anda yang lain
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('scroll', function() {
        const mainNav = document.getElementById('mainNav');
        if(mainNav) mainNav.classList.toggle('scrolled', window.scrollY > 50);
    }, { passive: true });
});