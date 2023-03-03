window.addEventListener('load', (event) => {

    //up.history.config.enabled = true;
    up.network.config.cacheSize = 0;

    /*up.compiler('input[type=text]', function(element) {
        element.addEventListener('focusout', function() {
            up.validate(element);
        });
    });*/

    // Don't highlight the fragment insertion from the initial compile on DOMContentLoaded.
    // Show the yellow flash when a new fragment was inserted.
    up.on('up:fragment:inserted', (event, fragment) => {
        fragment.classList.add('new-fragment', 'inserted')
        up.util.timer(100, () => fragment.classList.remove('inserted'))
        up.util.timer(1000, () => fragment.classList.remove('new-fragment'))
    })
})
