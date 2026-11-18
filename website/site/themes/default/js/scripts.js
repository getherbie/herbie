// theme toggle (light / dark)
var themeToggles = document.querySelectorAll('[data-theme-toggle]');

function updateThemeToggles() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var label = isDark ? 'Switch to light mode' : 'Switch to dark mode';
    themeToggles.forEach(function (button) {
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    });
}

themeToggles.forEach(function (button) {
    button.addEventListener('click', function () {
        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('herbie-theme', next);
        updateThemeToggles();
    });
});

updateThemeToggles();

// header: lift it with a shadow once the content has clearly scrolled underneath
(function () {
    var header = document.querySelector('.header');
    if (!header) {
        return;
    }

    // the shadow waits until scrolling has really started, not at the first pixel
    var THRESHOLD = 32;

    function update() {
        header.classList.toggle('is-scrolled', window.scrollY > THRESHOLD);
    }

    window.addEventListener('scroll', update, { passive: true });

    // a reload can start already scrolled
    update();
})();

// mobile navigation overlay
(function () {
    var nav = document.getElementById('mobile-nav');
    var trigger = document.getElementById('menuLink');
    if (!nav || !trigger) {
        return;
    }
    var closeButton = nav.querySelector('.mobile-nav-close');

    function open() {
        nav.classList.add('is-open');
        nav.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-nav-locked');
        if (closeButton) {
            closeButton.focus();
        }
    }

    function close() {
        nav.classList.remove('is-open');
        nav.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-nav-locked');
        trigger.focus();
    }

    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        open();
    });

    if (closeButton) {
        closeButton.addEventListener('click', close);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) {
            close();
        }
    });

    // a link inside the overlay navigates normally; no need to keep the overlay open
})();

// open all external links with new tab
let links = document.links;
for(let i = 0; i < links.length; i++) {
    if (links[i].hostname != window.location.hostname) {
        links[i].target = '_blank';
    }
}
