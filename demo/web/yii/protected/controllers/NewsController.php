<?php

class NewsController extends CController
{

    protected $items;

    public function init()
    {
        $this->items = array(
            1 => array(
                'title' => 'Newseintrag Nummer 1',
                'abstract' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
            ),
            2 => array(
                'title' => 'Newseintrag Nummer 2',
                'abstract' => 'Consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
            )
        );
    }

    public function actionIndex()
    {
        $this->render('index', array(
            'items' => $this->items
        ));
    }

    public function actionDetail($id)
    {
        if (empty($this->items[$id])) {
            throw new CHttpException(404, 'News nicht gefunden');
        }
        $this->render('detail', array(
            'item' => $this->items[$id]
        ));
    }
}
