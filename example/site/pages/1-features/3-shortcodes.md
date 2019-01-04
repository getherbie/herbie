---
title: Shortcodes
excerpt: Testet alle verfügbaren (System-)Shortcodes.
---

# Shortcodes

## Links

Link ohne Linktext:  
[link http://wikipedia.org]
    
Link mit einem Linktext:  
[link http://wikipedia.org text="Wikipedia"]
      
Link in einem neuen Fenster öffnen:  
[link http://wikipedia.org text="Wikipedia" target="_blank"]
      
Link mit einem Titel:  
[link http://wikipedia.org text="Wikipedia" title="Gehe zu Wikipedia!"]

Link mit einer eigenen CSS-Klasse:  
[link http://wikipedia.org text="Wikipedia" class="wikipedia"]    
  
Relativer Link:  
[link dokumentation/cheat-sheet text="Cheat Sheet"]    


## E-Mails

E-Mail ohne Linktext:  
[email john@doe.com]

E-Mail mit einem Linktext:  
[email john@doe.com text="John Doe"]

E-Mail mit einem Titel:        
[email john@doe.com text="John Doe" title="Mail an John Doe!"]

E-Mail mit einer eigenen CSS-Klasse:        
[email john@doe.com text="John Doe" class="email"]  


## Bilder

Bild ohne definierte Breite und Höhe:   
[image media/tulpen.jpg]
      
Bild mit einer definierten Breite und Höhe:  
[image media/tulpen.jpg width="300" height="200" class="image"] 
      
Bild mit einem alternativen Text:        
[image media/tulpen.jpg width="300" height="200" alt="Meine Herbie-Website"]

Bild mit einer eigenen CSS-Klasse:  
[image media/tulpen.jpg width="300" height="200" alt="Meine Herbie-Website" class="image"]

Bild mit einer zusätzlichen Legende:  
[image media/tulpen.jpg width="300" height="200" caption="Meine Herbie-Website"]

Externe Bilder von anderen Websites:  
[image https://www.getherbie.org/media/tulpen.jpg]


## Dateien

Einfacher Download:      
[file media/tulpen.jpg]
    
Download mit einem Text:      
[file media/tulpen.jpg text="Das ist ein Download mit allem drum und dran."]
      
Download mit einem Titel:  
[file media/tulpen.jpg text="Das ist ein Download" title="Jetzt herunterladen"]
      
Download mit einer eigenen Klasse:  
[file media/tulpen.jpg text="Das ist ein Download" class="download"]
      
Download mit zusätzlicher Anzeige der Dateiendung und -grösse:  
[file media/tulpen.jpg text="Das ist ein Download" class="download" info="1"]


## Datum
  
Datum ohne Formatierung:  
[date]

Datum mit eigener Formatierung:      
[date format="%e. %A %Y"]
    
Datum mit einer eigenen lokalen Einstellung:  
[date format="%e. %A %Y" locale="fr_FR"]


## Formatierer

Markdown: 
 
[markdown]Das ist ein mit *Markdown* formatierter Text.[/markdown]
      
Textile: 
       
[textile]Das ist ein mit *Textile* formatierter Text.[/textile]

Twig:  

[twig]Das ist ein mit {{ "<h1>twig</h1>"|upper|striptags }} formatierter Text.[/twig]


## Include

HTML-Datei:  
[include @site/pages/1-features/.include.html]

Twig-Datei:    
[include @site/pages/1-features/.include.twig]

Twig-Datei mit Attributen:  
[include @site/pages/1-features/.include.twig attrib1="ABC" attrib2="DEF"]


## Listing

[listing path="@widget/listing.twig" filter="parentRoute|test" sort="title|desc" shuffle="true" limit="2" pagination="true"]


## Blocks

Siehe Test-Seite [link test/blocks text="Blocks"].



<style>
figure img {
    max-width:100%;
}
</style>
