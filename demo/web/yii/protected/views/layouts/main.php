<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Herbie Flat-File CMS & Blog - Demo Website">
        <meta name="author" content="">
        <link rel="shortcut icon" href="<?php echo Yii::app()->baseUrl ?>/favicon.ico">

        <title><?php $this->beginWidget('Twigify') ?>{{ pageTitle({delim:' / ', siteTitle:'Herbie Demo Blog', rootTitle: 'Herbie Flat-File CMS & Blog - Demo Website', reverse:false}) }}<?php $this->endWidget() ?></title>

        <!-- Bootstrap core CSS -->
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet">

        <!-- Custom styles for this template -->
        <link href="<?php echo Yii::app()->baseUrl ?>/css/main.css" rel="stylesheet">

        <!-- Just for debugging purposes. Don't actually copy this line! -->
        <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>
        <div class="blog-masthead">
            <div class="container">
                <nav class="blog-nav">
                    <?php $this->beginWidget('Twigify') ?>
                        <a class="blog-nav-item {% if route == 'blog' %}active{% endif %}" href="{{ url('blog') }}">Home</a>
                        <a class="blog-nav-item {% if route == 'features' %}active{% endif %}" href="{{ url('features') }}">Features</a>
                        <a class="blog-nav-item {% if route == 'ueber-herbie' %}active{% endif %}" href="{{ url('ueber-herbie') }}">Über Herbie</a>
                        <a class="blog-nav-item {% if route == 'news' %}active{% endif %}" href="{{ url('news') }}">News</a>
                    <?php $this->endWidget() ?>
                </nav>
            </div>
        </div>
        <div class="container">

            <div class="blog-header">
                <h1 class="blog-title">Herbie Yii Demo Blog</h1>
                <p class="lead blog-description">Basierend auf dem offiziellen Bootstrap Blog Template.</p>
            </div>

            <div class="row">
                <div class="col-sm-8 blog-main">
                    <?php echo $content ?>
                </div>
                <div class="col-sm-3 col-sm-offset-1 blog-sidebar">
                    <div class="sidebar-module sidebar-module-inset">
                        <h4>Über Herbie</h4>
                        <p>Herbie ist ein einfaches Flat-File CMS- und Blogsystem, das auf simplen Textdateien basiert. Keine komplizierte Installation, keine Datenbank, nur Textdateien.</p>
                    </div>
                    <div class="sidebar-module">
                        <h4>Archiv</h4>
                        <ol class="list-unstyled">
                            <?php $this->beginWidget('Twigify') ?>
                                {% for item in site.posts.months %}
                                    {% set route = 'blog/' ~ item.year ~ '/' ~ item.month %}
                                    {% set label = item.date|strftime('%B %Y') %}
                                    <li>{{ link(route, label) }}</li>
                                {% endfor %}
                            <?php $this->endWidget() ?>
                        </ol>
                    </div>
                    <div class="sidebar-module">
                        <h4>Links</h4>
                        <ol class="list-unstyled">
                            <li><a href="http://www.github.com/getherbie">GitHub</a></li>
                            <li><a href="http://www.getherbie.org">Herbie</a></li>
                        </ol>
                    </div>
                </div>

            </div>
        </div>

        <div class="blog-footer">
            <p>
                <a href="#">Nach oben</a>
            </p>
            <p>&nbsp;</p>
            <p>Blog template built for <a href="http://getbootstrap.com">Bootstrap</a> by <a href="https://twitter.com/mdo">@mdo</a>.</p>
        </div>
        <!-- Bootstrap core JavaScript
        ================================================== -->
        <!-- Placed at the end of the document so the pages load faster -->
        <script src="https://code.jquery.com/jquery.js"></script>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    </body>
</html>