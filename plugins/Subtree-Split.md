Subtree Split
=============

Thanks to https://github.com/dflydev/git-subsplit for automating and simplifying
the process of managing one-way read-only subtree splits.

    git subsplit init https://github.com/getherbie/plugins.git

    git subsplit update

    git subsplit publish "
        disqus/:git@github.com:getherbie/plugin-disqus.git
        form/:git@github.com:getherbie/plugin-form.git
        googlemaps/:git@github.com:getherbie/plugin-googlemaps.git
        highlight/:git@github.com:getherbie/plugin-highlight.git
        imagine/:git@github.com:getherbie/plugin-imagine.git
        lipsum/:git@github.com:getherbie/plugin-lipsum.git
        shortcode/:git@github.com:getherbie/plugin-shortcode.git
        test/:git@github.com:getherbie/plugin-test.git
        vimeo/:git@github.com:getherbie/plugin-vimeo.git
        youtube/:git@github.com:getherbie/plugin-youtube.git
    " --heads=master
