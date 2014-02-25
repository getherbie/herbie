<?php $this->renderPartial('//_before'); ?>

<h1><?php echo $item['title'] ?></h1>
<p><?php echo $item['description'] ?></p>
<p><?php echo CHtml::link('Zurück', array('/news')) ?></p>