Subtree Split
=============

Thanks to https://github.com/dflydev/git-subsplit for automating and simplifying
the process of managing one-way read-only subtree splits.

    $ git subsplit init https://github.com/getherbie/plugins.git

    $ git subsplit update

    $ git subsplit publish "
        disqus/:https://github.com/getherbie/plugin-disqus.git
        form/:https://github.com/getherbie/plugin-form.git
        googlemaps/:https://github.com/getherbie/plugin-googlemaps.git
        highlight/:https://github.com/getherbie/plugin-highlight.git
        imagine/:https://github.com/getherbie/plugin-imagine.git
        lipsum/:https://github.com/getherbie/plugin-lipsum.git
        shortcode/:https://github.com/getherbie/plugin-shortcode.git
        test/:https://github.com/getherbie/plugin-test.git
        vimeo/:https://github.com/getherbie/plugin-vimeo.git
        youtube/:https://github.com/getherbie/plugin-youtube.git
    " --heads=master
