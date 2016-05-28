<?php

/**
 * Author: Pavel Petrov <itnelo@gmail.com>
 * Date: 11.02.16 15:25
 */

namespace yii\tools\crud;

use Yii;

/**
 * Class ReadAction
 * @package yii\tools\crud
 */
class ReadAction extends Action
{
    /** @inheritdoc */
    public $modelAction = false;

    /** @inheritdoc */
    public $redirectSuccess = false;

    /** @inheritdoc */
    public $flash = false;

    /** @inheritdoc */
    protected $type = 'read';

    /** @inheritdoc */
    protected $transaction = false;

    /**
     * @inheritdoc
     */
    protected function runCrudAction($model)
    {
        // Override this method and populate $this->params for rendering stage.
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function configureModel()
    {
        if (!$this->ensureModelSearch($this->searchModel())) {
            $this->model = $this->multiple ? [] : null;
        }
    }
}
