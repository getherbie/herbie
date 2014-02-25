<?php $this->renderPartial('//_before'); ?>

<?php foreach($items AS $id => $item): ?>
    <h1><?php echo CHtml::link($item['title'], array('/news/detail', 'id' => $id)) ?></h1>
    <p><?php echo $item['abstract'] ?></p>
<?php endforeach; ?>
