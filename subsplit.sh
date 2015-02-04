# Thanks to https://github.com/dflydev/git-subsplit for automating and simplifying
# the process of managing one-way read-only subtree splits.

# init subsplit
git subsplit init https://github.com/getherbie/herbie.git

# update subsplit
git subsplit update

# publish start repos
git subsplit publish "
    start/blog:https://github.com/getherbie/start-blog.git
    start/onepage:https://github.com/getherbie/start-onepage.git
    start/website:https://github.com/getherbie/start-website.git
" --heads=master

# publish plugin repos
git subsplit publish "
    plugins/adminpanel:https://github.com/getherbie/plugin-adminpanel.git
    plugins/disqus:https://github.com/getherbie/plugin-disqus.git
    plugins/form:https://github.com/getherbie/plugin-form.git
    plugins/googlemaps:https://github.com/getherbie/plugin-googlemaps.git
    plugins/highlight:https://github.com/getherbie/plugin-highlight.git
    plugins/imagine:https://github.com/getherbie/plugin-imagine.git
    plugins/lipsum:https://github.com/getherbie/plugin-lipsum.git
    plugins/random:https://github.com/getherbie/plugin-random.git
    plugins/feed:https://github.com/getherbie/plugin-feed.git
    plugins/shortcode:https://github.com/getherbie/plugin-shortcode.git
    plugins/simplecontact:https://github.com/getherbie/plugin-simplecontact.git
    plugins/simplesearch:https://github.com/getherbie/plugin-simplesearch.git
    plugins/test:https://github.com/getherbie/plugin-test.git
    plugins/vimeo:https://github.com/getherbie/plugin-vimeo.git
    plugins/youtube:https://github.com/getherbie/plugin-youtube.git
    plugins/xmlsitemap:https://github.com/getherbie/plugin-xmlsitemap.git
" --heads=master

# remove .subsplit directory
rm -r .subsplit
