function toggleClass(element, className) {
    if (!element || !className) {
        return;
    }

    var classString = element.className, nameIndex = classString.indexOf(className);
    if (nameIndex == -1) {
        classString += ' ' + className;
    }
    else {
        classString = classString.substr(0, nameIndex) + classString.substr(nameIndex + className.length);
    }
    element.className = classString;
}

document.getElementById('menuLink').addEventListener('click', function(e) {
    toggleClass(document.getElementById('mobile-menu'), 'pure-hidden-phone');
    e.preventDefault();
});

/*
(function(i, s, o, g, r, a, m) {
    i['GoogleAnalyticsObject'] = r;
    i[r] = i[r] || function() {
        (i[r].q = i[r].q || []).push(arguments)
    }, i[r].l = 1 * new Date();
    a = s.createElement(o),
            m = s.getElementsByTagName(o)[0];
    a.async = 1;
    a.src = g;
    m.parentNode.insertBefore(a, m)
})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
ga('create', 'UA-XXXX', 'example.com');
ga('send', 'pageview');
*/

// open all external links with new tab
let links = document.links;
for(let i = 0; i < links.length; i++) {
    if (links[i].hostname != window.location.hostname) {
        links[i].target = '_blank';
    }
}
